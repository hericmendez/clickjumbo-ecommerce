<?php
if (!defined('ABSPATH')) exit;

// Endpoint REST
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/generate-pix', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_generate_pix',
        'permission_callback' => '__return_true',
    ]);
});

// Endpoint público
function clickjumbo_generate_pix(WP_REST_Request $req) {
    $payment_data = $req->get_json_params();
    return new WP_REST_Response([
        'success' => true,
        'pix' => generate_pix($payment_data)
    ]);
}

// Função interna reutilizável
function generate_pix($payment_data) {
    $mock_pix_code = "00020126600014BR.GOV.BCB.PIX0123mock@pix.key520400005303986540625.005802BR5925ClickJumbo Loja6009Sao Paulo62100506PIX1236304B14F";

    return [
        'codigo' => $mock_pix_code,
        'qr_code_url' => "https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($mock_pix_code) . "&size=200x200"
    ];
}
