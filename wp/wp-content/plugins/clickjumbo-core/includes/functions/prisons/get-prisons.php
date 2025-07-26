<?php
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/prison-list', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_prison_list_names',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('clickjumbo/v1', '/prison-list-full', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_prison_list_full',
        'permission_callback' => '__return_true',
    ]);

    register_rest_route('clickjumbo/v1', '/prison-details/(?P<slug>[a-zA-Z0-9-_]+)', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_prison_detail_by_slug',
        'permission_callback' => '__return_true',
    ]);
});

// Público: nomes/slugs
function clickjumbo_prison_list_names($request) {
    $produtos_response = clickjumbo_get_products($request);
    if (is_wp_error($produtos_response))
        return $produtos_response;

    $produtos = $produtos_response->get_data()['content'];
    $prison_mock = [];

    foreach ($produtos as $produto) {
        if (!empty($produto['penitenciarias']) && is_array($produto['penitenciarias'])) {
            foreach ($produto['penitenciarias'] as $p) {
                $slug = sanitize_title($p['slug']);
                $label = $p['label'] ?? $p['slug'];
                $prison_mock[$slug] = $label;
            }
        }
    }

    $prison_tax = [];
    $terms = get_terms([
        'taxonomy' => 'penitenciaria',
        'hide_empty' => false,
    ]);

    foreach ($terms as $term) {
        $prison_tax[$term->slug] = $term->name;
    }

    $penitenciarias = array_merge($prison_mock, $prison_tax);
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

// Protegido: com cidade/estado/cep
function clickjumbo_prison_list_full($request) {
    $resultado = [];

    $terms = get_terms([
        'taxonomy' => 'penitenciaria',
        'hide_empty' => false,
    ]);

    foreach ($terms as $term) {
        $resultado[] = [
            'id' => $term->term_id,
            'nome' => $term->name,
            'slug' => $term->slug,
            'logradouro' => get_term_meta($term->term_id, 'logradouro', true) ?: 'não cadastrado',
            'numero' => get_term_meta($term->term_id, 'numero', true) ?: 'não cadastrado',
            'bairro' => get_term_meta($term->term_id, 'bairro', true) ?: 'não cadastrado',
            'cidade' => get_term_meta($term->term_id, 'cidade', true) ?: 'não cadastrado',
            'estado' => get_term_meta($term->term_id, 'estado', true) ?: 'não cadastrado',
            'cep' => get_term_meta($term->term_id, 'cep', true) ?: 'não cadastrado',
            'complemento' => get_term_meta($term->term_id, 'complemento', true) ?: '',
            'referencia' => get_term_meta($term->term_id, 'referencia', true) ?: '',
            'criado_em' => get_term_meta($term->term_id, 'criado_em', true) ?: '',
        ];
    }

    // 🔍 Filtro por nome
$search = strtolower(sanitize_text_field($request->get_param('search') ?? ''));
    if (!empty($search)) {
        $resultado = array_filter($resultado, function ($item) use ($search) {
            return strpos(strtolower($item['nome']), $search) !== false;
        });
    }

    // 🔃 Ordenação

    $order_by = strtolower($request->get_param('order_by') ?? 'id');

    $order = strtolower($request->get_param('order') ?? 'asc') === 'desc' ? 'desc' : 'asc';

    usort($resultado, function ($a, $b) use ($order_by, $order) {
        $valA = $a[$order_by] ?? '';
        $valB = $b[$order_by] ?? '';
        return $order === 'asc' ? ($valA <=> $valB) : ($valB <=> $valA);
    });

    // 📄 Paginação
    $pagina = max(1, intval($request->get_param('page') ?? 1));
    $por_pagina = max(1, intval($request->get_param('per_page') ?? 10));
    $total_itens = count($resultado);
    $total_paginas = ceil($total_itens / $por_pagina);
    $offset = ($pagina - 1) * $por_pagina;
    $paginado = array_slice($resultado, $offset, $por_pagina);

    return rest_ensure_response([
        'status' => 200,
        'message' => 'ok',
        'content' => $paginado,
        'total_itens' => $total_itens,
        'pagina_atual' => $pagina,
        'itens_por_pagina' => $por_pagina,
        'total_paginas' => $total_paginas,
    ]);
}


// Protegido: detalhes por slug
function clickjumbo_prison_detail_by_slug($request) {
    $slug = sanitize_title($request['slug']);

    $term = get_term_by('slug', $slug, 'penitenciaria');
    if ($term && !is_wp_error($term)) {
return rest_ensure_response([
    'status' => 200,
    'message' => 'ok',
    'content' => [
        'nome' => $term->name,
        'slug' => $term->slug,
        'logradouro' => get_term_meta($term->term_id, 'logradouro', true) ?: '',
        'numero' => get_term_meta($term->term_id, 'numero', true) ?: 'S/N',
        'bairro' => get_term_meta($term->term_id, 'bairro', true) ?: '',
        'cidade' => get_term_meta($term->term_id, 'cidade', true) ?: '',
        'estado' => get_term_meta($term->term_id, 'estado', true) ?: '',
        'cep' => get_term_meta($term->term_id, 'cep', true) ?: '',

        'complemento' => get_term_meta($term->term_id, 'complemento', true) ?: '',
        'referencia' => get_term_meta($term->term_id, 'referencia', true) ?: '',
        'criado_em' => get_term_meta($term->term_id, 'criado_em', true) ?: '',
    ],
]);

    }

    $produtos_response = clickjumbo_get_products($request);
    if (is_wp_error($produtos_response)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao buscar penitenciária (mock).',
        ], 500);
    }

    $produtos = $produtos_response->get_data()['content'];

    foreach ($produtos as $produto) {
        if (!empty($produto['penitenciarias']) && is_array($produto['penitenciarias'])) {
            foreach ($produto['penitenciarias'] as $p) {
                if (sanitize_title($p['slug']) === $slug) {
                    return rest_ensure_response([
                        'status' => 200,
                        'message' => 'ok',
                        'content' => [
                            'nome' => $p['label'],
                            'slug' => $p['slug'],
                            'cidade' => 'não cadastrado',
                            'estado' => 'não cadastrado',
                            'cep' => 'não cadastrado',
                        ],
                    ]);
                }
            }
        }
    }

    return new WP_REST_Response([
        'success' => false,
        'message' => 'Penitenciária não encontrada.',
    ], 404);
}

