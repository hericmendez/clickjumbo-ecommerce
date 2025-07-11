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
        $filtro_nome = sanitize_text_field($request->get_param('nome') ?? '');
        $filtro_categoria = sanitize_text_field($request->get_param('categoria') ?? '');
        $filtro_penitenciaria = sanitize_text_field($request->get_param('penitenciaria') ?? '');

        $ordenar_por = sanitize_text_field($request->get_param('ordenar_por')) ?: 'id';
        $direcao = strtoupper(sanitize_text_field($request->get_param('direcao')) ?: 'DESC');

        $page = max(1, intval($request->get_param('pagina') ?? 1));
        $per_page = max(1, min(100, intval($request->get_param('limite') ?? 10)));

        $orderby_map = [
            'id' => 'ID',
            'nome' => 'title',
            'preco' => 'price',
            'data' => 'date'
        ];

        $args = [
            'post_status' => 'publish',
            'post_type' => 'product',
            'orderby' => $orderby_map[$ordenar_por] ?? 'ID',
            'order' => in_array($direcao, ['ASC', 'DESC']) ? $direcao : 'DESC',
            'posts_per_page' => $per_page,
            'paged' => $page,
        ];

        $query = new WP_Query($args);
        $produtos = array_filter(array_map('wc_get_product', wp_list_pluck($query->posts, 'ID')));
        $resultado = [];
        $ids_incluidos = [];

        foreach ($produtos as $produto) {
            $produto_id = $produto->get_id();
            if (isset($ids_incluidos[$produto_id])) continue;

            $nome = $produto->get_name();
            if ($filtro_nome && stripos($nome, $filtro_nome) === false) continue;

            $is_padrao = get_post_meta($produto_id, '_cj_is_padrao', true) === 'yes';
            $is_premium = get_post_meta($produto_id, '_cj_is_premium', true) === 'yes';

            $penitenciarias = [];
            $penit_terms = get_the_terms($produto_id, 'penitenciaria');
            if (!empty($penit_terms) && !is_wp_error($penit_terms)) {
                foreach ($penit_terms as $term) {
                    $penitenciarias[] = [
                        'slug' => $term->slug,
                        'label' => $term->name
                    ];
                }
            }

            if ($is_padrao || empty($penitenciarias)) {
                $penitenciarias = [[ 'slug' => 'todas', 'label' => 'Todas' ]];
            }

            $slugs = wp_list_pluck($penitenciarias, 'slug');
            if ($slug_param && !$is_padrao && !in_array($slug_param, $slugs)) continue;
            if ($filtro_penitenciaria && !$is_padrao && !in_array($filtro_penitenciaria, $slugs)) continue;

            $categoria_principal = 'Sem Categoria';
            $subcategoria = 'Sem Subcategoria';
            $terms = get_the_terms($produto_id, 'product_cat');
            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    if ($term->parent == 0 && $categoria_principal === 'Sem Categoria') {
                        $categoria_principal = $term->name;
                    } elseif ($term->parent != 0) {
                        $parent = get_term($term->parent, 'product_cat');
                        if ($parent && !is_wp_error($parent)) {
                            $categoria_principal = $parent->name;
                            $subcategoria = $term->name;
                            break;
                        }
                    }
                }
            }

            if ($filtro_categoria && stripos($categoria_principal, $filtro_categoria) === false) continue;

            $criado_em_raw = get_post_meta($produto_id, '_cj_criado_em', true);
            $criado_em = $criado_em_raw ? date(DATE_ISO8601, strtotime($criado_em_raw)) : null;

            $resultado[] = [
                'id' => $produto_id,
                'nome' => $nome,
                'categoria' => $categoria_principal,
                'subcategoria' => $subcategoria,
                'penitenciarias' => $penitenciarias,
                'padrao' => $is_padrao,
                'premium' => $is_premium,
                'peso' => (float) $produto->get_weight(),
                'preco' => (float) $produto->get_price(),
                'maximo_por_cliente' => (int) (get_post_meta($produto_id, 'maximo_por_cliente', true) ?: 1),
                'thumb' => esc_url(get_the_post_thumbnail_url($produto_id, 'medium')),
                'criado_em' => $criado_em,
            ];

            $ids_incluidos[$produto_id] = true;
        }

        return rest_ensure_response([
            'status' => 200,
            'message' => 'ok',
            'total' => $query->found_posts,
            'pagina' => $page,
            'por_pagina' => $per_page,
            'total_paginas' => ceil($query->found_posts / $per_page),
            'content' => $resultado,
        ]);
    } catch (Exception $e) {
        return new WP_Error('erro_interno', 'Erro ao obter produtos: ' . $e->getMessage(), ['status' => 500]);
    }
}




