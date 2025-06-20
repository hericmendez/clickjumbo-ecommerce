<?php

function clickjumbo_handle_product_form($post, $produto_id = null)
{
    $editando = !!$produto_id;

    $nome = sanitize_text_field($post['nome']);
    $preco = floatval($post['preco']);
    $peso = floatval($post['peso']);
    $prison_slug = sanitize_title($post['penitenciaria']);
    $cat_id = intval($post['categoria']);
    $subcat = sanitize_text_field($post['subcategoria']);
    $max = intval($post['maxUnitsPerClient']);
    $sku = sanitize_text_field($post['sku']);

    // Validações
    if (!$nome || !$preco || !$peso || !$prison_slug || !$cat_id || $max < 1) {
        return 'Preencha todos os campos obrigatórios corretamente.';
    }

    if (empty($sku)) {
        $slug_base = sanitize_title($nome);
        $random = strtoupper(wp_generate_password(4, false, false));
        $sku = strtoupper(substr($slug_base, 0, 5)) . '-' . $random;

        while (wc_get_product_id_by_sku($sku)) {
            $random = strtoupper(wp_generate_password(4, false, false));
            $sku = strtoupper(substr($slug_base, 0, 5)) . '-' . $random;
        }
    }

    if ($editando) {
        wp_update_post([
            'ID' => $produto_id,
            'post_title' => $nome,
            'post_status' => 'publish'
        ]);
    } else {
        $produto_id = wp_insert_post([
            'post_title' => $nome,
            'post_type' => 'product',
            'post_status' => 'publish'
        ]);
    }

    if (is_wp_error($produto_id)) {
        return 'Erro ao salvar produto.';
    }

    // Taxonomias
    wp_set_object_terms($produto_id, $prison_slug, 'penitenciaria');
    wp_set_object_terms($produto_id, [$cat_id], 'product_cat');

    if ($subcat) {
        $subcat_term = term_exists($subcat, 'product_cat');
        if (!$subcat_term) {
            $subcat_term = wp_insert_term($subcat, 'product_cat', ['parent' => $cat_id]);
        }
        if (!is_wp_error($subcat_term)) {
            wp_set_object_terms($produto_id, [$subcat_term['term_id']], 'product_cat', true);
        }
    }

    // Metadados
    update_post_meta($produto_id, '_price', $preco);
    update_post_meta($produto_id, '_regular_price', $preco);
    update_post_meta($produto_id, '_weight', $peso);
    update_post_meta($produto_id, 'prison', $prison_slug);
    update_post_meta($produto_id, 'maxUnitsPerClient', $max);
    update_post_meta($produto_id, '_sku', $sku);

    update_post_meta($produto_id, '_stock_status', 'instock');
    update_post_meta($produto_id, '_manage_stock', 'no');
    update_post_meta($produto_id, '_visibility', 'visible');
    wc_update_product_stock_status($produto_id, 'instock');
    wp_update_post(['ID' => $produto_id, 'post_status' => 'publish']);

    // Redireciona
    wp_redirect(admin_url('admin.php?page=clickjumbo-prisons&produto=ok'));
    exit;
}

function clickjumbo_get_dados_produto($produto_id = null)
{
    $dados = [
        'nome' => '',
        'preco' => '',
        'peso' => '',
        'penitenciaria' => '',
        'categoria' => '',
        'subcategoria' => '',
        'max' => 1,
        'sku' => ''
    ];

    if ($produto_id && get_post_type($produto_id) === 'product') {
        $post = get_post($produto_id);
        $dados['nome'] = $post->post_title;
        $dados['preco'] = get_post_meta($produto_id, '_price', true);
        $dados['peso'] = get_post_meta($produto_id, '_weight', true);
        $dados['max'] = get_post_meta($produto_id, 'maxUnitsPerClient', true);
        $dados['sku'] = get_post_meta($produto_id, '_sku', true);

        $pen = wp_get_object_terms($produto_id, 'penitenciaria');
        $cat = wp_get_object_terms($produto_id, 'product_cat');

        $dados['penitenciaria'] = $pen[0]->slug ?? '';
        $dados['categoria'] = $cat[0]->term_id ?? '';
        $dados['subcategoria'] = $cat[1]->name ?? '';
    }

    return $dados;
}
