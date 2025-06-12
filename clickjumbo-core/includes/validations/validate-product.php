<?php
function clickjumbo_validate_product($data) {
    $errors = [];

    if (empty($data['name'])) $errors[] = 'Nome do produto é obrigatório.';
    if (!isset($data['price']) || $data['price'] < 0) $errors[] = 'Preço inválido.';
    if (!isset($data['weight']) || $data['weight'] < 0 || $data['weight'] > 12) {
        $errors[] = 'Peso deve estar entre 0 e 12kg.';
    }
    if (empty($data['prison'])) $errors[] = 'Produto deve estar vinculado a uma penitenciária.';

    return $errors;
}
