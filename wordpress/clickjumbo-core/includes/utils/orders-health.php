<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/orders-health', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_orders_health',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_orders_health() {
    $statuses = ['pending', 'processing', 'completed', 'cancelled'];
    $totals = [];

    foreach ($statuses as $status) {
        $totals[$status] = wc_get_orders(['status' => $status, 'return' => 'ids']);
        $totals[$status] = count($totals[$status]);
    }

    return $totals;
}
