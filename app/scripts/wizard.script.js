// scripts/wizard.script.js (ATUALIZADO)


import { cartMenu, initCartMenu } from '../components/cartMenu.js'
import { inicializarEnvioForm } from './envio.js' // ajuste se sua pasta for diferente

import { envioForm } from '../components/envioForm.js'
import { clientForm } from '../components/clientForm.js'
import { pagamentoForm, initPagamentoForm } from '../components/pagamentoForm.js'
import { getItem } from '../functions/localStorage.js'
import { watchOrderSlim } from './checkout.js' // para polling em caso de "in_process"

// --- monta UI base ---


const cartMenuDiv = document.getElementById('cartMenuDiv')
const clientFormDiv = document.getElementById('clientFormDiv')
const envioFormDiv = document.getElementById('envioFormDiv')
const pagamentoFormDiv = document.getElementById('pagamentoFormDiv')

// Carrinho

cartMenuDiv.innerHTML = cartMenu()
initCartMenu()

// Cliente

clientFormDiv.innerHTML = clientForm()

// Envio


envioFormDiv.innerHTML = envioForm()
inicializarEnvioForm()




// Pagamento: injeta HTML do form primeiro

pagamentoFormDiv.innerHTML = pagamentoForm()


// --- dados do usuário e total ---



const dadosUsuario = getItem('dadosUsuario') || {}
const totalCarrinho = getItem('totalCarrinho') || {}
const totalCompra = Number(
  (totalCarrinho.valorTotal || 0) + (totalCarrinho.frete || 0)
)




// --- inicializa o form + BRICK (lazy) ---
initPagamentoForm({
  // boleto
  prefillCpf: dadosUsuario.cpf || '',
  amount: totalCompra, 
  mpPublicKey: 'APP_USR-b4b8a9c2-5315-4b51-8f1b-ec60973a4047',    
  payerEmail: dadosUsuario.email || '',
  payerCPF: dadosUsuario.cpf || '',

  // callbacks do fluxo do Cartão
  onApproved: (orderId) => {
    // aprovado → sucesso
    if (orderId) {
      window.location.href = `/checkout/sucesso.html?order=${orderId}`
    } else {
      window.gotoStep?.(6)
    }
  },
onInProcess: orderId => {
  // em análise → Step 5 + (opcional) polling
  const c = document.getElementById('paymentUiDiv')
  if (c) {
    c.innerHTML = `
        <div class="alert alert-info d-flex align-items-center gap-2 mt-3">
          <i class="bi bi-hourglass-split"></i>
          <div>Pagamento em análise…</div>
        </div>`
  }
  window.gotoStep?.(5)
  if (orderId) {
    watchOrderSlim(orderId, { onApproved: () => window.gotoStep?.(6) })
  }
}

})

// (opcional) se quiser reagir ao voltar ao Step 4, sem recriar o Brick
document.getElementById('step4-tab')?.addEventListener('shown.bs.tab', () => {
  // nada aqui: o initPagamentoForm já foi chamado e o Brick é lazy (cria só ao selecionar "Cartão").
})


// FIM

