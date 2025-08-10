<?php
if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/validate-shipping', [
        'methods'  => 'POST',
        'callback' => 'clickjumbo_validate_shipping',
        'permission_callback' => '__return_true',
    ]);
});

/** Mantém só dígitos (CEP etc.) */
function cj_only_digits($v) {
    return preg_replace('/\D+/', '', (string)$v);
}

/**
 * POST /wp-json/clickjumbo/v1/validate-shipping
 * Aceita { envio: {...} } ou o objeto direto.
 */
function clickjumbo_validate_shipping(WP_REST_Request $request) {
    $body = json_decode($request->get_body(), true);
    if (!is_array($body)) $body = [];

    $shipping = $body['envio'] ?? $body['shipping'] ?? $body;

    // Para regra de frete grátis
    $valor_carrinho = (float) ($shipping['valor_carrinho'] ?? $body['valor_carrinho'] ?? 0);

    // -------- Validação de campos obrigatórios --------
    $required_fields = [
        'peso_carrinho',
        'forma_envio',
        'remetente',
        'destinatario',
        'frete_valor',
    ];
    $missing = [];

    foreach ($required_fields as $field) {
        if (!isset($shipping[$field])) $missing[] = $field;
    }

    $address_fields = ['nome','logradouro','numero','bairro','cidade','estado','cep'];
    foreach (['remetente','destinatario'] as $who) {
        foreach ($address_fields as $f) {
            if (empty($shipping[$who][$f] ?? null)) $missing[] = "$who.$f";
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

    // -------- Regras de negócio --------

    // 1) Frete grátis para pedidos >= 399,00
    if ($valor_carrinho >= 399.00) {
        if ((float)$shipping['frete_valor'] == 0.0) {
            return new WP_REST_Response(['success' => true, 'free_shipping' => true], 200);
        }
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Frete deveria ser grátis para pedidos acima de R$399,00.',
            'expected_frete_valor' => 0.0,
            'received_frete_valor' => (float)$shipping['frete_valor']
        ], 400);
    }

    // 2) Sanitiza e garante mínimos de dimensões/peso
    $peso        = max(0.05, (float)$shipping['peso_carrinho']); // evita zero
    $cep_origem  = cj_only_digits($shipping['remetente']['cep'] ?? '');
    $cep_destino = cj_only_digits($shipping['destinatario']['cep'] ?? '');
    $comprimento = max(16, (int)($shipping['comprimento'] ?? 16));
    $largura     = max(11, (int)($shipping['largura'] ?? 11));
    $altura      = max(2,  (int)($shipping['altura'] ?? 2));

    if (strlen($cep_origem) < 8 || strlen($cep_destino) < 8) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'CEP de origem ou destino inválido.',
            'debug'   => compact('cep_origem','cep_destino')
        ], 400);
    }

    // 3) Fonte única: chama a função direta (sem loopback)
    if (!function_exists('calcular_frete_melhor_envio')) {
        // Segurança: se a função não existir, melhor falhar explicitamente
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Função de cálculo de frete não encontrada no backend.',
            'debug'   => 'Carregue o arquivo que define calcular_frete_melhor_envio() antes deste endpoint.'
        ], 500);
    }

    $result = calcular_frete_melhor_envio(
        $cep_origem,
        $cep_destino,
        $peso,
        $comprimento,
        $largura,
        $altura
    );

    // Trata erros vindos do cálculo
    if (is_wp_error($result)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro ao consultar o cálculo de frete.',
            'debug'   => $result->get_error_message()
        ], 400);
    }
    if (!is_array($result) || !empty($result['error'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => $result['message'] ?? 'Não foi possível calcular o frete.',
            'debug'   => $result['debug']   ?? $result
        ], 400);
    }
    if (empty($result['success']) || empty($result['frete']) || !is_array($result['frete'])) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Não foi possível calcular o frete.',
            'debug'   => $result
        ], 400);
    }

    // 4) Normaliza forma de envio (SEDEX/PAC) + aliases comuns
    $forma_req = strtoupper(trim((string)($shipping['forma_envio'] ?? '')));
    if ($forma_req === '') {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Forma de envio não informada.'
        ], 400);
    }

    $aliases = [
        'SEDEX' => ['SEDEX','CORREIOS_SEDEX','SEDEX CONTRATO','03220','SEDEx','sedex'],
        'PAC'   => ['PAC','CORREIOS_PAC','PAC CONTRATO','03298','Pac','pac'],
    ];

    $frete_map = $result['frete']; // ex.: ['PAC'=>['valor'=>...], 'SEDEX'=>['valor'=>...]]
    $keys      = array_keys($frete_map);

    // Busca a chave correspondente no retorno
    $chosenKey = null;
    $candidate_labels = [$forma_req];
    if (isset($aliases[$forma_req])) {
        $candidate_labels = array_merge($candidate_labels, $aliases[$forma_req]);
    }
    $candidate_upper = array_map('strtoupper', $candidate_labels);

    foreach ($frete_map as $k => $_v) {
        if (in_array(strtoupper($k), $candidate_upper, true)) {
            $chosenKey = $k;
            break;
        }
    }
    if (!$chosenKey && isset($frete_map[$forma_req])) {
        $chosenKey = $forma_req;
    }

    if (!$chosenKey) {
        error_log('[CJ validate-shipping] Opção não encontrada: '.$forma_req.' | Retorno: '.implode(',', $keys));
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Não encontrei a opção selecionada no retorno do cálculo.',
            'debug'   => [
                'forma_envio' => $forma_req,
                'formas_disponiveis' => $keys
            ]
        ], 400);
    }

    // 5) Extrai valor e compara com tolerância
    $api_val = $frete_map[$chosenKey]['valor'] ?? $frete_map[$chosenKey]['valor_final'] ?? null;
    if ($api_val === null) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Retorno de cálculo sem campo de valor.',
            'debug'   => $frete_map[$chosenKey]
        ], 400);
    }

    $valor_frete_api      = round((float)$api_val, 2);
    $valor_frete_recebido = round((float)$shipping['frete_valor'], 2);

    $tolerancia = 0.05; // 5 centavos
    if (abs($valor_frete_api - $valor_frete_recebido) > $tolerancia) {
        error_log('[CJ validate-shipping] Divergência: esperado='.$valor_frete_api.' | recebido='.$valor_frete_recebido.' | forma='.$chosenKey);
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Valor do frete divergente do valor calculado.',
            'expected_frete_valor' => $valor_frete_api,
            'received_frete_valor' => $valor_frete_recebido,
            'debug' => [
                'forma_envio_resolvida' => $chosenKey,
                'formas_disponiveis'    => $keys
            ]
        ], 400);
    }

    // Tudo certo
    return new WP_REST_Response(['success' => true], 200);
}
