<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/export-orders', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_export_orders',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_export_orders() {
    $filename = 'pedidos_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Cliente', 'Penitenciária', 'Status', 'Total', 'Peso', 'Data']);

    $orders = wc_get_orders(['limit' => -1]);
    foreach ($orders as $order) {
        $peso = $order->get_meta('peso_total') ?: 0;
        fputcsv($output, [
            $order->get_id(),
            $order->get_billing_first_name(),
            $order->get_meta('penitenciaria'),
            $order->get_status(),
            $order->get_total(),
            $peso,
            $order->get_date_created()->date('Y-m-d H:i:s')
        ]);
    }

    fclose($output);
    exit;
}
