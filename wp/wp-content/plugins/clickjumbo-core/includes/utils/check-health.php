<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/check-health', [
        'methods' => 'GET',
        'callback' => 'clickjumbo_health_check',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_health_check() {
    $start = microtime(true);

    $components = [
        'product_list'        => function_exists('clickjumbo_get_products'),
        'login'               => function_exists('clickjumbo_auth_login'),
        'register'            => function_exists('clickjumbo_auth_register'),
        'validate_cart'       => function_exists('clickjumbo_validate_cart'),
        'calculate_shipping'  => class_exists('SoapClient') ? 'soap-enabled' : 'fallback',
        'validate_shipping'   => function_exists('clickjumbo_validate_shipping') ? 'simulado' : false,
        'validate_payment'    => false, // atualmente desativado
        'generate_pix'        => function_exists('generate_pix') ? 'mock' : false,
        'generate_boleto'     => function_exists('generate_boleto') ? 'mock' : false,
        'process_order'       => function_exists('clickjumbo_handle_process_order'),
    ];

    $end = microtime(true);
    $duration_ms = round(($end - $start) * 1000, 2);

    return new WP_REST_Response([
        'success'    => true,
        'message'    => 'Sistema funcionando.',
        'components' => $components,
        'duration_ms'=> $duration_ms,
        'timestamp'  => current_time('mysql')
    ]);
}
