<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_render_users_panel()
{
    /*
    
    if (!current_user_can('manage_options')) {
        echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
        return;
    }
   */
    // Filtros recebidos via GET
    $busca = isset($_GET['busca']) ? sanitize_text_field($_GET['busca']) : '';
    $role = isset($_GET['role']) ? sanitize_text_field($_GET['role']) : '';
    $pagina = isset($_GET['pagina']) ? max(1, intval($_GET['pagina'])) : 1;
    $por_pagina = 10;

    // Argumentos para buscar usuários
    $args = [
        'orderby' => 'ID',
        'order' => 'ASC',
        'number' => $por_pagina,
        'paged' => $pagina,
    ];

    // Filtro por role
    if ($role && $role !== 'all') {
        $args['role'] = $role;
    }

    // Filtro por nome (display_name ou user_login ou email)
    if ($busca) {
        $args['search'] = "*{$busca}*";
        $args['search_columns'] = ['user_login', 'user_email', 'display_name'];
    }

    // Busca total para paginação
    $total_args = $args;
    unset($total_args['number'], $total_args['paged']);
    $total_users = count(get_users($total_args));
    $total_paginas = ceil($total_users / $por_pagina);

    $users = get_users($args);

    // Pega todas as roles cadastradas no WP para o filtro
    global $wp_roles;
    $roles = $wp_roles->get_names();
    
        echo '<h1 class="mb-4">Lista de Usuários</h1>';
    
    // Form de filtro
    echo '<div class="wrap">';

    echo '<form method="get" class="mb-4 row g-3">';
    echo '<input type="hidden" name="page" value="clickjumbo-users" />';

    // Busca por nome
    echo '<div class="col-auto">';
    echo '<input type="text" class="form-control" name="busca" placeholder="Buscar por nome, email ou username" value="' . esc_attr($busca) . '" />';
    echo '</div>';

    // Filtro por role
    echo '<div class="col-auto">';
    echo '<select name="role" class="form-select">';
    echo '<option value="all">Todas as Funções</option>';
    foreach ($roles as $key => $role_name) {
        echo '<option value="' . esc_attr($key) . '" ' . selected($role, $key, false) . '>' . esc_html($role_name) . '</option>';
    }
    echo '</select>';
    echo '</div>';

    // Botão de busca
    echo '<div class="col-auto">';
    echo '<button type="submit" class="btn btn-primary">Filtrar</button>';
    echo '</div>';
    echo '</form>';
   
    
    // Tabela
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover align-middle">';
    echo '<thead class="table-light">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Nome</th>';
    echo '<th>Email</th>';

    echo '<th>Função</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';

    foreach ($users as $user) {
        $role_user = isset($user->roles[0]) ? $roles[$user->roles[0]] : '<span class="text-muted">Sem função</span>';
        echo '<tr>';
        echo '<td>' . esc_html($user->ID) . '</td>';
        echo '<td>' . esc_html($user->display_name ?: $user->user_login) . '</td>';
        echo '<td>' . esc_html($user->user_email) . '</td>';

        echo '<td>' . $role_user . '</td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';
    echo '</div>'; // .table-responsive

    // Paginação
    if ($total_paginas > 1) {
        echo '<nav><ul class="pagination mt-4">';
        for ($i = 1; $i <= $total_paginas; $i++) {
            $query = $_GET;
            $query['pagina'] = $i;
            $link = admin_url('admin.php?' . http_build_query($query));
            $active = $i === $pagina ? ' active' : '';
            echo '<li class="page-item' . $active . '"><a class="page-link" href="' . esc_url($link) . '">' . $i . '</a></li>';
        }
        echo '</ul></nav>';
    }

    echo '</div>'; // .wrap
}
