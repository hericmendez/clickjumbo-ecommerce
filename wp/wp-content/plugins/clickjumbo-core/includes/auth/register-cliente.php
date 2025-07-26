
<?php
if (!defined('ABSPATH')) exit;  

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/cliente/register', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_cliente_register',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_cliente_register(WP_REST_Request $request) {
    $data = $request->get_json_params();

    $username = sanitize_user($data['username'] ?? '');
    $email = sanitize_email($data['email'] ?? '');
    $password = $data['password'] ?? '';

    // Dados extras (opcionais, podem ser preenchidos depois)
    $detento = $data['detento'] ?? [];
    $endereco_usuario = $data['endereco_usuario'] ?? [];
    $endereco_visitante = $data['endereco_visitante'] ?? [];

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

    // Cria o usuário com role 'cliente'
    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao registrar usuário.'
        ], 500);
    }
    wp_update_user(['ID' => $user_id, 'role' => 'cliente']);

    // Salva campos extras
    update_user_meta($user_id, '_cj_detento', $detento);
    update_user_meta($user_id, '_cj_endereco_usuario', $endereco_usuario);
    update_user_meta($user_id, '_cj_endereco_visitante', $endereco_visitante);

    return new WP_REST_Response([
        'success' => true,
        'message' => 'Cliente registrado com sucesso.',
        'user_id' => $user_id
    ], 201);
}
