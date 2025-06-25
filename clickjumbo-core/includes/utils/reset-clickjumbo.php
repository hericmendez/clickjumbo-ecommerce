<?php
if (!defined('ABSPATH')) exit;

/**
 * Reset ClickJumbo Data: penitenciárias, produtos e taxonomias
 */
function clickjumbo_reset_data() {
    // Excluir penitenciárias (CPT penitenciaria)
    $penitenciarias = get_posts([
        'post_type' => 'penitenciaria',
        'posts_per_page' => -1,
        'post_status' => 'any',
    ]);
    foreach ($penitenciarias as $penitenciaria) {
        wp_delete_post($penitenciaria->ID, true);
    }

    // Excluir produtos (CPT product)
    $products = get_posts([
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'any',
    ]);
/*     foreach ($products as $product) {
        wp_delete_post($product->ID, true);
    }
 */
    // Excluir termos das taxonomias personalizadas
    $taxonomies = ['category', 'subcategory'];
/*     foreach ($taxonomies as $tax) {
        $terms = get_terms([
            'taxonomy' => $tax,
            'hide_empty' => false,
        ]);

        foreach ($terms as $term) {
            wp_delete_term($term->term_id, $tax);
        }
    } */

    return [
        'success' => true,
        'message' => 'Dados resetados com sucesso.',
        'deleted' => [
            'penitenciarias' => count($penitenciarias),
            'produtos' => count($products),
            'taxonomias' => $taxonomies,
        ],
    ];
}
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/reset-data', [
        'methods'  => 'POST',
        'callback' => 'clickjumbo_reset_data',
        'permission_callback' => '__return_true',
    ]);
});
