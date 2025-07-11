<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_render_shipments_panel() {
    if (!current_user_can('manage_options')) {
        echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
        return;
    }

    // Salva configurações do formulário de dimensões
    if (isset($_POST['save_dimensions'])) {
        update_option('cj_shipping_altura', intval($_POST['altura']));
        update_option('cj_shipping_largura', intval($_POST['largura']));
        update_option('cj_shipping_comprimento', intval($_POST['comprimento']));
        echo '<div class="alert alert-success">Dimensões salvas com sucesso!</div>';
    }

    $altura = get_option('cj_shipping_altura', 10);
    $largura = get_option('cj_shipping_largura', 15);
    $comprimento = get_option('cj_shipping_comprimento', 25);

    echo '<div class="wrap">';
    echo '<h1 class="mb-4">Painel de Envios</h1>';

    // Formulário de configurações
    echo '<form method="post" class="card p-4 mb-4 shadow-sm" style="max-width:600px;">';
    echo '<h5 class="mb-3">Dimensões padrão do pacote</h5>';
    echo '<div class="mb-3"><label class="form-label">Altura (cm)</label><input type="number" name="altura" class="form-control" value="' . esc_attr($altura) . '" /></div>';
    echo '<div class="mb-3"><label class="form-label">Largura (cm)</label><input type="number" name="largura" class="form-control" value="' . esc_attr($largura) . '" /></div>';
    echo '<div class="mb-3"><label class="form-label">Comprimento (cm)</label><input type="number" name="comprimento" class="form-control" value="' . esc_attr($comprimento) . '" /></div>';
    echo '<button type="submit" name="save_dimensions" class="btn btn-primary">Salvar Configurações</button>';
    echo '</form>';

    // Listagem de pedidos com dados de envio (a ser implementada depois, se necessário)

    echo '</div>';
}

function clickjumbo_render_melhor_envio_settings() {
    if (!current_user_can('manage_options')) {
        echo '<div class="alert alert-danger">Acesso negado.</div>';
        return;
    }

    if (isset($_POST['melhor_envio_token'])) {
        check_admin_referer('salvar_token_melhor_envio');
        $token = sanitize_text_field($_POST['melhor_envio_token']);
        update_option('melhor_envio_token', $token);
        echo '<div class="alert alert-success">Token atualizado com sucesso.</div>';
    }

    $token = get_option('melhor_envio_token', '');

    echo '<div class="wrap">';
    echo '<h1 class="mb-4">Configurações - Melhor Envio</h1>';
    echo '<form method="post" class="card p-4 shadow-sm" style="max-width:600px;">';
    wp_nonce_field('salvar_token_melhor_envio');
    echo '<div class="mb-3">';
    echo '<label for="melhor_envio_token" class="form-label">Token de Acesso (Bearer)</label>';
    echo '<input type="text" id="melhor_envio_token" name="melhor_envio_token" class="form-control" value="' . esc_attr($token) . '" />';
    echo '<div class="form-text">Cole aqui o token gerado em <a href="https://www.melhorenvio.com.br/" target="_blank">melhorenvio.com.br</a>.</div>';
    echo '</div>';
    echo '<button type="submit" class="btn btn-success">Salvar Token</button>';
    echo '</form>';
    echo '</div>';
}

// Hook do menu (caso queira separar em submenus futuramente)
add_action('admin_menu', function () {
    add_menu_page('Envios', 'Envios', 'manage_options', 'cj-shipments', 'clickjumbo_render_shipments_panel', 'dashicons-admin-site-alt3', 30);
    add_submenu_page('cj-shipments', 'Melhor Envio', 'Melhor Envio', 'manage_options', 'cj-melhor-envio', 'clickjumbo_render_melhor_envio_settings');
});

// Utilitário para salvar metadados de envio
function clickjumbo_store_shipping_meta($order_id, $frete_info, $penitenciaria_slug, $metodo = 'PAC') {
    update_post_meta($order_id, '_cj_shipping_data', [
        'valor' => $frete_info['valor'],
        'metodo' => $metodo,
        'status' => 'pendente',
        'penitenciaria_slug' => $penitenciaria_slug,
    ]);
}
