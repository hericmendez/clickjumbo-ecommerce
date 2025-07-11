<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_render_users_panel()
{
    // Verificação de permissão
    if (!current_user_can('manage_options')) {
        echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
        return;
    }

    // Buscar e ordenar usuários por ID
    $users = get_users([
        'orderby' => 'ID',
        'order' => 'ASC',
        'number' => -1
    ]);

    echo '<div class="wrap">';
    echo '<h1 class="mb-4">Lista de Usuários</h1>';

    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover align-middle">';
    echo '<thead class="table-light">';
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
        echo '<td>' . esc_html($user->user_login) . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>'; // .table-responsive
    echo '</div>'; // .wrap
}
