<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_validate_payment_data($payment) {
    if (!is_array($payment) || empty($payment['method']) || empty($payment['payment_data'])) {
        return false;
    }

    // Simples validação por enquanto
    return in_array($payment['method'], ['pix', 'boleto', 'credit-card', 'debt-card']);
}

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/validate-payment', [
        'methods' => 'POST',
        'callback' => function(WP_REST_Request $req) {
            $payment = $req->get_param('payment');
            $valid = clickjumbo_validate_payment_data($payment);
            return new WP_REST_Response(['success' => $valid], $valid ? 200 : 400);
        },
        'permission_callback' => '__return_true',
    ]);
});
