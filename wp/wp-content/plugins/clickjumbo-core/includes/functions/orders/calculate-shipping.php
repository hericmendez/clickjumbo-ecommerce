<?php
/**
 * Plugin Name: ClickJumbo - Cálculo de Frete
 * Description: Calcula o valor de frete usando a API da Melhor Envio (PAC e SEDEX).
 * Version: 2.1
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

function calcular_frete_melhor_envio($cep_origem, $cep_destino, $peso, $comprimento, $largura, $altura) {
    $token = get_option('melhor_envio_token');

    if (!$token) {
        return [
            'error' => true,
            'debug' => 'Token da Melhor Envio não configurado. Acesse o painel administrativo para cadastrar seu token.'
        ];
    }

    $url = "https://www.melhorenvio.com.br/api/v2/me/shipment/calculate";

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
        return [
            'error' => true,
            'message' => 'Erro na comunicação com a API da Melhor Envio.',
            'debug' => $res->get_error_message(),
            'tip' => 'Verifique sua conexão, credenciais e tente novamente.'
        ];
    }

    $body = json_decode(wp_remote_retrieve_body($res), true);

    // Se não é array, deu ruim na resposta
    if (!is_array($body)) {
        return [
            'error' => true,
            'message' => 'Resposta inválida da API da Melhor Envio.',
            'debug' => 'Corpo retornado: ' . substr(wp_remote_retrieve_body($res), 0, 300),
            'tip' => 'Aguarde alguns instantes ou confira se a API está fora do ar.'
        ];
    }

    // Filtrar apenas PAC e SEDEX
    $fretes = [];
    $errosTransportadora = [];
    error_log('[CJ_DEBUG] Resposta bruta da Melhor Envio: ' . print_r($body, true));

    foreach ($body as $servico) {
        $nome = strtoupper($servico['name'] ?? '');
        if (in_array($nome, ['PAC', 'SEDEX'])) {
            if (!empty($servico['error'])) {
                $errosTransportadora[$nome] = $servico['error'];
                continue;
            }
            $fretes[$nome] = [
                'valor' => isset($servico['price']) ? floatval($servico['price']) : null,
                'prazo' => $servico['delivery_time'] ?? null
            ];
        }
    }

    // Se só veio erro nas transportadoras, retorna motivo
    if (empty($fretes)) {
        $motivo = !empty($errosTransportadora) ? implode(' | ', $errosTransportadora)
                                               : 'Nenhum serviço PAC ou SEDEX retornado para este trecho.';
        return [
            'error' => true,
            'message' => 'Nenhum frete disponível para o trecho informado.',
            'debug' => $motivo,
            'tip' => 'Verifique se os CEPs de origem/destino são válidos e atendidos pelos Correios. Considere também possíveis restrições temporárias de logística na Melhor Envio.'
        ];
    }

    return [
        'success' => true,
        'frete' => $fretes,
    ];
}

// Função principal
function clickjumbo_calculate_shipping(WP_REST_Request $request) {
    $data = $request->get_json_params();

    $cep_origem = preg_replace('/[^0-9]/', '', $data['cep_origem'] ?? '');
    $cep_destino = preg_replace('/[^0-9]/', '', $data['cep_destino'] ?? '');
    $peso = floatval($data['peso'] ?? 0);
    $comprimento = intval(get_option('cj_shipping_comprimento', 25));
    $largura = intval(get_option('cj_shipping_largura', 15));
    $altura = intval(get_option('cj_shipping_altura', 10));

    // Se o CEP de destino não for válido, tenta obter pela penitenciária
    $prison_data = null;
    if (!preg_match('/^[0-9]{8}$/', $cep_destino)) {
        $prison_data = get_prison_data($data['cep_destino'] ?? '');
        if (!$prison_data || empty($prison_data['cep'])) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Penitenciária não encontrada ou sem CEP cadastrado.',
                'debug' => $data['cep_destino'] ?? ''
            ], 400);
        }
        $cep_destino = preg_replace('/[^0-9]/', '', $prison_data['cep']);
    }

    // Validação mínima
    if (!$cep_origem || !$cep_destino || $peso <= 0) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Dados insuficientes ou inválidos. Verifique origem, destino e peso.',
            'debug' => [
                'cep_origem' => $cep_origem,
                'cep_destino' => $cep_destino,
                'peso' => $peso
            ]
        ], 400);
    }

    // Verifica cache
    $cache_key = 'cj_frete_' . md5("$cep_origem-$cep_destino-$peso-$comprimento-$largura-$altura");
    $cached = get_transient($cache_key);

    if (is_array($cached)) {
        $cached['cached'] = true;
        return new WP_REST_Response($cached, 200);
    }

    // Calcula frete
    $resultado = calcular_frete_melhor_envio($cep_origem, $cep_destino, $peso, $comprimento, $largura, $altura);

    if (!empty($resultado['error'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $resultado['message'] ?? 'Erro ao calcular frete.',
            'debug'   => $resultado['debug'] ?? '',
            'tip'     => $resultado['tip'] ?? null
        ], 500);
    }

    $resposta = [
        'success' => true,
        'frete' => $resultado['frete'],
    ];

    if ($prison_data) {
        $resposta['penitenciaria'] = $prison_data;
    }

    set_transient($cache_key, $resposta, 6 * HOUR_IN_SECONDS);

    return new WP_REST_Response($resposta, 200);
}
