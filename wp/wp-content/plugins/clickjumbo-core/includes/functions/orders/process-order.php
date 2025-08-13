<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/process-order', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_handle_process_order',
        'permission_callback' => '__return_true',
    ]);
});



/**
 * Helper para obter o Access Token do MP quando for útil aos wrappers.
 */


function clickjumbo_handle_process_order($request)
{
    $data = $request->get_json_params();

    // --- Payload ---
    $user_id   = intval($data['cliente_id'] ?? 0);
    $carrinho  = $data['carrinho'] ?? [];
    $envio     = $data['envio'] ?? [];
    $pagamento = $data['pagamento'] ?? [];
    $detento   = $data['detento'] ?? [];

    // Validação básica
    if (!$user_id || empty($carrinho) || empty($envio) || empty($pagamento)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Payload incompleto'], 400);
    }

    $wp_user = get_userdata($user_id);
    if (!$wp_user) {
        return new WP_REST_Response(['success' => false, 'message' => 'Usuário inválido'], 401);
    }

    // Carrega penitenciária por slug (mesmo se enviar_para_penitenciaria = false)
    $slug = sanitize_title(
        $envio['slug_penitenciaria'] ??
        $detento['slug_penitenciaria'] ??
        ($envio['destinatario']['slug'] ?? '')
    );

    $penitenciaria_obj = null;
    if (!empty($slug) && function_exists('clickjumbo_get_prison_data_by_slug')) {
        $penitenciaria_obj = clickjumbo_get_prison_data_by_slug($slug);
    }

    // Fallback: usa os dados do destinatário
    if (!$penitenciaria_obj && !empty($envio['destinatario'])) {
        $penitenciaria_obj = [
            'nome'        => $envio['destinatario']['nome'] ?? ($envio['nome_penitenciaria'] ?? $detento['nome_penitenciaria'] ?? ''),
            'slug'        => $slug,
            'cep'         => $envio['destinatario']['cep'] ?? '',
            'cidade'      => $envio['destinatario']['cidade'] ?? '',
            'estado'      => $envio['destinatario']['estado'] ?? '',
            'referencia'  => $envio['destinatario']['referencia'] ?? '',
            'complemento' => $envio['destinatario']['complemento'] ?? '',
            'logradouro'  => $envio['destinatario']['logradouro'] ?? '',
            'bairro'      => $envio['destinatario']['bairro'] ?? '',
            'numero'      => $envio['destinatario']['numero'] ?? '',
        ];
    }

    // Se explicitamente enviar_para_penitenciaria = true, valida o slug
    if (!empty($envio['enviar_para_penitenciaria'])) {
        $slug = sanitize_title($envio['slug_penitenciaria'] ?? '');
        $penitenciaria_obj = function_exists('clickjumbo_get_prison_data_by_slug') ? clickjumbo_get_prison_data_by_slug($slug) : null;
        if (!$penitenciaria_obj) {
            return new WP_REST_Response(['success' => false, 'message' => 'Penitenciária inválida'], 400);
        }
    }

    // --- Valida carrinho (sem frete aqui) ---
    $req_cart = new WP_REST_Request('POST', '/clickjumbo/v1/validate-cart');
    $req_cart->set_body(wp_json_encode(['carrinho' => $carrinho]));
    $cart_validation = function_exists('clickjumbo_validate_cart') ? clickjumbo_validate_cart($req_cart) : null;

    if (is_wp_error($cart_validation) || !($cart_validation->get_data()['success'] ?? false)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Carrinho inválido',
            'debug'   => $cart_validation instanceof WP_REST_Response ? $cart_validation->get_data() : null
        ], 400);
    }

    // --- Guard-rails simples para envio (sem recalcular) ---
    $valorFrete = isset($envio['frete_valor']) ? (float)$envio['frete_valor'] : 0.0;
    $formaEnvio = strtoupper(trim((string)($envio['forma_envio'] ?? '')));
    if ($valorFrete < 0 || $formaEnvio === '') {
        return new WP_REST_Response(['success' => false, 'message' => 'Dados de envio inválidos'], 400);
    }

    // --- Recalcula produtos detalhados ---
    $produtos_completos = [];
    $pesoTotal = 0;
    $valorCarrinho = 0;

    foreach ($carrinho as $item) {
        $product_id = $item['id'] ?? null;
        $qtde       = $item['qtde'] ?? $item['qty'] ?? 1;
        if (!$product_id) continue;

        $produto = function_exists('clickjumbo_get_product_by_id') ? clickjumbo_get_product_by_id($product_id) : null;
        if ($produto) {
            $produto['qtde']     = $qtde;
            $produto['preco']    = round((float)$produto['preco'], 2);
            $produto['peso']     = round((float)$produto['peso'], 3);
            $produto['subtotal'] = round($produto['preco'] * $qtde, 2);

            $pesoTotal     += $produto['peso'] * $qtde;
            $valorCarrinho += $produto['subtotal'];
            $produtos_completos[] = $produto;
        }
    }

    $valorTotal = $valorCarrinho + $valorFrete;

    // --- Cria o pedido no WooCommerce antes do gateway ---
    $pedido = wc_create_order();
    foreach ($produtos_completos as $item) {
        $product = wc_get_product($item['id']);
        if ($product) $pedido->add_product($product, $item['qtde']);
    }
    // adiciona frete como taxa para bater total
    if ($valorFrete > 0) {
        $fee = new WC_Order_Item_Fee();
        $fee->set_name('Frete - ' . $formaEnvio);
        $fee->set_total($valorFrete);
        $pedido->add_item($fee);
    }

    $pedido->set_billing_first_name($wp_user->first_name ?: $wp_user->display_name);
    $pedido->set_billing_email($wp_user->user_email);
    $pedido->set_customer_id($user_id);
    // opcional: deixe o gateway "genérico" aqui; título informa o método
    $pedido->set_payment_method('mercadopago');
    $pedido->set_payment_method_title('Mercado Pago (' . strtoupper($pagamento['method'] ?? '') . ')');

    // --- Payload base p/ Mercado Pago ---
    $order_id = $pedido->get_id();
    $notification_url = rest_url('clickjumbo/v1/mp-webhook'); // webhook do MP

    $order_payload = [
        'id'                  => $order_id,
        'valor_total'         => round($valorTotal, 2),
        'cliente'             => [
            'nome'  => $wp_user->first_name ?: $wp_user->display_name,
            'email' => $wp_user->user_email,
            // 'cpf' => ... // (se você tiver)
        ],
        'destinatario'        => $envio['destinatario'] ?? [],
        'envio'               => $envio,
        'produtos'            => $produtos_completos,
        'detento'             => $detento,
        'penitenciaria'       => $penitenciaria_obj,

        // >>> CHAVES NOVAS PARA O WRAPPER DO MERCADO PAGO <<<
        'external_reference'  => (string)$order_id,
        'notification_url'    => $notification_url,
    ];

    // --- Gateway ---
    require_once __DIR__ . '/../payments/mercadopago.php';
    $payment_method = strtolower(trim($pagamento['method'] ?? 'pix'));

    // Cartão AGORA é pelo endpoint do Brick (não por aqui)
    if (in_array($payment_method, ['credit-card','debt-card','card'], true)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Pagamento por cartão deve ser iniciado pelo endpoint /mp/create-card-payment (Brick).'
        ], 400);
    }

    // PIX e BOLETO continuam aqui (como você já fazia),
    // mas AGORA os wrappers devem incluir external_reference e notification_url no POST /v1/payments.
    if ($payment_method === 'pix') {
        $mp_resp = cj_mp_pay_pix($order_payload);
    } elseif ($payment_method === 'boleto') {
        $mp_resp = cj_mp_pay_boleto($order_payload);
    } else {
        return new WP_REST_Response(['success' => false, 'message' => 'Método de pagamento não reconhecido'], 400);
    }

    if (!empty($mp_resp['error'])) {
        // mantém seu formato de erro + debug
        return new WP_REST_Response(['success' => false, 'message' => 'Falha no gateway de pagamento', 'gateway' => $mp_resp], 400);
    }

    // --- Metadados & status ---
    $pedido->update_meta_data('_cj_mp_gateway', 'mercadopago');
    $pedido->update_meta_data('_cj_mp_method', $payment_method);
    $pedido->update_meta_data('_cj_mp_payment_id', $mp_resp['payment_id'] ?? '');
    $pedido->update_meta_data('_cj_mp_status', $mp_resp['status'] ?? '');
    $pedido->update_meta_data('_cj_mp_status_detail', $mp_resp['status_detail'] ?? '');
    $pedido->update_meta_data('_cj_mp_raw', $mp_resp['raw'] ?? []);

    // também salva external_reference (útil p/ debug)
    $pedido->update_meta_data('_mp_external_reference', (string)$order_id);

    // aplica status inicial compatível com o retorno do MP
    $pedido->set_status(cj_mp_wc_status($mp_resp['status'] ?? 'pending'));

    // Metas suas
    $pedido->update_meta_data('cliente_id', $user_id);
    $pedido->update_meta_data('produtos', $produtos_completos);
    $pedido->update_meta_data('peso_total', round($pesoTotal, 3));
    $pedido->update_meta_data('valor_carrinho', round($valorCarrinho, 2));
    $pedido->update_meta_data('valor_frete', round($valorFrete, 2));
    $pedido->update_meta_data('valor_total', round($valorTotal, 2));
    $pedido->update_meta_data('enviar_para_penitenciaria', $envio['enviar_para_penitenciaria'] ?? false);
    $pedido->update_meta_data('penitenciaria', $penitenciaria_obj);
    $pedido->update_meta_data('detento', $detento);
    $pedido->update_meta_data('forma_envio', $envio['forma_envio'] ?? '');
    $pedido->update_meta_data('destinatario', $envio['destinatario'] ?? []);
    $pedido->update_meta_data('remetente', $envio['remetente'] ?? []);
    $pedido->update_meta_data('pagamento', $pagamento);
    $pedido->update_meta_data('pagamento_response', $mp_resp);

    if (!empty($mp_resp['qr_code_base64'])) {
        $pedido->update_meta_data('comprovante_url', $mp_resp['ticket_url'] ?? '');
    }

    $pedido->calculate_totals();
    $pedido->save();

    // --- Resposta (mantendo seu formato) ---
    $order_data = [
        'id'                   => $pedido->get_id(),
        'status'               => $pedido->get_status(),
        'penitenciaria'        => $penitenciaria_obj,
        'detento'              => $detento,
        'enviar_para_penitenciaria' => $envio['enviar_para_penitenciaria'] ?? false,
        'destinatario'         => $envio['destinatario'] ?? [],
        'remetente'            => $envio['remetente'] ?? [],
        'produtos'             => $produtos_completos,
        'peso_total'           => round($pesoTotal, 3),
        'valor_carrinho'       => round($valorCarrinho, 2),
        'valor_frete'          => round($valorFrete, 2),
        'valor_total'          => round($valorTotal, 2),
        'forma_envio'          => $envio['forma_envio'] ?? '',
        'pagamento'            => $pagamento,
        'pagamento_response'   => $mp_resp,
        'qrcode'               => $mp_resp['qr_code'] ?? null,
        'qrcode_base64'        => $mp_resp['qr_code_base64'] ?? null,
        'boleto_url'           => $mp_resp['boleto_url'] ?? null,
        'ticket_url'           => $mp_resp['ticket_url'] ?? null,
        'data'                 => current_time('d-m-Y H:i:s'),
    ];

    return new WP_REST_Response([
        'success'  => true,
        'message'  => 'Pedido processado com sucesso',
        'order_id' => $pedido->get_id(),
        'data'     => $order_data,
    ]);
}
