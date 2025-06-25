<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_render_shipments_panel() {
    if (!current_user_can('manage_options')) {
        echo '<div class="notice notice-error"><p>Você não tem permissão para acessar esta página.</p></div>';
        return;
    }

    // Salva configurações do formulário
    if (isset($_POST['save_dimensions'])) {
        update_option('cj_shipping_altura', intval($_POST['altura']));
        update_option('cj_shipping_largura', intval($_POST['largura']));
        update_option('cj_shipping_comprimento', intval($_POST['comprimento']));
        echo '<div class="updated"><p>Dimensões salvas com sucesso!</p></div>';
    }

    $altura = get_option('cj_shipping_altura', 10);
    $largura = get_option('cj_shipping_largura', 15);
    $comprimento = get_option('cj_shipping_comprimento', 25);

    echo '<div class="wrap">';
    echo '<h1>Painel de Envios</h1>';

    // Formulário de configurações
    echo '<form method="post" style="margin-bottom: 30px; display:flex;  flex-direction: column; justify-content: space-between;">';
    echo '<h2>Dimensões padrão do pacote</h2>';
    echo '<label>Altura (cm): <br/><input type="number" name="altura" value="' . esc_attr($altura) . '" /></label><br />';
    echo '<label>Largura (cm): <br/><input type="number" name="largura" value="' . esc_attr($largura) . '" /></label><br />';
    echo '<label>Comprimento (cm): <br/> <input type="number" name="comprimento" value="' . esc_attr($comprimento) . '" /></label><br />';
    echo '<input type="submit" name="save_dimensions" class="button button-primary" value="Salvar Configurações">';
    echo '</form>';

    // Listagem de pedidos com metadado de envio
    $args = [
        'post_type' => 'shop_order',
        'post_status' => array_keys(wc_get_order_statuses()),
        'meta_key' => '_cj_shipping_data',
        'posts_per_page' => -1
    ];

    $orders = get_posts($args);

    echo '<h2>Lista de Envios</h2>';
    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>ID</th><th>Penitenciária</th><th>Método</th><th>Valor</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    foreach ($orders as $order_post) {
        $order_id = $order_post->ID;
        $shipping_data = get_post_meta($order_id, '_cj_shipping_data', true);
        echo '<tr>';
        echo '<td>' . esc_html($order_id) . '</td>';
        echo '<td>' . esc_html($shipping_data['penitenciaria_slug'] ?? '-') . '</td>';
        echo '<td>' . esc_html($shipping_data['metodo'] ?? '-') . '</td>';
        echo '<td>R$ ' . number_format($shipping_data['valor'] ?? 0, 2, ',', '.') . '</td>';
        echo '<td>' . esc_html($shipping_data['status'] ?? '-') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
}

// Hook para adicionar ao menu do admin
add_action('admin_menu', function () {
    add_menu_page('Envios', 'Envios', 'manage_options', 'cj-shipments', 'clickjumbo_render_shipments_panel', 'dashicons-admin-site-alt3', 30);
});

// Função utilitária para salvar info de envio
function clickjumbo_store_shipping_meta($order_id, $frete_info, $penitenciaria_slug, $metodo = 'PAC') {
    update_post_meta($order_id, '_cj_shipping_data', [
        'valor' => $frete_info['valor'],
        'metodo' => $metodo,
        'status' => 'pendente',
        'penitenciaria_slug' => $penitenciaria_slug,
    ]);
}
