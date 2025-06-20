<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/orders/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'clickjumbo_delete_order',

    ]);
});

function clickjumbo_delete_order($request) {
    $order_id = absint($request['id']);

    if (!$order_id) {
        return new WP_REST_Response(['success' => false, 'message' => 'ID inválido.'], 400);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        error_log("Pedido $order_id não encontrado.");
        return new WP_REST_Response(['success' => false, 'message' => 'Pedido não encontrado.'], 404);
    }

    $status_before = get_post_status($order_id);
    error_log("Status antes da exclusão (pedido $order_id): $status_before");

    // Excluir permanentemente (forçar exclusão real)
    $deleted = wp_delete_post($order_id, true);

    if (!$deleted) {
        error_log("Erro ao excluir pedido $order_id.");
        return new WP_REST_Response(['success' => false, 'message' => 'Erro ao excluir o pedido.'], 500);
    }

    $still_exists = get_post_status($order_id);
    error_log("Status após tentativa de exclusão (pedido $order_id): " . var_export($still_exists, true));

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Pedido excluído com sucesso.',
        'debug' => [
            'id' => $order_id,
            'status_antes' => $status_before,
            'status_depois' => $still_exists
        ]
    ]);
}
