<?php
if (!defined('ABSPATH')) exit;

// Callback para a página principal (opcional)
function clickjumbo_render_dashboard_placeholder() {
    echo '<h1>Bem-vindo ao painel ClickJumbo</h1><p>Selecione uma opção no menu lateral.</p>';
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
        'Penitenciárias',
        'Penitenciárias',
        'manage_options',
        'clickjumbo-prisons',
        'clickjumbo_render_prison_panel'
    );
    // Submenu: Novo Produto (página oculta)
    add_submenu_page(
        null, // Página pai como null faz com que ela não apareça no menu
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
    add_submenu_page(
        'clickjumbo-dashboard',
        'Produtos',
        'Produtos',
        'manage_options',
        'clickjumbo-products',
        'clickjumbo_render_products_panel'
    );

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

});
