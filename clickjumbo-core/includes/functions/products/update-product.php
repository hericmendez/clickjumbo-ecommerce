<?php
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/update-product/(?P<id>\d+)', [
        'methods' => 'PUT',
        'callback' => 'clickjumbo_update_product',
        'permission_callback' => function () {
            return current_user_can('edit_products');
        }
    ]);
    register_rest_route('clickjumbo/v1', '/update-product-auth/(?P<id>\d+)', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_update_product',
        'permission_callback' => '__return_true',
    ]);

});

function clickjumbo_update_product($request)
{
    $nonce = $request->get_header('x_wp_nonce');
    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_REST_Response(['success' => false, 'message' => 'Token inválido.'], 401);
    }

    $id = intval($request['id']);
    $product = get_post($id);
    if (!$product || $product->post_type !== 'product') {
        return new WP_REST_Response(['success' => false, 'message' => 'Produto não encontrado.'], 404);
    }

    $data = $request->get_params();

    $updates = [];

    if (!empty($data['name']))
        $updates['post_title'] = sanitize_text_field($data['name']);
    if (!empty($updates))
        wp_update_post(array_merge(['ID' => $id], $updates));

    if (isset($data['price'])) {
        update_post_meta($id, '_price', floatval($data['price']));
        update_post_meta($id, '_regular_price', floatval($data['price']));
    }

    if (isset($data['sku']))
        update_post_meta($id, '_sku', sanitize_text_field($data['sku']));

    if (isset($data['penitenciaria'])) {
        wp_set_object_terms($id, [$data['penitenciaria']], 'penitenciaria');
    }
    // Atualizar categoria e subcategoria (evitando sobrescrever uma ou outra)
    $categoria_id = !empty($data['categoria']) ? intval($data['categoria']) : null;
    $subcategoria_id = !empty($data['subcategoria']) ? intval($data['subcategoria']) : null;

    $cat_terms = [];
    if ($categoria_id)
        $cat_terms[] = $categoria_id;
    if ($subcategoria_id)
        $cat_terms[] = $subcategoria_id;

    if (!empty($cat_terms)) {
        wp_set_object_terms($id, $cat_terms, 'product_cat');
    }

    return new WP_REST_Response(['success' => true, 'message' => 'Produto atualizado com sucesso.']);
}
