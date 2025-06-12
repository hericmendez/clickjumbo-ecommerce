<?php
if (!defined('ABSPATH')) exit;
require_once __DIR__ . '/../../../libs/dompdf/autoload.inc.php';
use Dompdf\Dompdf;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/orders/(?P<id>\d+)/receipt', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_generate_receipt',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_generate_receipt($request) {
    $id = absint($request['id']);
    $order = wc_get_order($id);
    if (!$order) return new WP_REST_Response(['error' => 'Pedido não encontrado'], 404);

    $html = "<h1>Recibo do Pedido #$id</h1>";
    $html .= "<p>Status: {$order->get_status()}</p>";
    $html .= "<p>Total: R$ {$order->get_total()}</p>";

    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream("recibo_pedido_$id.pdf", ["Attachment" => true]);

    exit;
}
