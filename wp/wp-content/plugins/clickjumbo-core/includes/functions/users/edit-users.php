<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/user', [
        'methods' => 'PUT',
        'callback' => 'clickjumbo_update_user',
        'permission_callback' => '__return_true', // Segurança no corpo da função
    ]);
});

function clickjumbo_update_user(WP_REST_Request $request) {
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

    // REGRAS DE SEGURANÇA
    $is_admin = in_array('administrator', $current_roles);
    $is_self = ($current_id === $user_id);

    if (!$is_admin && !$is_self) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Você não tem permissão para editar outros usuários.'
        ], 403);
    }

    // Restrição: subscriber/cliente só pode editar campos próprios (NÃO username, role, email)
    $update_data = ['ID' => $user_id];
    if ($is_admin) {
        if ($name = $request->get_param('name')) {
            $update_data['display_name'] = sanitize_text_field($name);
        }
        if ($email = $request->get_param('email')) {
            $update_data['user_email'] = sanitize_email($email);
        }
        if ($username = $request->get_param('username')) {
            $update_data['user_login'] = sanitize_user($username);
        }
        if ($role = $request->get_param('role')) {
            $update_data['role'] = sanitize_text_field($role);
        }
    } else { // subscriber/cliente
        if ($name = $request->get_param('name')) {
            $update_data['display_name'] = sanitize_text_field($name);
        }
    }

    $result = wp_update_user($update_data);
    if (is_wp_error($result)) {
        return new WP_REST_Response(['success' => false, 'message' => 'Erro ao atualizar usuário.'], 500);
    }

    // Atualiza metas extras (qualquer user pode atualizar os próprios)
    if ($detento = $request->get_param('detento')) {
        update_user_meta($user_id, '_cj_detento', $detento);
    }
    if ($endereco_usuario = $request->get_param('endereco_usuario')) {
        update_user_meta($user_id, '_cj_endereco_usuario', $endereco_usuario);
    }
    if ($endereco_visitante = $request->get_param('endereco_visitante')) {
        update_user_meta($user_id, '_cj_endereco_visitante', $endereco_visitante);
    }

    return new WP_REST_Response(['success' => true, 'message' => 'Usuário atualizado com sucesso.']);
}
