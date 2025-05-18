<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/generate-receipt', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_generate_receipt',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_generate_receipt(WP_REST_Request $request) {
    $orderId = $request->get_param('order_id');

    if (!$orderId) {
        return new WP_REST_Response(['success' => false], 400);
    }

    return new WP_REST_Response([
        'success' => true,
        'receipt_url' => 'https://example.com/receipt.pdf'
    ]);
}
