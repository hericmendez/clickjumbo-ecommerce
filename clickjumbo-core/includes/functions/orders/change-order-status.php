<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/orders/(?P<id>\d+)/status', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_change_order_status',
        'permission_callback' => function () {
            return current_user_can('manage_woocommerce');
        },
    ]);
});

function clickjumbo_change_order_status($request) {
    $order_id = absint($request['id']);
    $data = $request->get_json_params();
    $novo_status = sanitize_text_field($data['status'] ?? '');

    $permitidos = ['pending', 'processing', 'completed', 'cancelled'];

    if (!$order_id || !in_array($novo_status, $permitidos)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Dados inválidos.'], 400);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        return new WP_REST_Response(['success' => false, 'message' => 'Pedido não encontrado.'], 404);
    }

    $order->update_status($novo_status, 'Status alterado via painel.');
    $order->save();

    return new WP_REST_Response(['success' => true, 'message' => 'Status do pedido atualizado.']);
}
