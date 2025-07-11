<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/../../utils.php'; // para usar sendEmail()
require_once __DIR__ . '/../../../libs/dompdf/autoload.inc.php'; // para gerar PDF


add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/generate-boleto', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_generate_boleto',
        'permission_callback' => '__return_true',
    ]);
});

use Dompdf\Dompdf;

function clickjumbo_generate_boleto(WP_REST_Request $req) {
    $user = $req->get_param('user');
    $valor_total = floatval($req->get_param('valor_total'));

    if (!$user || !$user['email'] || $valor_total <= 0) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Dados incompletos para gerar boleto.'
        ], 400);
    }

    $nome = sanitize_file_name($user['name']);
    $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '-', $nome));
    $file_name = 'boleto-' . $slug . '-' . time() . '.pdf';

    $upload_dir = wp_upload_dir();
    $dir_path = $upload_dir['basedir'] . '/boletos';
    $dir_url = $upload_dir['baseurl'] . '/boletos';

    if (!file_exists($dir_path)) {
        wp_mkdir_p($dir_path);
    }

    $digitable_line = "23793.38127 60005.123456 19000.012345 1 " . str_pad(str_replace('.', '', number_format($valor_total, 2)), 13, '0', STR_PAD_LEFT);

    // ✅ Conteúdo do boleto (HTML)
    $html = "
    <h1>Boleto ClickJumbo</h1>
    <p><strong>Cliente:</strong> {$user['name']}</p>
    <p><strong>Valor:</strong> R$ " . number_format($valor_total, 2, ',', '.') . "</p>
    <p><strong>Linha Digitável:</strong><br><span style='font-size:1.4em;'>$digitable_line</span></p>
    <hr>
    <p style='font-size:0.9em;'>Este boleto é fictício e foi gerado apenas para fins de demonstração.</p>
    ";

    // ✅ Geração do PDF
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->render();
    file_put_contents("$dir_path/$file_name", $dompdf->output());

    $pdf_url = "$dir_url/$file_name";

    // ✅ Envia por email com anexo
    $body = "Olá {$user['name']}, segue seu boleto em anexo.\n\nLinha Digitável: $digitable_line";
    clickjumbo_send_email($user['email'], 'Seu Boleto - ClickJumbo', $body, "$dir_path/$file_name");

    return new WP_REST_Response([
        'success' => true,
        'boleto' => [
            'linha_digitavel' => $digitable_line,
            'pdf_url' => $pdf_url
        ]
    ], 200);
}

