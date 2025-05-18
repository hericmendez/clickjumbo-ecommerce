<?php
if (!defined('ABSPATH')) exit;

// INCLUDES NECESSÁRIOS
require_once dirname(__DIR__) . '/functions/get-product-list.php';
require_once dirname(__DIR__) . '/functions/generate-pix.php';
require_once dirname(__DIR__) . '/functions/generate-boleto.php';
require_once dirname(__DIR__) . '/functions/generate-receipt.php';
require_once dirname(__DIR__) . '/validations/validate-cart.php';
require_once dirname(__DIR__) . '/validations/validate-shipping.php';
require_once dirname(__DIR__) . '/validations/validate-payment.php';

// REGISTRA ENDPOINT
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/process-order', [
        'methods'  => 'POST',
        'callback' => 'clickjumbo_handle_process_order',
        'permission_callback' => '__return_true',
    ]);
});

// FUNÇÃO PRINCIPAL
function clickjumbo_handle_process_order($request) {
    $data = $request->get_json_params();

    $cart = $data['cart'] ?? null;
    $shipping = $data['shipping'] ?? null;
    $user = $data['user'] ?? null;
    $payment = $data['payment'] ?? null;

    if (!$cart || !$shipping || !$user || !$payment) {
        return new WP_REST_Response(['error' => 'Payload incompleto'], 400);
    }

    // ✅ Validação do carrinho
    $req_cart = new WP_REST_Request();
    $req_cart->set_body(json_encode(['cart' => $cart]));
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

    // ⚠️ Validação de pagamento temporariamente desativada
    // $payment_validation = clickjumbo_validate_payment(new WP_REST_Request([], [], ['body' => json_encode(['payment' => $payment])]));
    // if (is_wp_error($payment_validation) || !($payment_validation->get_data()['success'] ?? false)) {
    //     return new WP_REST_Response(['error' => 'Pagamento inválido', 'debug' => $payment_validation->get_data()], 400);
    // }

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
            generate_receipt($user['email']);
            break;
        default:
            return new WP_REST_Response(['error' => 'Método de pagamento não reconhecido'], 400);
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Pedido processado com sucesso',
        'payment_response' => $payment_response,
    ]);
}
