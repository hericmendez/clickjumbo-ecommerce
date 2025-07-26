import { getItem } from "../functions/localStorage.js";
import { appendCartData, appendCartTotal, getTotalOrderAmount } from "../functions/appendCartData.js";

export const cartMenu = () => `
  <div id="cartMenu" class=" card p-4 mb-4">
    <h3 class="mb-3">🛒 Seu Carrinho</h3>
    <div id="display" class="cartItemList mb-4"></div>
    <div class="card">
      <div class="card-header fs-4 fw-bold text-center">Finalizar Pedido</div>
      <div class="card-body">
        <div id="qtdeTotal" class="mb-4"></div>

      </div>
    </div>
  </div>
`;



export function initCartMenu() {
  // Pega os elementos recém-inseridos no DOM
  const display = document.getElementById("display");
  const qtdeTotal = document.getElementById("qtdeTotal");
  const dadosCarrinho = getItem("dadosCarrinho") || [];
  console.log("dadosCarrinho ==> ", dadosCarrinho);
  const totalCarrinho = getItem("totalCarrinho");

  appendCartData(dadosCarrinho, display, qtdeTotal);
  getTotalOrderAmount(dadosCarrinho, qtdeTotal);
  appendCartTotal(totalCarrinho, qtdeTotal);

}
