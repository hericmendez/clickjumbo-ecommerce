// wizard.js
import { watchOrderSlim } from './checkout.js'


import { getItem } from '../functions/localStorage.js'
import {
  validarCarrinhoAPI,
  validarEnvioForm,
  validarFreteAPI,
montarPayloadFrete, validarClienteForm

} from '../validations/index.js' // ajuste o caminho se necessário
import {
  montarPayloadDetento,
  montarPayloadVisitante
} from '../validations/montarPayload.js'
import {
  obterDadosPagamento,
  popularResumoCompra,
  processarPedido
} from './checkout.js'
import { renderPaymentScreen } from '../functions/paymentRouter.js'

import { inicializarEnvioForm } from './envio.js'
const user = getItem('user')
if (!user) {
  alert('Atenção! Faça login para continuar.')
}

let spinner = document.getElementById('loadingSpinner')

const dadosCarrinho = getItem('dadosCarrinho') || []

const dadosPenitenciaria = getItem('dadosPenitenciaria')
let payload = {
  cliente_id: user.id,
  carrinho: [],
  envio: {},
  detento: {},
  envio: {},
  pagamento: {}
}
document.addEventListener('DOMContentLoaded', function () {
  // Enable Tooltips (ok)
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  )
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  })

  // Lista dos steps
  const steps = ['step1', 'step2', 'step3', 'step4', 'step5', 'step6']
  let maxStepValidado = 0
  function gotoStep (step) {
    // step pode ser número (1..6) ou string ('step5')
    const targetId = typeof step === 'number' ? `step${step}` : String(step)
    const idx = steps.indexOf(targetId)
    if (idx === -1) return

    // libera as abas até o destino
    if (idx > maxStepValidado) maxStepValidado = idx
    updateWizardTabs(idx)

    // mostra a tab
    const link = document.querySelector(`a[href="#${targetId}"]`)
    if (link) {
      const tab = bootstrap.Tab.getOrCreateInstance(link)
      tab.show()
    }
  }
  // expõe global pra outros módulos (checkout.js etc.)
  window.gotoStep = gotoStep
  // Funções de validação por step
  const validacoes = {
    step1: async function () {
      const totalCarrinho = getItem('totalCarrinho') || {}
      payload.envio = {}
      spinner.style.display = 'block'
      payload.envio.peso_carrinho = totalCarrinho?.peso

      console.log('dadosCarrinho ==> ', dadosCarrinho)
      if (!dadosCarrinho.length) {
        alert('Seu dadosCarrinho está vazio!')
        return false
      }

      try {
        const result = await validarCarrinhoAPI(dadosCarrinho)
        console.log('dadosCarrinho ==> ', dadosCarrinho)

        if (result.success) {
          payload.carrinho = dadosCarrinho
          console.log('result ==> ', result)
          return true
        }
        /*         if (result.missing) {
          alert('Campos obrigatórios ausentes: ' + result.missing.join(', '))
        } else {
          alert(result.message || 'Erro ao validar dadosCarrinho.')
        }
        return false */
      } catch (e) {
        console.log('e ==> ', e)
        alert('Erro de comunicação com o servidor.')
        return false
      } finally {
        console.log('payload step 1:', payload)
        spinner.style.display = 'none'
      }
    },
    step2: async function () {
      //spinner.style.display = 'block'

const formValido = await validarClienteForm()

      console.log('formValido ==> ', formValido)
      if (formValido) {
        const { envio } = montarPayloadFrete()
        const detentoObj = montarPayloadDetento()
        console.log('detentoObj ==> ', detentoObj)
        console.log('envio ==> ', envio?.remetente)
        payload.detento = detentoObj

        const visitante = montarPayloadVisitante()

        payload.envio.remetente = visitante
        payload.envio.destinatario = dadosPenitenciaria
        payload.envio.nome_penitenciaria = dadosPenitenciaria.nome
        payload.envio.slug_penitenciaria = dadosPenitenciaria.slug
      }
      console.log('payload step 2:', payload)
      spinner.style.display = 'none'
      return formValido
    },
    step3: async function () {
      inicializarEnvioForm()

      // Validação visual dos campos do formulário de envio
      if (!validarEnvioForm()) return false

      // Verifica se o usuário selecionou uma opção de frete
      const selecionado = document.querySelector(
        'input[name="freteMetodo"]:checked'
      )
      if (!selecionado) {
        alert('Selecione uma opção de frete antes de continuar.')

        // Destaque visual nos cards (em vermelho)
        document.querySelectorAll('.frete-card').forEach(c => {
          c.classList.remove('border-success')
          c.classList.add('border-danger')
        })

        return false
      }

      // Limpa qualquer erro visual anterior
      document.querySelectorAll('.frete-card').forEach(c => {
        c.classList.remove('border-danger')
      })

      // ✅ Obtemos o frete selecionado do localStorage
      const freteSelecionado = getItem('dadosFrete')
      if (!freteSelecionado) {
        alert('Erro: nenhum frete selecionado encontrado.')
        return false
      }

      // Monta o payload completo (remetente, destinatário, peso)
      const payloadCompleto = montarPayloadFrete()
console.log('payloadCompleto ==> ', payloadCompleto)


      // Valida no backend
      const valido = await validarFreteAPI(payloadCompleto)
      if (!valido) {
        alert('Erro ao validar frete. Verifique os dados e tente novamente.')
        return false
      }

      // ✅ Adiciona frete ao payload principal
      payload.envio.frete_valor = freteSelecionado.valor
      payload.envio.forma_envio = freteSelecionado.metodo
      const enviarParaPenitenciaria =
        !document.getElementById('btnRadioOutro').checked
      payload.envio.enviar_para_penitenciaria = enviarParaPenitenciaria
      console.log('enviarParaPenitenciaria ==> ', enviarParaPenitenciaria)
      console.log('payload step 3:', payload)
      popularResumoCompra(payload)
      return true
    },
    step4: async function () {
      const { valorTotal, frete } = getItem('totalCarrinho') || {}
const totalCompra = (valorTotal || 0) + (frete || 0)

// 1) Descobre método


      const dadosPagamento = await obterDadosPagamento(totalCompra)

      if (!dadosPagamento) return false

// 2) Se for CARTÃO -> usa o Brick e NÃO chama processarPedido()
if (dadosPagamento.method === 'card') {
  try {
    // garante que o Brick está pronto
    await window.__mpCard?.ensure?.()
    if (!window.__mpCard?.isReady?.()) {
      alert(
        'Carregando componente de cartão. Tente novamente em alguns segundos.'
      )
      return false
    }
    // dispara o fluxo do Brick (onSubmit no pagamentoForm.js cuidará do resto)
    await window.__mpCard.submit()
    // se aprovado, onApproved() já envia ao Step 6; se in_process, Step 5.
    return true
  } catch (e) {
    console.error(e)
    alert('Erro ao enviar pagamento do cartão.')
    return false
  }
}

// 3) Caso contrário (PIX/BOLETO): segue o fluxo normal

      payload.pagamento = dadosPagamento

      const resultado = await processarPedido(payload)
if (!resultado?.success) return false

// vai pro Step 5 e desenha a UI (pix/boleto)
gotoStep(5)

      const container = document.getElementById('paymentUiDiv')
      if (container) {
renderPaymentScreen(resultado, container)

      }
// começa a escutar o status até aprovar
watchOrderSlim(resultado.order_id, {
  onApproved: () => gotoStep(6)
})

return true

    }
    // step5 = sucesso, não precisa validar nada
  }

  // Função que libera só até o passo validado
  function updateWizardTabs (activeIdx) {
    steps.forEach((stepId, idx) => {
      const tab = document.querySelector(`a[href="#${stepId}"]`)
      if (!tab) return
      if (idx <= maxStepValidado || idx === activeIdx) {
        tab.classList.remove('disabled')
        tab.setAttribute('tabindex', '0')
        tab.style.pointerEvents = 'auto'
      } else {
        tab.classList.add('disabled')
        tab.setAttribute('tabindex', '-1')
        tab.style.pointerEvents = 'none'
      }
    })
  }

  // Troca de aba: atualiza tabs
  document
    .querySelectorAll('.nav-tabs a[data-bs-toggle="tab"]')
    .forEach(function (tab, idx) {
      tab.addEventListener('show.bs.tab', function (e) {
        updateWizardTabs(idx)
      })
    })

  // Impede clique em steps futuros
  document
    .querySelectorAll('.nav-tabs a[data-bs-toggle="tab"]')
    .forEach(function (tab, idx) {
      tab.addEventListener('click', function (e) {
        if (idx > maxStepValidado) {
          e.preventDefault()
          e.stopPropagation()
        }
      })
    })

  // Ao clicar "Próximo"
  document.querySelectorAll('.next').forEach(function (btn) {
    btn.addEventListener('click', async function (e) {
      console.log('click')

      const activeTab = document.querySelector('.nav-tabs .active')
      if (!activeTab) return
      const href = activeTab.getAttribute('href') || activeTab.dataset.bsTarget
      const stepId = href ? href.replace('#', '') : null
      const li = activeTab.closest('li')
      if (!li || !li.nextElementSibling) return

      // Qual o índice do passo atual?
      const idxAtual = steps.indexOf(stepId)
      if (stepId && validacoes[stepId]) {
        const passou = await validacoes[stepId]()
        if (!passou) {
          e.preventDefault()
          e.stopPropagation()
          return
        } else {
          if (idxAtual + 1 > maxStepValidado) {
            maxStepValidado = idxAtual + 1
          }
          updateWizardTabs(idxAtual + 1)
        }
      }
      // Mostra próxima aba
      const nextTabLink = li.nextElementSibling.querySelector('a')
      if (!nextTabLink) return
      const nextTab = new bootstrap.Tab(nextTabLink)
      nextTab.show()
    })
  })

  // Botão Anterior
  document.querySelectorAll('.previous').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const activeTab = document.querySelector('.nav-tabs .active')
      if (!activeTab) return
      const href = activeTab.getAttribute('href') || activeTab.dataset.bsTarget
      const stepId = href ? href.replace('#', '') : null
      const li = activeTab.closest('li')
      if (!li || !li.previousElementSibling) return
      const idxAtual = steps.indexOf(stepId)
      updateWizardTabs(idxAtual - 1)

      const prevTabLink = li.previousElementSibling.querySelector('a')
      if (!prevTabLink) return
      const prevTab = new bootstrap.Tab(prevTabLink)
      prevTab.show()
    })
  })

  // Inicializa wizard (só 1º passo liberado)
  updateWizardTabs(0)
})
