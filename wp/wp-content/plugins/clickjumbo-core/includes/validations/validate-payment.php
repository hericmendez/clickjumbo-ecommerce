<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_validate_payment_data($pagamento) {
    if (!is_array($pagamento) || empty($pagamento['metodo']) || empty($pagamento['payment_data'])) {
        return false;
    }

    // Simples validação por enquanto
    return in_array($pagamento['metodo'], ['pix', 'boleto', 'credit-card', 'debt-card']);
}

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/validate-pagamento', [
        'methods' => 'POST',
        'callback' => function(WP_REST_Request $req) {
            $pagamento = $req->get_param('pagamento');
            $valid = clickjumbo_validate_payment_data($pagamento);
            return new WP_REST_Response(['success' => $valid], $valid ? 200 : 400);
        },
        'permission_callback' => '__return_true',
    ]);
});
