<?php

// Token de acesso mockado (use "Authorization: Bearer clickjumbo123token")
define('CLICKJUMBO_TOKEN_FIXO', 'clickjumbo123token');

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/product-list', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_products',
        'permission_callback' => '__return_true',
    ]);



    register_rest_route('clickjumbo/v1', '/product-details/(?P<id>\\d+)', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_product_details',
        'permission_callback' => '__return_true'
    ]);
});

function clickjumbo_get_products($request)
{
    try {
        $slug_param = sanitize_title($request->get_param('slug') ?? '');

        $args = [
            'post_status' => 'publish',
            'limit' => 100,
            'post_type' => 'product',
            'orderby' => 'date',
            'order' => 'DESC',
        ];

        $produtos = wc_get_products($args);
        $resultado = [];

        foreach ($produtos as $produto) {
            $categoria_principal = 'Sem Categoria';
            $subcategoria = 'Sem Subcategoria';

            $terms = get_the_terms($produto->get_id(), 'product_cat');
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    if ($term->parent == 0 && $categoria_principal === 'Sem Categoria') {
                        $categoria_principal = $term->name;
                    } elseif ($term->parent != 0) {
                        $parent_term = get_term($term->parent, 'product_cat');
                        if ($parent_term && !is_wp_error($parent_term)) {
                            $categoria_principal = $parent_term->name;
                            $subcategoria = $term->name;
                            break;
                        }
                    }
                }
            }

            // Penitenciária e filtro por slug
            $penitenciaria_terms = get_the_terms($produto->get_id(), 'penitenciaria');
            $penitenciaria_nome = 'Sem Penitenciária';
            $penitenciaria_slug = '';

            if (!empty($penitenciaria_terms) && !is_wp_error($penitenciaria_terms)) {
                foreach ($penitenciaria_terms as $term) {
                    if ($slug_param && sanitize_title($term->slug) === $slug_param) {
                        $penitenciaria_nome = $term->name;
                        $penitenciaria_slug = $term->slug;
                        break;
                    }
                    if (!$slug_param && $penitenciaria_slug === '') {
                        $penitenciaria_nome = $term->name;
                        $penitenciaria_slug = $term->slug;
                    }
                }

                // Se for uma requisição filtrada e nenhum slug bateu, pula
                if ($slug_param && $penitenciaria_slug !== $slug_param) {
                    continue;
                }
            } else {
                if ($slug_param) continue;
            }

            $max_units = get_post_meta($produto->get_id(), 'maxUnitsPerClient', true) ?: 1;
            $thumb = get_the_post_thumbnail_url($produto->get_id(), 'medium');

            $resultado[] = [
                'id' => $produto->get_id(),
                'name' => $produto->get_name(),
                'category' => $categoria_principal,
                'subcategory' => $subcategoria,
                'penitenciaria' => $penitenciaria_nome,
                'penitenciaria_slug' => $penitenciaria_slug,
                'weight' => (float) $produto->get_weight(),
                'price' => (float) $produto->get_price(),
                'maxUnitsPerClient' => (int) $max_units,
                'thumb' => esc_url($thumb),
            ];
        }

        return rest_ensure_response([
            'status' => 200,
            'message' => 'ok',
            'content' => $resultado,
        ]);
    } catch (Exception $e) {
        return new WP_Error('erro_interno', 'Erro ao obter produtos: ' . $e->getMessage(), ['status' => 500]);
    }
}







function clickjumbo_get_product_details($request)
{
    $id = intval($request['id']);
    $produto = wc_get_product($id);

    if (!$produto || $produto->get_type() !== 'simple') {
        return new WP_Error('not_found', 'Produto não encontrado ou inválido.', ['status' => 404]);
    }

    $categoria_principal = 'Sem Categoria';
    $categoria_principal_id = null;
    $subcategoria = '';

    $terms = get_the_terms($produto->get_id(), 'product_cat');
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            if ($term->parent == 0 && $categoria_principal === 'Sem Categoria') {
                $categoria_principal = $term->name;
                $categoria_principal_id = $term->term_id;
            } elseif ($term->parent != 0) {
                $parent = get_term($term->parent, 'product_cat');
                if ($parent && !is_wp_error($parent)) {
                    $categoria_principal = $parent->name;
                    $categoria_principal_id = $parent->term_id;
                    $subcategoria = $term->name;
                    break;
                }
            }
        }
    }

    // Penitenciária
    $penitenciaria_terms = get_the_terms($produto->get_id(), 'penitenciaria');
    $penitenciaria = (!empty($penitenciaria_terms) && !is_wp_error($penitenciaria_terms))
        ? $penitenciaria_terms[0]->slug
        : '';

    return rest_ensure_response([
        'success' => true,
        'content' => [
            'id' => $produto->get_id(),
            'name' => $produto->get_name(),
            'price' => (float) $produto->get_price(),
            'weight' => (float) $produto->get_weight(),
            'sku' => $produto->get_sku(),
            'categoria' => $categoria_principal,
            'categoria_id' => $categoria_principal_id,
            'subcategoria' => $subcategoria,
            'penitenciaria' => $penitenciaria,
            'maxUnitsPerClient' => (int) get_post_meta($produto->get_id(), 'maxUnitsPerClient', true) ?: 1,
            'thumb' => esc_url(get_the_post_thumbnail_url($produto->get_id(), 'medium'))
        ]
    ]);
}
