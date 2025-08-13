<?php
if (!defined('ABSPATH')) exit;

/**
 * GET /wp-json/clickjumbo/v1/orders/<id>/full
 * Retorna um payload completo do pedido (HPOS-safe),
 * com cliente, detento, penitenciária, envio, produtos, pagamento (PIX/boleto/link/comprovante), etc.
 */
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/orders/(?P<id>\d+)/full', [
        'methods'  => 'GET',
        'callback' => 'clickjumbo_get_order_full',
        // ATENÇÃO: está aberto (return true). Troque por uma checagem de permissão quando for pra produção.
        'permission_callback' => function (WP_REST_Request $req) {
            return true;
        },
        'args' => [
            'id' => [
                'validate_callback' => function($v){ return is_numeric($v) && (int)$v > 0; }
            ],
        ],
    ]);
});

/* ============================================================
 *  Helpers utilitários (robustos) 
 * ============================================================ */

// URL?
function cj_is_url($v){ return is_string($v) && filter_var($v, FILTER_VALIDATE_URL); }

// Converte QUALQUER coisa em array (array|objeto|JSON|serialize)
function cj_any_to_array($v){
    if (is_array($v)) return $v;
    if (is_object($v)) return json_decode(json_encode($v), true) ?: (array)$v;
    if (is_string($v)) {
        // tenta unserialize (WordPress)
        if (function_exists('is_serialized') && is_serialized($v)) {
            $u = maybe_unserialize($v);
            return cj_any_to_array($u);
        }
        // tenta JSON
        $d = json_decode($v, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($d)) return $d;
    }
    return [];
}

// Busca recursiva pela PRIMEIRA chave em $keys (case-insensitive). Retorna string não vazia.
function cj_find_first_key($arr, array $keys){
    $want = array_map('strtolower', $keys);
    $it = new RecursiveIteratorIterator(new RecursiveArrayIterator((array)$arr), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $k => $v){
        if (in_array(strtolower((string)$k), $want, true)) {
            if (is_string($v) && $v !== '') return $v;
        }
    }
    return '';
}

// Junta TODAS as metas do pedido em um mega array (abrindo serialize/JSON/objetos)
function cj_collect_meta_arrays($order_id){
    $all = get_post_meta($order_id); // [key => [values...]]
    $bags = [];
    foreach ($all as $key => $vals){
        foreach ((array)$vals as $v){
            $bags[] = cj_any_to_array($v);
        }
    }
    $merged = [];
    foreach ($bags as $a) if ($a) $merged[] = $a;
    return $merged ? array_merge_recursive(...$merged) : [];
}

/**
 * Vasculha metas para campos típicos de gateways (Mercado Pago etc.)
 * Retorna:
 *  - pix_copia_cola, qr_code, qr_base64
 *  - boleto_url (ticket_url/external_resource_url/pdf)
 *  - pay_link (init_point/checkout_url/etc)
 *  - receipt_url (comprovante/receipt)
 */
function cj_guess_payment_bits_from_meta($order_id){
    $m = cj_collect_meta_arrays($order_id);

    // PIX (várias chaves comuns)
    $pix_copia = cj_find_first_key($m, [
        'pix_copia_cola','copia_cola','qr_code_text','emv','qr_data','qrCode','qrCodeText'
    ]);
    $pix_qr = cj_find_first_key($m, [
        'qr_code','qrcode','pix_qr_code','qrData'
    ]);
    $pix_b64 = cj_find_first_key($m, [
        'qr_base64','qr_code_base64','pix_qr_base64','qrcode_base64','qr_code_base64'
    ]);

    // Links (inclui nomenclatura Mercado Pago)
    $ticket_url  = cj_find_first_key($m, ['ticket_url','external_resource_url']); // boleto
    $pay_link    = cj_find_first_key($m, ['init_point','sandbox_init_point','payment_url','checkout_url','pay_url','link_pagamento']);
    $receipt_url = cj_find_first_key($m, ['receipt_url','comprovante_url','voucher_url']);

    // Valida URLs
    if (!cj_is_url($ticket_url))  $ticket_url = '';
    if (!cj_is_url($pay_link))    $pay_link = '';
    if (!cj_is_url($receipt_url)) $receipt_url = '';

    return [
        'pix_copia_cola' => $pix_copia,
        'qr_code'        => $pix_qr,
        'qr_base64'      => $pix_b64,
        'boleto_url'     => $ticket_url,
        'pay_link'       => $pay_link,
        'receipt_url'    => $receipt_url,
    ];
}

// Converte valor para string com 2 casas (Woo safe)
function cj_clean_money($v) {
    if ($v === '' || $v === null) return '0.00';
    $n = wc_format_decimal($v, 2);
    return (string) $n;
}

