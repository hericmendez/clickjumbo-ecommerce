<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/mp-webhook', [
        'methods'             => 'POST',
        'callback'            => 'clickjumbo_mp_webhook_handler',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_mp_webhook_handler(WP_REST_Request $request) {
    $body = $request->get_json_params();
    // Estruturas variam; normalmente vem payment id em $body['data']['id'] ou $body['id']
    $payment_id = $body['data']['id'] ?? $body['id'] ?? null;
    if (!$payment_id) {
        return new WP_REST_Response(['received' => true, 'note' => 'sem payment_id'], 200);
    }

    // Recupera payment no MP
    if (!function_exists('cj_mp_init')) {
        require_once __DIR__ . '/../payments/mercadopago.php';
    }
    cj_mp_init();
    if (!class_exists('MercadoPago\Payment')) {
        return new WP_REST_Response(['error' => 'SDK ausente'], 500);
    }

    $payment = MercadoPago\Payment::find_by_id($payment_id);
    if (!$payment) {
        return new WP_REST_Response(['error' => 'payment não encontrado no MP'], 404);
    }

    $mp_status = $payment->status ?? 'pending';
    $mp_detail = $payment->status_detail ?? null;

    // Descobrir pedido pelo meta _cj_mp_payment_id
    $orders = wc_get_orders([
        'limit'        => 1,
        'meta_key'     => '_cj_mp_payment_id',
        'meta_value'   => (string)$payment_id,
        'meta_compare' => '=',
        'orderby'      => 'date',
        'order'        => 'DESC',
    ]);

    if (empty($orders)) {
        return new WP_REST_Response(['warning' => 'pedido não encontrado p/ payment_id'], 200);
    }

    /** @var WC_Order $order */
    $order = $orders[0];
    $order->update_meta_data('_cj_mp_status', $mp_status);
    $order->update_meta_data('_cj_mp_status_detail', $mp_detail);
    $order->update_meta_data('_cj_mp_webhook_raw', json_decode(json_encode($payment), true));
    $order->set_status(cj_mp_wc_status($mp_status));
    $order->save();

    return new WP_REST_Response(['ok' => true, 'order_id' => $order->get_id(), 'status' => $mp_status], 200);
}
