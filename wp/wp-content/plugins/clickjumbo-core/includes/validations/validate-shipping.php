<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/validate-shipping', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_validate_shipping',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_validate_shipping(WP_REST_Request $request) {
    $body = json_decode($request->get_body(), true);
    $shipping = $body['envio'] ?? $body['shipping'] ?? $body;

    // Valor do carrinho para regra de frete grátis
    $valor_carrinho = floatval($shipping['valor_carrinho'] ?? $body['valor_carrinho'] ?? 0);
error_log('Log de teste Axis - ' . date('Y-m-d H:i:s'));

    // Campos obrigatórios
    $required_fields = [
        'slug_penitenciaria',
        'peso_carrinho',
        'forma_envio',
        'remetente',
        'destinatario',
        'frete_valor'
    ];
    $missing = [];
    foreach ($required_fields as $field) {
        if (!isset($shipping[$field])) {
            $missing[] = $field;
        }
    }

    // Campos obrigatórios de endereço
    $address_fields = ['nome', 'logradouro', 'numero', 'bairro', 'cidade', 'estado', 'cep'];
    foreach (['remetente', 'destinatario'] as $who) {
        foreach ($address_fields as $f) {
            if (empty($shipping[$who][$f] ?? null)) {
                $missing[] = "$who.$f";
            }
        }
    }

    if (!empty($shipping['enviar_para_penitenciaria']) && empty($shipping['slug_penitenciaria'])) {
        $missing[] = 'slug_penitenciaria';
    }

    if (!empty($missing)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Campos obrigatórios ausentes.',
            'missing' => $missing
        ], 400);
    }

    // REGRAS DE NEGÓCIO

    // 1. Frete grátis para pedidos >= 399,00
    if ($valor_carrinho >= 399.00) {
        if (floatval($shipping['frete_valor']) == 0) {
            return new WP_REST_Response(['success' => true, 'free_shipping' => true], 200);
        } else {
            return new WP_REST_Response([
                'success' => false,
                'message' => 'Frete deveria ser grátis para pedidos acima de R$399,00.',
                'expected_frete_valor' => 0,
                'received_frete_valor' => $shipping['frete_valor']
            ], 400);
        }
    }

    // 2. Monta o payload para o cálculo real do frete (dimensões serão ignoradas no back)
    $calc_payload = [
        'cep_origem'   => $shipping['remetente']['cep'],
        'cep_destino'  => $shipping['destinatario']['cep'],
        'peso'         => floatval($shipping['peso_carrinho']),
        // Os campos abaixo são enviados mas ignorados no backend; só por compatibilidade:
        'comprimento'  => intval($shipping['comprimento'] ?? 16),
        'largura'      => intval($shipping['largura'] ?? 11),
        'altura'       => intval($shipping['altura'] ?? 2)
    ];

    $api_url = home_url('/wp-json/clickjumbo/v1/calculate-shipping');
$result = calcular_frete_melhor_envio(
    $calc_payload['cep_origem'],
    $calc_payload['cep_destino'],
    $calc_payload['peso'],
    $calc_payload['comprimento'],
    $calc_payload['largura'],
    $calc_payload['altura']
);


    if (is_wp_error($response)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao consultar o cálculo de frete.',
            'debug' => $response->get_error_message()
        ], 400);
    }

   // $result = json_decode(wp_remote_retrieve_body($response), true);

    if (empty($result['success']) || empty($result['frete'][$shipping['forma_envio']]['valor'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Não foi possível calcular o frete.',
            'debug' => $result
        ], 400);
    }

    $valor_frete_api = floatval($result['frete'][$shipping['forma_envio']]['valor']);
    $valor_frete_recebido = floatval($shipping['frete_valor']);

    // Permite diferença mínima para arredondamento
    if (abs($valor_frete_api - $valor_frete_recebido) > 0.01) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Valor do frete divergente do valor calculado.',
            'expected_frete_valor' => $valor_frete_api,
            'received_frete_valor' => $valor_frete_recebido
        ], 400);
    }

    return new WP_REST_Response(['success' => true], 200);
}
