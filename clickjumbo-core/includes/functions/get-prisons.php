<?php
// includes/functions/prison-endpoints.php

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/prison-list', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_prison_list_names',
        'permission_callback' => '__return_true', // Público
    ]);

    register_rest_route('clickjumbo/v1', '/prison-list-full', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_prison_list_full',

    ]);

    register_rest_route('clickjumbo/v1', '/prison-details/(?P<slug>[a-zA-Z0-9-_]+)', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_prison_detail_by_slug',

    ]);
});

// ✅ Público: Apenas nomes/slugs
function clickjumbo_prison_list_names($request)
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

// 🔐 Protegido: Lista completa com cidade/estado/cep
function clickjumbo_prison_list_full($request) {
    $resultado = [];

    // 1. Taxonomia oficial
    $terms = get_terms([
        'taxonomy' => 'penitenciaria',
        'hide_empty' => false,
    ]);

    foreach ($terms as $term) {
        $slug = $term->slug;
        $resultado[$slug] = [
            'nome'   => $term->name,
            'slug'   => $slug,
            'cidade' => get_term_meta($term->term_id, 'cidade', true) ?: 'não cadastrado',
            'estado' => get_term_meta($term->term_id, 'estado', true) ?: 'não cadastrado',
            'cep'    => get_term_meta($term->term_id, 'cep', true) ?: 'não cadastrado',
        ];
    }

    // 2. Penitenciárias dos mocks
    $produtos_response = clickjumbo_listar_produtos_json($request);
    if (!is_wp_error($produtos_response)) {
        $produtos = $produtos_response->get_data()['content'];

        foreach ($produtos as $produto) {
            $nome = $produto['prison'];
            $slug = sanitize_title($nome);

            // Se ainda não estiver no array vindo da taxonomia, adiciona como mock
            if (!isset($resultado[$slug])) {
                $resultado[$slug] = [
                    'nome'   => $nome,
                    'slug'   => $slug,
                    'cidade' => 'não cadastrado',
                    'estado' => 'não cadastrado',
                    'cep'    => 'não cadastrado',
                ];
            }
        }
    }

    return rest_ensure_response([
        'status' => 200,
        'message' => 'ok',
        'content' => array_values($resultado), // reindexa o array
    ]);
}

// 🔐 Protegido: Detalhe por slug
function clickjumbo_prison_detail_by_slug($request) {
    $slug = sanitize_title($request['slug']);

    // 1. Tenta buscar na taxonomia oficial
    $term = get_term_by('slug', $slug, 'penitenciaria');

    if ($term && !is_wp_error($term)) {
        return rest_ensure_response([
            'status' => 200,
            'message' => 'ok',
            'content' => [
                'nome'   => $term->name,
                'slug'   => $term->slug,
                'cidade' => get_term_meta($term->term_id, 'cidade', true) ?: 'não cadastrado',
                'estado' => get_term_meta($term->term_id, 'estado', true) ?: 'não cadastrado',
                'cep'    => get_term_meta($term->term_id, 'cep', true) ?: 'não cadastrado',
            ],
        ]);
    }

    // 2. Tenta buscar no mock (meta['prison']) de produtos
    $produtos_response = clickjumbo_listar_produtos_json($request);
    if (is_wp_error($produtos_response)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao buscar penitenciária (mock).',
        ], 500);
    }

    $produtos = $produtos_response->get_data()['content'];

    foreach ($produtos as $produto) {
        $nome = $produto['prison'] ?? '';
        if (sanitize_title($nome) === $slug) {
            return rest_ensure_response([
                'status' => 200,
                'message' => 'ok',
                'content' => [
                    'nome'   => $nome,
                    'slug'   => $slug,
                    'cidade' => 'não cadastrado',
                    'estado' => 'não cadastrado',
                    'cep'    => 'não cadastrado',
                ],
            ]);
        }
    }

    // 3. Se não encontrar em nenhum lugar
    return new WP_REST_Response([
        'success' => false,
        'message' => 'Penitenciária não encontrada.',
    ], 404);
}
