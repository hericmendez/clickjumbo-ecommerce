<?php
if (!defined('ABSPATH')) exit;

/**
 * GET /wp-json/clickjumbo/v1/order-status
 * Parâmetros:
 *   - id (int)        -> ID do pedido (recomendado)
 *   - order_key (str) -> opcional, se preferir buscar pela chave wc_order_xxx
 *
 * Retorna payload enxuto para polling no front.
 */
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/order-status', [
        'methods'  => 'GET',
        'callback' => 'clickjumbo_order_status_slim',
        'permission_callback' => '__return_true', // público p/ polling
        'args' => [
            'id' => [
                'description' => 'ID do pedido',
                'type'        => 'integer',
                'required'    => false,
            ],
            'order_key' => [
                'description' => 'Chave do pedido (wc_order_...)',
                'type'        => 'string',
                'required'    => false,
            ],
        ],
    ]);
});

function clickjumbo_order_status_slim(WP_REST_Request $req) {
    $order_id  = absint($req->get_param('id'));
    $order_key = sanitize_text_field((string) $req->get_param('order_key'));

    if (!$order_id && !$order_key) {
        return new WP_REST_Response([
            'success' => false,
            'status'  => 'unknown',
            'message' => 'Informe id ou order_key.'
        ], 400);
    }

    // Carrega pedido por ID ou pela order_key
    $order = null;
    if ($order_id) {
        $order = wc_get_order($order_id);
    } else {
        $query = new WC_Order_Query([
            'limit'     => 1,
            'orderby'   => 'date',
            'order'     => 'DESC',
            'return'    => 'objects',
            'order_key' => $order_key,
        ]);
        $orders = $query->get_orders();
        $order  = $orders ? reset($orders) : null;
    }

    if (!$order) {
        return new WP_REST_Response([
            'success' => false,
            'status'  => 'unknown',
            'message' => 'Pedido não encontrado.'
        ], 404);
    }

    // Status WooCommerce (ex.: wc-pending, wc-processing, wc-completed, wc-cancelled)
    $status = $order->get_status();

    // Metadados úteis para a tela
    $payment_method = $order->get_payment_method(); // 'pix', 'boleto', 'credit-card'...
    $payment_id     = $order->get_meta('_cj_mp_payment_id') ?: $order->get_meta('mp_payment_id') ?: null;
    $gateway_raw    = $order->get_meta('pagamento_response');
    $total          = (float) $order->get_total();

    // Extrai um resumo mínimo do gateway (quando existir)
    $gateway = null;
    if (is_array($gateway_raw)) {
        $gateway = [
            'method'        => $payment_method,
            'status'        => $gateway_raw['status']        ?? null, // approved / pending / rejected ...
            'status_detail' => $gateway_raw['status_detail'] ?? null,
        ];
    }

    // Otimização leve: ETag para reduzir payload em polling
    $last_mod = $order->get_date_modified();
    $last_mod_str = $last_mod ? $last_mod->date('c') : '';
    $etag = '"' . md5($order->get_id() . '|' . $status . '|' . $last_mod_str) . '"';

    $if_none_match = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : '';
    if ($if_none_match === $etag) {
        // 304 Not Modified para economizar banda
        $response = new WP_REST_Response(null, 304);
        $response->header('ETag', $etag);
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        return $response;
    }

    $payload = [
        'success'      => true,
        'id'           => $order->get_id(),
        'status'       => $status,           // use no front: wc-processing = aprovado
        'total'        => $total,
        'payment_id'   => $payment_id,
        'payment_method'=> $payment_method,
        'gateway'      => $gateway,
        'updated_at'   => $last_mod ? $last_mod->date('Y-m-d H:i:s') : null,
    ];

    // Evita cache agressivo em proxies/CDN
    nocache_headers();

    $response = new WP_REST_Response($payload, 200);
    $response->header('ETag', $etag);
    $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    return $response;
}
