<?php
// includes/api/edit-prison.php

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/update-prison/(?P<slug>[a-zA-Z0-9\-]+)', [
        'methods' => 'PUT',
        'callback' => 'clickjumbo_update_prison',
        'permission_callback' => function () {
            return current_user_can('manage_options');
        }
    ]);
});
function clickjumbo_update_prison($request)
{
    // Verifica nonce
    $nonce = $request->get_header('x_wp_nonce');
    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Token inválido ou não fornecido.'
        ], 401);
    }

    $slug = sanitize_title($request['slug']);
    $term = get_term_by('slug', $slug, 'penitenciaria');
    if (!$term) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Penitenciária não encontrada.'
        ], 404);
    }

    $data = json_decode($request->get_body(), true);
    if (!$data || !isset($data['nome'], $data['cidade'], $data['estado'], $data['cep'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Dados incompletos.'
        ], 400);
    }

    wp_update_term($term->term_id, 'penitenciaria', ['name' => sanitize_text_field($data['nome'])]);

    update_term_meta($term->term_id, 'cidade', sanitize_text_field($data['cidade']));
    update_term_meta($term->term_id, 'estado', sanitize_text_field($data['estado']));
    update_term_meta($term->term_id, 'cep', sanitize_text_field($data['cep']));

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Penitenciária atualizada com sucesso.'
    ]);
}
