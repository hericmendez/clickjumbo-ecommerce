import { apiFetch } from '../api/apiFetch.js'
import { API_URL } from '../api/baseUrl.js'
import {getItem} from '../functions/localStorage.js'


export async function validarCarrinhoAPI (dadosCarrinho) {
  
  try {
    const payload = {
      carrinho: dadosCarrinho
    }
    const token = getItem('token')
    const response = await apiFetch(`/validate-cart`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify(payload)
    })
    const data = await response.json()
    
    console.log('response ==> ', data) 
    return data;
  } catch (error) {
    console.log('error ==> ', error)
    /* 
   Exemplo de obj de erro:
    {
      success: false,
      message: 'Erro(s) na validação do carrinho.',
      errors: [
        'Limite de unidades excedido para o produto Passatempo sabor morango. Máximo permitido: 1.'
      ]
    }
   */
  }
}
