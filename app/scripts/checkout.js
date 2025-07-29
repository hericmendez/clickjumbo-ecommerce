// checkout.js
import { API_URL } from './baseUrl.js'
import { getItem } from '../functions/localStorage.js'

const dadosCarrinho = getItem('dadosCarrinho') || []
const freteInfo = getItem('dadosFrete')
const token = getItem('token')
const dadosUsuario = getItem('dadosUsuario')

export async function obterDadosPagamento (valorTotal) {
  const metodo = document.querySelector(
    "input[name='paymentMethod']:checked"
  )?.value

  if (!metodo) {
    alert('Selecione uma forma de pagamento.')
    return null
  }

  let dados_pagamento = {
    valor_recebido: valorTotal,
    id_transacao: `TRANS_${Date.now()}`
  }

  // Cartão (ainda não implementado)
  if (metodo === 'card') {
    const nome = document.querySelector("input[name='cardName']").value
    const numero = document.querySelector("input[name='cardNumber']").value
    const validade = document.querySelector(
      "input[name='cardExpiration']"
    ).value
    const cvv = document.querySelector("input[name='cardCVV']").value

    if (!nome || !numero || !validade || !cvv) {
      alert('Preencha todos os dados do cartão.')
      return null
    }

    // Simulação de transação
    dados_pagamento = {
      ...dados_pagamento,
      nome,
      numero,
      validade,
      cvv
    }

    // Aqui entraria a chamada real ao gateway de pagamento

    // Exemplo fictício de rejeição
    const aprovado = true
    if (!aprovado) {
      alert('Pagamento recusado pelo gateway.')
      return null
    }
  }

  // Pix e Boleto podem retornar um código ou QR para exibir depois
  if (metodo === 'pix') {
    // Simulação de geração de código
    dados_pagamento.id_transacao = `PIX_${Date.now()}`
  }

  if (metodo === 'boleto') {
    try {
      const res = await fetch(`${API_URL}/generate-boleto`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${token}`
        },
        body: JSON.stringify({
          user: {
            nome: dadosUsuario.nome,
            email: dadosUsuario.email
          },
          valor_total: valorTotal
        })
      })

      const boleto = await res.json()

      if (!boleto.success) {
        alert('Erro ao gerar boleto.')
        return null
      }

      dados_pagamento.id_transacao =
        boleto.boleto?.linha_digitavel || `BOLETO_${Date.now()}`
      // Você pode salvar a URL do PDF ou código de barras para exibir no modal depois
    } catch (error) {
      console.error(error)
      alert('Erro ao gerar boleto.')
      return null
    }
  }

  return {
    method: metodo,
    dados_pagamento
  }
}

export async function processarPedido (payload) {
  const { carrinho } = payload
  if (Array.isArray(carrinho) && carrinho.length > 0) {
    const carrinho_resumido = carrinho.map(produto => ({
      id: produto.id,
      qtde: produto.qtde
    }))
    console.log('carrinho_resumido ==> ', carrinho_resumido)
      payload.carrinho = carrinho_resumido;
  console.log("payload processado ==> ", payload);
  }

  try {
    const res = await fetch(`${API_URL}/process-order`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify(payload)
    })

    return await res.json()
  } catch (e) {
    console.log('erro ao processar pagamento:', e)
    window.alert('Ocorreu um erro ao proceessar o pagamento.')
  }
}
