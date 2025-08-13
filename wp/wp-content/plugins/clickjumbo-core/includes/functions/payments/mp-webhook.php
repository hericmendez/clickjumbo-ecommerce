<?php
// routes/mp-webhook.php
if (!defined('ABSPATH')) exit;

if (!function_exists('cj_mp_get_token')) {
  function cj_mp_get_token() {
    $opt = get_option('clickjumbo_mp_access_token');
    if ($opt) return $opt;
    $env = getenv('MP_ACCESS_TOKEN');
    return $env ?: '';
  }
}

add_action('rest_api_init', function(){
  register_rest_route('clickjumbo/v1', '/mp-webhook', [
    'methods'  => ['POST','GET'], // MP pode reprocessar e também validar GET
    'permission_callback' => '__return_true',
    'callback' => function(WP_REST_Request $req){

      // --- 1) Descobrir tipo e id ---
      $type = $req->get_param('type') ?: $req->get_param('topic');  // 'payment' ou 'merchant_order'
      $id   = $req->get_param('data')['id'] ?? $req->get_param('id');

      if (!$id) {
        $body = json_decode($req->get_body(), true);
        $id   = $body['data']['id'] ?? $body['id'] ?? null;
        if (!$type) $type = $body['type'] ?? $body['topic'] ?? null;
      }

      if (!$type) {
        return new WP_REST_Response(['ok'=>true,'ignored'=>true,'reason'=>'no type'], 200);
      }

      $token = cj_mp_get_token();
      if (!$token) return new WP_REST_Response(['ok'=>false,'error'=>'missing token'], 500);

      // --- 2) Se vier merchant_order, converte para payment id ---
      if ($type === 'merchant_order') {
        if (!$id) return new WP_REST_Response(['ok'=>true,'ignored'=>true,'reason'=>'no merchant_order id'], 200);
        $moRes = wp_remote_get(
          'https://api.mercadopago.com/merchant_orders/'.urlencode($id),
          ['headers'=>['Authorization'=>"Bearer $token",'Accept'=>'application/json'], 'timeout'=>20]
        );
        if (is_wp_error($moRes)) {
          error_log('[MP WEBHOOK] merchant_order http error: '.$moRes->get_error_message());
          return new WP_REST_Response(['ok'=>false], 500);
        }
        $mo = json_decode(wp_remote_retrieve_body($moRes), true);
        if (!is_array($mo)) return new WP_REST_Response(['ok'=>true,'ignored'=>true,'reason'=>'invalid merchant_order'], 200);

        // pega primeiro pagamento válido
        if (!empty($mo['payments'][0]['id'])) {
          $id   = $mo['payments'][0]['id'];
          $type = 'payment';
        } else {
          return new WP_REST_Response(['ok'=>true,'ignored'=>true,'reason'=>'merchant_order with no payments'], 200);
        }
      }

      // --- 3) Fora disso, só processamos payment ---
      if ($type !== 'payment' || !$id) {
        return new WP_REST_Response(['ok'=>true,'ignored'=>true,'reason'=>'not payment'], 200);
      }

      // --- 4) Busca o pagamento no MP ---
      $res = wp_remote_get(
        'https://api.mercadopago.com/v1/payments/'.urlencode($id),
        ['headers'=>['Authorization'=>"Bearer $token",'Accept'=>'application/json'], 'timeout'=>20]
      );
      if (is_wp_error($res)) {
        error_log('[MP WEBHOOK] payment http error: '.$res->get_error_message());
        return new WP_REST_Response(['ok'=>false], 500);
      }
      $p = json_decode(wp_remote_retrieve_body($res), true);
      if (!is_array($p)) return new WP_REST_Response(['ok'=>true,'ignored'=>true,'reason'=>'invalid payment body'], 200);

      // --- 5) Descobre o order_id pelo external_reference ---
      $order_id = intval($p['external_reference'] ?? 0);
      if (!$order_id) return new WP_REST_Response(['ok'=>true,'no_order'=>true], 200);

      $order = function_exists('wc_get_order') ? wc_get_order($order_id) : null;
      if (!$order) return new WP_REST_Response(['ok'=>true,'no_wc'=>true], 200);

      // --- 6) Idempotência simples: se status igual ao último salvo, ignora ---
      $new_status = strtolower($p['status'] ?? '');
      $last_status = strtolower($order->get_meta('_mp_status', true));
      $last_pid    = (string)$order->get_meta('_mp_payment_id', true);
      $pid         = (string)($p['id'] ?? '');

      if ($pid && $last_pid === $pid && $last_status === $new_status) {
        return new WP_REST_Response(['ok'=>true,'ignored'=>true,'reason'=>'same status'], 200);
      }

      // --- 7) Atualiza metas (duas chaves por compatibilidade) ---
      $order->update_meta_data('_mp_payment_id', $pid);
      $order->update_meta_data('_mp_status', $p['status'] ?? '');
      $order->update_meta_data('_mp_status_detail', $p['status_detail'] ?? '');
      $order->update_meta_data('_mp_raw_last', $p);
      // Legacy:
      $order->update_meta_data('_cj_mp_payment_id', $pid);
      $order->update_meta_data('_cj_mp_status', $p['status'] ?? '');
      $order->update_meta_data('_cj_mp_status_detail', $p['status_detail'] ?? '');
      $order->update_meta_data('_cj_mp_raw_last', $p);

      // --- 8) Aplica status no Woo ---
      $detail = $p['status_detail'] ?? '';
      switch ($new_status) {
        case 'approved':
        case 'authorized':
          $order->payment_complete($pid ?: '');
          // garante 'processing' (para produtos físicos)
          if (!in_array($order->get_status(), ['processing','completed'], true)) {
            $order->update_status('processing', 'Pagamento aprovado no Mercado Pago. Detalhe: '.$detail);
          } else {
            $order->add_order_note('Pagamento aprovado no Mercado Pago. Detalhe: '.$detail);
          }
          break;

        case 'in_process':
        case 'pending':
          // deixa on-hold enquanto análise/pendência
          if ($order->get_status() !== 'on-hold') {
            $order->update_status('on-hold', 'Pagamento em análise no Mercado Pago. Detalhe: '.$detail);
          }
          break;

        case 'refunded':
        case 'charged_back':
          // marcado como reembolsado
          if ($order->get_status() !== 'refunded') {
            $order->update_status('refunded', 'Pagamento estornado/chargeback no Mercado Pago. Detalhe: '.$detail);
          } else {
            $order->add_order_note('Estorno/chargeback confirmado. Detalhe: '.$detail);
          }
          break;

        case 'cancelled':
          if ($order->get_status() !== 'cancelled') {
            $order->update_status('cancelled', 'Pagamento cancelado no Mercado Pago. Detalhe: '.$detail);
          }
          break;

        case 'rejected':
          if ($order->get_status() !== 'failed') {
            $order->update_status('failed', 'Pagamento rejeitado no Mercado Pago. Detalhe: '.$detail);
          }
          break;

        default:
          // outros estados: authorized/pending/in_mediation etc. → mantém
          $order->add_order_note('Webhook Mercado Pago: status '.$new_status.' (sem transição). Detalhe: '.$detail);
      }

      $order->save();
      return new WP_REST_Response(['ok'=>true], 200);
    }
  ]);
});
