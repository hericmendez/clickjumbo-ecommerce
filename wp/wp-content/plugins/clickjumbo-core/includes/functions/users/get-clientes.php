<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/clientes', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_clientes',
        'permission_callback' => '__return_true', // Use lógica de permissão conforme necessidade
    ]);
});


add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/cliente(?:/[a-zA-Z0-9\-]+)?$#', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_clientes',
        'permission_callback' => '__return_true', // Use lógica de permissão conforme necessidade
    ]);
});


function clickjumbo_get_clientes(WP_REST_Request $request) {
    $cliente_id = intval($request->get_param('cliente_id'));

    // Monta filtro para buscar clientes
    $args = [
        'role' => 'client',
        'fields' => 'all'
    ];

    if ($cliente_id) {
        $args['include'] = [$cliente_id];
    }

    $clientes = get_users($args);

    $result = [];
    foreach ($clientes as $user) {
        $detento = get_user_meta($user->ID, '_cj_detento', true) ?: new stdClass();
        $endereco_usuario = get_user_meta($user->ID, '_cj_endereco_usuario', true) ?: new stdClass();
        $endereco_visitante = get_user_meta($user->ID, '_cj_endereco_visitante', true) ?: new stdClass();
        $role = isset($user->roles[0]) ? $user->roles[0] : null;

            $historico_compras = clickjumbo_get_historico_compras_by_user($cliente_id);


        $result[] = [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'username' => $user->user_login,
            'role' => $role, 
            'dados' => [
                'detento' => $detento,
                'endereco_usuario' => $endereco_usuario,
                'endereco_visitante' => $endereco_visitante,
                'historico_compras' => $historico_compras
            ]
        ];
    }

    // Se buscou por um cliente específico e não achou, retorna 404
    if ($cliente_id && empty($result)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Cliente não encontrado.'
        ], 404);
    }

    return rest_ensure_response($cliente_id ? $result[0] : $result);
}


function clickjumbo_get_cliente_data(WP_REST_Request $request) {
    $cliente_id = get_current_user_id();
    if (!$cliente_id) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Não autenticado.'
        ], 401);
    }
    $user = get_userdata($cliente_id);

    // Dados extras...
    $detento = get_user_meta($cliente_id, '_cj_detento', true) ?: new stdClass();
    $endereco_usuario = get_user_meta($cliente_id, '_cj_endereco_usuario', true) ?: new stdClass();
    $endereco_visitante = get_user_meta($cliente_id, '_cj_endereco_visitante', true) ?: new stdClass();

    // Histórico de compras (integra aqui)
    $historico_compras = cj_get_historico_compras_by_user($cliente_id);

    $response = [
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'username' => $user->user_login,
        'dados' => [
            'detento' => $detento,
            'endereco_usuario' => $endereco_usuario,
            'endereco_visitante' => $endereco_visitante,
            'historico_compras' => $historico_compras,
        ]
    ];

    return rest_ensure_response($response);
}
