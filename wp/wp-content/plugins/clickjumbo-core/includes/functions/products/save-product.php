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

    // --- VALIDAÇÃO DE CAMPOS PRINCIPAIS ---
    $nome_produto = sanitize_text_field($fields['nome'] ?? $fields['name'] ?? '');
    if (empty($nome_produto)) {
        return new WP_Error('missing_title', 'O campo "nome" ou "name" é obrigatório.', ['status' => 400]);
    }

    $preco = floatval($fields['preco'] ?? $fields['price'] ?? 0);
    $peso = floatval($fields['peso'] ?? $fields['weight'] ?? 0);
    $sku = sanitize_text_field($fields['sku'] ?? '') ?: 'CJ-' . date('Ymd-His');
    $max_units = intval($fields['maxUnitsPerClient'] ?? 1);


    // --- NOVOS CAMPOS BOOLEANOS ---
    $is_premium = filter_var($fields['premium'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $is_padrao = filter_var($fields['padrao'] ?? false, FILTER_VALIDATE_BOOLEAN);

    // --- PENITENCIÁRIAS (MODO PADRÃO OU ARRAY) ---
$penitenciarias_input = $fields['penitenciaria'] ?? [];

// Reagrupa penitenciarias do formato flatten para [{ slug, label }]
if (!is_array($penitenciarias_input)) {
    $penitenciarias_input = [];
} elseif (isset($fields['penitenciaria[0][slug]'])) {
    $penitenciarias_input = [];
    foreach ($fields as $key => $value) {
        if (preg_match('/penitenciaria\[(\d+)\]\[(slug|label)\]/', $key, $matches)) {
            $index = $matches[1];
            $field = $matches[2];
            $penitenciarias_input[$index][$field] = sanitize_text_field($value);
        }
    }
}

// Reagrupa penitenciarias do formato flatten para [{ slug, label }]
if (!is_array($penitenciarias_input)) {
    $penitenciarias_input = [];
} elseif (isset($fields['penitenciaria[0][slug]'])) {
    $penitenciarias_input = [];
    foreach ($fields as $key => $value) {
        if (preg_match('/penitenciaria\[(\d+)\]\[(slug|label)\]/', $key, $matches)) {
            $index = $matches[1];
            $field = $matches[2];
            $penitenciarias_input[$index][$field] = sanitize_text_field($value);
        }
    }
}


    if (!is_array($penitenciarias_input)) {
        return new WP_Error('invalid_penitenciaria_format', 'O campo "penitenciaria" deve ser um array de objetos com slug e label.', ['status' => 400]);
    }

    if ($is_padrao) {
        $penitenciarias_input = [[ 'slug' => 'todas', 'label' => 'Todas' ]]; // padrão: disponível para todas
    }

    $penitenciaria_term_ids = [];

    foreach ($penitenciarias_input as $penitenciaria_item) {
        if (!isset($penitenciaria_item['slug'])) continue;
        $slug = sanitize_title($penitenciaria_item['slug']);

        if ($slug === 'todas') continue;

        $term = get_term_by('slug', $slug, 'penitenciaria');
        if ($term && !is_wp_error($term)) {
            $penitenciaria_term_ids[] = $term->term_id;
        }
    }

    if (!$is_padrao && empty($penitenciaria_term_ids)) {
        return new WP_Error('missing_penitenciaria', 'Pelo menos uma penitenciária válida deve ser informada.', ['status' => 400]);
    }


    // --- VALIDAÇÃO DA CATEGORIA ---
    $categoria_input = sanitize_text_field($fields['categoria'] ?? '');
    $subcat_name = sanitize_text_field($fields['subcategoria'] ?? '');
    $categoria_id = 0;
    $subcat_id = 0;

    if (empty($categoria_input)) {
        return new WP_Error('missing_categoria', 'O campo "categoria" é obrigatório.', ['status' => 400]);
    }

    $categoria_term = get_term_by('name', $categoria_input, 'product_cat');
    if (!$categoria_term || is_wp_error($categoria_term) || $categoria_term->parent != 0) {
        $categorias_pai = get_terms([
            'taxonomy' => 'product_cat',
            'parent' => 0,
            'hide_empty' => false,
        ]);
        $categorias_disponiveis = array_map(fn($term) => $term->name, $categorias_pai);

        return new WP_Error(
            'invalid_categoria',
            'A categoria informada não existe ou não é uma categoria principal.',
            ['status' => 400, 'categorias_disponiveis' => $categorias_disponiveis]
        );
    }
if ($produto_id > 0) {
    $post_data['id'] = $produto_id;
    $produto_id = wp_update_post($post_data);
    $is_editing = true;
} else {
    $produto_id = wp_insert_post($post_data);
    $is_editing = false;
}
    $categoria_id = $categoria_term->term_id;

    // --- SUBCATEGORIA (OPCIONAL) ---
    if (!empty($subcat_name)) {
        $subcat_term = get_term_by('name', $subcat_name, 'product_cat');

        // Verifica se subcategoria já existe e é filha da categoria atual
        if ($subcat_term && $subcat_term->parent != $categoria_id) {
            
            return new WP_Error(
                'invalid_subcategoria',
                'Já existe uma subcategoria com esse nome, mas vinculada a outra categoria.',
                ['status' => 400]
            );
        }

        // Se não existir, cria
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

    // --- CRIAÇÃO OU ATUALIZAÇÃO DO PRODUTO ---
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


    // --- METADADOS E TERMOS ---
    update_post_meta($produto_id, '_price', $preco);
    update_post_meta($produto_id, '_regular_price', $preco);
    update_post_meta($produto_id, '_weight', $peso);
    update_post_meta($produto_id, '_sku', $sku);
    update_post_meta($produto_id, '_cj_max_units', $max_units);
    update_post_meta($produto_id, '_stock_status', 'instock');
    update_post_meta($produto_id, '_manage_stock', 'no');
    update_post_meta($produto_id, '_product_version', WC()->version);
    update_post_meta($produto_id, '_product_type', 'simple');
    update_post_meta($produto_id, '_cj_is_premium', $is_premium ? 'yes' : 'no');
    update_post_meta($produto_id, '_cj_is_padrao', $is_padrao ? 'yes' : 'no');
if (!$is_editing) {
    update_post_meta($produto_id, '_cj_criado_em', current_time('Y-m-d H:i:s'));
}

    wp_set_object_terms($produto_id, 'simple', 'product_type');
    wp_set_object_terms($produto_id, array_filter([$categoria_id, $subcat_id]), 'product_cat');
    if (!$is_padrao) {
wp_set_object_terms($produto_id, $penitenciaria_term_ids, 'penitenciaria');

    }


    // --- IMAGEM ---
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
            set_post_thumbnail($produto_id, $thumb_id);
            $image_url = wp_get_attachment_image_url($thumb_id, 'full');
        } else {
            delete_post_thumbnail($produto_id);
        }
    }
$criado_em_raw = get_post_meta($produto_id, '_cj_criado_em', true);
$criado_em_formatado = $criado_em_raw ? date('d-m-Y H:i', strtotime($criado_em_raw)) : null;

    // --- RESPOSTA FINAL ---
return rest_ensure_response([
    'success' => true,
    'id' => $produto_id,
    'image_url' => $image_url,
    'product' => [
        'id' => $produto_id,
        'nome' => $nome_produto,
        'categoria' => $categoria_input,
        'subcategoria' => $subcat_name ?: 'Sem Subcategoria',
        'penitenciarias' => $penitenciarias_input,
        'peso' => $peso,
        'preco' => $preco,
        'maximo_por_cliente' => $max_units,
        'premium' => $is_premium,
        'padrao' => $is_padrao,
        'thumb' => esc_url($image_url),
        'criado_em' => $criado_em_formatado,
    ]
]);

}

