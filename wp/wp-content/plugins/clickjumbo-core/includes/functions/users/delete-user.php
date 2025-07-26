<?php
if (!defined('ABSPATH')) exit;
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/user', [
        'methods' => 'DELETE',
        'callback' => 'clickjumbo_delete_user',
        'permission_callback' => '__return_true', // Segurança no corpo da função
    ]);
});

function clickjumbo_delete_user(WP_REST_Request $request) {
    $current = wp_get_current_user();
    $current_id = $current->ID;
    $current_roles = (array) $current->roles;

    $user_id = intval($request->get_param('user_id'));
    if (!$user_id) {
        return new WP_REST_Response(['success' => false, 'message' => 'user_id obrigatório.'], 400);
    }
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return new WP_REST_Response(['success' => false, 'message' => 'Usuário não encontrado.'], 404);
    }

    $is_admin = in_array('administrator', $current_roles);
    $is_self = ($current_id === $user_id);

    if (!$is_admin && !$is_self) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Você não tem permissão para deletar outros usuários.'
        ], 403);
    }

    // Protege contra exclusão do próprio admin logado (pode comentar se quiser permitir autoexclusão)
    if ($is_admin && $is_self) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Você não pode deletar seu próprio usuário administrador.'
        ], 403);
    }

    require_once(ABSPATH.'wp-admin/includes/user.php');
    $deleted = wp_delete_user($user_id);

    if (!$deleted) {
        return new WP_REST_Response(['success' => false, 'message' => 'Erro ao deletar usuário.'], 500);
    }

    return new WP_REST_Response(['success' => true, 'message' => 'Usuário deletado com sucesso.']);
}
