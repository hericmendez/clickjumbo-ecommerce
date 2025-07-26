import { shortString, formatarDecimal } from "./extraFunctions.js";
import { setItem } from "./localStorage.js";

export function appendCartData(dadosCarrinho, display, qtdeTotal) {
  display.innerHTML = ""; // Limpa o conteúdo atual
    console.log("dadosCarrinho:", dadosCarrinho)

  if (!dadosCarrinho.length) {
    display.innerHTML = `
      <div class="alert alert-warning text-center fw-bold fs-5" role="alert">
        Carrinho vazio!
      </div>
    `;
    return;
  }

  const ul = document.createElement("ul");
  ul.classList.add("list-group");

  dadosCarrinho.forEach((item) => {
    const li = document.createElement("li");
    li.classList.add(
      "list-group-item",
      "d-flex",
      "justify-content-between",
      "align-items-center"
    );
    li.innerHTML = `
      <div class="d-flex align-items-center flex-grow-1 flex-row">
        <div class="border border-2 border-primary me-2 rounded-3 bg-gray" style="width: 100px; height: 100px; display: flex; justify-content: center; align-items: center;">
          <img  src="${item.thumb || '../app/assets/product_placeholder.png'}" alt="Produto"
    }" style="width: 60px; height: auto; border-radius: 4px; opacity: ${item.thumb? 1: 0.5}" />
        </div>
        <div class="ms-2">
<strong>${shortString(item.nome, 30)}</strong><br />
Peso: ${(item.peso * (item.qtde || 1)).toFixed(2)}kg<br />
Qtde: ${item.qtde || 1}<br />


        </div>
      </div>
      <div class="d-flex flex-column align-items-end fs-4 fw-bold">
        R$${(item.preco * (item.qtde || 1)).toFixed(2)}
        <button class="btn btn-sm btn-danger mt-2 remove-item" data-id="${
          item.id
        }">Remover</button>
      </div>
    `;
    ul.appendChild(li);
  });

  display.appendChild(ul);

  // Atualiza os eventos dos botões de remoção
  setTimeout(() => {
    document.querySelectorAll(".remove-item").forEach((btn) => {
      btn.addEventListener("click", () => {
        const id = btn.dataset.id;
        const carrinhoAtualizado = dadosCarrinho.filter((item) => item.id != id);
        setItem("dadosCarrinho", carrinhoAtualizado);
        appendCartData(carrinhoAtualizado, display, qtdeTotal);
        window.location.reload()
      });
    });
  }, 0);
}


export const getTotalOrderAmount = (
  data,
  parent,
  discountPercent = 0,
  onWeightExceeded = null
) => {
  const total = data.map((e) => e.preco).reduce((prev, curr) => prev + curr, 0);
  const quantidade = data.length;

  const frete = 0;//dadosFrete?.valor ? parseFloat(dadosFrete.valor) : 0;
  const taxaServico = total*0.1; //10% fixo
  const desconto = Math.floor(total * (discountPercent / 100));
  const peso = data
    .map((e) => e.peso || 0)
    .reduce((prev, curr) => prev + curr, 0);

  if (onWeightExceeded && peso > 12) {
    onWeightExceeded(peso);
    return;
  }
  
  const valorTotal = total + frete +taxaServico - desconto;
  const carrinho = { total, quantidade, frete, taxaServico, desconto, valorTotal, peso };
  setItem("totalCarrinho", carrinho);
  appendCartTotal(carrinho, parent);
};
 
export const appendCartTotal = (
  { total, quantidade, frete, desconto, valorTotal, peso },
  parent
) => {
  parent.innerHTML = null;

  const cartDiv1 = document.createElement("div");
  const totalCarrinho1 = document.createElement("p");
  totalCarrinho1.innerText = `Total do carrinho:`;
  const totalCarrinho2 = document.createElement("p");
  totalCarrinho2.innerText = `R$${total?.toFixed(2).replace(".", ",")}`;
  cartDiv1.append(totalCarrinho1, totalCarrinho2);
  cartDiv1.setAttribute("class", "cartFontDiv");

  const cartDiv2 = document.createElement("div");
  const cartQuantity1 = document.createElement("p");
  cartQuantity1.innerText = `Qtde Produtos:`;
  const cartQuantity2 = document.createElement("p");
  cartQuantity2.innerText = `${quantidade}`;
  cartDiv2.append(cartQuantity1, cartQuantity2);
  cartDiv2.setAttribute("class", "cartFontDiv");

  const cartDiv3 = document.createElement("div");
  const shippingCharges1 = document.createElement("p");
  shippingCharges1.innerText = `Valor do Envio:`;
  const shippingCharges2 = document.createElement("p");
  shippingCharges2.innerText =
    frete > 0 ? `R$${frete.toFixed(2)}` : "Não calculado";
  cartDiv3.append(shippingCharges1, shippingCharges2);
  cartDiv3.setAttribute("class", "cartFontDiv");
    
  const cartDiv4 = document.createElement("div");
  const discountTotal1 = document.createElement("p");
  discountTotal1.innerText = `Desconto:`;
  const discountTotal2 = document.createElement("p");
  discountTotal2.innerText = `R$${formatarDecimal(desconto)}`;
  cartDiv4.append(discountTotal1, discountTotal2);
  cartDiv4.setAttribute("class", "cartFontDiv");

  const cartDiv5 = document.createElement("div");
  const taxaServico1 = document.createElement("p");
  taxaServico1.innerText = `Taxa de serviço (10%):`;
  const taxaServico2 = document.createElement("p");
  taxaServico2.innerText = `R$${formatarDecimal(total*0.1)}`;
  cartDiv5.append(taxaServico1, taxaServico2);
  cartDiv5.setAttribute("class", "cartFontDiv");

  const cartDiv6 = document.createElement("div");
  const finalTotal1 = document.createElement("p");
  finalTotal1.innerText = `Total:`;
  const finalTotal2 = document.createElement("p");
  finalTotal2.innerText = `R$${formatarDecimal(valorTotal)}`;
  cartDiv6.append(finalTotal1, finalTotal2);
  cartDiv6.setAttribute("class", "cartFontDiv");

  const cartDiv7 = document.createElement("div");
  const weightLabel = document.createElement("p");
  weightLabel.innerText = `Peso Total:`;
  const weightValue = document.createElement("p");
  weightValue.innerText = `${formatarDecimal(peso)}kg`;
  cartDiv7.append(weightLabel, weightValue);
  cartDiv7.setAttribute("class", "cartFontDiv");

  parent.append(cartDiv2,cartDiv7,  cartDiv1, cartDiv5, cartDiv6);
};
