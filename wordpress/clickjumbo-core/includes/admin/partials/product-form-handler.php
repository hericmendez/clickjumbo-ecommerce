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
    $penitenciaria = sanitize_text_field($post['penitenciaria'] ?? '');
    $categoria_id = intval($post['categoria'] ?? 0);
    $subcategoria = sanitize_text_field($post['subcategoria'] ?? '');
    $maxUnits = intval($post['maxUnitsPerClient'] ?? 1);

    if (!$nome || $peso <= 0 || $preco <= 0) {
        return 'Preencha todos os campos obrigatórios.';
    }

    $dados_produto = [
        'post_title' => $nome,
        'post_type' => 'product',
        'post_status' => 'publish',
    ];

    if ($produto_id) {
        $dados_produto['ID'] = $produto_id;
        wp_update_post($dados_produto);
    } else {
        $produto_id = wp_insert_post($dados_produto);
        if (is_wp_error($produto_id)) {
            return 'Erro ao salvar produto.';
        }
    }

    // Atribui a categoria como termo da taxonomia correta
    if ($categoria_id > 0) {
        wp_set_object_terms($produto_id, [(int) $categoria_id], 'product_cat', false);
    }

    // Subcategoria salva apenas como meta livre (não taxonomia)
// Subcategoria é um termo filho da categoria
    if (!empty($subcategoria)) {
        wp_set_object_terms($produto_id, [$subcategoria], 'product_cat', true); // true = adiciona, não substitui
    }


    // Outras metas
    update_post_meta($produto_id, '_weight', $peso);
    update_post_meta($produto_id, '_price', $preco);
    update_post_meta($produto_id, '_sku', $sku);
    update_post_meta($produto_id, 'prison', $penitenciaria);
    update_post_meta($produto_id, 'maxUnitsPerClient', $maxUnits);

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

        $pen = wp_get_object_terms($produto_id, 'penitenciaria');
        $dados['penitenciaria'] = !empty($pen) && !is_wp_error($pen) ? $pen[0]->slug : '';

        $cat = wp_get_object_terms($produto_id, 'product_cat');
        if (!empty($cat) && !is_wp_error($cat)) {
    foreach ($cat as $term) {
        if ($term->parent === 0) {
            $dados['categoria_id'] = $term->term_id;
        } else {
            $dados['subcategoria'] = $term->term_id;
        }
    }
}
        $dados['categoria_id'] = !empty($cat) && !is_wp_error($cat) ? $cat[0]->term_id : '';

        // Subcategoria vem do campo livre, não da taxonomia
        $dados['subcategoria'] = get_post_meta($produto_id, 'product_cat', true) ?: '';
    }

    return $dados;
}
