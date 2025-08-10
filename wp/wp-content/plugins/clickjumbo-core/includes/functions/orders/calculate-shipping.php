<?php
/**
 * Plugin Name: ClickJumbo - Cálculo de Frete
 * Description: Calcula o valor de frete usando a API da Melhor Envio (PAC e SEDEX).
 * Version: 2.2
 * Author: ClickJumbo
 */

if (!defined('ABSPATH')) exit;

// Rota da API
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/calculate-shipping', [
        'methods'  => 'POST',
        'callback' => 'clickjumbo_calculate_shipping',
        'permission_callback' => '__return_true',
    ]);
});

// Busca dados da penitenciária por slug
function get_prison_data($slug) {
    $slug = trim((string)$slug);
    if ($slug === '') return null;

    $url = home_url('/wp-json/clickjumbo/v1/prison-details/' . rawurlencode($slug));
    $response = wp_remote_get($url, ['timeout' => 15]);

    if (is_wp_error($response)) {
        error_log("Erro ao buscar penitenciária [$slug]: " . $response->get_error_message());
        return null;
    }

    $code = (int) wp_remote_retrieve_response_code($response);
    if ($code < 200 || $code >= 300) {
        error_log("Erro ao buscar penitenciária [$slug]: HTTP $code");
        return null;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return (is_array($body) && !empty($body['cep'])) ? $body : null;
}

/**
 * Chamada à Melhor Envio (fonte única)
 */
function calcular_frete_melhor_envio($cep_origem, $cep_destino, $peso, $comprimento, $largura, $altura) {
$token = cj_me_get_token();
$env   = cj_me_get_env();

if (!$token) {
    return [
        'error'   => true,
        'message' => 'Token da Melhor Envio ausente.',
        'debug'   => 'Defina em: opção WordPress "melhor_envio_token", ou env MELHOR_ENVIO_TOKEN, ou constante CJ_MELHOR_ENVIO_TOKEN.',
    ];
}

    $env   = get_option('melhor_envio_environment', 'production'); // 'sandbox' | 'production'

    if (!$token) {
        return [
            'error' => true,
            'message' => 'Token da Melhor Envio não configurado.',
            'debug' => 'Configure em Configurações > Melhor Envio (melhor_envio_token).'
        ];
    }

    $base = ($env === 'sandbox')
        ? 'https://sandbox.melhorenvio.com.br'
        : 'https://www.melhorenvio.com.br';

    $url = $base . '/api/v2/me/shipment/calculate';

    $payload = [
        'from' => [ 'postal_code' => preg_replace('/\D+/', '', (string)$cep_origem) ],
        'to'   => [ 'postal_code' => preg_replace('/\D+/', '', (string)$cep_destino) ],
        'package' => [
            // mínimos de Correios
            'height' => max(2,  (int)$altura),
            'width'  => max(11, (int)$largura),
            'length' => max(16, (int)$comprimento),
            'weight' => max(0.10, (float)$peso),
        ],
    ];

    $args = [
        'headers' => [
            'Authorization' => "Bearer {$token}",
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'ClickJumbo Frete ' . site_url(),
        ],
        'body'    => wp_json_encode($payload),
        'timeout' => 25,
    ];

    $res = wp_remote_post($url, $args);

    if (is_wp_error($res)) {
        return [
            'error'   => true,
            'message' => 'Erro na comunicação com a API da Melhor Envio.',
            'debug'   => $res->get_error_message(),
        ];
    }

    $code = (int) wp_remote_retrieve_response_code($res);
    $raw  = wp_remote_retrieve_body($res);

    if ($code < 200 || $code >= 300) {
        return [
            'error'   => true,
            'message' => "HTTP {$code} da API da Melhor Envio.",
            'debug'   => substr($raw, 0, 500),
        ];
    }

    $body = json_decode($raw, true);
    if (!is_array($body)) {
        return [
            'error'   => true,
            'message' => 'Resposta inválida da API da Melhor Envio.',
            'debug'   => substr($raw, 0, 500),
        ];
    }

    // Filtra apenas PAC/SEDEX (robusto p/ rótulos variados)
    $fretes = [];
    $errosTransportadora = [];

    foreach ($body as $servico) {
        $name = strtoupper(trim((string)($servico['name'] ?? '')));
        // Normaliza rótulos
        $label = null;
        if (strpos($name, 'SEDEX') !== false) $label = 'SEDEX';
        if (strpos($name, 'PAC')   !== false) $label = 'PAC';
        if (!$label) continue;

        if (!empty($servico['error'])) {
            $errosTransportadora[$label] = is_string($servico['error']) ? $servico['error'] : json_encode($servico['error']);
            continue;
        }

        $price = isset($servico['price']) ? (float)$servico['price'] : null;

        // delivery_time pode ser número ou array com 'days'
        $prazo = $servico['delivery_time'] ?? null;
        if (is_array($prazo)) {
            $prazo = $prazo['days'] ?? ($prazo['min'] ?? null);
        }

        if ($price === null) continue;

        $fretes[$label] = [
            'valor'        => round($price, 2),
            'valor_final'  => round($price, 2), // compat com validações que checam 'valor_final'
            'prazo'        => is_numeric($prazo) ? (int)$prazo : $prazo,
            // opcionalmente exponha metadados úteis:
            'service_id'   => $servico['id'] ?? null,
            'raw_name'     => $servico['name'] ?? null,
        ];
    }

    if (empty($fretes)) {
        $motivo = !empty($errosTransportadora) ? implode(' | ', $errosTransportadora)
                                               : 'Nenhum serviço PAC/SEDEX retornado para este trecho.';
        return [
            'error'   => true,
            'message' => 'Nenhum frete disponível para o trecho informado.',
            'debug'   => $motivo,
        ];
    }

    return [
        'success' => true,
        'frete'   => $fretes,
    ];
}

/**
 * Handler da rota /calculate-shipping
 */
function clickjumbo_calculate_shipping(WP_REST_Request $request) {
    $data = $request->get_json_params() ?: [];

    // aceita override de dimensões via request; senão, usa opções; senão, defaults
    $comprimento = (int) ($data['comprimento'] ?? get_option('cj_shipping_comprimento', 25));
    $largura     = (int) ($data['largura']     ?? get_option('cj_shipping_largura', 15));
    $altura      = (int) ($data['altura']      ?? get_option('cj_shipping_altura', 10));

    $cep_origem  = preg_replace('/\D+/', '', (string) ($data['cep_origem']  ?? ''));
    $cep_destino = preg_replace('/\D+/', '', (string) ($data['cep_destino'] ?? ''));
    $peso        = (float) ($data['peso'] ?? 0);

    // Se o "cep_destino" não veio no formato válido, interpretamos como SLUG
    $prison_data = null;
    if (!preg_match('/^\d{8}$/', $cep_destino)) {
        $prison_data = get_prison_data($data['cep_destino'] ?? '');
        if (!$prison_data || empty($prison_data['cep'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Penitenciária não encontrada ou sem CEP cadastrado.',
                'debug'   => $data['cep_destino'] ?? ''
            ], 400);
        }
        $cep_destino = preg_replace('/\D+/', '', (string) $prison_data['cep']);
    }

    // Validação mínima
    if (!$cep_origem || !$cep_destino || $peso <= 0) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Dados insuficientes ou inválidos. Verifique origem, destino e peso.',
            'debug' => [
                'cep_origem'  => $cep_origem,
                'cep_destino' => $cep_destino,
                'peso'        => $peso
            ]
        ], 400);
    }

    // Cache
    $cache_key = 'cj_frete_' . md5("{$cep_origem}-{$cep_destino}-{$peso}-{$comprimento}-{$largura}-{$altura}");
    $cached = get_transient($cache_key);
    if (is_array($cached)) {
        // garante o campo 'cached'
        $cached['cached'] = true;
        return new WP_REST_Response($cached, 200);
    }

    // Calcula
    $resultado = calcular_frete_melhor_envio($cep_origem, $cep_destino, $peso, $comprimento, $largura, $altura);

    if (!empty($resultado['error'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $resultado['message'] ?? 'Erro ao calcular frete.',
            'debug'   => $resultado['debug']   ?? null,
        ], 502);
    }

    $resposta = [
        'success' => true,
        'frete'   => $resultado['frete'],
        'cached'  => false,
    ];

    if ($prison_data) {
        $resposta['penitenciaria'] = $prison_data;
    }

    set_transient($cache_key, $resposta, 6 * HOUR_IN_SECONDS);

    return new WP_REST_Response($resposta, 200);
}
function cj_me_get_token() {
    // 1) option (site)
    $t = get_option('melhor_envio_token');

    // 2) multisite (rede)
    if (!$t && is_multisite()) {
        $t = get_site_option('melhor_envio_token');
    }

    // 3) env var
    if (!$t && getenv('MELHOR_ENVIO_TOKEN')) {
        $t = getenv('MELHOR_ENVIO_TOKEN');
    }

    // 4) constante (wp-config.php)
    if (!$t && defined('CJ_MELHOR_ENVIO_TOKEN')) {
        $t = CJ_MELHOR_ENVIO_TOKEN;
    }

    $t = trim((string)$t);
    return $t !== '' ? $t : null;
}

function cj_me_get_env() {
    // 'sandbox' ou 'production'
    $env = get_option('melhor_envio_environment', '');
    if (!$env && is_multisite()) $env = get_site_option('melhor_envio_environment', '');
    if (!$env && getenv('MELHOR_ENVIO_ENV')) $env = getenv('MELHOR_ENVIO_ENV');
    if (!$env && defined('CJ_MELHOR_ENVIO_ENV')) $env = CJ_MELHOR_ENVIO_ENV;
    $env = strtolower(trim((string)$env));
    return in_array($env, ['sandbox','production'], true) ? $env : 'production';
}
$token = cj_me_get_token();
$env   = cj_me_get_env();

if (!$token) {
    return [
        'error'   => true,
        'message' => 'Token da Melhor Envio ausente.',
        'debug'   => 'Defina em: opção WordPress "melhor_envio_token", ou env MELHOR_ENVIO_TOKEN, ou constante CJ_MELHOR_ENVIO_TOKEN.',
    ];
}