function clickjumbo_get_product_by_id($id)
{
  $produto = wc_get_product($id);

  if (!$produto || $produto->get_type() !== 'simple') {
    return null;
  }

  $categoria_principal = 'Sem Categoria';
  $subcategoria = '';

  $terms = get_the_terms($id, 'product_cat');
  if (!empty($terms) && !is_wp_error($terms)) {
    foreach ($terms as $term) {
      if ($term->parent === 0 && $categoria_principal === 'Sem Categoria') {
        $categoria_principal = $term->name;
      } elseif ($term->parent !== 0) {
        $parent = get_term($term->parent, 'product_cat');
        if ($parent && !is_wp_error($parent)) {
          $categoria_principal = $parent->name;
          $subcategoria = $term->name;
          break;
        }
      }
    }
  }

  $penitenciaria_terms = get_the_terms($id, 'penitenciaria');
  $penitenciaria = (!empty($penitenciaria_terms) && !is_wp_error($penitenciaria_terms))
    ? $penitenciaria_terms[0]->slug
    : '';

  return [
    'id' => $produto->get_id(),
    'nome' => $produto->get_name(),
    'preco' => (float) $produto->get_price(),
    'peso' => (float) $produto->get_weight(),
    'sku' => $produto->get_sku(),
    'categoria' => $categoria_principal,
    'subcategoria' => $subcategoria,
    'penitenciaria' => $penitenciaria,
    'maximo_por_cliente' => (int) get_post_meta($id, 'maximo_por_cliente', true) ?: 1,
    'thumb' => esc_url(get_the_post_thumbnail_url($id, 'medium')),
    'criado_em' => get_post_meta($produto->get_id(), '_cj_criado_em', true),

  ];
}


function clickjumbo_get_product_details($request)
{
    $id = intval($request['id']);
    $produto = wc_get_product($id);

    if (!$produto || $produto->get_type() !== 'simple') {
        return new WP_Error('not_found', 'Produto não encontrado ou inválido.', ['status' => 404]);
    }

    $categoria_principal = 'Sem Categoria';
    $subcategoria = '';

    $terms = get_the_terms($produto->get_id(), 'product_cat');
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term) {
            if ($term->parent === 0 && $categoria_principal === 'Sem Categoria') {
                $categoria_principal = $term->name;
            } elseif ($term->parent !== 0) {
                $parent = get_term($term->parent, 'product_cat');
                if ($parent && !is_wp_error($parent)) {
                    $categoria_principal = $parent->name;
                    $subcategoria = $term->name;
                    break;
                }
            }
        }
    }

    $max_units = get_post_meta($produto->get_id(), 'maximo_por_cliente', true) ?: 1;
    $is_premium = get_post_meta($produto->get_id(), '_cj_is_premium', true) === 'yes';
    $is_padrao = get_post_meta($produto->get_id(), '_cj_is_padrao', true) === 'yes';

    // Penitenciárias
    $penitenciarias = [];
    $penit_terms = get_the_terms($produto->get_id(), 'penitenciaria');
    if (!empty($penit_terms) && !is_wp_error($penit_terms)) {
        foreach ($penit_terms as $term) {
            $penitenciarias[] = [
                'slug' => $term->slug,
                'label' => $term->name
            ];
        }
    }

    if ($is_padrao || empty($penitenciarias)) {
        $penitenciarias = [[ 'slug' => 'todas', 'label' => 'Todas' ]];
    }

    return rest_ensure_response([
        'success' => true,
        'content' => [
            'id' => $produto->get_id(),
            'nome' => $produto->get_name(),
            'categoria' => $categoria_principal,
            'subcategoria' => $subcategoria,
            'penitenciarias' => $penitenciarias,
            'peso' => (float) $produto->get_weight(),
            'preco' => (float) $produto->get_price(),
            'sku' => $produto->get_sku(),
            'maximo_por_cliente' => (int) $max_units,
            'premium' => $is_premium,
            'padrao' => $is_padrao,
            'thumb' => esc_url(get_the_post_thumbnail_url($produto->get_id(), 'medium')),
            'criado_em' => get_post_meta($produto->get_id(), '_cj_criado_em', true),

        ]
    ]);
}


