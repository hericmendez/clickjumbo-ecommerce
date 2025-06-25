<?php
if (!defined('ABSPATH'))
    exit;

// INCLUDES NECESSÁRIOS
require_once dirname(__DIR__) . '/products/get-products.php';
require_once dirname(__DIR__) . '/orders/generate-pix.php';
require_once dirname(__DIR__) . '/orders/generate-boleto.php';
require_once dirname(__DIR__) . '/orders/generate-receipt.php';
require_once dirname(__DIR__) . '/../validations/validate-cart.php';
require_once dirname(__DIR__) . '/../validations/validate-shipping.php';
require_once dirname(__DIR__) . '/../validations/validate-payment.php';
require_once dirname(__DIR__) . '/../admin/shipments-panel.php'; // ← necessário para acessar clickjumbo_store_shipping_meta

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
    $cart = $data['cart'] ?? null;
    $shipping = $data['shipping'] ?? null;
    $payment = $data['payment'] ?? null;
    $user_id = intval($data['user_id'] ?? 0);

    if (!$cart || !$shipping || !$payment || !$user_id) {
        return new WP_REST_Response(['error' => 'Payload incompleto'], 400);
    }

    $wp_user = get_userdata($user_id);
    if (!$wp_user) {
        return new WP_REST_Response(['error' => 'ID de usuário inválido ou inexistente'], 401);
    }

    // ✅ Validação do carrinho
    $req_cart = new WP_REST_Request('POST', '/clickjumbo/v1/validate-cart');
$req_cart->set_body_params(['cart' => $cart]);

    $cart_validation = clickjumbo_validate_cart($req_cart);
    if (is_wp_error($cart_validation) || !($cart_validation->get_data()['success'] ?? false)) {
        return new WP_REST_Response(['error' => 'Carrinho inválido', 'debug' => $cart_validation->get_data()], 400);
    }

    // ✅ Validação do frete
    $req_shipping = new WP_REST_Request();
    $req_shipping->set_body(json_encode(['shipping' => $shipping]));
    $shipping_validation = clickjumbo_validate_shipping($req_shipping);
    if (is_wp_error($shipping_validation) || !($shipping_validation->get_data()['success'] ?? false)) {
        return new WP_REST_Response(['error' => 'Frete inválido', 'debug' => $shipping_validation->get_data()], 400);
    }

    // ✅ Gerar método de pagamento
    $method = $payment['method'];
    $payment_response = null;
    switch ($method) {
        case 'pix':
            $payment_response = generate_pix($payment['payment_data']);
            break;
        case 'boleto':
            $payment_response = generate_boleto($payment['payment_data']);
            break;
        case 'credit-card':
        case 'debt-card':
            $payment_response = ["status" => "confirmed", "message" => "Pagamento aprovado"];
            generate_receipt($wp_user->user_email);
            break;
        default:
            return new WP_REST_Response(['error' => 'Método de pagamento não reconhecido'], 400);
    }

    // 🔄 Recalcular produtos com detalhes
    $produtos_completos = [];
    $pesoTotal = 0;
    $valorCarrinho = 0;
    foreach ($cart as $item) {
        $product_id = $item['id'] ?? null;
        $qty = $item['qty'] ?? 1;
        if (!$product_id) continue;
        $produto = clickjumbo_get_product_by_id($product_id);
        if ($produto) {
            $produto['qty'] = $qty;
            $produto['price'] = round(floatval($produto['price']), 2);
            $produto['weight'] = round(floatval($produto['weight']), 3);
            $produto['subtotal'] = round($produto['price'] * $qty, 2);
            $pesoTotal += $produto['weight'] * $qty;
            $valorCarrinho += $produto['subtotal'];
            $produtos_completos[] = $produto;
        }
    }

    $valorFrete = floatval($shipping['frete_valor'] ?? 0);
    $valorTotal = $valorCarrinho + $valorFrete;

    // 🔍 Buscar penitenciária
    $slug = sanitize_title($shipping['prison_slug'] ?? '');
    $penitenciaria_obj = clickjumbo_get_prison_data_by_slug($slug);

    // ✅ Criar pedido no WooCommerce
    $order = wc_create_order();
    foreach ($produtos_completos as $item) {
        $product = wc_get_product($item['id']);
        if ($product) {
            $order->add_product($product, $item['qty']);
        }
    }

    $order->set_billing_first_name($wp_user->first_name ?: $wp_user->display_name);
    $order->set_billing_email($wp_user->user_email);
    $order->set_payment_method($method);
    $order->set_status($payment_response['status'] === 'confirmed' ? 'processing' : 'pending');
    $order->set_customer_id($user_id);
    $order->update_meta_data('user_id', $user_id);
    $order->update_meta_data('penitenciaria', $penitenciaria_obj);
    $order->update_meta_data('produtos', $produtos_completos);
    $order->update_meta_data('shipping', $shipping);
    $order->update_meta_data('pesoTotal', round($pesoTotal, 3));
    $order->update_meta_data('valorTotal', round($valorTotal, 2));
    $order->update_meta_data('comprovante_url', $payment_response['qrcode_url'] ?? '');
    $order->calculate_totals();
    $order->save();

    // ✅ Salva os dados de envio para exibição no painel
    clickjumbo_store_shipping_meta(
        $order->get_id(),
        ['valor' => $valorFrete],
        $slug,
        $shipping['metodo'] ?? 'PAC'
    );

    // Formatando para retorno
    foreach ($produtos_completos as &$produto) {
        $produto['price'] = number_format($produto['price'], 2, '.', '');
        $produto['weight'] = number_format($produto['weight'], 3, '.', '');
        $produto['subtotal'] = number_format($produto['subtotal'], 2, '.', '');
    }
    $valorCarrinho = number_format($valorCarrinho, 2, ',', '');
    $valorFrete = number_format($valorFrete, 2, ',', '');
    $valorTotal = number_format($valorTotal, 2, ',', '');
    $pesoTotal = number_format($pesoTotal, 3, ',', '');

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Pedido processado com sucesso',
        'order_id' => $order->get_id(),
        'data' => [
            'id' => $order->get_id(),
            'status' => $order->get_status(),
            'penitenciaria' => $penitenciaria_obj,
            'cliente' => [
                'nome' => $wp_user->display_name,
                'email' => $wp_user->user_email,
                'endereco' => $shipping['sender_address'] ?? '',
            ],
            'produtos' => $produtos_completos,
            'shipping' => $shipping,
            'pesoTotal' => $pesoTotal,
            'valorCarrinho' => $valorCarrinho,
            'valorFrete' => $valorFrete,
            'valorTotal' => $valorTotal,
            'pagamento' => [
                'metodo' => $method,
                'status' => $payment_response['status'] ?? null,
                'invoice_url' => $payment_response['qrcode_url'] ?? '',
            ],
            'data' => current_time('d-m-Y H:i:s'),
        ],
    ]);
}
