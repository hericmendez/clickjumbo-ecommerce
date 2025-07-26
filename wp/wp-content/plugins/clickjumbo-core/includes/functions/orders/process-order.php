<?php
if (!defined('ABSPATH')) exit;

// Inclua aqui os requires/imports necessários (products, validações, payment, etc)
// ... seus requires ...

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/process-order', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_handle_process_order',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_handle_process_order($request)
{
    $data = $request->get_json_params();

    // Novos nomes conforme seu payload
    $user_id = intval($data['cliente_id'] ?? 0);
    $carrinho = $data['carrinho'] ?? [];
    $envio = $data['envio'] ?? [];
    $pagamento = $data['pagamento'] ?? [];
    $detento = $data['detento'] ?? [];

    // Validação básica
    if (!$user_id || empty($carrinho) || empty($envio) || empty($pagamento)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Payload incompleto'], 400);
    }

    $wp_user = get_userdata($user_id);
    if (!$wp_user) {
        return new WP_REST_Response(['success' => false, 'message' => 'Usuário inválido'], 401);
    }

    // Valida penitenciária se for envio para penitenciária
    $penitenciaria_obj = null;
    if (!empty($envio['enviar_para_penitenciaria'])) {
        $slug = sanitize_title($envio['slug_penitenciaria'] ?? '');
        $penitenciaria_obj = function_exists('clickjumbo_get_prison_data_by_slug') ? clickjumbo_get_prison_data_by_slug($slug) : null;
        if (!$penitenciaria_obj) {
            return new WP_REST_Response(['success' => false, 'message' => 'Penitenciária inválida'], 400);
        }
    }

    // Validação do carrinho (ajuste para seu validate-cart se necessário)
    $req_cart = new WP_REST_Request('POST', '/clickjumbo/v1/validate-cart');
    $req_cart->set_body_params(['carrinho' => $carrinho]);
    $cart_validation = function_exists('clickjumbo_validate_cart') ? clickjumbo_validate_cart($req_cart) : null;
    if (is_wp_error($cart_validation) || !($cart_validation->get_data()['success'] ?? false)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Carrinho inválido', 'debug' => $cart_validation->get_data() ?? null], 400);
    }

    // Validação do frete (opcional: ajuste se necessário)
    $req_frete = new WP_REST_Request();
    $req_frete->set_body(json_encode(['envio' => $envio]));
    $frete_valido = function_exists('clickjumbo_validate_shipping') ? clickjumbo_validate_shipping($req_frete) : null;
    if (is_wp_error($frete_valido) || !($frete_valido->get_data()['success'] ?? false)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Frete inválido', 'debug' => $frete_valido->get_data() ?? null], 400);
    }

    // Processa o pagamento
    $forma_envio = $envio['forma_envio'] ?? null;
    $pagamento_response = null;
    switch ($pagamento['method'] ?? $pagamento['forma_envio'] ?? $forma_envio) {
        case 'pix':
            $pagamento_response = function_exists('generate_pix') ? generate_pix($pagamento['payment_data'] ?? []) : ['status' => 'confirmado'];
            break;
        case 'boleto':
            $pagamento_response = function_exists('generate_boleto') ? generate_boleto($pagamento['payment_data'] ?? []) : ['status' => 'confirmado'];
            break;
        case 'credit-card':
        case 'debt-card':
            $pagamento_response = ["status" => "confirmado", "message" => "Pagamento aprovado"];
            if (function_exists('generate_receipt')) generate_receipt($wp_user->user_email);
            break;
        default:
            return new WP_REST_Response(['success' => false, 'message' => 'Método de pagamento não reconhecido'], 400);
    }

    // Recalcula produtos detalhados
    $produtos_completos = [];
    $pesoTotal = 0;
    $valorCarrinho = 0;
    foreach ($carrinho as $item) {
        $product_id = $item['id'] ?? null;
        $qtde = $item['qtde'] ?? $item['qty'] ?? 1;
        if (!$product_id) continue;
        $produto = function_exists('clickjumbo_get_product_by_id') ? clickjumbo_get_product_by_id($product_id) : null;
        if ($produto) {
            $produto['qtde'] = $qtde;
            $produto['preco'] = round(floatval($produto['preco']), 2);
            $produto['peso'] = round(floatval($produto['peso']), 3);
            $produto['subtotal'] = round($produto['preco'] * $qtde, 2);
            $pesoTotal += $produto['peso'] * $qtde;
            $valorCarrinho += $produto['subtotal'];
            $produtos_completos[] = $produto;
        }
    }

    $valorFrete = floatval($envio['frete_valor'] ?? 0);
    $valorTotal = $valorCarrinho + $valorFrete;

    // Cria o pedido no WooCommerce
    $pedido = wc_create_order();
    foreach ($produtos_completos as $item) {
        $product = wc_get_product($item['id']);
        if ($product) $pedido->add_product($product, $item['qtde']);
    }

    // Dados de cobrança (ajuste conforme seu fluxo)
    $pedido->set_billing_first_name($wp_user->first_name ?: $wp_user->display_name);
    $pedido->set_billing_email($wp_user->user_email);

    // Define status conforme o gateway
    $pedido->set_status(($pagamento_response['status'] ?? '') === 'confirmado' ? 'wc-completed' : 'wc-pending');
    $pedido->set_payment_method($pagamento['method'] ?? $pagamento['forma_envio'] ?? $forma_envio);
    $pedido->set_customer_id($user_id);

    // Salva todos os metadados principais do pedido
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
    $pedido->update_meta_data('frete_valor', $valorFrete);
    $pedido->update_meta_data('destinatario', $envio['destinatario'] ?? []);
    $pedido->update_meta_data('remetente', $envio['remetente'] ?? []);
    $pedido->update_meta_data('pagamento', $pagamento);
    $pedido->update_meta_data('pagamento_response', $pagamento_response);
    if (isset($pagamento_response['qrcode_url'])) $pedido->update_meta_data('comprovante_url', $pagamento_response['qrcode_url']);

    $pedido->calculate_totals();
    $pedido->save();

    // Payload final de resposta
    $order_data = [
        'id' => $pedido->get_id(),
        'status' => $pedido->get_status(),
        'penitenciaria' => $penitenciaria_obj,
        'detento' => $detento,
        'enviar_para_penitenciaria' => $envio['enviar_para_penitenciaria'] ?? false,
        'destinatario' => $envio['destinatario'] ?? [],
        'remetente' => $envio['remetente'] ?? [],
        'produtos' => $produtos_completos,
        'peso_total' => round($pesoTotal, 3),
        'valor_carrinho' => round($valorCarrinho, 2),
        'valor_frete' => round($valorFrete, 2),
        'valor_total' => round($valorTotal, 2),
        'forma_envio' => $envio['forma_envio'] ?? '',
        'pagamento' => $pagamento,
        'pagamento_response' => $pagamento_response,
        'comprovante_url' => $pagamento_response['qrcode_url'] ?? '',
        'data' => current_time('d-m-Y H:i:s'),
    ];

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Pedido processado com sucesso',
        'order_id' => $pedido->get_id(),
        'data' => $order_data,
    ]);
}
