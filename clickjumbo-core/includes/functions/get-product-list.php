<?php

// Token de acesso mockado (use "Authorization: Bearer clickjumbo123token")
define('CLICKJUMBO_TOKEN_FIXO', 'clickjumbo123token');

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/product-list', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_listar_produtos_json',
        'permission_callback' => '__return_true',

    ]);


    register_rest_route('clickjumbo/v1', '/product-list/prison', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_filtrar_por_penitenciaria',
        'permission_callback' => '__return_true',

    ]);
});

/**
 * Retorna a lista de produtos com metadados e categoria.
 */
function clickjumbo_listar_produtos_json($request)
{
    try {
        $args = [
            'status' => 'publish',
            'limit' => 100,
        ];

        $produtos = wc_get_products($args);
        $resultado = [];

        foreach ($produtos as $produto) {
            $categoria_principal = 'Sem Categoria';
            $subcategoria = 'Sem Subcategoria';
            $categorias = $produto->get_category_ids();
            $caminhos = [];

            foreach ($categorias as $cat_id) {
                $term = get_term_by('id', $cat_id, 'product_cat');
                if (!$term)
                    continue;

                $path = [$term];
                while ($term->parent != 0) {
                    $term = get_term($term->parent, 'product_cat');
                    if (!$term)
                        break;
                    array_unshift($path, $term);
                }

                $caminhos[] = $path;
            }

            if (!empty($caminhos)) {
                $mais_profundo = $caminhos[0];
                $categoria_principal = $mais_profundo[0]->name ?? 'Sem Categoria';
                $subcategoria = end($mais_profundo)->name ?? 'Sem Subcategoria';
                if ($categoria_principal === $subcategoria) {
                    $subcategoria = 'Sem Subcategoria';
                }
            }

            $penitenciaria = get_post_meta($produto->get_id(), 'prison', true) ?: 'Penitenciária A';
            $max_units = get_post_meta($produto->get_id(), 'maxUnitsPerClient', true) ?: 1;

            $resultado[] = [
                'id' => $produto->get_id(),
                'name' => $produto->get_name(),
                'category' => $categoria_principal,
                'subcategory' => $subcategoria,
                'prison' => $penitenciaria,
                'weight' => (float) $produto->get_weight(),
                'price' => (float) $produto->get_price(),
                'maxUnitsPerClient' => (int) $max_units,
                'thumb' => 'mock/images/' . strtolower(str_replace(' ', '_', $produto->get_name())) . '.png',
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

/**
 * Retorna a lista de penitenciárias únicas com slug e label.
 */
function clickjumbo_listar_penitenciarias($request)
{
    // 1. Penitenciárias vindas do campo `meta['prison']` (mock)
    $produtos_response = clickjumbo_listar_produtos_json($request);
    if (is_wp_error($produtos_response))
        return $produtos_response;

    $produtos = $produtos_response->get_data()['content'];
    $penis_mock = [];

    foreach ($produtos as $produto) {
        $nome = $produto['prison'];
        $slug = sanitize_title($nome);
        $penis_mock[$slug] = $nome;
    }

    // 2. Penitenciárias reais da taxonomia
    $penis_tax = [];
    $terms = get_terms([
        'taxonomy' => 'penitenciaria',
        'hide_empty' => false,
    ]);

    foreach ($terms as $term) {
        $penis_tax[$term->slug] = $term->name;
    }

    // 3. Combinar e evitar duplicatas
    $penitenciarias = array_merge($penis_mock, $penis_tax);
    $penitenciarias_unicas = [];

    foreach ($penitenciarias as $slug => $nome) {
        $penitenciarias_unicas[$slug] = [
            'slug' => $slug,
            'label' => $nome,
        ];
    }

    return rest_ensure_response([
        'status' => 200,
        'message' => 'ok',
        'content' => array_values($penitenciarias_unicas),
    ]);
}


/**
 * Retorna produtos filtrados por penitenciária via ?slug=penitenciaria-a
 */
function clickjumbo_filtrar_por_penitenciaria($request)
{
    $slug_param = sanitize_title($request->get_param('slug') ?? '');

    if (!$slug_param) {
        return new WP_Error('parametro_faltando', 'Informe o parâmetro ?slug=...', ['status' => 400]);
    }

    $todos = clickjumbo_listar_produtos_json($request);
    if (is_wp_error($todos))
        return $todos;

    $produtos = $todos->get_data()['content'];

    $filtrados = array_filter($produtos, function ($produto) use ($slug_param) {
        return sanitize_title($produto['prison']) === $slug_param;
    });

    return rest_ensure_response([
        'status' => 200,
        'message' => 'ok',
        'content' => array_values($filtrados),
    ]);
}
