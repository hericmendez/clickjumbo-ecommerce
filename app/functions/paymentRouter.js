import { pixScreen } from '../components/pixScreen.js';
import { initPixScreen } from './pix.js';
import { boletoScreen } from '../components/boletoScreen.js';
import { initBoletoScreen } from './boleto.js';
import { apiFetch } from '../api/apiFetch.js';

/**
 * Decide qual UI exibir com base no method e na resposta do backend.
 * @param {Object} apiData - resposta de /process-order
 * @param {HTMLElement} container - ex: document.getElementById('paymentUiDiv')
 */
export function renderPaymentScreen(apiData, container) {
  if (!apiData?.success) {
    container.innerHTML = `<div class="alert alert-danger">Falha ao processar o pedido.</div>`;
    return;
  }

  const orderId = apiData.order_id;
  const d = apiData.data || {};
  const method = (d.pagamento?.method || '').toLowerCase();
  const amount = d.valor_total;

  const ticketUrl = d.ticket_url || d.pagamento_response?.ticket_url || null;

  // helper de polling
  const pollFn = async (id) => {
    const r = await apiFetch(`/order-status?id=${id}`);
    return await r.json(); // { status: 'wc-...' }
  };

const onPaid = (id) => {
  try { sessionStorage.setItem('cj_last_order', String(id)); } catch {}
  if (typeof window.gotoStep === 'function') {
    // opcional: mostrar o # do pedido no Step 6
    const el = document.getElementById('cjOrderId');
    if (el) el.textContent = `#${id}`;
    window.gotoStep(6);
  } else {
    // fallback, caso o wizard não esteja na página
    window.location.href = `/app/orderPlaced.html`;
  }
};


  if (method === 'pix') {
    const expiresAt = d.pagamento_response?.raw?.date_of_expiration || null;
    container.innerHTML = pixScreen({
      orderId,
      amount,
      qrCodeBase64: d.qrcode_base64,
      qrCodeText: d.qrcode,
      ticketUrl,
      expiresAt
    });
    initPixScreen({
      orderId,
      qrCodeBase64: d.qrcode_base64,
      qrCodeText: d.qrcode,
      expiresAt,
      onPaid,
      enableManualCheck: true,
      pollFn,
      pollIntervalMs: 15000
    });
    return;
  }

  if (method === 'boleto') {
    const boletoUrl = d.boleto_url || d.pagamento_response?.boleto_url || ticketUrl;
    const linha = d.pagamento_response.raw.transaction_details.barcode.content || ''; // se vier
    container.innerHTML = boletoScreen({ orderId, amount, boletoUrl, linhaDigitavel: linha });
    initBoletoScreen({ orderId, onPaid, pollFn, pollIntervalMs: 30000 });
    return;
  }

  // Cartão
  if (method === 'credit-card' || method === 'debt-card' || method === 'card') {
    const st = (d.pagamento_response?.status || '').toLowerCase();
    if (['approved','authorized'].includes(st)) {
      container.innerHTML = `
        <div class="alert alert-success d-flex align-items-center gap-2">
          <i class="bi bi-check-circle"></i>
          <div>Pagamento aprovado! Redirecionando…</div>
        </div>`;
      setTimeout(() => onPaid(orderId), 1200);
    } else if (['in_process','pending'].includes(st)) {
      container.innerHTML = `
        <div class="alert alert-info d-flex align-items-center gap-2">
          <i class="bi bi-hourglass-split"></i>
          <div>Pagamento em análise…</div>
        </div>`;
      // ainda dá pra usar polling:
      const doPoll = async () => {
        const r = await pollFn(orderId);
        const s = (r?.status||'').toLowerCase();
        if (['wc-processing','completed','processing'].includes(s)) onPaid(orderId);
      };
      const t = setInterval(doPoll, 10000);
      doPoll();
      setTimeout(()=>clearInterval(t), 5*60*1000);
    } else {
      container.innerHTML = `
        <div class="alert alert-danger d-flex align-items-center gap-2">
          <i class="bi bi-x-circle"></i>
          <div>Pagamento não aprovado: ${d.pagamento_response?.status_detail || 'tente novamente'}</div>
        </div>`;
    }
    return;
  }

  // Fallback
  container.innerHTML = `<div class="alert alert-warning">Método de pagamento desconhecido.</div>`;
}
