import { shortString } from "./extraFunctions.js";
import { setItem } from "./localStorage.js";

export const appendData = (
  data,
  parent,
  handleAddToCart,
  handleRemoveOne,
  cartData = []
) => {
  parent.innerHTML = null;

  data.map((item) => {
    const {
      id,
      brand,
      thumb,
      price,
      category,
      subcategory,
      weight,
      maxUnitsPerClient,
    } = item;

    // Verifica quantas unidades do item já estão no carrinho
    const carrinhoItem = cartData.find((prod) => prod.id === id);
    const qtdeAtual = carrinhoItem ? carrinhoItem.qty : 0;

    const div = document.createElement("div");
    div.setAttribute("id", "foodDiv");

    const imgDiv = document.createElement("div");
    imgDiv.setAttribute("id", "imgDiv");

    const detailsDiv = document.createElement("div");
    detailsDiv.setAttribute("id", "detailsDiv");

    const btnDiv = document.createElement("div");
    btnDiv.setAttribute("id", "btnDiv");
    btnDiv.style = "display: flex; flex-direction: row;";
    const img = document.createElement("img");
    img.src = `${window.location.origin}${window.location.pathname.replace(
      /\/[^\/]*$/,
      "/"
    )}${thumb.replace(/^(\.\.\/)+/, "")}`;
    console.log("img.src ==> ", img.src);
    img.style = "width:100%;";
    const name = document.createElement("p");
    name.textContent = shortString(brand, 20).toUpperCase();
    name.style = "font-weight:600; font-size:17px";

    const foodCategory = document.createElement("p");
    foodCategory.textContent = `${category} > ${subcategory}`;
    foodCategory.style = "color:gray; font-size:14px; font-weight:400";

    const rate = document.createElement("p");
    rate.textContent = `R$${price.toFixed(2).replace(".", ",")}`;
    rate.style = "color:red; font-weight:600; font-size:22px";

    const pesoEl = document.createElement("p");
    pesoEl.textContent = `Peso: ${weight}kg`;
    pesoEl.style = "color:gray; font-size:14px";

    const limiteEl = document.createElement("p");
    limiteEl.textContent = `Qtd: ${qtdeAtual} / ${maxUnitsPerClient}`;
    limiteEl.style = "color:gray; font-size:14px";

    const addBtn = document.createElement("button");
    console.log("addBtn ==> ", addBtn);
    addBtn.textContent = "+";
    addBtn.setAttribute("class", "btn btn-sm btn-success mx-1");
    addBtn.disabled = true;
    addBtn.addEventListener("click", () => {
      handleAddToCart(item);
    });

    const removeBtn = document.createElement("button");
    removeBtn.textContent = "-";
    removeBtn.setAttribute("class", "btn btn-sm btn-danger mx-1");
    removeBtn.disabled = qtdeAtual <= 0;
    removeBtn.addEventListener("click", () => {
      handleRemoveOne(item);
    });

    btnDiv.append(removeBtn, addBtn);
    detailsDiv.append(name, foodCategory, rate, pesoEl, limiteEl);
    div.append(imgDiv, img, detailsDiv, btnDiv);
    parent.append(div);
  });
};
