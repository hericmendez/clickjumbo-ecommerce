<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_get_users($request)
{
    // Parâmetros
    $ids_param = $request->get_param('ids');
    $name = $request->get_param('name'); // Busca por nome
    $role = $request->get_param('role'); // Filtro por role (ex: 'cliente', 'administrator', etc)
    $page = max(1, intval($request->get_param('page') ?: 1));
    $per_page = min(50, max(1, intval($request->get_param('per_page') ?: 10))); // máximo 50 por página

    // Caso tenha passado ?ids=1,2,3
    if ($ids_param) {
        $ids = explode(',', $ids_param);
        $ids = array_map('intval', $ids); // Sanitiza os valores
        $users = array_map('get_userdata', $ids);
    } else {
        // Monta os argumentos para get_users
        $args = [
            'number' => $per_page,
            'paged' => $page,
        ];

        if ($role) {
            $args['role'] = $role;
        }
        if ($name) {
            // Busca por display_name ou user_login
            $args['search'] = '*' . esc_attr($name) . '*';
            $args['search_columns'] = ['user_login', 'display_name'];
        }

        $users = get_users($args);
    }

    // Transforma usuários em array limpo
    $result = [];

    foreach ($users as $user) {
        if (!$user || !($user instanceof WP_User)) continue;

        // Pega a primeira role do usuário, se existir
        $role = isset($user->roles[0]) ? $user->roles[0] : null;

        $result[] = [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'username' => $user->user_login,
            'role' => $role
        ];
    }

    // Conta total (para frontend mostrar paginação)
    $total_args = [
        'count_total' => true,
    ];
    if ($role) $total_args['role'] = $role;
    if ($name) {
        $total_args['search'] = '*' . esc_attr($name) . '*';
        $total_args['search_columns'] = ['user_login', 'display_name'];
    }
    $total_users = count_users();
    $total = $total_users['total_users'] ?? count($result);

    return rest_ensure_response([
        'users' => $result,
        'page' => $page,
        'per_page' => $per_page,
        'total' => $total,
        'found' => count($result)
    ]);
}

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/users', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_users',
        'permission_callback' => '__return_true', // Troque por autenticação se quiser
    ]);
});