// 🔍 Busca nome por slug
function clickjumbo_get_prison_name_by_slug($slug) {
    $slug = sanitize_title($slug);

    $term = get_term_by('slug', $slug, 'penitenciaria');
    if ($term && !is_wp_error($term)) {
        return $term->name;
    }

    $produtos_response = clickjumbo_get_products(new WP_REST_Request('GET', '', ['slug' => $slug]));
    if (!is_wp_error($produtos_response)) {
        $produtos = $produtos_response->get_data()['content'];
        foreach ($produtos as $produto) {
            if (!empty($produto['penitenciarias']) && is_array($produto['penitenciarias'])) {
                foreach ($produto['penitenciarias'] as $p) {
                    if (sanitize_title($p['slug']) === $slug) {
                        return $p['label'];
                    }
                }
            }
        }
    }

    return 'desconhecida';
}

// 🔍 Busca dados completos por slug
function clickjumbo_get_prison_data_by_slug($slug) {
    $slug = sanitize_title($slug);

    // Tenta buscar como termo cadastrado (taxonomia 'penitenciaria')
    $term = get_term_by('slug', $slug, 'penitenciaria');
    if ($term && !is_wp_error($term)) {
        return [
            'nome' => $term->name,
            'slug' => $term->slug,
            'logradouro' => get_term_meta($term->term_id, 'logradouro', true) ?: '',
            'numero' => get_term_meta($term->term_id, 'numero', true) ?: 'S/N',
            'bairro' => get_term_meta($term->term_id, 'bairro', true) ?: '',
            'cidade' => get_term_meta($term->term_id, 'cidade', true) ?: '',
            'estado' => get_term_meta($term->term_id, 'estado', true) ?: '',
            'cep' => get_term_meta($term->term_id, 'cep', true) ?: '',
            'complemento' => get_term_meta($term->term_id, 'complemento', true) ?: '',
            'referencia' => get_term_meta($term->term_id, 'referencia', true) ?: '',
            'criado_em' => get_term_meta($term->term_id, 'criado_em', true) ?: '',
        ];
    }

    // Se não achou, tenta na lista full da REST API (ajusta a URL se necessário)
    $url = home_url('/wp-json/clickjumbo/v1/prison-list-full');
    $res = wp_remote_get($url);
    if (!is_wp_error($res)) {
        $items = json_decode(wp_remote_retrieve_body($res), true)['content'] ?? [];
        foreach ($items as $p) {
            if ($p['slug'] === $slug) return $p;
        }
    }

    // Se não achou em nenhum, retorna null
    return null;
}

