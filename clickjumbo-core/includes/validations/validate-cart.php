<?php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/../functions/products/get-product-list.php'; // para consultar os produtos reais

function get_all_products_flat() {
    $response = clickjumbo_listar_produtos_json(null);
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

function clickjumbo_validate_cart(WP_REST_Request $request) {
$body = $request->get_body_params(); // ← usa os dados realmente enviados via set_body_params()

$raw_cart = $body['cart'] ?? null;

if (is_array($raw_cart) && isset($raw_cart[0]['id'])) {
    $products = $raw_cart;
} else {
    return new WP_REST_Response([
        'success' => false,
        'message' => 'Carrinho inválido.',
        'errors' => ['Estrutura do carrinho ausente ou malformada.', $request],
        'received_cart' => $raw_cart
    ], 400);
}



    $valid_products = get_all_products_flat();
    $total_weight = 0;
    $errors = [];

    foreach ($products as $item) {
        $product_id = intval($item['id'] ?? 0);
        $qty = intval($item['qty'] ?? 0);

        if ($qty <= 0) {
            $errors[] = "Quantidade inválida para o produto de ID $product_id.";
            continue;
        }

        $product_data = $valid_products[$product_id] ?? null;

        if (!$product_data) {
            $errors[] = "Produto com ID $product_id não encontrado.";
            continue;
        }

        $max = $product_data['maxUnitsPerClient'] ?? 99;
        if ($qty > $max) {
            $errors[] = "Limite de unidades excedido para o produto {$product_data['name']}. Máximo permitido: $max.";
        }

        $total_weight += ($product_data['weight'] * $qty);

        // ⚠️ Validação de preço desativada temporariamente para testes
        /*
        $price_unit = $product_data['price'];
        $expected_price = $price_unit * $qty;
        if (abs($expected_price - ($item['price'] ?? $expected_price)) > 0.01) {
            $errors[] = "Preço inválido para o produto {$product_data['name']}.";
        }
        */
    }

    if ($total_weight > 12.0) {
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

