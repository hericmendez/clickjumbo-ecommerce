<?php
if (!defined('ABSPATH'))
    exit;

// GET /orders → Lista de pedidos

function clickjumbo_get_orders($request)
{
    $user_id = $request->get_param('user_id');

    $args = [
        'status' => ['pending', 'processing', 'completed'],
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'type' => 'shop_order',
        'return' => 'objects',
        'post_status' => ['wc-pending', 'wc-processing', 'wc-completed'],
    ];

    if ($user_id) {
        $args['customer_id'] = intval($user_id); // ← usa o relacionamento nativo do WooCommerce
    }


    $orders = wc_get_orders($args);

    if (empty($orders)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Nenhum pedido encontrado para este usuário.'
        ], 404);
    }

    $result = [];

    foreach ($orders as $order) {
        if (get_post_status($order->get_id()) === false) {
            continue;
        }

        $result[] = [
            'id' => $order->get_id(),
            'cliente' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
            'user_id' => $order->get_meta('user_id'), // 👈 adiciona aqui
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
    $order_post = get_post($order ? $order->get_id() : 0);
    error_log('post_author: ' . ($order_post->post_author ?? 'não encontrado'));

    error_log('post_author: ' . $order_post->post_author); // deve ser 1

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

    $shipping = $order->get_meta('shipping');

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
        'shipping' => $shipping,
        'total' => wc_format_decimal($order->get_total(), 2),
        'pagamento' => [
            'metodo' => $order->get_payment_method(),
            'status' => $order->get_status(),
            'comprovante_url' => $order->get_meta('comprovante_url'),
        ],
        'data' => $order->get_date_created()->date('d-m-Y H:i:s'),
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
