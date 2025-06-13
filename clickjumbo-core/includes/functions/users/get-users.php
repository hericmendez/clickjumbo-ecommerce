<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_get_users($request)
{
    $ids_param = $request->get_param('ids');

    // Caso tenha passado ?ids=1,2,3
    if ($ids_param) {
        $ids = explode(',', $ids_param);
        $ids = array_map('intval', $ids); // Sanitiza os valores

        $users = array_map('get_userdata', $ids);
    } else {
        // Caso queira todos os usuários
        $users = get_users(); // WP_User[]
    }

    // Transforma usuários em array limpo
    $result = [];

    foreach ($users as $user) {
        if (!$user || !($user instanceof WP_User)) continue;

        $result[] = [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'username' => $user->user_login,
        ];
    }

    return rest_ensure_response($result);
}
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/users', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_get_users',
        'permission_callback' => '__return_true',
    ]);
});
