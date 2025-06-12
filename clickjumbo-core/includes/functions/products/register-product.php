<?php
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/register-product', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_register_product',
        'permission_callback' => "__return_true",
    ]);
});

function clickjumbo_register_product($request)
{
    $nonce = $request->get_header('x_wp_nonce');
    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_REST_Response(['success' => false, 'message' => 'Token inválido.'], 401);
    }

    $data = json_decode($request->get_body(), true);
    $required = ['name', 'price', 'sku', 'penitenciaria'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return new WP_REST_Response(['success' => false, 'message' => "Campo '$field' obrigatório."], 400);
        }
    }

    $post_id = wp_insert_post([
        'post_type' => 'product',
        'post_status' => 'publish',
        'post_title' => sanitize_text_field($data['name']),
    ]);

    if (is_wp_error($post_id)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Erro ao criar produto.'], 500);
    }

    update_post_meta($post_id, '_price', floatval($data['price']));
    update_post_meta($post_id, '_regular_price', floatval($data['price']));
    update_post_meta($post_id, '_sku', sanitize_text_field($data['sku']));

    // Associar à penitenciária (taxonomy)
    wp_set_object_terms($post_id, [$data['penitenciaria']], 'penitenciaria');

    return new WP_REST_Response(['success' => true, 'message' => 'Produto cadastrado com sucesso.', 'id' => $post_id]);
}
