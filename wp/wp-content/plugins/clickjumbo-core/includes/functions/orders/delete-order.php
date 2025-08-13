<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/orders/(?P<id>\d+)', [
        'methods'  => 'DELETE',
        'callback' => 'clickjumbo_delete_order',
        'permission_callback' => function () {
            return current_user_can('delete_shop_orders') || current_user_can('manage_woocommerce');
        },
        'args' => [
            'id' => [
                'validate_callback' => function($param){ return is_numeric($param) && $param > 0; }
            ]
        ]
    ]);
});

/**
 * Deleta pedido de forma definitiva (HPOS-safe) e limpa linhas “espelho”, se existirem.
 */
function clickjumbo_delete_order(WP_REST_Request $request) {
    $order_id = absint($request['id']);
    if (!$order_id) {
        return new WP_REST_Response(['success' => false, 'message' => 'ID inválido.'], 400);
    }

    $order = wc_get_order($order_id);
    if (!$order) {
        // Já era / não existe
        return new WP_REST_Response(['success' => true, 'message' => 'Pedido já inexistente.'], 200);
    }

    // Apaga filhos (reembolsos, etc.) antes
    $children = wc_get_orders(['parent' => $order_id, 'return' => 'ids', 'limit' => -1]);
    foreach ($children as $child_id) {
        $child = wc_get_order($child_id);
        if ($child) $child->delete(true); // força delete
    }

    // Delete definitivo do pedido (funciona em HPOS e no modelo antigo)
    $order->delete(true);

    // Limpa transientes/caches
    if (function_exists('wc_delete_shop_order_transients')) {
        wc_delete_shop_order_transients($order_id);
    }

    // (Opcional) Se você tiver tabelas próprias, apague aqui também
    // ajuste os nomes conforme o seu schema.
    global $wpdb;
    // Exemplos comuns – comente/remova se não existir:
    // $wpdb->delete($wpdb->prefix.'clickjumbo_orders', ['order_id' => $order_id], ['%d']);
    // $wpdb->delete($wpdb->prefix.'clickjumbo_order_items', ['order_id' => $order_id], ['%d']);

    // Verificação final
    $exists_after = (bool) wc_get_order($order_id);

    return new WP_REST_Response([
        'success' => !$exists_after,
        'deleted' => !$exists_after,
        'message' => !$exists_after ? 'Pedido excluído com sucesso.' : 'Não foi possível excluir completamente.',
        'debug'   => ['exists_after' => $exists_after]
    ], !$exists_after ? 200 : 500);
}
