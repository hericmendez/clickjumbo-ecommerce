<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_render_users_panel()
{
    // Verificação de permissão
    if (!current_user_can('manage_options')) {
        echo '<div class="notice notice-error"><p>Você não tem permissão para acessar esta página.</p></div>';
        return;
    }

    // Buscar e ordenar usuários por ID
    $users = get_users([
        'orderby' => 'ID',
        'order' => 'ASC',
        'number' => -1
    ]);

    echo '<div class="wrap">';
    echo '<h1>Lista de Usuários</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Nome</th>';
    echo '<th>Email</th>';
    echo '<th>Username</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($users as $user) {
        echo '<tr>';
        echo '<td>' . esc_html($user->ID) . '</td>';
        echo '<td>' . esc_html($user->display_name ?: $user->user_login) . '</td>';
        echo '<td>' . esc_html($user->user_email) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}
