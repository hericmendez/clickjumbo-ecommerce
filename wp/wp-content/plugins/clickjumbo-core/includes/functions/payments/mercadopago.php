<?php
// payments/mercadopago.php
if (!defined('ABSPATH')) exit;

/**
 * ==== CONFIG ====
 * Salve o token em:
 * - option: mp_access_token  (ou mercadopago_access_token)
 * - OU constante: CJ_MP_ACCESS_TOKEN
 * - OU env: MP_ACCESS_TOKEN
 *
 * Para sandbox, use usuários de teste do MP (mesmos endpoints).
 */

/*--------------------------------------------------------------
# Helpers de ambiente/credenciais
--------------------------------------------------------------*/
if (!function_exists('cj_mp_get_token')) {
  function cj_mp_get_token() {
    $t = get_option('mp_access_token');
    if (!$t) $t = get_option('mercadopago_access_token');
    if (!$t && is_multisite()) {
      $t = get_site_option('mp_access_token') ?: get_site_option('mercadopago_access_token');
    }
    if (!$t && defined('MP_ACCESS_TOKEN'))      $t = MP_ACCESS_TOKEN;
    if (!$t && defined('CJ_MP_ACCESS_TOKEN'))   $t = CJ_MP_ACCESS_TOKEN;
    if (!$t && getenv('MP_ACCESS_TOKEN'))       $t = getenv('MP_ACCESS_TOKEN');
    if (!$t && getenv('MERCADOPAGO_ACCESS_TOKEN')) $t = getenv('MERCADOPAGO_ACCESS_TOKEN');
    $t = trim((string)$t);
    return $t !== '' ? $t : null;
  }
}

function cj_mp_get_public_key() {
  $k = get_option('mp_public_key');
  if (!$k && is_multisite()) $k = get_site_option('mp_public_key');
  if (!$k && defined('MP_PUBLIC_KEY'))    $k = MP_PUBLIC_KEY;
  if (!$k && getenv('MP_PUBLIC_KEY'))     $k = getenv('MP_PUBLIC_KEY');
  $k = trim((string)$k);
  return $k !== '' ? $k : null;
}


/** GET/POST genérico ao MP */
function cj_mp_http($method, $path, $payload = null, $idempotency = null) {
  $token = cj_mp_get_token();
  if (!$token) return ['error'=>true,'debug'=>'MP token ausente (mp_access_token).'];

  $url = 'https://api.mercadopago.com' . $path;
  $headers = [
    'Authorization' => "Bearer {$token}",
    'Content-Type'  => 'application/json',
    'Accept'        => 'application/json',
  ];
  if ($idempotency) $headers['X-Idempotency-Key'] = $idempotency;

  $args = ['headers'=>$headers,'timeout'=>30];
  if ($method === 'POST' || $method === 'PUT') $args['body'] = wp_json_encode($payload ?: []);

  if ($method === 'GET')      $res = wp_remote_get($url, $args);
  elseif ($method === 'PUT')  $res = wp_remote_request($url, $args + ['method'=>'PUT']);
  else                        $res = wp_remote_post($url, $args);

  if (is_wp_error($res)) return ['error'=>true,'debug'=>$res->get_error_message()];
  $code = (int) wp_remote_retrieve_response_code($res);
  $body = json_decode(wp_remote_retrieve_body($res), true);

  if ($code < 200 || $code >= 300) {
    return ['error'=>true,'debug'=>$body ?: ['http_code'=>$code]];
  }
  return $body;
}

add_action('rest_api_init', function () {
  register_rest_route('clickjumbo/v1','/mp-token',[
    'methods'=>'GET','permission_callback'=>'__return_true',
    'callback'=>function(){
      $t = cj_mp_get_token();
      return ['has_token'=>(bool)$t,'len'=>$t?strlen($t):0,'prefix'=>$t?substr($t,0,6):null];
    }
  ]);
});


