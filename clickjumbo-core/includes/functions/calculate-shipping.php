<?php
/**
 * Plugin Name: ClickJumbo - Cálculo de Frete
 * Description: Retorna o valor do frete PAC e SEDEX via API dos Correios, com fallback e cache.
 * Version: 1.3
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

function get_mocked_prison_data($slug) {
    $mocked = [
        'penitenciaria-a' => [
            'cep' => '15991-534'
        ],
        'penitenciaria-b' => [
            'cep' => '01153-000'
        ],
    ];

    return $mocked[$slug] ?? null;
}

function clickjumbo_calculate_shipping(WP_REST_Request $request) {
    $data = $request->get_json_params();

    $cep_origem = preg_replace('/[^0-9]/', '', $data['cep_origem'] ?? '');

    // Se o cep_destino for um slug de penitenciária, tenta buscar o CEP real
    if (!empty($data['cep_destino']) && !preg_match('/^[0-9]{5}-?[0-9]{3}$/', $data['cep_destino'])) {
        $prison_data = get_mocked_prison_data($data['cep_destino']);
        if (!$prison_data) {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Penitenciária não encontrada.'
            ], 400);
        }
        $cep_destino = $prison_data['cep'];
    } else {
        $cep_destino = preg_replace('/[^0-9]/', '', $data['cep_destino'] ?? '');
    }

    $peso = floatval($data['peso'] ?? 0);
    $comprimento = intval($data['comprimento'] ?? 16);
    $largura = intval($data['largura'] ?? 11);
    $altura = intval($data['altura'] ?? 2);

    if (!$cep_origem || !$cep_destino || $peso <= 0) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Dados insuficientes ou inválidos.'
        ], 400);
    }

    $cache_key = 'cj_frete_' . md5("$cep_origem-$cep_destino-$peso-$comprimento-$largura-$altura");
    $cached_result = get_transient($cache_key);

    if ($cached_result) {
        return new WP_REST_Response(array_merge($cached_result, ['cached' => true]), 200);
    }

    $servicos = [
        '04014' => 'SEDEX',
        '04510' => 'PAC'
    ];

    $fretes = [];
    $debug = [];
    $fallback = false;

    foreach ($servicos as $codigo => $nome) {
        $params = http_build_query([
            'nCdEmpresa' => '',
            'sDsSenha' => '',
            'nCdServico' => $codigo,
            'sCepOrigem' => $cep_origem,
            'sCepDestino' => $cep_destino,
            'nVlPeso' => max(1, ceil($peso)),
            'nCdFormato' => 1,
            'nVlComprimento' => max(16, $comprimento),
            'nVlAltura' => max(2, $altura),
            'nVlLargura' => max(11, $largura),
            'nVlDiametro' => 0,
            'sCdMaoPropria' => 'N',
            'nVlValorDeclarado' => 0,
            'sCdAvisoRecebimento' => 'N',
            'StrRetorno' => 'xml'
        ]);

        $url = "https://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?$params";

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'ClickJumbo Shipping Plugin'
        ]);

        $response = curl_exec($curl);
        $erro = curl_error($curl);
        curl_close($curl);

        if ($erro || !$response) {
            $fretes[$nome] = [
                'valor' => calcular_frete_fallback($cep_origem, $cep_destino, $peso, $comprimento, $largura, $altura, $nome),
                'prazo' => $nome === 'SEDEX' ? '3 dias úteis' : '7 dias úteis'
            ];
            $debug[] = "Erro ao consultar $nome: $erro";
            $fallback = true;
            continue;
        }

        $xml = simplexml_load_string($response);
        if (!$xml || !isset($xml->cServico->Valor)) {
            $fretes[$nome] = [
                'valor' => calcular_frete_fallback($cep_origem, $cep_destino, $peso, $comprimento, $largura, $altura, $nome),
                'prazo' => $nome === 'SEDEX' ? '3 dias úteis' : '7 dias úteis'
            ];
            $debug[] = "Erro no XML de resposta para $nome.";
            $fallback = true;
            continue;
        }

        $valor = floatval(str_replace(',', '.', (string)$xml->cServico->Valor));
        $prazo = (string)$xml->cServico->PrazoEntrega;

        $fretes[$nome] = [
            'valor' => $valor,
            'prazo' => "$prazo dias úteis"
        ];
    }

    set_transient($cache_key, [
        'success' => true,
        'frete' => $fretes,
        'fallback' => $fallback,
        'debug' => $debug
    ], 6 * HOUR_IN_SECONDS);

    return new WP_REST_Response([
        'success' => true,
        'frete' => $fretes,
        'fallback' => $fallback,
        'debug' => $debug
    ], 200);
}
