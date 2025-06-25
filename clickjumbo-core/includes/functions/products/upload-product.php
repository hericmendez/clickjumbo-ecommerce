<?php
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/upload-product', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_upload_product',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_upload_product($request)
{
    if (!current_user_can('edit_posts')) {
        return new WP_Error('unauthorized', 'Permissão negada.', ['status' => 403]);
    }

    $params = $request->get_file_params();
    $fields = $request->get_params();
    $produto_id = intval($fields['produto_id'] ?? 0);
    $image_url = '';

    // DEBUG opcional
    // error_log('POST: ' . print_r($fields, true));
    // error_log('FILES: ' . print_r($params, true));

    // --- Criar ou atualizar post ---
    $post_data = [
        'post_title' => sanitize_text_field($fields['nome']),
        'post_type' => 'product',
        'post_status' => 'publish',
        'meta_input' => [
            '_price' => floatval($fields['preco']),
            '_weight' => floatval($fields['peso']),
            '_sku' => sanitize_text_field($fields['sku']),
            '_cj_max_units' => intval($fields['maxUnitsPerClient']),
        ],
    ];

    if ($produto_id > 0) {
        $post_data['ID'] = $produto_id;
        $produto_id = wp_update_post($post_data);
    } else {
        $produto_id = wp_insert_post($post_data);
    }

    if (is_wp_error($produto_id)) {
        return $produto_id;
    }

    // --- Categoria (ID ou nome) ---
    $categoria_input = $fields['categoria'] ?? '';
    $categoria_id = 0;

    if (!empty($categoria_input)) {
        if (is_numeric($categoria_input)) {
            $categoria_id = intval($categoria_input);
        } else {
            $categoria_term = get_term_by('name', sanitize_text_field($categoria_input), 'product_cat');
            if ($categoria_term && !is_wp_error($categoria_term)) {
                $categoria_id = $categoria_term->term_id;
            }
        }
    }

    // --- Subcategoria ---
    $subcat_name = sanitize_text_field($fields['subcategoria'] ?? '');
    $subcat_id = null;

    if (!empty($subcat_name) && $categoria_id > 0) {
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

    // --- Associar categorias ao produto ---
    $term_ids = array_filter([$categoria_id, $subcat_id]);
    if (!empty($term_ids)) {
        wp_set_object_terms($produto_id, $term_ids, 'product_cat');
    }

    // --- Penitenciária ---
    if (!empty($fields['penitenciaria'])) {
        $penit = get_term_by('slug', sanitize_text_field($fields['penitenciaria']), 'penitenciaria');
        if ($penit && !is_wp_error($penit)) {
            wp_set_object_terms($produto_id, [$penit->term_id], 'penitenciaria');
        }
    }

    // --- Imagem ---
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
        // Se não veio nova imagem, mantém a antiga
        $thumb_id = get_post_thumbnail_id($produto_id);
        if ($thumb_id) {
            $image_url = wp_get_attachment_image_url($thumb_id, 'full');
        }
    }

    return rest_ensure_response([
        'success' => true,
        'id' => $produto_id,
        'image_url' => $image_url,
    ]);
}