// Converte qualquer coisa (arr/obj/json) em array “plano”
function cj_to_array_deep($v) {
    if (is_array($v)) return $v;
    if (is_object($v)) return json_decode(json_encode($v), true) ?: (array)$v;
    if (is_string($v) && $v !== '') {
        $d = json_decode($v, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($d)) return $d;
    }
    return [];
}

// Normaliza endereço
function cj_norm_endereco($maybe, $fallback = []) {
    $a = cj_to_array_deep($maybe);
    return [
        'nome'        => $a['nome']        ?? ($fallback['nome']        ?? ''),
        'logradouro'  => $a['logradouro']  ?? $a['rua'] ?? ($fallback['logradouro'] ?? ''),
        'numero'      => $a['numero']      ?? ($fallback['numero']      ?? ''),
        'bairro'      => $a['bairro']      ?? ($fallback['bairro']      ?? ''),
        'cidade'      => $a['cidade']      ?? ($fallback['cidade']      ?? ''),
        'estado'      => $a['estado']      ?? ($fallback['estado']      ?? ''),
        'cep'         => $a['cep']         ?? ($fallback['cep']         ?? ''),
        'complemento' => $a['complemento'] ?? ($fallback['complemento'] ?? ''),
        'referencia'  => $a['referencia']  ?? ($fallback['referencia']  ?? ''),
    ];
}

// Normaliza penitenciária (aceita string, array, json, objeto)
function cj_norm_penitenciaria($meta) {
    $a = cj_to_array_deep($meta);
    if (empty($a)) {
        if (is_string($meta) && $meta !== '') return ['nome' => $meta];
        return null;
    }
    return [
        'nome'       => (string)($a['nome'] ?? $a['name'] ?? ''),
        'slug'       => (string)($a['slug'] ?? ''),
        'logradouro' => (string)($a['logradouro'] ?? $a['rua'] ?? ''),
        'numero'     => (string)($a['numero'] ?? ''),
        'bairro'     => (string)($a['bairro'] ?? ''),
        'cidade'     => (string)($a['cidade'] ?? ''),
        'estado'     => (string)($a['estado'] ?? ''),
        'cep'        => (string)($a['cep'] ?? ''),
        'complemento'=> (string)($a['complemento'] ?? ''),
        'referencia' => (string)($a['referencia'] ?? ''),
        'criado_em'  => (string)($a['criado_em'] ?? ''),
    ];
}

// Normaliza detento
function cj_norm_detento($meta) {
    $a = cj_to_array_deep($meta);
    if (empty($a)) return null;
    return [
        'nome'               => (string)($a['nome'] ?? ''),
        'matricula'          => (string)($a['matricula'] ?? ''),
        'raio'               => (string)($a['raio'] ?? ''),
        'cela'               => (string)($a['cela'] ?? ''),
        'nome_penitenciaria' => (string)($a['nome_penitenciaria'] ?? ''),
        'slug_penitenciaria' => (string)($a['slug_penitenciaria'] ?? ''),
    ];
}

// Soma peso dos itens (fallback se não tiver meta)
function cj_sum_peso_pedido($order) {
    $total_weight = 0.0;
    foreach ($order->get_items('line_item') as $item) {
        $product = $item->get_product();
        if (!$product) continue;
        $w = (float)$product->get_weight();
        $q = (float)$item->get_quantity();
        $total_weight += $w * $q;
    }
    return wc_format_decimal($total_weight, 3);
}

/* ============================================================
 *  Handler principal
 * ============================================================ */

