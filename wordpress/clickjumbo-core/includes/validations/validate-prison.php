<?php
// includes/validations/validate-penitenciaria.php

function validate_product_penitenciaria($post_id, $post, $update) {
    // Verifica se é o tipo de post correto
    if ($post->post_type !== 'product') return;

    // Previne execução em autosave, revisions, etc
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Verifica permissões
    if (!current_user_can('edit_post', $post_id)) return;

    // Verifica se a penitenciária foi atribuída
    $terms = wp_get_post_terms($post_id, 'penitenciaria');

    if (empty($terms)) {
        // Remove o hook para evitar loop infinito
        remove_action('save_post_product', 'validate_product_penitenciaria', 10);

        // Deleta o produto (opcional: impede publicação com erro)
        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'publish'
        ]);

        // Reaplica o hook
        add_action('save_post_product', 'validate_product_penitenciaria', 10, 3);

        // Define uma flag de erro na sessão
        add_filter('redirect_post_location', function ($location) {
            return add_query_arg('penitenciaria_missing', 1, $location);
        });
    }
}
add_action('save_post_product', 'validate_product_penitenciaria', 10, 3);

// Exibir mensagem de erro no admin
function show_penitenciaria_admin_notice() {
    if (isset($_GET['penitenciaria_missing'])) {
        echo '<div class="notice notice-error is-dismissible"><p><strong>Erro:</strong> Todo produto deve estar associado a uma penitenciária.</p></div>';
    }
}
add_action('admin_notices', 'show_penitenciaria_admin_notice');
