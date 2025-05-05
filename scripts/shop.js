import { notify } from "../components/notify.js";
import { appendData } from "../functions/appendData.js";
import { showTotal } from "../functions/showTotal.js";
import { appendCartData } from "../functions/appendCartData.js";
import mock from "../mock/produtos_clickjumbo.js";
import { setItem } from "../functions/localStorage.js";
import updateCartSummaryBar from "../functions/updateCartSummaryBar.js";


const display = document.getElementById("display");
const totalFood = document.getElementById("totalFood");
const notifyDiv = document.getElementById("notifyDiv");
const trendingBtn = document.getElementsByName("trendingBtn"); // Botões de categoria
const penitenciariaSelect = document.getElementById("penitenciariaSelect"); // Select de penitenciária
const totalAmount = document.getElementById("totalAmount");
const MAX_WEIGHT = 12;
let cartData = JSON.parse(localStorage.getItem("cartData")) || []; // Recupera o carrinho do localStorage, ou cria um vazio
console.log("cartData ==> ", cartData);
let currentCategory = "Alimentos"; // Categoria inicial
let currentPenitenciaria = "penitenciariaA"; // Penitenciária inicial

notifyDiv.innerHTML = notify("success", "Item is added to the bag");

// Adiciona evento aos botões de categoria
for (let btn of trendingBtn) {
  btn.addEventListener("click", () => {
    currentCategory = btn.value; // Altera a categoria com base no botão
    displayItems(currentPenitenciaria, currentCategory);
  });
}

// Adiciona evento ao select de penitenciária
penitenciariaSelect.addEventListener("change", (e) => {
  currentPenitenciaria = e.target.value; // Altera a penitenciária selecionada
  displayItems(currentPenitenciaria, currentCategory);
});
function handleRemoveOne(item) {
  const index = cartData.findIndex((i) => i.id === item.id);
  if (index !== -1) {
    cartData[index].qty -= 1;
    if (cartData[index].qty <= 0) cartData.splice(index, 1);
    setItem("cartData", cartData);
    displayItems(currentPenitenciaria, currentCategory); // ou qualquer função que atualize a tela
    showTotal(cartData, totalFood);
    updateCartSummaryBar(cartData);

  }
}

function displayItems(penitenciaria, category="alimentos") {
  const items =
    mock[penitenciaria]?.filter((item) => item.category === category) || [];

  if (!Array.isArray(items) || items.length === 0) {
    console.warn(`Nenhum dado encontrado para ${category} na ${penitenciaria}`);
    return;
  }

  // Adiciona peso simulado se ainda não houver
  items.forEach((item) => {
    item.weight = item.weight || parseFloat((Math.random() * 2 + 1).toFixed(2)); // entre 1kg e 3kg
  });

  appendData(items, display, handleAddToCart, handleRemoveOne, cartData);
  showTotal(cartData, totalFood);
  updateCartSummaryBar(cartData);

}

function handleAddToCart(item) {
  // Calcula o peso total do carrinho considerando as quantidades
  const totalWeight = cartData.reduce(
    (acc, curr) => acc + (curr.weight || 0) * (curr.qty || 1),
    0
  );

  // Verifica se o peso total mais o peso do item ultrapassa o limite
  if (totalWeight + (item.weight || 0) > MAX_WEIGHT) {
    notifyDiv.innerHTML = notify(
      "danger",
      `Limite de peso excedido! Máximo: ${MAX_WEIGHT}kg`
    );
    const toastEl = document.getElementById("liveToast");
    if (toastEl) new bootstrap.Toast(toastEl).show();
    return; // Impede a adição se o limite de peso for atingido
  }

  // Verifica se o item já está no carrinho
  const existing = cartData.find((i) => i.id === item.id);
  if (existing) {
    existing.qty = (existing.qty || 0) + 1;
  } else {
    cartData.push({ ...item, qty: 1 }); // Garante que a quantidade começa com 1
  }

  // Salva o carrinho no localStorage
  localStorage.setItem("cartData", JSON.stringify(cartData));
  notifyDiv.innerHTML = notify("success", "Item adicionado com sucesso!");
  const toastEl = document.getElementById("liveToast");
  if (toastEl) new bootstrap.Toast(toastEl).show();

  // Re-renderiza o carrinho e as informações de total
  displayItems(currentPenitenciaria, currentCategory);
  showTotal(cartData, totalFood);
  updateCartSummaryBar(cartData);

  // Atualiza o peso total do carrinho
  const pesoInfo = document.getElementById("pesoTotalInfo");
  if (pesoInfo) {
    const pesoTotal = cartData.reduce(
      (acc, curr) => acc + (curr.weight || 0) * (curr.qty || 1),
      0
    );
    pesoInfo.innerText = `Peso total do carrinho: ${pesoTotal.toFixed(
      2
    )}kg (máximo: ${MAX_WEIGHT}kg)`;
  }
}


