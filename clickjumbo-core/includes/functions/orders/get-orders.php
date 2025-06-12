<?php
if (!defined('ABSPATH'))
    exit;

// GET /orders → Lista de pedidos
function clickjumbo_get_orders($request)
{
    $args = [
        'status' => ['pending', 'processing', 'completed'],
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'type' => 'shop_order',
        'return' => 'objects',
'post_status' => ['wc-pending', 'wc-processing', 'wc-completed']
    ];


    $orders = wc_get_orders($args);

    $result = [];

    foreach ($orders as $order) {
        if (get_post_status($order->get_id()) === false) {
            continue; // ignora pedido já deletado
        }
        $result[] = [
            'id' => $order->get_id(),
            'cliente' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'penitenciaria' => $order->get_meta('penitenciaria'),
            'status' => $order->get_status(),
            'total' => $order->get_total(),
            'data' => $order->get_date_created()->date('Y-m-d H:i:s'),
        ];
    }

    return rest_ensure_response($result);
}

// GET /orders/:id → Detalhes de um pedido
function clickjumbo_get_order_details($request)
{
    $id = absint($request['id']);
    $order = wc_get_order($id);

    // Se o pedido não existe ou foi deletado (status false ou trash)
    $status = get_post_status($id);
    if (!$order || $status === false || $status === 'trash') {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Pedido não encontrado ou já excluído.'
        ], 404);
    }

    $produtos = [];
    foreach ($order->get_items() as $item) {
        $produtos[] = [
            'nome' => $item->get_name(),
            'quantidade' => $item->get_quantity(),
            'preco_unitario' => wc_format_decimal($item->get_total() / max(1, $item->get_quantity()), 2),
            'subtotal' => wc_format_decimal($item->get_total(), 2),
        ];
    }

    return rest_ensure_response([
        'id' => $order->get_id(),
        'status' => $order->get_status(),
        'penitenciaria' => $order->get_meta('penitenciaria'),
        'cliente' => [
            'nome' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'email' => $order->get_billing_email(),
            'endereco' => $order->get_billing_address_1() . ', ' . $order->get_billing_city(),
        ],
        'produtos' => $produtos,
        'total' => wc_format_decimal($order->get_total(), 2),
        'pagamento' => [
            'metodo' => $order->get_payment_method(),
            'status' => $order->get_status(),
            'comprovante_url' => $order->get_meta('comprovante_url'),
        ],
        'data' => $order->get_date_created()->date('Y-m-d H:i:s'),
    ]);
}


// REGISTRO DAS ROTAS
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/orders', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_orders',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('clickjumbo/v1', '/orders/(?P<id>\d+)', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_order_details',
        'permission_callback' => '__return_true',
    ]);
});
