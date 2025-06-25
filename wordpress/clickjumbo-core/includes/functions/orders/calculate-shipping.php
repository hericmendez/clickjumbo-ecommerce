<?php
/**
 * Plugin Name: ClickJumbo - Cálculo de Frete
 * Description: Calcula o valor de frete usando a API da Melhor Envio (PAC e SEDEX).
 * Version: 2.0
 * Author: ClickJumbo
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/calculate-shipping', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_calculate_shipping',
        'permission_callback' => '__return_true',
    ]);
});

// Busca os dados reais da penitenciária por slug
function get_prison_data($slug) {
    $url = home_url("/wp-json/clickjumbo/v1/prison-details/$slug");
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        error_log("Erro ao buscar penitenciária [$slug]: " . $response->get_error_message());
        return null;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return (is_array($body) && !empty($body['cep'])) ? $body : null;
}

// Faz o cálculo de frete usando a API da Melhor Envio
function calcular_frete_melhor_envio($cep_origem, $cep_destino, $peso, $comprimento, $largura, $altura) {
 // Substitua pelo token real da Melhor Envio
$url = "https://www.melhorenvio.com.br/api/v2/me/shipment/calculate";

$token = get_option('melhor_envio_token');

if (!$token) {
    return new WP_REST_Response([
        'success' => false,
        'message' => 'Token da Melhor Envio não configurado.'
    ], 400);
}


    $payload = [
        "from" => [ "postal_code" => $cep_origem ],
        "to" => [ "postal_code" => $cep_destino ],
        "package" => [
            "height" => max(2, $altura),
            "width" => max(11, $largura),
            "length" => max(16, $comprimento),
            "weight" => max(0.1, $peso)
        ]
    ];

    $args = [
        'headers' => [
            'Authorization' => "Bearer $token",
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'ClickJumbo Frete'
        ],
        'body' => json_encode($payload),
        'timeout' => 15
    ];

    $res = wp_remote_post($url, $args);

    if (is_wp_error($res)) {
        return [ 'error' => true, 'debug' => $res->get_error_message() ];
    }

    $body = json_decode(wp_remote_retrieve_body($res), true);

    if (!is_array($body)) {
        return [ 'error' => true, 'debug' => 'Resposta inválida da Melhor Envio.' ];
    }

    // Filtrar apenas PAC e SEDEX
    $fretes = [];
    foreach ($body as $servico) {
        $nome = strtoupper($servico['name'] ?? '');
        if (in_array($nome, ['PAC', 'SEDEX'])) {
            $fretes[$nome] = [
                'valor' => floatval($servico['price']),
                'prazo' => $servico['delivery_time'] . ' dias úteis'
            ];
        }
    }

    if (empty($fretes)) {
        return [ 'error' => true, 'debug' => 'Nenhum frete válido retornado.' ];
    }

    return [ 'success' => true, 'frete' => $fretes ];
}

// Função principal da rota
function clickjumbo_calculate_shipping(WP_REST_Request $request) {
    $data = $request->get_json_params();

    $cep_origem = preg_replace('/[^0-9]/', '', $data['cep_origem'] ?? '');
    $peso = floatval($data['peso'] ?? 0);
    $comprimento = intval($data['comprimento'] ?? 16);
    $largura = intval($data['largura'] ?? 11);
    $altura = intval($data['altura'] ?? 2);

    // Determina o CEP de destino
    $prison_data = null;
    if (!empty($data['cep_destino']) && !preg_match('/^[0-9]{5}-?[0-9]{3}$/', $data['cep_destino'])) {
        $prison_data = get_prison_data($data['cep_destino']);
        if (!$prison_data) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Penitenciária não encontrada.'
            ], 400);
        }
        $cep_destino = preg_replace('/[^0-9]/', '', $prison_data['cep']);
    } else {
        $cep_destino = preg_replace('/[^0-9]/', '', $data['cep_destino'] ?? '');
    }

    // Validação mínima
    if (!$cep_origem || !$cep_destino || $peso <= 0) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Dados insuficientes ou inválidos.'
        ], 400);
    }

    // Gera cache
    $cache_key = 'cj_frete_' . md5("$cep_origem-$cep_destino-$peso-$comprimento-$largura-$altura");
    $cached = get_transient($cache_key);
    if ($cached) {
        return new WP_REST_Response(array_merge($cached, ['cached' => true]), 200);
    }

    $resultado = calcular_frete_melhor_envio($cep_origem, $cep_destino, $peso, $comprimento, $largura, $altura);

    if (!empty($resultado['error'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao calcular frete.',
            'debug' => $resultado['debug']
        ], 500);
    }

    $resposta = [
        'success' => true,
        'frete' => $resultado['frete']
    ];

    if ($prison_data) {
        $resposta['penitenciaria'] = $prison_data;
    }

    set_transient($cache_key, $resposta, 6 * HOUR_IN_SECONDS);

    return new WP_REST_Response($resposta, 200);
}
