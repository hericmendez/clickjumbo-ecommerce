<?php
// includes/functions/register-taxonomies.php

function register_penitenciaria_taxonomy() {
    $labels = array(
        'name'              => 'Penitenciárias',
        'singular_name'     => 'Penitenciária',
        'search_items'      => 'Buscar Penitenciárias',
        'all_items'         => 'Todas as Penitenciárias',
        'edit_item'         => 'Editar Penitenciária',
        'update_item'       => 'Atualizar Penitenciária',
        'add_new_item'      => 'Adicionar nova Penitenciária',
        'new_item_name'     => 'Nome da nova Penitenciária',
        'menu_name'         => 'Penitenciárias',
    );

    $args = array(
        'hierarchical'      => false, // false = tipo "tags" (sem hierarquia)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'show_in_rest'      => true, // Para ser compatível com Gutenberg e API
        'rewrite'           => array('slug' => 'penitenciaria'),
    );

    register_taxonomy('penitenciaria', 'product', $args);
}
add_action('init', 'register_penitenciaria_taxonomy');
