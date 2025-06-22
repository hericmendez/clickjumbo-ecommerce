<?php
if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/get-categories', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_categories',
        'permission_callback' => '__return_true'
    ]);
});

function clickjumbo_get_categories(WP_REST_Request $request)
{
    $taxonomy = $request->get_param('taxonomy') ?: 'product_cat'; // padrão: category

    if (!taxonomy_exists($taxonomy)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Taxonomia inválida.',
            'taxonomy' => $taxonomy,
        ], 400);
    }

    $terms = get_terms([
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
    ]);

    if (is_wp_error($terms)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao buscar categorias.',
            'error' => $terms->get_error_message()
        ], 500);
    }

    $result = array_map(function ($term) {
        return [
            'id' => $term->term_id,
            'name' => $term->name,
            'slug' => $term->slug,
            'count' => $term->count,
        ];
    }, $terms);

    return new WP_REST_Response([
        'success' => true,
        'taxonomy' => $taxonomy,
        'categories' => $result
    ], 200);
}
