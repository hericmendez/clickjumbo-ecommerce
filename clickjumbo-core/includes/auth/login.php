<?php
if (!defined('ABSPATH')) exit;

use Firebase\JWT\JWT;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/login', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_auth_login',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_auth_login(WP_REST_Request $request) {
    $creds = $request->get_json_params();

    $username = sanitize_text_field($creds['username'] ?? '');
    $password = $creds['password'] ?? '';

    if (empty($username) || empty($password)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Usuário e senha são obrigatórios.'
        ], 400);
    }

    // Permitir login por e-mail
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

    // Gera o JWT
    $secret_key = 'clickjumbo-secret-key'; // use a mesma chave do middleware

    $payload = [
        'user_id' => $user->ID,
        'email' => $user->user_email,
        'iat' => time(),
        'exp' => time() + (60 * 60 * 24) // válido por 24h
    ];

    $token = JWT::encode($payload, $secret_key, 'HS256');

    return new WP_REST_Response([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email
        ]
    ]);
}
