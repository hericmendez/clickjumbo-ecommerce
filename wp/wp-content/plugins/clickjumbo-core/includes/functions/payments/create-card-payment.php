<?php
// includes/functions/orders/mp-create-card-payment.php
if (!defined('ABSPATH')) exit;

require_once __DIR__ . '/../payments/mercadopago.php';

add_action('rest_api_init', function () {
  register_rest_route('clickjumbo/v1', '/mp/create-card-payment', [
    'methods'  => 'POST',
    'permission_callback' => '__return_true', // público (checkout)
    'callback' => 'clickjumbo_mp_create_card_payment',
  ]);
});

/**
 * Recebe formData do Card Payment Brick e cria o pagamento no MP.
 * Se não vier order.id, cria um pedido mínimo no Woo e usa como external_reference.
 *
 * Body esperado:
 * {
 *   "formData": { token, installments, payment_method_id, issuer_id?, payer: { email, identification: { type, number } } },
 *   "order": { "id"?: number, "amount": number, "description"?: string }
 * }
 */
function clickjumbo_mp_create_card_payment(WP_REST_Request $req) {
  $body  = $req->get_json_params();
  $form  = isset($body['formData']) && is_array($body['formData']) ? $body['formData'] : [];
  $order = isset($body['order'])    && is_array($body['order'])    ? $body['order']    : [];

  // ------- validações básicas -------
  $amount = floatval($order['amount'] ?? 0);
  $token  = sanitize_text_field($form['token'] ?? '');
  $installments = intval($form['installments'] ?? 1);
  $payment_method_id = sanitize_text_field($form['payment_method_id'] ?? '');
  $issuer_id = isset($form['issuer_id']) ? intval($form['issuer_id']) : null;

  $payer_email = sanitize_email($form['payer']['email'] ?? '');
  $payer_id_type = sanitize_text_field($form['payer']['identification']['type'] ?? 'CPF');
  $payer_id_number = preg_replace('/\D+/', '', $form['payer']['identification']['number'] ?? '');

  if ($amount <= 0)        return new WP_REST_Response(['success'=>false,'message'=>'Valor inválido.'], 400);
  if (!$token)             return new WP_REST_Response(['success'=>false,'message'=>'Token ausente.'], 400);
  if (!$payment_method_id) return new WP_REST_Response(['success'=>false,'message'=>'payment_method_id ausente.'], 400);
  if (!$payer_email)       return new WP_REST_Response(['success'=>false,'message'=>'E-mail do pagador ausente.'], 400);
  if (!$payer_id_number)   return new WP_REST_Response(['success'=>false,'message'=>'Documento do pagador ausente.'], 400);

  // ------- cria/obtém pedido -------
  $wc_order_id = intval($order['id'] ?? 0);
  if (!$wc_order_id && function_exists('wc_create_order')) {
    try {
      $o = wc_create_order();
      if (is_a($o, 'WC_Order')) {
        // adiciona uma "taxa" igual ao total (pedido mínimo)
        $fee = new WC_Order_Item_Fee();
        $fee->set_name($order['description'] ?: 'Pedido ClickJumbo');
        $fee->set_total($amount);
        $o->add_item($fee);

        // billing básico
        if ($payer_email) $o->set_billing_email($payer_email);
        $o->set_payment_method('mercadopago');
        $o->set_payment_method_title('Mercado Pago (CARTÃO)');

        $o->add_meta_data('_cj_created_from_brick', 1, true);
        $o->calculate_totals();
        $o->save();
        $wc_order_id = $o->get_id();
      }
    } catch (Throwable $e) {
      // se falhar, segue sem criar pedido (a conciliação fica mais difícil)
      $wc_order_id = 0;
    }
  }

  // ------- monta payload p/ wrapper -------
  $order_payload = [
    'id'                  => $wc_order_id ?: null,
    'valor_total'         => round($amount, 2),
    'cliente'             => [
      'nome'  => '',
      'email' => $payer_email,
      'cpf'   => $payer_id_number,
    ],
    'destinatario'        => [],
    'external_reference'  => (string)($wc_order_id ?: 'BRICK-'.wp_generate_uuid4()),
    'notification_url'    => rest_url('clickjumbo/v1/mp-webhook'),
  ];

  $dados_pagamento = [
    'token'             => $token,
    'installments'      => max(1, $installments),
    'payment_method_id' => $payment_method_id,
    'issuer_id'         => $issuer_id ?: null,
  ];

  // ------- chama wrapper (usa Access Token do servidor) -------
  $mp_resp = cj_mp_pay_card($order_payload, $dados_pagamento);
  if (!empty($mp_resp['error'])) {
    return new WP_REST_Response(['success'=>false,'message'=>'Erro no Mercado Pago','gateway'=>$mp_resp], 502);
  }

  // ------- atualiza pedido (se existir) -------
  if ($wc_order_id && function_exists('wc_get_order')) {
    $o = wc_get_order($wc_order_id);
    if ($o) {
      $o->update_meta_data('_mp_payment_id', $mp_resp['payment_id'] ?? '');
      $o->update_meta_data('_mp_payment_payload', $mp_resp);
      $st = strtolower($mp_resp['status'] ?? '');

      switch ($st) {
        case 'approved':
        case 'authorized':
          $o->payment_complete($mp_resp['payment_id'] ?? '');
          if (!in_array($o->get_status(), ['processing','completed'], true)) {
            $o->update_status('processing', 'Pagamento aprovado no Mercado Pago (cartão).');
          }
          break;

        case 'in_process':
        case 'pending':
          if ($o->get_status() !== 'on-hold') {
            $o->update_status('on-hold', 'Pagamento em análise no Mercado Pago (cartão).');
          }
          break;

        case 'refunded':
        case 'charged_back':
          if ($o->get_status() !== 'refunded') {
            $o->update_status('refunded', 'Estorno/chargeback no Mercado Pago (cartão).');
          }
          break;

        case 'cancelled':
          if ($o->get_status() !== 'cancelled') {
            $o->update_status('cancelled', 'Pagamento cancelado no Mercado Pago (cartão).');
          }
          break;

        default:
          if ($o->get_status() !== 'failed') {
            $o->update_status('failed', 'Pagamento não aprovado no Mercado Pago (cartão).');
          }
      }
      $o->save();
    }
  }

  // ------- resposta -------
  return new WP_REST_Response([
    'success'   => true,
    'order_id'  => $wc_order_id ?: null,
    'payment'   => $mp_resp, // contém id, status, status_detail, raw...
  ], 200);
}