/*--------------------------------------------------------------
# Mapping de status MP -> WooCommerce
--------------------------------------------------------------*/
if (!function_exists('cj_mp_wc_status')) {
  function cj_mp_wc_status($mp_status) {
    $mp = strtolower((string)$mp_status);
    switch ($mp) {
      case 'approved':     return 'processing'; // ou 'completed' se não tiver entrega
      case 'authorized':   return 'on-hold';
      case 'in_process':   return 'on-hold';
      case 'in_mediation': return 'on-hold';
      case 'pending':      return 'pending';
      case 'rejected':     return 'failed';
      case 'cancelled':    return 'cancelled';
      case 'refunded':     return 'refunded';
      case 'charged_back': return 'refunded';
      default:             return 'pending';
    }
  }
}

/*--------------------------------------------------------------
# Builder de payer (CPF/endereço) a partir do payload
--------------------------------------------------------------*/
function cj_split_name($full) {
  $full = trim(preg_replace('/\s+/', ' ', (string)$full));
  if ($full === '') return ['Cliente', 'Sobrenome'];
  $parts = explode(' ', $full);
  if (count($parts) === 1) return [$parts[0], 'Sobrenome'];
  $first = array_shift($parts);
  $last  = implode(' ', $parts);
  return [$first, $last];
}

function cj_mp_build_payer(array $order_payload, array $fallback = []) {
  $cli  = $order_payload['cliente'] ?? [];
  $dest = $order_payload['destinatario'] ?? [];

  $nomeFonte = $cli['nome'] ?? $dest['nome'] ?? ($fallback['nome'] ?? 'Cliente Sobrenome');
  [$first, $last] = cj_split_name($nomeFonte);

  $cpf = preg_replace('/\D+/', '', $dest['cpf'] ?? $cli['cpf'] ?? ($fallback['cpf'] ?? '19119119100')); // sandbox ok

  return [
    'email'      => $cli['email'] ?? ($fallback['email'] ?? 'comprador-teste@example.com'),
    'first_name' => $first,
    'last_name'  => $last,
    'identification' => ['type'=>'CPF','number'=>$cpf],
    'address' => [
      'zip_code'      => preg_replace('/\D+/', '', $dest['cep'] ?? ''),
      'street_name'   => $dest['logradouro'] ?? '',
      'street_number' => (string)($dest['numero'] ?? '0'),
      'neighborhood'  => $dest['bairro'] ?? '',
      'city'          => $dest['cidade'] ?? '',
      'federal_unit'  => $dest['estado'] ?? '',
    ],
  ];
}


/*--------------------------------------------------------------
# Pagamentos
--------------------------------------------------------------*/
function cj_mp_pay_pix(array $order_payload) {
  $amount   = round((float)$order_payload['valor_total'], 2);
  $order_id = (string)($order_payload['id'] ?? '');
  $payer    = cj_mp_build_payer($order_payload);

  $payload = [
    'transaction_amount' => $amount,
    'payment_method_id'  => 'pix',
    'description'        => 'Pedido #' . $order_id,
    'external_reference' => $order_id,
    'payer'              => $payer,
    'notification_url'   => site_url('/wp-json/clickjumbo/v1/mp-webhook'),
  ];

  $resp = cj_mp_http('POST', '/v1/payments', $payload, 'pix-'.$order_id);
  if (!empty($resp['error'])) return ['error'=>true,'status'=>'error','raw'=>$resp];

  $td = $resp['point_of_interaction']['transaction_data'] ?? [];

  return [
    'status'         => $resp['status'] ?? 'pending',
    'status_detail'  => $resp['status_detail'] ?? 'pending',
    'payment_id'     => (string)($resp['id'] ?? ''),
    'qr_code'        => $td['qr_code'] ?? null,
    'qr_code_base64' => $td['qr_code_base64'] ?? null,
    'ticket_url'     => $td['ticket_url'] ?? null,
    'expiration_date'=> $td['qr_code_expiration_date'] ?? null,
    'raw'            => $resp,
  ];
}

// mercadopago.php

