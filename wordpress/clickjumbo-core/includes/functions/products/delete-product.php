<?php
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/delete-product/(?P<id>\d+)', [
        'methods' => 'DELETE',
        'callback' => 'clickjumbo_delete_product',
        'permission_callback' => function () {
            return current_user_can('delete_products');
        }
    ]);
});

function clickjumbo_delete_product($request)
{
    $nonce = $request->get_header('x_wp_nonce');
    if (!wp_verify_nonce($nonce, 'wp_rest')) {
        return new WP_REST_Response(['success' => false, 'message' => 'Token inválido.'], 401);
    }

    $id = intval($request['id']);
    $post = get_post($id);
    if (!$post || $post->post_type !== 'product') {
        return new WP_REST_Response(['success' => false, 'message' => 'Produto não encontrado.'], 404);
    }

    wp_delete_post($id, true);
    return new WP_REST_Response(['success' => true, 'message' => 'Produto excluído com sucesso.']);
}
