<?php
function clickjumbo_render_dashboard_placeholder()
{
    if (!current_user_can('manage_options')) {
        echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
        return;
    }

    $user = wp_get_current_user();
    $username = esc_html($user->display_name ?: $user->user_login);

    echo '<div class="wrap">';
    echo '<div class="card shadow-sm p-5" style="max-width:800px; margin:auto; margin-top:40px;">';
    echo '<h1 class="mb-3">Bem-vindo, ' . $username . ' 👋</h1>';
    echo '<p class="lead">Este é o painel administrativo do <strong>ClickJumbo</strong>.</p>';
    echo '<p class="text-muted">Use o menu lateral para gerenciar pedidos, produtos, penitenciárias e mais.</p>';
    echo '<hr>';
    echo '<p class="text-secondary">Dica: Você pode começar configurando os produtos padrão ou cadastrando penitenciárias.</p>';
    echo '</div>';
    echo '</div>';
}


// Menu principal + submenus
add_action('admin_menu', function () {
    // Menu principal "ClickJumbo"
    add_menu_page(
        'ClickJumbo',
        'ClickJumbo',
        'manage_options',
        'clickjumbo-dashboard',
        'clickjumbo_render_dashboard_placeholder',
        'dashicons-store', // ícone principal
        20
    );

    // Submenu: Penitenciárias
    add_submenu_page(
        'clickjumbo-dashboard',
        'Produtos',
        'Produtos',
        'manage_options',
        'clickjumbo-products',
        'clickjumbo_render_products_panel'
    );
    // Submenu: Novo Produto (página oculta)
    add_submenu_page(
       'clickjumbo-dashboard', // Página pai como null faz com que ela não apareça no menu
        'Novo Produto',
        'Novo Produto',
        'manage_options',
        'clickjumbo-novo-produto',
        'clickjumbo_render_novo_produto_form'
    );
    add_submenu_page(
        null,
        'Importar CSV',
        'Importar CSV',
        'manage_options',
        'clickjumbo-import-csv',
        'clickjumbo_render_import_csv_form'
    );
    // Submenu oculto para exportação de produtos (acesso via URL direta)
    add_submenu_page(
        null, // não aparece no menu
        'Exportar Produtos',
        'Exportar Produtos',
        'manage_options',
        'clickjumbo-export-csv',
        'clickjumbo_export_products_csv'
    );

    // Submenu: Produtos
/*     add_submenu_page(
        'clickjumbo-dashboard',
        'Produtos',
        'Produtos',
        'manage_options',
        'clickjumbo-products',
        'clickjumbo_render_products_panel'
    ); */

    // Submenu: Pedidos
    add_submenu_page(
        'clickjumbo-dashboard',
        'Pedidos',
        'Pedidos',
        'manage_options',
        'clickjumbo-orders',
        'clickjumbo_render_orders_panel'
    );

    add_submenu_page(
        'clickjumbo-dashboard',
        'Usuários',
        'Usuários',
        'manage_options',
        'clickjumbo-users',
        'clickjumbo_render_users_panel'
    );

    add_submenu_page(
        'clickjumbo-dashboard',
        'Envios',
        'Envios',
        'manage_options',
        'clickjumbo-shipping',
        'clickjumbo_render_shipments_panel',
    );
        add_submenu_page(
        null,
        'Frete - Melhor Envio',
        'Frete - Melhor Envio',
        'manage_options',
        'clickjumbo-shipping',
        'clickjumbo_render_melhor_envio_settings',
    );
});
