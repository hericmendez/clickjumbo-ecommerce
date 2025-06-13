<?php
if (!defined('ABSPATH'))
    exit;

// INCLUDES NECESSÁRIOS
require_once dirname(__DIR__) . '/products/get-product-list.php';
require_once dirname(__DIR__) . '/orders/generate-pix.php';
require_once dirname(__DIR__) . '/orders/generate-boleto.php';
require_once dirname(__DIR__) . '/orders/generate-receipt.php';
require_once dirname(__DIR__) . '/../validations/validate-cart.php';
require_once dirname(__DIR__) . '/../validations/validate-shipping.php';
require_once dirname(__DIR__) . '/../validations/validate-payment.php';

// REGISTRA ENDPOINT
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/process-order', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_handle_process_order',
        'permission_callback' => '__return_true',
    ]);
});

// FUNÇÃO PRINCIPAL
function clickjumbo_handle_process_order($request)
{
    $data = $request->get_json_params();
    $cart = $data['cart']['products'] ?? null;

    $shipping = $data['shipping'] ?? null;
    $user = $data['user'] ?? null;
    $payment = $data['payment'] ?? null;

    if (!$cart || !$shipping || !$user || !$payment) {
        return new WP_REST_Response(['error' => 'Payload incompleto'], 400);
    }

    // ✅ Validação do carrinho
    $req_cart = new WP_REST_Request('POST', '/clickjumbo/v1/validate-cart');
    $req_cart->set_body_params(['cart' => $cart]); // envia como array direto


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
            generate_receipt($user['email']);
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
        if (!$product_id)
            continue;

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
    error_log('Slug da penitenciária: ' . $slug); // 👈 debug temporário

    $penitenciaria_obj = null;

    $penitenciaria_obj = clickjumbo_get_prison_data_by_slug($slug);




    // ✅ Criar pedido no WooCommerce
    $order = wc_create_order();
    foreach ($produtos_completos as $item) {
        $product = wc_get_product($item['id']);
        if ($product) {
            $order->add_product($product, $item['qty']);
        }
    }

    $full_name = $user['name'] ?? '';
    $parts = explode(' ', $full_name, 2);
    $first_name = $parts[0] ?? '';
    $last_name = $parts[1] ?? '';

    $order->set_billing_first_name($first_name);
    $order->set_billing_last_name($last_name);
    $order->set_billing_email($user['email']);
    $order->set_payment_method($method);
    $order->set_status($payment_response['status'] === 'confirmed' ? 'processing' : 'pending');
    if (isset($user['id'])) {
        $order->update_meta_data('user_id', intval($user['id']));
    }


    if (!empty($user['id'])) {
        $order->update_meta_data('user_id', intval($user['id']));
    }
    if (!empty($user['email'])) {
        $order->update_meta_data('user_email', sanitize_email($user['email']));
    }

    // Metadados
    $order->update_meta_data('penitenciaria', $penitenciaria_obj);
    $order->update_meta_data('produtos', $produtos_completos);
    $order->update_meta_data('shipping', $shipping);
    $order->update_meta_data('pesoTotal', round($pesoTotal, 3));
    $order->update_meta_data('valorTotal', round($valorTotal, 2));
    $order->update_meta_data('comprovante_url', $payment_response['qrcode_url'] ?? '');

    $order->calculate_totals();
    $order->save();
    error_log('Produtos completos: ' . print_r($produtos_completos, true));
    error_log('Valor Total: ' . $valorTotal);
    error_log(print_r($penitenciaria_obj, true));
    // Formatando produtos
    foreach ($produtos_completos as &$produto) {
        $produto['price'] = number_format($produto['price'], 2, '.', '');
        $produto['weight'] = number_format($produto['weight'], 3, '.', '');
        $produto['subtotal'] = number_format($produto['subtotal'], 2, '.', '');
    }

    // Totais formatados
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
                'nome' => $user['name'],
                'email' => $user['email'],
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
