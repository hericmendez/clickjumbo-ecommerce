<?php
if (!defined('ABSPATH')) exit;  


add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/user/change-role', [
        'methods' => 'PUT',
        'callback' => 'clickjumbo_change_user_role',
        'permission_callback' => '__return_true'
    ]);
});

function clickjumbo_change_user_role(WP_REST_Request $request) {
    $user_id = intval($request->get_param('user_id'));
    $role = sanitize_text_field($request->get_param('role'));

    if (!$user_id || !$role) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'user_id e role são obrigatórios.'
        ], 400);
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Usuário não encontrado.'
        ], 404);
    }

    // Protege: não deixa admin perder seu próprio admin
    $current_user = wp_get_current_user();
    if ($user_id == $current_user->ID && $role !== 'administrator') {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Você não pode remover seu próprio acesso de administrador.'
        ], 403);
    }

    // Verifica se a role existe
    global $wp_roles;
    if (!isset($wp_roles->roles[$role])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Role inválida.'
        ], 400);
    }

    $user->set_role($role);

    return new WP_REST_Response([
        'success' => true,
        'message' => "Role do usuário atualizada para $role."
    ]);
}