function clickjumbo_get_order_full(WP_REST_Request $req) {
    $order_id = absint($req['id']);
    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_REST_Response(['success' => false, 'message' => 'Pedido não encontrado.'], 404);
    }

    // Cliente
    $cliente = [
        'nome'     => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
        'email'    => $order->get_billing_email(),
        'endereco' => trim(
            implode(' ', array_filter([$order->get_billing_address_1(), $order->get_billing_address_2()])) .
            ( $order->get_billing_city() ? ', '.$order->get_billing_city() : '' ) .
            ( $order->get_billing_state() ? ' - '.$order->get_billing_state() : '' ) .
            ( $order->get_billing_postcode() ? ', CEP '.$order->get_billing_postcode() : '' )
        ),
    ];

    // Penitenciária / Detento
    $peni    = cj_norm_penitenciaria($order->get_meta('penitenciaria'));
    $detento = cj_norm_detento($order->get_meta('detento'));

    // Produtos
    $produtos = [];
    foreach ($order->get_items('line_item') as $item) {
        $qty  = (float)$item->get_quantity();
        $tot  = (float)$item->get_total(); // total da linha (com descontos)
        $unit = $qty > 0 ? ($tot / $qty) : 0.0;
        $produtos[] = [
            'nome'            => $item->get_name(),
            'quantidade'      => $qty,
            'preco_unitario'  => cj_clean_money($unit),
            'subtotal'        => cj_clean_money($tot),
        ];
    }

    // Envio
    $shipping_items = $order->get_items('shipping');
    $forma_envio = '';
    if (!empty($shipping_items)) {
        $first = reset($shipping_items);
        $forma_envio = $first ? ($first->get_name() ?: $first->get_method_title()) : '';
    }
    $frete_valor = $order->get_shipping_total();

    $peso_meta = $order->get_meta('peso_carrinho');
    $peso_carrinho = $peso_meta !== '' ? (string)$peso_meta : cj_sum_peso_pedido($order);

    $envio_meta = cj_any_to_array($order->get_meta('envio'));
    $remetente  = cj_norm_endereco($envio_meta['remetente'] ?? $order->get_meta('remetente'), [
        'nome'       => get_bloginfo('name'),
        'logradouro' => get_option('woocommerce_store_address'),
        'numero'     => get_option('woocommerce_store_address_2'),
        'cidade'     => get_option('woocommerce_store_city'),
        'estado'     => get_option('woocommerce_store_state'),
        'cep'        => get_option('woocommerce_store_postcode'),
    ]);
    $destinatario = cj_norm_endereco($envio_meta['destinatario'] ?? $order->get_meta('destinatario'), [
        'nome'       => $peni['nome'] ?? trim($order->get_shipping_first_name().' '.$order->get_shipping_last_name()),
        'logradouro' => $order->get_shipping_address_1(),
        'numero'     => $order->get_shipping_address_2(),
        'cidade'     => $order->get_shipping_city(),
        'estado'     => $order->get_shipping_state(),
        'cep'        => $order->get_shipping_postcode(),
    ]);
    $enviar_para_penitenciaria = (string)($envio_meta['enviar_para_penitenciaria'] ?? $order->get_meta('enviar_para_penitenciaria') ?? '');

    $envio = [
        'frete_valor'               => cj_clean_money($frete_valor),
        'forma_envio'               => $forma_envio ?: ($envio_meta['forma_envio'] ?? ''),
        'enviar_para_penitenciaria' => $enviar_para_penitenciaria,
        'peso_carrinho'             => $peso_carrinho,
        'remetente'                 => $remetente,
        'destinatario'              => $destinatario,
    ];

    // Pagamento (inteligente p/ gateways)
    $pm_id    = $order->get_payment_method();
    $pm_title = $order->get_payment_method_title();
    $status   = $order->get_status();

    // Diretos que podem existir
    $pay_meta = cj_any_to_array($order->get_meta('pagamento'));
    $direct_link   = $order->get_meta('payment_link') ?: $order->get_meta('checkout_url');
    $direct_boleto = $order->get_meta('boleto_url')   ?: $order->get_meta('boleto');
    $direct_comp   = $order->get_meta('comprovante_url');

    // Scan completo nas metas
    $scan = cj_guess_payment_bits_from_meta($order_id);

    // Escolhas finais
    $link_pagamento  = ($pay_meta['link_pagamento'] ?? '') ?: $direct_link ?: $scan['pay_link'];
    $boleto_url      = ($pay_meta['boleto_url']     ?? '') ?: $direct_boleto ?: $scan['boleto_url'];
    $comprovante_url = ($pay_meta['comprovante_url']?? '') ?: $direct_comp   ?: $scan['receipt_url'];

    // PIX
    $pix_copia = ($pay_meta['pix_copia_cola'] ?? '') ?: $order->get_meta('pix_copia_cola') ?: $scan['pix_copia_cola'];
    $pix_qr    = ($pay_meta['qr_code']        ?? '') ?: $order->get_meta('qr_code')        ?: $scan['qr_code'];
    $pix_b64   = ($pay_meta['qr_base64']      ?? '') ?: $order->get_meta('pix_qr_base64')  ?: $scan['qr_base64'];

    $pagamento = [
        'metodo'           => $pm_id ?: $pm_title,
        'status'           => $status,
        'link_pagamento'   => (string) $link_pagamento,
        'comprovante_url'  => (string) $comprovante_url,
        'boleto_url'       => (string) $boleto_url,
        'pix'              => [
            'copia_cola' => (string) $pix_copia,
            'qr_code'    => (string) $pix_qr,
            'qr_base64'  => (string) $pix_b64,
        ],
    ];

    // Totais e data
    $total = cj_clean_money($order->get_total());
    $data  = $order->get_date_created();
    $data_fmt = $data ? $data->date('Y-m-d H:i:s') : '';

    // Payload final
    $payload = [
        'id'            => $order_id,
        'status'        => $status,
        'penitenciaria' => $peni,
        'cliente'       => $cliente,
        'detento'       => $detento,
        'envio'         => $envio,
        'produtos'      => $produtos,
        'total'         => $total,
        'pagamento'     => $pagamento,
        'data'          => $data_fmt,
    ];

    return new WP_REST_Response($payload, 200);
}
