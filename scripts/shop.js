import { notify } from "../components/notify.js";
import { appendData } from "../functions/appendData.js";
import { showTotal } from "../functions/showTotal.js";
import { appendCartData } from "../functions/appendCartData.js";
import updateCartSummaryBar from "../functions/updateCartSummaryBar.js";
import { API_URL } from "./baseUrl.js";
console.log("API_URL ==> ", API_URL);
let cachedData = null;
let currentOrder = "asc"; // padrão

const display = document.getElementById("display");
const totalFood = document.getElementById("totalFood");
const notifyDiv = document.getElementById("notifyDiv");
const trendingBtn = document.getElementsByName("trendingBtn");

const totalAmount = document.getElementById("totalAmount");
const clearCartBtn = document.getElementById("clearCartBtn");
const pesoInfo = document.getElementById("pesoTotalInfo");
const orderSelectContainer = document.getElementById("orderSelectContainer");

const MAX_WEIGHT = 12;
let cartData = JSON.parse(localStorage.getItem("cartData")) || [];
let currentCategory = "Alimentos";

const urlParams = new URLSearchParams(window.location.search);

let currentPrison =
  urlParams.size === 0 ? null : decodeURIComponent(urlParams.get("p"));
console.log("currentPrison ==> ", currentPrison);
if (!currentPrison) {
  window.alert(`Penitenciária não informada. Redirecionando...`);
  window.location.href = "/";
}

const orderBySelect = document.getElementById("orderBySelect");
if (orderBySelect) {
  orderBySelect.addEventListener("change", (e) => {
    currentOrder = e.target.value;
    displayItems(currentPrison, currentCategory);
  });
}
trendingBtn.forEach((btn) => {
  btn.addEventListener("click", () => {
    currentCategory = btn.value;
    displayItems(currentPrison, currentCategory);
  });
});
function showNotification(type, message) {
  const { wrapper, id } = notify(type, message);
  notifyDiv.appendChild(wrapper);

  const toastEl = document.getElementById(id);
  const toast = bootstrap.Toast.getOrCreateInstance(toastEl);
  console.log("toast ==> ", toast);
  toast.show();
}

clearCartBtn.addEventListener("click", () => {
  if (cartData.length === 0) {
    notifyDiv.innerHTML = notify("info", "O carrinho já está vazio.");
    showToast();
    return;
  }

  const confirmClear = confirm("Tem certeza que deseja limpar o carrinho?");
  if (confirmClear) {
    clearCartData();
    showNotification("success", "Item adicionado com sucesso!");
  }
});

async function displayItems(slug, category = "Alimentos") {
  const spinner = document.getElementById("loadingSpinner");

  let data;

  if (!cachedData) {
    if (spinner) spinner.style.display = "block";

    try {
      // Simula tempo de carregamento apenas quando buscando dados
      // await new Promise((resolve) => setTimeout(resolve, 800));
      const response = await fetch(
        `${API_URL}/produtos/por-penitenciaria?slug=${currentPrison}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
        }
      );
      if (!response.ok) {
        throw new Error(`Erro HTTP: ${res.status}`);
      }
      const text = await response.text();

      data = JSON.parse(text);
      console.log("data ==> ", data);

      cachedData = data;
    } catch (error) {
      console.error("Erro ao buscar produtos:", error);
    } finally {
      if (spinner) spinner.style.display = "none";
    }
  } else {
    data = cachedData;
  }
  const prisonName = data.content[0].prison;
  if (!data || !Array.isArray(data.content)) {
    console.warn("Erro ao carregar produtos da API.");
    return;
  }

  const items = data.content.filter(
    (item) =>
      item.category?.trim().toLowerCase() === category.trim().toLowerCase()
  );
  items.sort((a, b) => {
    const nameA = a.subcategory?.toLowerCase() || "";
    const nameB = b.subcategory?.toLowerCase() || "";

    if (currentOrder === "asc") {
      return nameA.localeCompare(nameB);
    } else {
      return nameB.localeCompare(nameA);
    }
  });

  if (items.length === 0) {
    display.innerHTML = `
      <div class="alert alert-warning text-center fw-bold my-4" role="alert">
        Nenhum produto encontrado para <strong>${category}</strong> na <strong>${prisonName}</strong>.
      </div>
    `;

    if (orderSelectContainer) orderSelectContainer.innerHTML = "";
    return;
  }

  items.forEach((item) => {
    item.weight = item.weight || parseFloat((Math.random() * 2 + 1).toFixed(2));
  });

  appendData(items, display, handleAddToCart, handleRemoveOne, cartData);
  showTotal(cartData, totalFood);
  updateCartSummaryBar(cartData);
  updatePesoInfo();
}

function handleAddToCart(item) {
  const existing = cartData.find((i) => i.id === item.id);
  const currentQty = existing?.qty || 0;

  if (currentQty >= item.maxUnitsPerClient) {
    notifyDiv.innerHTML = notify(
      "warning",
      `Limite de ${item.maxUnitsPerClient} unidades por cliente para este item.`
    );
    showNotification("success", "Item adicionado com sucesso!");

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

  displayItems(currentPrison, currentCategory);
}

function handleRemoveOne(item) {
  const index = cartData.findIndex((i) => i.id === item.id);
  if (index !== -1) {
    cartData[index].qty -= 1;
    if (cartData[index].qty <= 0) {
      cartData.splice(index, 1);
    }

    saveCart();
    displayItems(currentPrison, currentCategory);
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
  displayItems(currentPrison, currentCategory);
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
displayItems(currentPrison, currentCategory);
updatePesoInfo();
