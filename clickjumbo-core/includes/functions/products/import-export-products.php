<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/export-products-csv', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_export_products_csv',
        'permission_callback' => "__return_true",
    ]);
});

function clickjumbo_export_products_csv() {
    $args = [
        'status' => ['pending', 'processing', 'completed'],
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
    ];

    $products = wc_get_products($args);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="pedidos_exportados.csv"');

    $output = fopen('php://output', 'w');

    // Cabeçalho
    fputcsv($output, [
        'Pedido ID',
        'Cliente',
        'Penitenciária',
        'Produto',
        'Categoria',
        'Subcategoria',
        'Peso (kg)',
        'Preço (R$)',
        'Quantidade',
        'Subtotal (R$)',
        'Data'
    ]);

    foreach ($products as $order) {
        $cliente = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $penitenciaria_slug = $order->get_meta('penitenciaria');
        $penitenciaria_nome = $penitenciaria_slug; // Você pode buscar detalhes se quiser

        $data_pedido = $order->get_date_created()->date('d/m/Y H:i');

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if (!$product) continue;

            $produto_id = $product->get_id();
            $nome = $product->get_name();
            $categoria = wc_get_product_category_list($produto_id, ', ');
            $subcategoria = 'Sem Subcategoria'; // Customizar se necessário
            $peso = $product->get_weight();
            $preco = wc_format_decimal($item->get_total() / $item->get_quantity(), 2);
            $quantidade = $item->get_quantity();
            $subtotal = wc_format_decimal($item->get_total(), 2);

            fputcsv($output, [
                $order->get_id(),
                $cliente,
                $penitenciaria_nome,
                $nome,
                $categoria,
                $subcategoria,
                $peso,
                $preco,
                $quantidade,
                $subtotal,
                $data_pedido
            ]);
        }
    }

    fclose($output);
    exit;
}