function cj_mp_pay_boleto(array $order_payload) {
  $amount   = round((float)$order_payload['valor_total'], 2);
  $order_id = (string)($order_payload['id'] ?? '');
  $payer    = cj_mp_build_payer($order_payload);

  // === Expira em 3 dias, formato ISO-8601 ===
  // Opção 100% compatível: UTC com sufixo 'Z'
  $expiresUtc  = (new DateTimeImmutable('now', new DateTimeZone('UTC')))
                   ->add(new DateInterval('P3D'))
                   ->setTime(23, 59, 59);
  $expiresIso  = $expiresUtc->format('Y-m-d\TH:i:s\Z');  // ex.: 2025-08-13T23:59:59Z

  // Se preferir horário local com offset (também aceito):
  // $tz         = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('America/Sao_Paulo');
  // $expiresBr  = (new DateTimeImmutable('now', $tz))->add(new DateInterval('P3D'))->setTime(23,59,59);
  // $expiresIso = $expiresBr->format('Y-m-d\TH:i:sP');   // ex.: 2025-08-13T23:59:59-03:00

  $payload = [
    'transaction_amount' => $amount,
    'payment_method_id'  => 'bolbradesco',
    'description'        => 'Pedido #' . $order_id,
    'external_reference' => $order_id,
    'payer'              => $payer,
  //  'date_of_expiration' => $expiresIso, // <- ISO válido
    'notification_url'   => site_url('/wp-json/clickjumbo/v1/mp-webhook'),
  ];

  // LOG pra garantir o que está indo:
  if (function_exists('wp_json_encode')) {
    error_log('[MP BOLETO PAYLOAD] ' . wp_json_encode($payload));
  } else {
    error_log('[MP BOLETO PAYLOAD] ' . json_encode($payload));
  }

  $resp = cj_mp_http('POST', '/v1/payments', $payload, 'boleto-'.$order_id);
  if (!empty($resp['error'])) return ['error'=>true,'status'=>'error','raw'=>$resp];

  return [
    'status'        => $resp['status'] ?? 'pending',
    'status_detail' => $resp['status_detail'] ?? 'pending',
    'payment_id'    => (string)($resp['id'] ?? ''),
    'barcode'       => $resp['barcode']['content'] ?? null,
    'boleto_url'    => $resp['transaction_details']['external_resource_url'] ?? null,
    'raw'           => $resp,
  ];
}



/**
 * Cartão de crédito (servidor): requer `dados_pagamento` com:
 *   token, installments (int), issuer_id (opcional), payment_method_id (ex: "visa")
 * No front, gere o `token` com o SDK do MP.
 */
function cj_mp_pay_card(array $order_payload, array $dados_pagamento) {
  $amount   = round((float)$order_payload['valor_total'], 2);
  $order_id = (string)($order_payload['id'] ?? '');
  $payer    = cj_mp_build_payer($order_payload);

  $token        = $dados_pagamento['token'] ?? '';
  $installments = (int)($dados_pagamento['installments'] ?? 1);
  $pm_id        = $dados_pagamento['payment_method_id'] ?? ($dados_pagamento['brand'] ?? 'visa');
  $issuer_id    = $dados_pagamento['issuer_id'] ?? null;

  if (!$token) return ['error'=>true,'status'=>'error','raw'=>'Token de cartão ausente'];

  $payload = [
    'transaction_amount' => $amount,
    'token'              => $token,
    'installments'       => max(1, $installments),
    'payment_method_id'  => $pm_id,
    'issuer_id'          => $issuer_id,
    'description'        => 'Pedido #' . $order_id,
    'external_reference' => $order_id,
    'payer'              => $payer,
    'notification_url'   => site_url('/wp-json/clickjumbo/v1/mp-webhook'),
    // opcional: 'statement_descriptor' => get_bloginfo('name')
  ];

  $resp = cj_mp_http('POST', '/v1/payments', $payload, 'card-'.$order_id);
  if (!empty($resp['error'])) return ['error'=>true,'status'=>'error','raw'=>$resp];

  return [
    'status'        => $resp['status'] ?? 'pending',
    'status_detail' => $resp['status_detail'] ?? 'pending',
    'payment_id'    => (string)($resp['id'] ?? ''),
    'raw'           => $resp,
  ];
}

