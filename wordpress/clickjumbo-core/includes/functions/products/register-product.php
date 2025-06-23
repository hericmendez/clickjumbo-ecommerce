<?php

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/register-product', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_register_product',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_register_product($request)
{
    $data = json_decode($request->get_body(), true);

    $required = ['name', 'price', 'sku', 'penitenciaria'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => "Campo obrigatório: '$field'."
            ], 400);
        }
    }

    // Criar produto
    $post_id = wp_insert_post([
        'post_type' => 'product',
        'post_status' => 'publish',
        'post_title' => sanitize_text_field($data['name']),
    ]);

    if (is_wp_error($post_id)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao criar o produto.'
        ], 500);
    }

    // Metadados
    update_post_meta($post_id, '_price', floatval($data['price']));
    update_post_meta($post_id, '_regular_price', floatval($data['price']));
    update_post_meta($post_id, '_sku', sanitize_text_field($data['sku']));
    update_post_meta($post_id, '_weight', isset($data['weight']) ? floatval($data['weight']) : 0);
    update_post_meta($post_id, 'maxUnitsPerClient', isset($data['maxUnitsPerClient']) ? intval($data['maxUnitsPerClient']) : 1);
    update_post_meta($post_id, 'thumb', esc_url_raw($data['thumb'] ?? ''));

    // Subcategoria (salva para referência no front, independentemente de ser vinculada)
    $subcat_name = sanitize_text_field($data['subcategory'] ?? '');
    update_post_meta($post_id, 'subcategoria', $subcat_name);

    // Tipo do produto
    wp_set_object_terms($post_id, 'simple', 'product_type');

    // Relacionar penitenciária
    wp_set_object_terms($post_id, [$data['penitenciaria']], 'penitenciaria');

    // --- Categoria e Subcategoria (hierarquia WooCommerce) ---
    $categoria_input = $data['categoria'] ?? '';
    $categoria_id = null;
    $subcategoria_id = null;

    // Buscar ou criar categoria principal
    if (!empty($categoria_input)) {
        if (is_numeric($categoria_input)) {
            $categoria_term = get_term_by('id', intval($categoria_input), 'product_cat');
        } else {
            $categoria_term = get_term_by('name', sanitize_text_field($categoria_input), 'product_cat');
            if (!$categoria_term) {
                $result = wp_insert_term(sanitize_text_field($categoria_input), 'product_cat');
                if (!is_wp_error($result)) {
                    $categoria_term = get_term($result['term_id'], 'product_cat');
                }
            }
        }

        if ($categoria_term && !is_wp_error($categoria_term)) {
            $categoria_id = $categoria_term->term_id;
        }
    }

    // Buscar ou criar subcategoria (filha da categoria)
    if (!empty($subcat_name) && $categoria_id) {
            error_log("Registrando subcategoria: $subcat_name");

        $subcat_term = get_term_by('name', $subcat_name, 'product_cat');
        if (!$subcat_term) {
            $result = wp_insert_term($subcat_name, 'product_cat', ['parent' => $categoria_id]);
            if (!is_wp_error($result)) {
                $subcat_term = get_term($result['term_id'], 'product_cat');
            }
        }

        if ($subcat_term && !is_wp_error($subcat_term)) {
            $subcategoria_id = $subcat_term->term_id;
        }
    }

    // Associar categorias ao produto
    $termos = array_filter([$categoria_id, $subcategoria_id]);
    if (!empty($termos)) {
        wp_set_object_terms($post_id, $termos, 'product_cat');
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Produto cadastrado com sucesso.',
        'id' => $post_id
    ], 201);
}
