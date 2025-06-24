<?php

// Token de acesso mockado (use "Authorization: Bearer clickjumbo123token")
define('CLICKJUMBO_TOKEN_FIXO', 'clickjumbo123token');

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/product-list', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_listar_produtos_json',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('clickjumbo/v1', '/product-list/penitenciaria', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_filtrar_por_penitenciaria',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('clickjumbo/v1', '/product-details/(?P<id>\\d+)', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_product_details',
        'permission_callback' => '__return_true'
    ]);
});

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

            // Recupera todos os termos da taxonomia 'product_cat'
            $terms = get_the_terms($produto->get_id(), 'product_cat');

            if (!empty($terms) && !is_wp_error($terms)) {
                // Percorre os termos e encontra o par categoria + subcategoria com base na hierarquia
foreach ($terms as $term) {
    if ($term->parent == 0) {
        // Se ainda não definimos a principal, define
        if ($categoria_principal === 'Sem Categoria') {
            $categoria_principal = $term->name;
        }
    } else {
        $parent_term = get_term($term->parent, 'product_cat');
        if ($parent_term && !is_wp_error($parent_term)) {
            // Define apenas se for coerente
            $categoria_principal = $parent_term->name;
            $subcategoria = $term->name;
            break; // encontrou um par válido, pode parar
        }
    }
}


            }

            // Penitenciária
            $penitenciaria_terms = get_the_terms($produto->get_id(), 'penitenciaria');
            $penitenciaria = (!empty($penitenciaria_terms) && !is_wp_error($penitenciaria_terms))
                ? $penitenciaria_terms[0]->name
                : 'Sem Penitenciária';

            // Metadados adicionais
            $max_units = get_post_meta($produto->get_id(), 'maxUnitsPerClient', true) ?: 1;
            $thumb = get_post_meta($produto->get_id(), 'thumb', true);

            $resultado[] = [
                'id' => $produto->get_id(),
                'name' => $produto->get_name(),
                'category' => $categoria_principal,
                'subcategory' => $subcategoria,
                'penitenciaria' => $penitenciaria,
                'weight' => (float) $produto->get_weight(),
                'price' => (float) $produto->get_price(),
                'maxUnitsPerClient' => (int) $max_units,
                'thumb' => esc_url($thumb),
                //'raw_data' => $produto->get_data(), 

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
        return sanitize_title($produto['penitenciaria']) === $slug_param;
    });

    return rest_ensure_response([
        'status' => 200,
        'message' => 'ok',
        'content' => array_values($filtrados),
    ]);
}
