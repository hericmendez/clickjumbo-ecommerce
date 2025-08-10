<?php
if (!defined('ABSPATH'))
    exit;

require_once __DIR__ . '/../functions/products/get-products.php'; // para consultar os produtos reais

function get_all_products_flat()
{
$request = new WP_REST_Request('GET', '/clickjumbo/v1/product-list');
$response = clickjumbo_get_products($request);

    if (is_wp_error($response)) {
        return [];
    }

    $produtos = $response->get_data()['content'] ?? [];
    $mapa = [];

    foreach ($produtos as $produto) {
        $id = $produto['id'];
        $mapa[$id] = $produto;
    }

    return $mapa;
}

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/validate-cart', [
        'methods' => 'POST',
        'callback' => 'clickjumbo_validate_cart',
        'permission_callback' => '__return_true',
    ]);
});

function clickjumbo_validate_cart(WP_REST_Request $request)
{
$body = json_decode($request->get_body(), true);
    $raw_cart = $body['carrinho'] ?? null;

     //error_log('REQUEST: ' . print_r($request, true));

   // error_log('BODY: ' . print_r($body, true));
//    error_log('RAW CART: ' . print_r($raw_cart, true)); 
    if (is_array($raw_cart) && isset($raw_cart[0]['id'])) {
        $products = $raw_cart;
    } else {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Carrinho inválido.',
            'errors' => ['Estrutura do carrinho ausente ou malformada.', $body],
            'received_cart' => $raw_cart
        ], 400);
    }

    $valid_products = get_all_products_flat();
    $peso_total = 0;
    $errors = [];

    foreach ($products as $item) {
        $produto_id = intval($item['id'] ?? 0);
        $qtde = intval($item['qtde'] ?? 0);

        if ($qtde <= 0) {
            $errors[] = "Quantidade inválida para o produto de ID $produto_id.";
            continue;
        }

        $product_data = $valid_products[$produto_id] ?? null;

        if (!$product_data) {
            $errors[] = "Produto com ID $produto_id não encontrado.";
            continue;
        }

        $max = $product_data['maximo_por_cliente'] ?? 99;
        if ($qtde > $max) {
            $errors[] = "Limite de unidades excedido para o produto {$product_data['nome']}. Máximo permitido: $max.";
        }

        $peso_total += ($product_data['peso'] * $qtde);

        // ⚠️ Validação de preço desativada temporariamente para testes
        /*
        $preco_unit = $product_data['preco'];
        $expected_preco = $preco_unit * $qtde;
        if (abs($expected_preco - ($item['preco'] ?? $expected_preco)) > 0.01) {
            $errors[] = "Preço inválido para o produto {$product_data['nome']}.";
        }
        */
    }

    if ($peso_total > 12.0) {
        $errors[] = 'Peso total do carrinho excede 12kg.';
    }

    if (!empty($errors)) {
        return new WP_REST_Response([
            'success' => false,
            'message' => 'Erro(s) na validação do carrinho.',
            'errors' => $errors
        ], 400);
    }

    return new WP_REST_Response(['success' => true], 200);
}

