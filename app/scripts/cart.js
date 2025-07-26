import { getItem } from '../functions/localStorage.js'
import {
  appendCartData,
  appendCartTotal,
  getTotalOrderAmount
} from '../functions/appendCartData.js'
import { validarCarrinhoAPI } from '../validations/validarCarrinhoAPI.js'
// DOM Elements
const display = document.getElementById('display')
const qtdeTotal = document.getElementById('qtdeTotal')

let dadosCarrinho = getItem('dadosCarrinho') || []
const totalCarrinho = getItem('totalCarrinho')
const btnCarrinho = document.getElementById('btnCarrinho')
const mockedCart = [
  {
    id: 67,
    qtde: 3
  },
    {
    id: 68,
    qtde: 3
  }
]
let carrinhoResumido = []
btnCarrinho.addEventListener('click', async e => {
  e.preventDefault()
  carrinhoResumido = dadosCarrinho.map(item => ({
    id: item.id,
    qtde: item.qtde
  }))
/*   const carrinhoValido = await validarCarrinhoAPI(mockedCart)
  console.log('carrinhoValido ==> ', carrinhoValido)
  if (!carrinhoValido.success) {
    window.alert(`${carrinhoValido?.message}\n
${carrinhoValido?.errors?.join('\n\n')}`)
  } */
})
// Renderiza carrinho
appendCartData(dadosCarrinho, display, qtdeTotal)
getTotalOrderAmount(dadosCarrinho, qtdeTotal)
appendCartTotal(totalCarrinho, qtdeTotal)
