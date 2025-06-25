<?php
// includes/functions/products/register-category.php

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/register-category', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_register_product_category',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_register_product_category(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    $name = sanitize_text_field($params['name'] ?? '');
    $slug = sanitize_title($params['slug'] ?? '');
    $parent_id = intval($params['parent'] ?? 0); // 0 = categoria raiz

    if (!$name || !$slug) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Nome e slug são obrigatórios.'
        ], 400);
    }

    // Verifica se já existe uma categoria com o mesmo nome ou slug
    $existing = get_term_by('slug', $slug, 'product_cat');
    if ($existing) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Já existe uma categoria com esse slug.',
            'term_id' => $existing->term_id,
        ], 409);
    }

    $args = ['slug' => $slug];
    if ($parent_id > 0) {
        $args['parent'] = $parent_id;
    }

    $result = wp_insert_term($name, 'product_cat', $args);

    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao criar categoria.',
            'error' => $result->get_error_message()
        ], 500);
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Categoria criada com sucesso.',
        'term_id' => $result['term_id']
    ], 201);
}
