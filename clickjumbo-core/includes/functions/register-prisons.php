<?php
// includes/functions/register-prisons.php

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/register-prison', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_register_prison',
        'permission_callback' => '__return_true', // Lembre de proteger isso depois!
    ]);
});

function clickjumbo_register_prison($request) {
    $params = $request->get_json_params();

    $nome   = sanitize_text_field($params['nome'] ?? '');
    $cidade = sanitize_text_field($params['cidade'] ?? '');
    $estado = sanitize_text_field($params['estado'] ?? '');
    $cep    = preg_replace('/[^0-9]/', '', $params['cep'] ?? ''); // Remove traços e letras

    if (!$nome || !$cidade || !$estado || !$cep) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Campos obrigatórios: nome, cidade, estado e CEP.',
        ], 400);
    }

    if (strlen($cep) !== 8) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'CEP inválido. Use 8 dígitos numéricos.',
        ], 400);
    }

    // Verifica se já existe uma penitenciária com esse nome
    if (term_exists($nome, 'penitenciaria')) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Já existe uma penitenciária com esse nome.',
        ], 409);
    }

    // Cria o termo
    $result = wp_insert_term($nome, 'penitenciaria');

    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao registrar penitenciária.',
            'error' => $result->get_error_message(),
        ], 500);
    }

    $term_id = $result['term_id'];

    // Salva metadados
    update_term_meta($term_id, 'cidade', $cidade);
    update_term_meta($term_id, 'estado', $estado);
    update_term_meta($term_id, 'cep', $cep);

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Penitenciária registrada com sucesso.',
        'term_id' => $term_id,
    ]);
}
