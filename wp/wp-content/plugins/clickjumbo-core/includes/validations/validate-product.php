<?php
function clickjumbo_validate_product($data) {
    $errors = [];

    if (empty($data['nome'])) $errors[] = 'Nome do produto é obrigatório.';
    if (!isset($data['preco']) || $data['preco'] < 0) $errors[] = 'Preço inválido.';
    if (!isset($data['peso']) || $data['peso'] < 0 || $data['peso'] > 12) {
        $errors[] = 'Peso deve estar entre 0 e 12kg.';
    }
    if (empty($data['penitenciaria'])) $errors[] = 'Produto deve estar vinculado a uma penitenciária.';

    return $errors;
}
