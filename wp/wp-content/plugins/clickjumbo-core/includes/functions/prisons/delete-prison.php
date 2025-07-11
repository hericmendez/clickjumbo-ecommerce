<?php
// includes/api/delete-prison.php

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/delete-prison/(?P<slug>[a-zA-Z0-9\-]+)', [
        'methods' => 'DELETE',
        'callback' => 'clickjumbo_delete_prison',
       /*'permission_callback' => function () {
            return current_user_can('manage_options');
        }*/ 
        'permission_callback'=> '__return_true'
    ]);
});

function clickjumbo_delete_prison($request)
{
    // ⚠️ Verifica o nonce manualmente
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

    wp_delete_term($term->term_id, 'penitenciaria');

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Penitenciária excluída com sucesso.'
    ]);
}
