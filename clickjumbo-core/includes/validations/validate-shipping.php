<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/validate-shipping', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_validate_shipping',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_validate_shipping(WP_REST_Request $request) {
    // Lê o corpo da request como JSON
    $body = json_decode($request->get_body(), true);
    $shipping = $body['shipping'] ?? null;

    // Log para debug
    error_log('[validate-shipping] Payload recebido: ' . print_r($shipping, true));

    // Verifica existência e tipo
    if (!$shipping || !is_array($shipping)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Estrutura de envio ausente ou malformada.'
        ], 400);
    }

    $required_fields = [
        'prison_name',
        'cart_weight',
        'method',
        'sender_address',
        'frete_valor'
    ];

    $missing = [];
    foreach ($required_fields as $field) {
        if (!isset($shipping[$field])) {
            $missing[] = $field;
        }
    }

    // Validação do endereço
    $address_fields = ['cep', 'rua', 'cidade', 'estado'];
    foreach ($address_fields as $f) {
        if (empty($shipping['sender_address'][$f] ?? null)) {
            $missing[] = "sender_address.$f";
        }
    }

    if (!empty($missing)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Campos obrigatórios ausentes.',
            'missing' => $missing
        ], 400);
    }

    return new WP_REST_Response(['success' => true], 200);
}
