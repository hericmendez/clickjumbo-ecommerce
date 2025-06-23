<?php


function clickjumbo_handle_product_form($post, $produto_id = null)
{
    if (!current_user_can('manage_options')) {
        return 'Você não tem permissão para isso.';
    }

    $nome = sanitize_text_field($post['nome'] ?? '');
    $peso = floatval($post['peso'] ?? 0);
    $preco = floatval($post['preco'] ?? 0);
    $sku = sanitize_text_field($post['sku'] ?? '');
    $penitenciaria = sanitize_title($post['penitenciaria'] ?? '');
    $categoria_id = intval($post['categoria'] ?? 0);
    $subcategoria = sanitize_text_field($post['subcategoria'] ?? '');
    $maxUnits = intval($post['maxUnitsPerClient'] ?? 1);

    if (!$nome || $peso <= 0 || $preco <= 0) {
        return 'Preencha todos os campos obrigatórios.';
    }

    $post_data = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'post_title' => $nome,
    ];

    if ($produto_id) {
        $post_data['ID'] = $produto_id;
        $produto_id = wp_update_post($post_data);
    } else {
        $produto_id = wp_insert_post($post_data);
    }

    if (is_wp_error($produto_id)) {
        error_log('Erro ao salvar produto: ' . $produto_id->get_error_message());
        return 'Erro ao salvar produto.';
    }

    // Garantir status publish
    if (get_post_status($produto_id) !== 'publish') {
        wp_update_post(['ID' => $produto_id, 'post_status' => 'publish']);
    }

    // Taxonomias
    if ($categoria_id > 0) {
        $termos = [$categoria_id];

        if (!empty($subcategoria)) {
            $sub_term = get_term_by('name', $subcategoria, 'product_cat');

            if (!$sub_term) {
                $criado = wp_insert_term($subcategoria, 'product_cat', ['parent' => $categoria_id]);
                if (!is_wp_error($criado) && isset($criado['term_id'])) {
                    $termos[] = $criado['term_id'];
                }
            } else {
                $termos[] = $sub_term->term_id;
            }
        }

        wp_set_object_terms($produto_id, $termos, 'product_cat', false);
    }


    if (!empty($penitenciaria)) {
        wp_set_object_terms($produto_id, [$penitenciaria], 'penitenciaria', false);
    }

    // Tipo de produto (obrigatório pro WooCommerce)
    wp_set_object_terms($produto_id, 'simple', 'product_type', false);

    // Metadados
    update_post_meta($produto_id, '_weight', $peso);
    update_post_meta($produto_id, '_price', $preco);
    update_post_meta($produto_id, '_regular_price', $preco);
    update_post_meta($produto_id, '_sku', $sku);
    update_post_meta($produto_id, 'maxUnitsPerClient', $maxUnits);
    update_post_meta($produto_id, 'subcategoria', $subcategoria);

    return null;
}





function clickjumbo_get_dados_produto($produto_id = null)
{
    $dados = [
        'nome' => '',
        'preco' => '',
        'peso' => '',
        'penitenciaria' => '',
        'categoria_id' => '',
        'subcategoria' => '',
        'max' => 1,
        'sku' => ''
    ];

    if ($produto_id && get_post_type($produto_id) === 'product') {
        $post = get_post($produto_id);

        $dados['nome'] = $post->post_title ?? '';
        $dados['preco'] = get_post_meta($produto_id, '_price', true) ?: '';
        $dados['peso'] = get_post_meta($produto_id, '_weight', true) ?: '';
        $dados['sku'] = get_post_meta($produto_id, '_sku', true) ?: '';
        $dados['max'] = intval(get_post_meta($produto_id, 'maxUnitsPerClient', true)) ?: 1;

        // Penitenciária
        $pen = wp_get_object_terms($produto_id, 'penitenciaria');
        $dados['penitenciaria'] = (!empty($pen) && !is_wp_error($pen)) ? $pen[0]->slug : '';

        // Categoria
        $cats = wp_get_object_terms($produto_id, 'product_cat');
        if (!empty($cats) && !is_wp_error($cats)) {
            foreach ($cats as $term) {
                if ($term->parent === 0) {
                    $dados['categoria_id'] = $term->term_id;
                }
            }
        }

        // Subcategoria: campo livre
        $dados['subcategoria'] = get_post_meta($produto_id, 'subcategoria', true) ?: '';
    }

    return $dados;
}

