<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_get_taxonomies(WP_REST_Request $request) {
    $result = [];

    // Categorias e subcategorias (tudo em product_cat)
    $terms = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
    ]);

    if (!is_wp_error($terms)) {
        $result['product_cat'] = array_map(function ($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'parent' => $term->parent,
                'count' => $term->count,
            ];
        }, $terms);
    }

    // Penitenciárias (continua como está)
    $prison = get_terms([
        'taxonomy' => 'penitenciaria',
        'hide_empty' => false,
    ]);

    if (!is_wp_error($prison)) {
        $result['penitenciaria'] = array_map(function ($term) {
            return [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
            ];
        }, $prison);
    }

    return new WP_REST_Response([
        'success' => true,
        'taxonomies' => $result
    ]);
}

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/taxonomies', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_taxonomies',
        'permission_callback' => '__return_true'
    ]);
});
