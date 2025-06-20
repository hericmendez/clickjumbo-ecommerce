<?php
if (!defined('ABSPATH')) exit;

/**
 * Envia um e-mail com ou sem anexo.
 *
 * @param string $to Email do destinatário.
 * @param string $subject Assunto do email.
 * @param string $message Corpo do email (HTML permitido).
 * @param string|null $attachment Caminho absoluto do arquivo para anexar (opcional).
 * @return bool True se o email foi enviado com sucesso, false caso contrário.
 */
function clickjumbo_send_email($to, $subject, $message, $attachment = null) {
    $headers = ['Content-Type: text/html; charset=UTF-8'];

    $attachments = [];

    if ($attachment && file_exists($attachment)) {
        $attachments[] = $attachment;
    }

    return wp_mail($to, $subject, $message, $headers, $attachments);
}


function calcular_frete_fallback($cep_origem, $cep_destino, $peso, $comprimento, $largura, $altura, $tipo = 'PAC') {
    $base = 10.00;

    $peso_kg = max(1, ceil($peso));
    $peso_custo = $peso_kg * 3.75;

    $volume_cm3 = $comprimento * $largura * $altura;
    $volume_custo = ($volume_cm3 / 1000) * 0.75;

    $prefix_origem = intval(substr($cep_origem, 0, 3));
    $prefix_destino = intval(substr($cep_destino, 0, 3));
    $distancia_km = abs($prefix_origem - $prefix_destino) * 10;
    $distancia_custo = ($distancia_km / 100) * 1.00;

    $total = $base + $peso_custo + $volume_custo + $distancia_custo;

    if (strtoupper($tipo) === 'SEDEX') {
        $total *= 1.10;
    }

    return round($total, 2);
}