import { shortString, numberWithCommas } from "../extraFunctions.js";
import { setItem } from "../localStorage.js";

export const appendCartData = (data, parent, orderTotalParent) => {
  console.log("data ==> ", data);
  parent.innerHTML = null;

  // Envolve a tabela em um wrapper com classe "table-responsive"
  const wrapperDiv = document.createElement("div");
  wrapperDiv.setAttribute("class", "table-responsive");

  const table = document.createElement("table");
  table.setAttribute(
    "class",
    "table table-bordered table-hover table-sm text-start table-striped"
  );
  table.style =
    "width: 100%; text-align: center; font-size: 22px; min-width: 600px;";

  const thead = document.createElement("thead");
  thead.innerHTML = `
    <tr class="table-dark">
      <th class="text-left">Produto</th>
      <th>Peso (kg)</th>
      <th>Preço</th>
      <th></th>
    </tr>
  `;
  table.appendChild(thead);

  const tbody = document.createElement("tbody");

  data.forEach((item, index) => {
    const { brand, thumb, category, subcategory, price, weight } = item;

    const tr = document.createElement("tr");

    // Nome + thumb juntos em uma célula
    const tdTitle = document.createElement("td");
    const productDiv = document.createElement("div");
    const infoDiv = document.createElement("div");
    const centerContent = "display: flex; align-items: center; gap: 10px;";
    productDiv.style = centerContent;

    const img = document.createElement("img");
    img.src = thumb;
    img.style =
      "width: 80px; height: 80px; object-fit: cover; border-radius: 6px;";

    const titleText = document.createElement("span");
    titleText.textContent = shortString(brand, 50).toUpperCase();
    titleText.style = "font-weight: 600;";
    const descriptionText = document.createElement("span");
    descriptionText.textContent = `${category}, ${subcategory}`;
    descriptionText.style = "font-weight: 400; font-size: 14px;";
    infoDiv.style = "display: flex; flex-direction: column;";
    infoDiv.append(titleText, descriptionText);
    productDiv.append(img, infoDiv);

    tdTitle.appendChild(productDiv);

    // Preço
    const tdPrice = document.createElement("td");
    const safePrice = typeof price === "number" ? price : 0;
    tdPrice.textContent = `R$ ${numberWithCommas(safePrice.toFixed(2))}`;

    // Peso
    const tdWeight = document.createElement("td");
    const safeWeight = typeof weight === "number" ? weight : 0;
    tdWeight.textContent = `${safeWeight.toFixed(2)}kg`;

    // Botão de remover
    const tdRemove = document.createElement("td");
    const removeBtn = document.createElement("button");
    removeBtn.textContent = "Remover";
    removeBtn.setAttribute("class", "btn btn-sm btn-danger");
    removeBtn.addEventListener("click", () => {
      data.splice(index, 1);
      setItem("cartData", data);
      const toast = new bootstrap.Toast(document.getElementById("liveToast"));
      toast.show();
      appendCartData(data, parent, orderTotalParent);
      getTotalOrderAmount(data, orderTotalParent);
    });
    tdRemove.appendChild(removeBtn);

    [tdPrice, tdWeight].forEach((td) => {
      td.style.verticalAlign = "middle";
      td.style.textAlign = "left";
    });

    tdRemove.style.verticalAlign = "middle";
    tdRemove.style.textAlign = "center";
    tdRemove.style.width = "10%";

    tr.append(tdTitle, tdWeight, tdPrice, tdRemove);
    tbody.appendChild(tr);
  });

  table.appendChild(tbody);
  wrapperDiv.appendChild(table);
  parent.appendChild(wrapperDiv);
};

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
  cartTotal2.innerText = `R$${numberWithCommas(total.toFixed(2))}`;
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
  finalTotal2.innerText = `R$${numberWithCommas(grandTotal)}`;
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
