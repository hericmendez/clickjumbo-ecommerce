<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/orders/by-user', [
        'methods'             => 'GET',
        'callback'            => 'clickjumbo_orders_by_user',
        'permission_callback' => '__return_true',
        'args' => [
            'user_id'  => ['required' => true],
            'page'     => ['required' => false, 'default' => 1],
            'per_page' => ['required' => false, 'default' => 10],
            'status'   => ['required' => false],
        ],
    ]);
});

/** --------- HELPERS ROBUSTOS ---------- */

/** Descompacta retorno do wc_get_orders em [orders[], total, pages], aceitando array ou stdClass */
function cj_unpack_wc_orders($res) {
    $orders = []; $total = 0; $pages = 1;

    if (is_array($res)) {
        if (array_key_exists('orders', $res)) {
            $orders = is_array($res['orders']) ? $res['orders'] : [];
            $total  = (int)($res['total'] ?? count($orders));
            $pages  = (int)($res['max_num_pages'] ?? 1);
        } else {
            // alguns setups retornam direto um array de WC_Order
            $orders = $res;
            $total  = count($orders);
            $pages  = 1;
        }
    } elseif (is_object($res)) {
        // stdClass com ->orders / ->total / ->max_num_pages
        if (isset($res->orders)) {
            $orders = is_array($res->orders) ? $res->orders : [];
            $total  = (int)($res->total ?? count($orders));
            $pages  = (int)($res->max_num_pages ?? 1);
        }
    }

    return [$orders, $total, $pages];
}

/** Converte meta penitenciária (array|objeto|json|string) em array normalizado ou null */
function cj_norm_peni_meta($meta) {
    $a = [];
    if (is_array($meta)) {
        $a = $meta;
    } elseif (is_object($meta)) {
        // cast simples só converte o topo; para segurança, json_encode/decode
        $a = json_decode(json_encode($meta), true);
        if (!is_array($a)) $a = (array)$meta;
    } elseif (is_string($meta) && $meta !== '') {
        $decoded = json_decode($meta, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $a = $decoded;
        } else {
            // string simples (apenas nome)
            $a = ['nome' => $meta];
        }
    }

    $nome   = isset($a['nome'])   ? (string)$a['nome']   : '';
    $cidade = isset($a['cidade']) ? (string)$a['cidade'] : '';
    $estado = isset($a['estado']) ? (string)$a['estado'] : '';
    if ($nome === '' && $cidade === '' && $estado === '') return null;

    return ['nome' => $nome, 'cidade' => $cidade, 'estado' => $estado];
}

/** Converte WC_Order -> linha do payload */
function cj_order_to_row($order) {
    $date = $order->get_date_created();
    return [
        'id'            => $order->get_id(),
        'cliente'       => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
        'user_id'       => (string)$order->get_user_id(),
        'penitenciaria' => cj_norm_peni_meta($order->get_meta('penitenciaria')),
        'status'        => $order->get_status(),
        'total'         => wc_format_decimal($order->get_total(), 2),
        'data'          => $date ? $date->date('Y-m-d H:i:s') : '',
    ];
}

/** --------- HANDLER ---------- */
function clickjumbo_orders_by_user(WP_REST_Request $req) {
    $user_id  = absint($req->get_param('user_id'));
    $page     = max(1, (int)$req->get_param('page'));
    $per_page = min(50, max(1, (int)$req->get_param('per_page')));

    if (!$user_id) {
        return new WP_REST_Response(['success' => false, 'message' => 'user_id obrigatório'], 400);
    }

    // status
    $status_param = $req->get_param('status');
    if (is_string($status_param)) {
        $status = array_filter(array_map('trim', explode(',', $status_param)));
    } elseif (is_array($status_param)) {
        $status = array_filter($status_param);
    } else {
        $status = ['pending','processing','completed','cancelled','failed','refunded'];
    }

    // busca principal por customer (id + email)
    $customers = [$user_id];
    if ($u = get_userdata($user_id)) {
        if (!empty($u->user_email)) $customers[] = $u->user_email;
    }

    $args = [
        'customer'   => $customers, // <--- CORRETO
        'status'     => $status,
        'orderby'    => 'date',
        'order'      => 'DESC',
        'limit'      => $per_page,
        'page'       => $page,
        'paginate'   => true,
        'return'     => 'objects',
        'type'       => 'shop_order',
    ];

    $res1 = function_exists('wc_get_orders') ? wc_get_orders($args) : ['orders'=>[], 'total'=>0, 'max_num_pages'=>0];
    list($orders, $total, $pages) = cj_unpack_wc_orders($res1);

    // fallback: meta user_id (se você grava isso no pedido)
    if (empty($orders)) {
        $args_meta = [
            'status'     => $status,
            'orderby'    => 'date',
            'order'      => 'DESC',
            'limit'      => $per_page,
            'page'       => $page,
            'paginate'   => true,
            'return'     => 'objects',
            'type'       => 'shop_order',
            'meta_key'   => 'user_id',
            'meta_value' => (string)$user_id,
        ];
        $res2 = wc_get_orders($args_meta);
        list($orders, $total, $pages) = cj_unpack_wc_orders($res2);
    }

    $rows = array_map('cj_order_to_row', $orders);

    $response = rest_ensure_response($rows);
    $response->header('X-WP-Total', $total);
    $response->header('X-WP-TotalPages', $pages);
    return $response;
}
