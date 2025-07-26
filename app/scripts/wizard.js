// wizard.js

import {
  validarCarrinhoAPI,
  validarClienteForm,
  validarEnvioForm,
  validarFreteAPI,
  validarPagamento,
  montarPayloadFrete
} from '../validations/index.js' // ajuste o caminho se necessário
import {
  montarPayloadDetento,
  montarPayloadVisitante
} from '../validations/montarPayload.js'
let spinner = document.getElementById('loadingSpinner')
let payload = {}

console.log('spinner ==> ', spinner)
//   spinner.style.display = "block";
//   spinner.style.display = "none";
document.addEventListener('DOMContentLoaded', function () {
  // Enable Tooltips (ok)
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  )
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
  })

  // Lista dos steps
  const steps = ['step1', 'step2', 'step3', 'step4', 'step5']
  let maxStepValidado = 0

  // Funções de validação por step
  const validacoes = {
    step1: async function () {
      spinner.style.display = 'block'
      const carrinho = JSON.parse(localStorage.getItem('dadosCarrinho') || '[]')
      console.log('carrinho ==> ', carrinho)
      if (!carrinho.length) {
        alert('Seu carrinho está vazio!')
        return false
      }

      try {
        const result = await validarCarrinhoAPI(carrinho)
        console.log('carrinho ==> ', carrinho)

        if (result.success) {
          payload.carrinho = carrinho
          console.log('result ==> ', result)
          return true
        }
        /*         if (result.missing) {
          alert('Campos obrigatórios ausentes: ' + result.missing.join(', '))
        } else {
          alert(result.message || 'Erro ao validar carrinho.')
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

      const formValido = true//await validarClienteForm()
      console.log('formValido ==> ', formValido)
      if (formValido) {
        const { envio } = montarPayloadFrete()
        const detentoObj = montarPayloadDetento()
        console.log('detentoObj ==> ', detentoObj)
        console.log('envio ==> ', envio?.remetente)
        payload.detento = detentoObj
        payload.envio = {}
        const visitante = montarPayloadVisitante()
        payload.envio.remetente = visitante
        console.log('visitante ==> ', visitante)

        //payload.envio.remetente = montarPayloadVisitante()
      }
      console.log('payload step 2:', payload)
      spinner.style.display = 'none'
      return formValido
    },
    step3: async function () {
      // Endereço: validação DOM E backend
      if (!validarEnvioForm()) return false
      console.log("validarEnvioForm() ==> ", validarEnvioForm());
      const payloadFrete = montarPayloadFrete()
      console.log("payloadFrete ==> ", payloadFrete);
      try {
        spinner.style.display = 'block'
        const result = await validarFreteAPI(payload)
        if (result.success) return true
        if (result.message) {
          if (result.missing) {
            alert('Campos obrigatórios ausentes: ' + result.missing.join(', '))
          } else if (result.expected_frete_valor) {
            alert(
              `Valor do frete divergente!`
            )
          } else {
            alert(result.message)
          }
        } else {
          alert('Erro ao validar frete.')
        }
        return false
      } catch (e) {
        alert('Erro de comunicação com o servidor.')
        return false
      } finally {
        spinner.style.display = 'none'
      }
    },
    step4: async function () {
      spinner.style.display = 'block'
      const pagtoValido = await validarPagamento()
      spinner.style.display = 'none'
      return pagtoValido
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
