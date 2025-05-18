import { shortString, numberWithCommas } from "./extraFunctions.js";
import { setItem } from "./localStorage.js";

export function appendCartData(cartData, display, totalAmount) {
  display.innerHTML = ""; // Limpa o conteúdo atual

  if (!cartData.length) {
    display.innerHTML = `
      <div class="alert alert-warning text-center fw-bold fs-5" role="alert">
        Carrinho vazio!
      </div>
    `;
    return;
  }

  const ul = document.createElement("ul");
  ul.classList.add("list-group");

  cartData.forEach((item) => {
    const li = document.createElement("li");
    li.classList.add(
      "list-group-item",
      "d-flex",
      "justify-content-between",
      "align-items-center"
    );
    li.innerHTML = `
      <div class="d-flex align-items-center flex-grow-1 flex-row">
        <div class="border border-2 border-dark me-2 rounded-3 bg-gray" style="width: 100px; height: 100px; display: flex; justify-content: center; align-items: center;">
          <img src="${item.thumb || ""}" alt="${
      item.brand || "Produto"
    }" style="width: 60px; height: auto; border-radius: 4px;" />
        </div>
        <div class="ms-2">
          <strong>${shortString(item.name, 30)}</strong><br />
          Peso: ${(item.weight * (item.qty || 1)).toFixed(2)}kg<br />
          Qtde: ${item.qty || 1}<br />
        </div>
      </div>
      <div class="d-flex flex-column align-items-end fs-4 fw-bold">
        R$${(item.price * (item.qty || 1)).toFixed(2)}
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
        const updatedCart = cartData.filter((item) => item.id != id);
        setItem("cartData", updatedCart);
        appendCartData(updatedCart, display, totalAmount);
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
  const total = data.map((e) => e.price).reduce((prev, curr) => prev + curr, 0);
  const quantity = data.length;
  const shipping = total < 999 && quantity > 0 ? 50 : 0;
  const discount = Math.floor(total * (discountPercent / 100));
  const weight = data
    .map((e) => e.weight || 0)
    .reduce((prev, curr) => prev + curr, 0);

  if (onWeightExceeded && weight > 12) {
    onWeightExceeded(weight);
    return;
  }

  const grandTotal = total + shipping - discount;
  const cartTotal = { total, quantity, shipping, discount, grandTotal, weight };
  setItem("cartTotal", cartTotal);
  appendCartTotal(cartTotal, parent);
};

export const appendCartTotal = (
  { total, quantity, shipping, discount, grandTotal, weight },
  parent
) => {
  parent.innerHTML = null;

  const cartDiv1 = document.createElement("div");
  const cartTotal1 = document.createElement("p");
  cartTotal1.innerText = `Total da Compra:`;
  const cartTotal2 = document.createElement("p");
  cartTotal2.innerText = `R$${total.toFixed(2).replace(".", ",")}`;
  cartDiv1.append(cartTotal1, cartTotal2);
  cartDiv1.setAttribute("class", "cartFontDiv");

  const cartDiv2 = document.createElement("div");
  const cartQuantity1 = document.createElement("p");
  cartQuantity1.innerText = `Qtde Produtos:`;
  const cartQuantity2 = document.createElement("p");
  cartQuantity2.innerText = `${quantity}`;
  cartDiv2.append(cartQuantity1, cartQuantity2);
  cartDiv2.setAttribute("class", "cartFontDiv");

  const cartDiv3 = document.createElement("div");
  const shippingCharges1 = document.createElement("p");
  shippingCharges1.innerText = `Valor do Envio:`;
  const shippingCharges2 = document.createElement("p");
  shippingCharges2.innerText = `R$${shipping}`;
  cartDiv3.append(shippingCharges1, shippingCharges2);
  cartDiv3.setAttribute("class", "cartFontDiv");

  const cartDiv4 = document.createElement("div");
  const discountTotal1 = document.createElement("p");
  discountTotal1.innerText = `Desconto:`;
  const discountTotal2 = document.createElement("p");
  discountTotal2.innerText = `R$${numberWithCommas(discount)}`;
  cartDiv4.append(discountTotal1, discountTotal2);
  cartDiv4.setAttribute("class", "cartFontDiv");

  const cartDiv5 = document.createElement("div");
  const finalTotal1 = document.createElement("p");
  finalTotal1.innerText = `Total:`;
  const finalTotal2 = document.createElement("p");
  finalTotal2.innerText = `R$${grandTotal.toFixed(2).replace(".", ",")}`;
  cartDiv5.append(finalTotal1, finalTotal2);
  cartDiv5.setAttribute("class", "cartFontDiv");

  const cartDiv6 = document.createElement("div");
  const weightLabel = document.createElement("p");
  weightLabel.innerText = `Peso Total:`;
  const weightValue = document.createElement("p");
  weightValue.innerText = `${weight.toFixed(2)} kg`;
  cartDiv6.append(weightLabel, weightValue);
  cartDiv6.setAttribute("class", "cartFontDiv");

  parent.append(cartDiv1, cartDiv2, cartDiv3, cartDiv4, cartDiv6, cartDiv5);
};