// Inicializa com a penitenciária e categoria padrão
displayItems(currentPenitenciaria, currentCategory);

// Exibe peso total na interface
const pesoInfo = document.getElementById("pesoTotalInfo");
if (pesoInfo) {
  const pesoTotal = cartData.reduce(
    (acc, curr) => acc + (curr.weight || 0) * (curr.qty || 1),
    0
  );
  
  pesoInfo.innerText = `Peso total do carrinho: ${pesoTotal.toFixed(
    2
  )}kg (máximo: ${MAX_WEIGHT}kg)`;
} else {
  const insertAfter = document.querySelector("#totalFood");
  if (insertAfter) {
    const pesoDiv = document.createElement("div");
    pesoDiv.id = "pesoTotalInfo";
    pesoDiv.className = "alert alert-info mt-2 fw-bold";
    const pesoTotal = cartData.reduce(
      (acc, curr) => acc + (curr.weight || 0),
      0
    );
    pesoDiv.innerText = `Peso total do carrinho: ${pesoTotal.toFixed(
      2
    )}kg (máximo: ${MAX_WEIGHT}kg)`;
    insertAfter.parentElement.insertBefore(pesoDiv, insertAfter.nextSibling);
  }
}
const prisonSelect = document.getElementById("penitenciariaSelect");

prisonSelect.addEventListener("change", () => {
  if (cartData.length > 0) {
    const confirmClear = confirm(
      "Você está trocando de penitenciária. Isso irá limpar seu carrinho. Deseja continuar?"
    );

    if (!confirmClear) {
      // Força voltar ao valor anterior no select
      penitenciariaSelect.value = currentPenitenciaria;
      return;
    }

    cartData = []; // Limpa o carrinho global
    localStorage.setItem("cartData", JSON.stringify(cartData)); // Atualiza o storage

    // Atualiza a exibição
    appendCartData([], display, totalAmount);
    displayItems(currentPenitenciaria, currentCategory);
    showTotal(cartData, totalFood);
    updateCartSummaryBar(cartData);

    const pesoInfo = document.getElementById("pesoTotalInfo");
    if (pesoInfo) {
      pesoInfo.innerText = `Peso total do carrinho: 0.00kg (máximo: ${MAX_WEIGHT}kg)`;
    }
  }

  currentPenitenciaria = penitenciariaSelect.value;
  displayItems(currentPenitenciaria, currentCategory);
});

clearCartBtn.addEventListener("click", () => {
  console.log("click")
  if (cartData.length === 0) {
    notifyDiv.innerHTML = notify("info", "O carrinho já está vazio.");
    const toastEl = document.getElementById("liveToast");
    if (toastEl) new bootstrap.Toast(toastEl).show();
    return;
  }

  const confirmClear = confirm("Tem certeza que deseja limpar o carrinho?");
  if (!confirmClear) return;

  cartData = [];
  localStorage.setItem("cartData", JSON.stringify(cartData));

  appendCartData([], display, totalAmount); // se você quiser limpar visualmente a lista do carrinho (caso esteja exibindo uma)
  displayItems(currentPenitenciaria, currentCategory); // Re-renderiza a listagem
  showTotal(cartData, totalFood);
  updateCartSummaryBar(cartData); // Atualiza o rodapé
  const pesoInfo = document.getElementById("pesoTotalInfo");
  if (pesoInfo) {
    pesoInfo.innerText = `Peso total do carrinho: 0.00kg (máximo: ${MAX_WEIGHT}kg)`;
  }

  notifyDiv.innerHTML = notify("success", "Carrinho limpo com sucesso!");
  const toastEl = document.getElementById("liveToast");
  if (toastEl) new bootstrap.Toast(toastEl).show();
});
