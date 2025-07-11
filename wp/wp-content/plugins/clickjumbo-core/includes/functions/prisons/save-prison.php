<?php
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/save-prison(?:/(?P<slug>[a-zA-Z0-9\-]+))?', [
        'methods' => ['POST', 'PUT'],
        'callback' => 'clickjumbo_save_prison',
'permission_callback' => function () {
    return current_user_can('manage_options');
}

    ]);
});

function clickjumbo_save_prison($request)
{
    $method = $request->get_method();
    $slug = sanitize_title($request->get_param('slug') ?? '');
    $params = $request->get_json_params();

    $campos_obrigatorios = [
        'nome' => 'Nome',
        'cidade' => 'Cidade',
        'estado' => 'Estado',
        'cep' => 'CEP',
        'logradouro' => 'Logradouro',
        'numero' => 'Número'
    ];

    $faltando = [];

    foreach ($campos_obrigatorios as $campo => $label) {
        $valor = trim($params[$campo] ?? '');
        if (empty($valor)) {
            $faltando[] = $label;
        }
    }

    if (!empty($faltando)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Os seguintes campos são obrigatórios: ' . implode(', ', $faltando),
        ], 400);
    }

    $nome = sanitize_text_field($params['nome']);
    $cidade = sanitize_text_field($params['cidade']);
    $estado = sanitize_text_field($params['estado']);
    $cep = preg_replace('/[^0-9]/', '', $params['cep']);
    $logradouro = sanitize_text_field($params['logradouro']);
    $numero = sanitize_text_field($params['numero']);
    $complemento = sanitize_text_field($params['complemento'] ?? '');
    $referencia = sanitize_text_field($params['referencia'] ?? '');

    // Se PUT (atualização)
    if ($method === 'PUT') {
        if (!$slug) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Slug não informado na URL.',
            ], 400);
        }

        $term = get_term_by('slug', $slug, 'penitenciaria');
        if (!$term) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Penitenciária não encontrada.',
            ], 404);
        }

wp_update_term($term->term_id, 'penitenciaria', ['name' => $nome]);

        update_term_meta($term->term_id, 'cidade', $cidade);
        update_term_meta($term->term_id, 'estado', $estado);
        update_term_meta($term->term_id, 'cep', $cep);
        update_term_meta($term->term_id, 'logradouro', $logradouro);
        update_term_meta($term->term_id, 'numero', $numero);
        update_term_meta($term->term_id, 'complemento', $complemento);
        update_term_meta($term->term_id, 'referencia', $referencia);
$term_id = $term->term_id;

$prison_data = [
    'nome' => $term->name,
    'slug' => $term->slug,
    'cidade' => get_term_meta($term_id, 'cidade', true),
    'estado' => get_term_meta($term_id, 'estado', true),
    'cep' => get_term_meta($term_id, 'cep', true),
    'logradouro' => get_term_meta($term_id, 'logradouro', true),
    'numero' => get_term_meta($term_id, 'numero', true),
    'complemento' => get_term_meta($term_id, 'complemento', true),
    'referencia' => get_term_meta($term_id, 'referencia', true),
    'criado_em' => get_term_meta($term_id, 'criado_em', true),
];


        return new WP_REST_Response([
            'success' => true,
            'message' => 'Penitenciária atualizada com sucesso.',
            'slug' => $slug,
            'prison' => $prison_data

        ]);
    }

    // Se POST (criação)
    if (term_exists($nome, 'penitenciaria')) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Já existe uma penitenciária com esse nome.',
        ], 409);
    }

    $result = wp_insert_term($nome, 'penitenciaria');
    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao registrar penitenciária.',
            'error' => $result->get_error_message(),
        ], 500);
    }

    $term_id = $result['term_id'];

    update_term_meta($term_id, 'cidade', $cidade);
    update_term_meta($term_id, 'estado', $estado);
    update_term_meta($term_id, 'cep', $cep);
    update_term_meta($term_id, 'logradouro', $logradouro);
    update_term_meta($term_id, 'numero', $numero);
    update_term_meta($term_id, 'complemento', $complemento);
    update_term_meta($term_id, 'referencia', $referencia);
    update_term_meta($term_id, 'criado_em', current_time('Y-m-d H:i:s'));
    $term_obj = get_term_by('slug', sanitize_title($nome), 'penitenciaria');
    $term_id = $term_obj->term_id;

    $prison_data = [
        'nome' => $term_obj->name,
        'slug' => $term_obj->slug,
        'cidade' => get_term_meta($term_id, 'cidade', true),
        'estado' => get_term_meta($term_id, 'estado', true),
        'cep' => get_term_meta($term_id, 'cep', true),
        'logradouro' => get_term_meta($term_id, 'logradouro', true),
        'numero' => get_term_meta($term_id, 'numero', true),
        'complemento' => get_term_meta($term_id, 'complemento', true),
        'referencia' => get_term_meta($term_id, 'referencia', true),
        'criado_em' => get_term_meta($term_id, 'criado_em', true),
        ];

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Penitenciária registrada com sucesso.',
        'slug' => get_term($term_id)->slug,
        'prison' => $prison_data
    ]);
}
