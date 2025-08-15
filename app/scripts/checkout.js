// checkout.js
import { apiFetch } from '../api/apiFetch.js'
import { API_URL } from '../api/baseUrl.js' // (ok ficar sem uso por enquanto)
import { getItem } from '../functions/localStorage.js'

const dadosCarrinho = getItem('dadosCarrinho') || []
const freteInfo = getItem('dadosFrete')
const token = getItem('token')
const dadosUsuario = getItem('dadosUsuario')

// Utils
function onlyDigits (v) {
  return (v == null ? '' : String(v)).replace(/\D+/g, '')
}
function toMoney (n) {
  return Number(n || 0).toFixed(2)
}

// === PAGAMENTO ===
// scripts/checkout.js
// (ALTERE SOMENTE A PARTE DE PAGAMENTO)

export async function obterDadosPagamento (valorTotal) {
const metodo = document.querySelector(
  "input[name='paymentMethod']:checked"
)?.value
  console.log("metodo ==> ", metodo);
  if (!metodo) {

    alert('Selecione uma forma de pagamento.')
    return null
  }

  // Para Pix/Boleto, o backend gera tudo no /process-order
  if (metodo === 'pix' || metodo === 'boleto') {
    const dados_pagamento = {
      valor_recebido: Number(valorTotal),
      id_transacao: `${metodo.toUpperCase()}_${Date.now()}`
    }
    return { method: metodo, dados_pagamento }
  }

// Para Cartão: quem resolve é o Card Brick (tokenização + POST no seu endpoint).

  if (metodo === 'card') {
return { method: 'card' } // sem dados locais

  }

return null

}

// === RESUMO DO CARRINHO ===
export function popularResumoCompra (payload) {
  const produtos = Array.isArray(payload?.carrinho) ? payload.carrinho : []
  const envio = payload?.envio || {}

  const valorCarrinho = produtos.reduce((s, item) => {
    const preco = Number(item.preco || 0)
    const qtd = Number(item.qtde || 0)
    return s + preco * qtd
  }, 0)

  const valorFrete = Number(envio?.frete_valor ?? 0)
  const valorTotal = valorCarrinho + valorFrete

  // Quantidade total
  const qtdeTotal = produtos.reduce((acc, p) => acc + Number(p.qtde || 0), 0)
  const elQtde = document.getElementById('qtde-carrinho')
  if (elQtde) elQtde.textContent = qtdeTotal

  // Listas
  const ulItens = document.getElementById('itens-carrinho')
  const ulResumo = document.getElementById('resumo-carrinho')
  if (ulItens) ulItens.innerHTML = ''
  if (ulResumo) ulResumo.innerHTML = ''

  // Itens
  if (ulItens) {
    produtos.forEach(prod => {
      const li = document.createElement('li')
      li.className = 'list-group-item d-flex justify-content-between lh-sm'
      li.innerHTML = `
        <div>
          <h6 class="my-0">${prod.nome}</h6>
          <small class="text-muted">Qtd: ${prod.qtde}</small>
        </div>
        <span class="text-muted">R$ ${toMoney(
          (prod.preco || 0) * (prod.qtde || 0)
        )}</span>
      `
      ulItens.appendChild(li)
    })
  }

  // Resumo
  if (ulResumo) {
    ;[
      { label: 'Subtotal', value: valorCarrinho },
      { label: 'Frete', value: valorFrete },
      { label: 'Total', value: valorTotal, strong: true }
    ].forEach(item => {
      const li = document.createElement('li')
      li.className = 'list-group-item d-flex justify-content-between'
      li.innerHTML = `
        <span>${item.label}</span>
        ${
          item.strong
            ? `<strong>R$ ${toMoney(item.value)}</strong>`
            : `<span>R$ ${toMoney(item.value)}</span>`
        }
      `
      ulResumo.appendChild(li)
    })
  }
}

// === RENDER STEP 5 (PIX/BOLETO) ===
export function renderPagamentoStep5 (data) {
  const root = document.getElementById('paymentUiDiv')
  if (!root) return
  root.innerHTML = ''
  const d = data || {}

  // BOLETO
  if (d.boleto_url || d.barcode) {
    const wrap = document.createElement('div')
    wrap.innerHTML = `
      <h5 class="mb-2">Boleto bancário</h5>
      ${
        d.boleto_url
          ? `<a class="btn btn-primary" href="${d.boleto_url}" target="_blank" rel="noopener">Abrir boleto (PDF)</a>`
          : ''
      }
      ${
        d.barcode
          ? `
        <div class="input-group mt-2">
          <input class="form-control" id="boletoLinhaDigitavel" value="${d.barcode}" readonly>
          <button class="btn btn-outline-secondary" id="btnCopyBoleto">Copiar linha digitável</button>
        </div>`
          : ''
      }
      <div class="text-muted small mt-2">O status será atualizado automaticamente após a compensação.</div>
    `
    root.appendChild(wrap)
    root.querySelector('#btnCopyBoleto')?.addEventListener('click', () => {
      const v = root.querySelector('#boletoLinhaDigitavel')?.value || ''
      navigator.clipboard.writeText(v).catch(() => {})
    })
  }

  // PIX
  if (d.qrcode_base64 || d.qrcode || d.ticket_url) {
    const wrap = document.createElement('div')
    wrap.className = 'mt-4'
    wrap.innerHTML = `
      <h5 class="mb-2">Pix</h5>
      ${
        d.qrcode_base64
          ? `<img class="img-fluid mb-2" alt="QR Pix" src="data:image/png;base64,${d.qrcode_base64}">`
          : ''
      }
      ${
        d.qrcode
          ? `
        <div class="input-group">
          <input class="form-control" id="pixCopyInput" value="${d.qrcode}" readonly>
          <button class="btn btn-outline-secondary" id="btnCopyPix">Copiar</button>
        </div>`
          : ''
      }
      ${
        d.ticket_url
          ? `<a class="btn btn-link p-0" href="${d.ticket_url}" target="_blank" rel="noopener">Abrir no Mercado Pago</a>`
          : ''
      }
      <div class="text-muted small mt-2">O status será atualizado automaticamente após a confirmação.</div>
    `
    root.appendChild(wrap)
    root.querySelector('#btnCopyPix')?.addEventListener('click', () => {
      const v = root.querySelector('#pixCopyInput')?.value || ''
      navigator.clipboard.writeText(v).catch(() => {})
    })
  }
}

