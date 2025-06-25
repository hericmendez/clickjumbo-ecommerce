<?php
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/save-product', [
        'methods' => ['POST', 'PUT'],
        'callback' => 'clickjumbo_save_product',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_save_product($request)
{
    $params = $request->get_file_params();
    $fields = $request->get_params();
    $produto_id = intval($fields['produto_id'] ?? 0);
    $image_url = '';

    // Campos principais
    $nome_produto = sanitize_text_field($fields['nome'] ?? $fields['name'] ?? '');
    if (empty($nome_produto)) {
        return new WP_Error('missing_title', 'O campo "name" ou "nome" é obrigatório.', ['status' => 400]);
    }

    $preco = floatval($fields['preco'] ?? $fields['price'] ?? 0);
    $peso = floatval($fields['peso'] ?? $fields['weight'] ?? 0);
    $sku = sanitize_text_field($fields['sku'] ?? '');
    if (empty($sku)) {
        $sku = 'CJ-' . date('Ymd-His'); // ex: CJ-20250625-153045
    }

    $max_units = intval($fields['maxUnitsPerClient'] ?? 1);

    // Criação ou atualização
    $post_data = [
        'post_title' => $nome_produto,
        'post_type' => 'product',
        'post_status' => 'publish',
    ];

    if ($produto_id > 0) {
        $post_data['ID'] = $produto_id;
        $produto_id = wp_update_post($post_data);
    } else {
        $produto_id = wp_insert_post($post_data);
    }

    if (is_wp_error($produto_id) || !$produto_id) {
        return new WP_Error('insert_failed', 'Erro ao salvar o produto.', ['status' => 500]);
    }

    // Metadados WooCommerce
    update_post_meta($produto_id, '_price', $preco);
    update_post_meta($produto_id, '_regular_price', $preco);
    update_post_meta($produto_id, '_weight', $peso);
    update_post_meta($produto_id, '_sku', $sku);
    update_post_meta($produto_id, '_cj_max_units', $max_units);
    update_post_meta($produto_id, '_stock_status', 'instock');
    update_post_meta($produto_id, '_manage_stock', 'no');
    update_post_meta($produto_id, '_product_version', WC()->version);
    update_post_meta($produto_id, '_product_type', 'simple');
    wp_set_object_terms($produto_id, 'simple', 'product_type');

    // Categoria e subcategoria
    $categoria_input = sanitize_text_field($fields['categoria'] ?? '');
    $subcat_name = sanitize_text_field($fields['subcategoria'] ?? '');
    $categoria_id = 0;
    $subcat_id = 0;

    if ($categoria_input) {
        $categoria_term = get_term_by('name', $categoria_input, 'product_cat');
        if ($categoria_term && !is_wp_error($categoria_term)) {
            $categoria_id = $categoria_term->term_id;
        }
    }

    if ($subcat_name && $categoria_id) {
        $subcat_term = get_term_by('name', $subcat_name, 'product_cat');
        if (!$subcat_term) {
            $created = wp_insert_term($subcat_name, 'product_cat', ['parent' => $categoria_id]);
            if (!is_wp_error($created)) {
                $subcat_term = get_term($created['term_id'], 'product_cat');
            }
        }
        if ($subcat_term && !is_wp_error($subcat_term)) {
            $subcat_id = $subcat_term->term_id;
        }
    }

    wp_set_object_terms($produto_id, array_filter([$categoria_id, $subcat_id]), 'product_cat');

    // Penitenciária
    $penit = null;
    if (!empty($fields['penitenciaria'])) {
        $penit = get_term_by('slug', sanitize_text_field($fields['penitenciaria']), 'penitenciaria');
        if ($penit && !is_wp_error($penit)) {
            wp_set_object_terms($produto_id, [$penit->term_id], 'penitenciaria');
        }
    }

    // Imagem
    if (isset($params['thumb']) && !empty($params['thumb']['tmp_name'])) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attach_id = media_handle_upload('thumb', $produto_id);
        if (!is_wp_error($attach_id)) {
            set_post_thumbnail($produto_id, $attach_id);
            $image_url = wp_get_attachment_image_url($attach_id, 'full');
        }
    } else {
        $thumb_id = get_post_thumbnail_id($produto_id);
        if ($thumb_id) {
            set_post_thumbnail($produto_id, $thumb_id); // reforça vínculo
            $image_url = wp_get_attachment_image_url($thumb_id, 'full');
        } else {
            delete_post_thumbnail($produto_id); // limpa se não houver
        }
    }


    // Objeto compatível com /product-list
    return rest_ensure_response([
        'success' => true,
        'id' => $produto_id,
        'image_url' => $image_url,
        'product' => [
            'id' => $produto_id,
            'name' => $nome_produto,
            'category' => $categoria_input ?: 'Sem Categoria',
            'subcategory' => $subcat_name ?: 'Sem Subcategoria',
            'penitenciaria' => $penit->name ?? 'Sem Penitenciária',
            'penitenciaria_slug' => $penit->slug ?? '',
            'weight' => $peso,
            'price' => $preco,
            'maxUnitsPerClient' => $max_units,
            'thumb' => esc_url($image_url),
        ]
    ]);
}
