<?php
if (!defined('ABSPATH'))
    exit;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Rotas públicas
function clickjumbo_public_routes()
{
    return [
        '#^/clickjumbo/v1/login$#',
        '#^/clickjumbo/v1/register$#',
        '#^/clickjumbo/v1/product-list$#',
        '#^/clickjumbo/v1/product-list/prison$#',
        '#^/clickjumbo/v1/prison-list$#',
        '#^/clickjumbo/v1/check-health$#',
        '#^/clickjumbo/v1/products-by-prison-admin$#',
        '#^/jwt-auth/v1/token$#',
        '#^/clickjumbo/v1/prison-list-full$#',
        '#^/clickjumbo/v1/prison-details/(?P<slug>[a-zA-Z0-9-_]+)$#',
        '#^/clickjumbo/v1/delete-prison/(?P<slug>[a-zA-Z0-9-]+)$#',
        '#^/clickjumbo/v1/update-prison/(?P<slug>[a-zA-Z0-9-]+)$#',
        '#^/clickjumbo/v1/register-prison$#',
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
