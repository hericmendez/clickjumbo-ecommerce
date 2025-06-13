<?php
if (!defined('ABSPATH'))
    exit;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Rotas públicas
function clickjumbo_public_routes()
{
    $routes = [
        'login',
        'register',
        'check-health',
        'product-list',
        'product-list/prison',
        'prison-list',
        'prison-list-full',
        'products-by-prison-admin',
        'product-details/(?P<id>\d+)',
        'prison-details/.+',
        'delete-product/\d+',
        'register-prison',
        'update-prison/.+',
        'delete-prison/.+',
        'export-products',
        'orders',
        'orders/.+',
        'orders/\d+',
        'orders/\d+/cancel',
        'orders/\d+/status',
        'delete-order',
        '/users'
    ];

    return [
        '#^/clickjumbo/v1/(' . implode('|', $routes) . ')$#',
        '#^/jwt-auth/v1/token$#',
    ];
}



add_filter('rest_pre_dispatch', 'clickjumbo_check_token', 10, 3);

function clickjumbo_check_token($result, WP_REST_Server $server, WP_REST_Request $request)
{
    $route = $request->get_route();

    // Ignora rotas públicas
    foreach (clickjumbo_public_routes() as $pattern) {
        if (preg_match($pattern, $route)) {
            return $result;
        }
    }


    $auth = $request->get_header('Authorization');
    if (!$auth || !str_starts_with($auth, 'Bearer ')) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Token não fornecido.'
        ], 401);
    }

    $token = trim(str_replace('Bearer', '', $auth));
    $secret_key = 'clickjumbo-secret-key';

    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
        $request->set_param('user_id', $decoded->user_id ?? null);
        $request->set_param('email', $decoded->email ?? null);
        return $result;
    } catch (Exception $e) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Token inválido ou expirado.',
            'error' => $e->getMessage()
        ], 403);
    }
}
