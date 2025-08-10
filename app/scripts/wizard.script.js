//wizard.script.js

import { cartMenu, initCartMenu } from '../components/cartMenu.js'
import { inicializarEnvioForm } from '../scripts/envio.js' // ou o caminho correto
import { envioForm } from '../components/envioForm.js'
import { clientForm } from '../components/clientForm.js'
import { pagamentoForm, initPagamentoForm } from '../components/pagamentoForm.js'
import { getItem } from '../functions/localStorage.js'

const cartMenuDiv = document.getElementById('cartMenuDiv')
cartMenuDiv.innerHTML = cartMenu()
initCartMenu()

const clientFormDiv = document.getElementById('clientFormDiv')
clientFormDiv.innerHTML = clientForm()

const envioFormDiv = document.getElementById('envioFormDiv')

envioFormDiv.innerHTML = envioForm()

const pagamentoFormDiv = document.getElementById('pagamentoFormDiv')


const dadosUsuario = getItem('dadosUsuario') || {}
initPagamentoForm(dadosUsuario.cpf || '')
document.getElementById('step4-tab')?.addEventListener('shown.bs.tab', () => {
  const du = getItem('dadosUsuario') || {}
  initPagamentoForm(du.cpf || '')
})
pagamentoFormDiv.innerHTML = pagamentoForm()

envioFormDiv.innerHTML = envioForm()
inicializarEnvioForm() // <== ✅ aqui está a mágica


import { pixScreen } from '../components/pixScreen.js';
import { initPixScreen } from '../functions/pix.js';
import { apiFetch } from '../api/apiFetch.js'

// depois que você chamar o /process-order (method: 'pix') e tiver a resposta `data`:
function renderPix(data) {
  const container = document.getElementById('pixPaymentDiv');

  const orderId   = data.order_id;
  const resp      = data.data || {};
  const amount    = resp.valor_total;
  const qrBase64  = resp.qrcode_base64;
  const qrText    = resp.qrcode;
  const ticketUrl = resp.ticket_url || resp.pagamento_response?.ticket_url;
  const expiresAt = resp.pagamento_response?.raw?.date_of_expiration || null;

  // injeta HTML
  container.innerHTML = pixScreen({
    orderId,
    amount,
    qrCodeBase64: qrBase64,
    qrCodeText: qrText,
    ticketUrl,
    expiresAt
  });

  // inicializa ações
  initPixScreen({
    orderId,
    qrCodeBase64: qrBase64,
    qrCodeText: qrText,
    expiresAt,
    onPaid: (id) => {
      window.location.href = `/checkout/sucesso.html?order=${id}`;
    },
    enableManualCheck: true,
    // polling simples (crie /clickjumbo/v1/order-status?id=XXX)
    pollFn: async (id) => {
      const r = await apiFetch(`/order-status?id=${id}`, {}, true);
      return await r.json(); // { status: 'wc-processing' | 'wc-pending' | ...}
    },
    pollIntervalMs: 15000
  });
}
