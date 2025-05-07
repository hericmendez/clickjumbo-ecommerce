import { notify } from "../components/notify.js";
import { appendData } from "../functions/appendData.js";
import { showTotal } from "../functions/showTotal.js";
import { appendCartData } from "../functions/appendCartData.js";
import { fetchedData } from "../mock/fetchedData.js";
import updateCartSummaryBar from "../functions/updateCartSummaryBar.js";

let cachedData = null;

const display = document.getElementById("display");
const totalFood = document.getElementById("totalFood");
const notifyDiv = document.getElementById("notifyDiv");
const trendingBtn = document.getElementsByName("trendingBtn");
const penitenciariaSelect = document.getElementById("penitenciariaSelect");
const totalAmount = document.getElementById("totalAmount");
const clearCartBtn = document.getElementById("clearCartBtn");
const pesoInfo = document.getElementById("pesoTotalInfo");

const MAX_WEIGHT = 12;
let cartData = JSON.parse(localStorage.getItem("cartData")) || [];
let currentCategory = "Alimentos";
let currentPenitenciaria = "Penitenciária A";

notifyDiv.innerHTML = notify("success", "Item is added to the bag");

trendingBtn.forEach((btn) => {
  btn.addEventListener("click", () => {
    currentCategory = btn.value;
    displayItems(currentPenitenciaria, currentCategory);
  });
});

penitenciariaSelect.addEventListener("change", () => {
  if (cartData.length > 0) {
    const confirmClear = confirm(
      "Você está trocando de penitenciária. Isso irá limpar seu carrinho. Deseja continuar?"
    );
    if (!confirmClear) {
      penitenciariaSelect.value = currentPenitenciaria;
      return;
    }

    clearCartData();
  }

  currentPenitenciaria = penitenciariaSelect.value;
  displayItems(currentPenitenciaria, currentCategory);
});

clearCartBtn.addEventListener("click", () => {
  if (cartData.length === 0) {
    notifyDiv.innerHTML = notify("info", "O carrinho já está vazio.");
    showToast();
    return;
  }

  const confirmClear = confirm("Tem certeza que deseja limpar o carrinho?");
  if (confirmClear) {
    clearCartData();
    notifyDiv.innerHTML = notify("success", "Carrinho limpo com sucesso!");
    showToast();
  }
});

async function displayItems(penitenciaria, category = "Alimentos") {
  try {
    let data;

    // Se já carregamos uma vez, reutiliza
    if (cachedData) {
      data = cachedData;
    } else {
      // Modo real:
      const response = await fetch(
        "http://clickjumbo.local/wp-json/clickjumbo/v1/produtos"
      );
      data = await response.json();

      // Modo mock:
      // data = fetchedData;
      cachedData = data; // Armazena para uso futuro
    }

    if (!data || !Array.isArray(data.content)) {
      console.warn("Erro ao carregar produtos da API.");
      return;
    }

    const items = data.content.filter(
      (item) =>
        item.prison?.trim().toLowerCase() ===
          penitenciaria.trim().toLowerCase() &&
        item.category?.trim().toLowerCase() === category.trim().toLowerCase()
    );

    if (items.length === 0) {
      console.warn(
        `Nenhum produto encontrado para ${category} na ${penitenciaria}`
      );
      return;
    }

    // Atribui peso falso se não tiver (apenas se necessário)
    items.forEach((item) => {
      item.weight =
        item.weight || parseFloat((Math.random() * 2 + 1).toFixed(2));
    });

    appendData(items, display, handleAddToCart, handleRemoveOne, cartData);
    showTotal(cartData, totalFood);
    updateCartSummaryBar(cartData);
    updatePesoInfo();
  } catch (error) {
    console.error("Erro ao buscar produtos:", error);
  }
  console.log("cachedData ==> ", cachedData);
}

function handleAddToCart(item) {
  const existing = cartData.find((i) => i.id === item.id);
  const currentQty = existing?.qty || 0;

  if (currentQty >= item.maxUnitsPerClient) {
    notifyDiv.innerHTML = notify(
      "warning",
      `Limite de ${item.maxUnitsPerClient} unidades por cliente para este item.`
    );
    showToast();
    return;
  }

  const totalWeight = calculateCartWeight();
  const addedWeight = item.weight || 0;
  if (totalWeight + addedWeight > MAX_WEIGHT) {
    notifyDiv.innerHTML = notify(
      "danger",
      `Limite de peso excedido! Máximo: ${MAX_WEIGHT}kg`
    );
    showToast();
    return;
  }

  if (existing) {
    existing.qty += 1;
  } else {
    cartData.push({ ...item, qty: 1 });
  }

  saveCart();
  notifyDiv.innerHTML = notify("success", "Item adicionado com sucesso!");
  showToast();

  displayItems(currentPenitenciaria, currentCategory);
}

function handleRemoveOne(item) {
  const index = cartData.findIndex((i) => i.id === item.id);
  if (index !== -1) {
    cartData[index].qty -= 1;
    if (cartData[index].qty <= 0) {
      cartData.splice(index, 1);
    }

    saveCart();
    displayItems(currentPenitenciaria, currentCategory);
  }
}

function saveCart() {
  localStorage.setItem("cartData", JSON.stringify(cartData));
  showTotal(cartData, totalFood);
  updateCartSummaryBar(cartData);
  updatePesoInfo();
}

function clearCartData() {
  cartData = [];
  saveCart();
  appendCartData([], display, totalAmount);
  displayItems(currentPenitenciaria, currentCategory);
}

function calculateCartWeight() {
  return cartData.reduce(
    (acc, curr) => acc + (curr.weight || 0) * (curr.qty || 1),
    0
  );
}

function updatePesoInfo() {
  const pesoTotal = calculateCartWeight();
  if (pesoInfo) {
    pesoInfo.innerText = `Peso total do carrinho: ${pesoTotal.toFixed(
      2
    )}kg (máximo: ${MAX_WEIGHT}kg)`;
  } else {
    const insertAfter = document.querySelector("#totalFood");
    if (insertAfter) {
      const pesoDiv = document.createElement("div");
      pesoDiv.id = "pesoTotalInfo";
      pesoDiv.className = "alert alert-info mt-2 fw-bold";
      pesoDiv.innerText = `Peso total do carrinho: ${pesoTotal.toFixed(
        2
      )}kg (máximo: ${MAX_WEIGHT}kg)`;
      insertAfter.parentElement.insertBefore(pesoDiv, insertAfter.nextSibling);
    }
  }
}

function showToast() {
  const toastEl = document.getElementById("liveToast");
  if (toastEl) new bootstrap.Toast(toastEl).show();
}

// Inicialização
displayItems(currentPenitenciaria, currentCategory);
updatePesoInfo();