// === POLLING DE STATUS ===
export function watchOrder (
  id,
  onApproved,
  interval = 5000,
  timeoutMs = 15 * 60_000
) {
  const start = Date.now()
  const timer = setInterval(async () => {
    try {
      const r = await fetch(`/wp-json/clickjumbo/v1/order-status/${id}`).then(
        x => x.json()
      )
      if (
        r?.success &&
        (r.mp_status === 'approved' ||
          ['processing', 'completed'].includes(r.status))
      ) {
        clearInterval(timer)
        onApproved?.(r)
      }
      if (Date.now() - start > timeoutMs) clearInterval(timer)
    } catch {
      /* noop */
    }
  }, interval)
  return () => clearInterval(timer)
}

// === PROCESSAR PEDIDO ===
export async function processarPedido (payload) {
  // Cart: reduzir ao essencial
  const carrinho = Array.isArray(payload?.carrinho) ? payload.carrinho : []
  if (carrinho.length > 0) {
    payload.carrinho = carrinho.map(p => ({ id: p.id, qtde: p.qtde }))
  }

  // Se método = boleto, injeta CPF no destinatário
  
  const metodo = payload?.pagamento?.method
  if (metodo === 'boleto') {
    const cpfInput = document.getElementById('cpfBoleto')
    const cpf = onlyDigits(cpfInput?.value || payload?.envio?.destinatario?.cpf)
    if (!cpf) {
      const group = document.getElementById('boletoCpfGroup')
      group?.classList.remove('d-none') // garante que apareça
      cpfInput?.focus()
      alert('Informe o CPF para boleto.')
      return { success: false, message: 'CPF obrigatório para boleto' }
    }
    payload.envio = payload.envio || {}
    payload.envio.destinatario = payload.envio.destinatario || {}
    payload.envio.destinatario.cpf = cpf
  }

  try {
    const res = await apiFetch(`/process-order`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify(payload)
    })
    const out = await res.json()

    if (!out?.success) {
      const msgBasica = out?.message || 'Erro ao processar pedido'
      const detalheGW =
        out?.gateway?.raw?.debug?.message ||
        (typeof out?.gateway?.raw === 'string' ? out.gateway.raw : '')
      alert([msgBasica, detalheGW].filter(Boolean).join(' - '))
      return out
    }

    // Ir para Step 5 e renderizar UI dinâmica
    if (typeof window.gotoStep === 'function') window.gotoStep(5)
    renderPagamentoStep5(out.data)

    // Auto-avançar (ou atualizar UI) quando aprovar
    watchOrder(out.order_id, () => {
      // já estamos no step 5; aqui você pode trocar o conteúdo para "Pagamento aprovado"
      // ex.: renderPagamentoAprovado()
    })

    return out
  } catch (e) {
    console.log('erro ao processar pagamento:', e)
    alert('Ocorreu um erro ao processar o pagamento.')
    return { success: false, message: 'network-error' }
  }
}
// polling slim usando ETag do seu /order-status
export function watchOrderSlim (
  orderId,
  { onApproved, interval = 4000, timeoutMs = 15 * 60_000 } = {}
) {
  let stopped = false
  let etag = null
  const start = Date.now()

  async function tick () {
    if (stopped) return

    const headers = {}
    if (etag) headers['If-None-Match'] = etag

    let resp
    try {
      resp = await fetch(
        `/wp-json/clickjumbo/v1/order-status?id=${encodeURIComponent(orderId)}`,
        { headers }
      )
    } catch {
      // rede falhou? tenta de novo no próximo tick
      return
    }

    if (resp.status === 304) {
      // nada mudou
    } else if (resp.ok) {
      etag = resp.headers.get('ETag') || etag
      const data = await resp.json()
      if (data?.success) {
        const status = String(data.status || '').toLowerCase() // ex: 'processing', 'completed'
        const gw =
          data.gateway && data.gateway.status
            ? String(data.gateway.status).toLowerCase()
            : ''
        const paid =
          ['processing', 'completed'].includes(status) || gw === 'approved'

        if (paid) {
          stopped = true
          onApproved?.(data)
        }
      }
    }

    if (!stopped && Date.now() - start < timeoutMs) {
      setTimeout(tick, interval)
    }
  }

  tick()
  return () => {
    stopped = true
  }
}

