
<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/register', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_auth_register',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_auth_register(WP_REST_Request $request) {
    $data = $request->get_json_params();

    $username = sanitize_user($data['username'] ?? '');
    $email = sanitize_email($data['email'] ?? '');
    $password = $data['password'] ?? '';

    if (empty($username) || empty($email) || empty($password)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Usuário, e-mail e senha são obrigatórios.'
        ], 400);
    }

    if (username_exists($username) || email_exists($email)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Usuário ou e-mail já cadastrado.'
        ], 409);
    }

    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao registrar usuário.'
        ], 500);
    }

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Usuário registrado com sucesso.',
        'user_id' => $user_id
    ], 201);
}