/*--------------------------------------------------------------
# Webhook do Mercado Pago
--------------------------------------------------------------*/
add_action('rest_api_init', function () {
  register_rest_route('clickjumbo/v1', '/mp-webhook', [
    'methods'  => ['POST','GET'],
    'permission_callback' => '__return_true',
    'callback' => 'cj_mp_webhook_handler',
  ]);
});

/** Localiza pedido por external_reference ou meta _cj_mp_payment_id */
function cj_mp_find_order($payment_id = null, $external_reference = null) {
  if ($external_reference) {
    $order = wc_get_order((int)$external_reference);
    if ($order) return $order;
  }
  if ($payment_id) {
    $orders = wc_get_orders([
      'limit' => 1,
      'meta_key' => '_cj_mp_payment_id',
      'meta_value' => (string)$payment_id,
      'meta_compare' => '='
    ]);
    if (!empty($orders)) return $orders[0];
  }
  return null;
}

/** Atualiza pedido a partir de um objeto de pagamento do MP */
function cj_mp_update_order_from_payment(array $payment) {
  $payment_id = (string)($payment['id'] ?? '');
  $ext_ref    = (string)($payment['external_reference'] ?? '');
  $status     = $payment['status'] ?? 'pending';
  $status_det = $payment['status_detail'] ?? '';

  $order = cj_mp_find_order($payment_id, $ext_ref);
  if (!$order) return ['updated'=>false,'reason'=>'order-not-found','ref'=>$ext_ref,'payment_id'=>$payment_id];

  // Metas
  $order->update_meta_data('_cj_mp_payment_id', $payment_id);
  $order->update_meta_data('_cj_mp_status', $status);
  $order->update_meta_data('_cj_mp_status_detail', $status_det);
  $order->update_meta_data('_cj_mp_raw_last', $payment);

  // Status WC
  $wc_status = cj_mp_wc_status($status);
  $order->set_status($wc_status);
  $order->save();

  return ['updated'=>true,'order_id'=>$order->get_id(),'wc_status'=>$wc_status];
}

function cj_mp_webhook_handler(WP_REST_Request $req) {
  // MP pode mandar GET com ?type=payment&data.id=123  OU POST com { "id": 123 } / topic
  $query = $req->get_query_params();
  $body  = json_decode($req->get_body(), true);

  $payment_id = null;
  if (!empty($query['data']['id']))      $payment_id = $query['data']['id'];
  if (!$payment_id && !empty($query['id'])) $payment_id = $query['id'];
  if (!$payment_id && !empty($body['data']['id'])) $payment_id = $body['data']['id'];
  if (!$payment_id && !empty($body['id']))         $payment_id = $body['id'];

  if (!$payment_id) {
    // às vezes vem topic=payment e resource aponta para a URL; ignoramos e retornamos ok
    return new WP_REST_Response(['success'=>true,'message'=>'noop'], 200);
  }

  // Busca pagamento no MP
  $payment = cj_mp_http('GET', '/v1/payments/' . urlencode($payment_id));
  if (!empty($payment['error'])) {
    return new WP_REST_Response(['success'=>false,'message'=>'mp fetch error','debug'=>$payment], 200);
  }

  $upd = cj_mp_update_order_from_payment($payment);
  return new WP_REST_Response(['success'=>true,'update'=>$upd], 200);
}

/*--------------------------------------------------------------
# Endpoint de status para polling no front
--------------------------------------------------------------*/
add_action('rest_api_init', function () {
  register_rest_route('clickjumbo/v1','/order-status/(?P<id>\d+)', [
    'methods'=>'GET','permission_callback'=>'__return_true',
    'callback'=>function(WP_REST_Request $r){
      $id = (int)$r['id']; $o = wc_get_order($id);
      if(!$o) return new WP_REST_Response(['success'=>false],404);
      return [
        'success'=>true,
        'status'=>$o->get_status(),
        'mp_status'=>get_post_meta($id,'_cj_mp_status',true),
        'mp_status_detail'=>get_post_meta($id,'_cj_mp_status_detail',true),
      ];
    }
  ]);
});
