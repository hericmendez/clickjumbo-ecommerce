<?php
if (!defined('ABSPATH')) exit;

use Firebase\JWT\JWT;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/cliente/login', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_cliente_login',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_cliente_login(WP_REST_Request $request) {
    $creds = $request->get_json_params();

    $username = sanitize_text_field($creds['username'] ?? '');
    $password = $creds['password'] ?? '';

    if (empty($username) || empty($password)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Usuário e senha são obrigatórios.'
        ], 400);
    }

    // Permitir login por e-mail também
    if (is_email($username)) {
        $user = get_user_by('email', $username);
        if (!$user) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'E-mail não encontrado.'
            ], 404);
        }
        $username = $user->user_login;
    }

    $user = wp_authenticate($username, $password);

    if (is_wp_error($user)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Credenciais inválidas.'
        ], 403);
    }

    // Só permite login de cliente
    if (!in_array('cliente', $user->roles)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Acesso restrito a clientes.'
        ], 403);
    }

    // Gera o JWT
    $secret_key = 'clickjumbo-secret-key';

    $payload = [
        'user_id' => $user->ID,
        'email' => $user->user_email,
        'iat' => time(),
        'exp' => time() + (60 * 60 * 24) // válido por 24h
    ];

    $token = JWT::encode($payload, $secret_key, 'HS256');

    // Busca campos extras já no login
    $detento = get_user_meta($user->ID, '_cj_detento', true) ?: new stdClass();
    $endereco_usuario = get_user_meta($user->ID, '_cj_endereco_usuario', true) ?: new stdClass();
    $endereco_visitante = get_user_meta($user->ID, '_cj_endereco_visitante', true) ?: new stdClass();

    // Mock para histórico (depois implementar o endpoint real)
    $historico_compras = [];

    return new WP_REST_Response([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'username' => $user->user_login,
            'dados' => [
                'detento' => $detento,
                'endereco_usuario' => $endereco_usuario,
                'endereco_visitante' => $endereco_visitante,
                'historico_compras' => $historico_compras
            ]
        ]
    ]);
}
