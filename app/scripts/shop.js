import { notify } from "../components/notify.js";
import { appendData } from "../functions/appendData.js";
import { showTotal } from "../functions/showTotal.js";
import { appendCartData } from "../functions/appendCartData.js";
import updateCartSummaryBar from "../functions/updateCartSummaryBar.js";
import { API_URL } from "../api/baseUrl.js";
import { apiFetch } from "../api/apiFetch.js";
console.log("API_URL ==> ", API_URL);
let cachedData = null;
let currentOrder = "asc"; // padrão

const display = document.getElementById("display");
const totalFood = document.getElementById("totalFood");
const notifyDiv = document.getElementById("notifyDiv");
const trendingBtn = document.getElementsByName("trendingBtn");

const qtdeTotal = document.getElementById("qtdeTotal");
const clearCartBtn = document.getElementById("clearCartBtn");
const pesoInfo = document.getElementById("pesoTotalInfo");
const orderSelectContainer = document.getElementById("orderSelectContainer");

const PESO_MAX = 12;
let dadosCarrinho = JSON.parse(localStorage.getItem("dadosCarrinho")) || [];
let currentCategory = {
    "nome": "Alimentos",
    "slug": "alimentos",
};

const urlParams = new URLSearchParams(window.location.search);


let slugPenitenciaria =
  urlParams.size === 0 ? null : decodeURIComponent(urlParams.get("p"));

if (!slugPenitenciaria) {
  window.alert(`Penitenciária não informada. Redirecionando...`);
  window.location.href = "/";
}
console.log("slugPenitenciaria")
const orderBySelect = document.getElementById("orderBySelect");
if (orderBySelect) {
  orderBySelect.addEventListener("change", (e) => {
    currentOrder = e.target.value;
    displayItems(slugPenitenciaria, currentCategory);
  });
} 

function showNotification(type, message) {
  const { wrapper, id } = notify(type, message);
  notifyDiv.appendChild(wrapper);

  const toastEl = document.getElementById(id);
  const toast = bootstrap.Toast.getOrCreateInstance(toastEl);
  console.log("toast ==> ", toast);
  toast.show();
}

clearCartBtn.addEventListener("click", () => {
  if (dadosCarrinho.length === 0) {
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




async function displayItems(slug, categoria) {
  const spinner = document.getElementById("loadingSpinner");

  let data;

  if (!cachedData) {
    if (spinner) spinner.style.display = "block";

    try {
      // Simula tempo de carregamento apenas quando buscando dados
      // await new Promise((resolve) => setTimeout(resolve, 800));
      const response = await apiFetch(
        `/product-list?categoria=${categoria?.slug}`,
        {
          method: "GET",
          headers: {
            "Content-Type": "application/json",
          },
        },
         true
      );
      if (!response.ok) {
        throw new Error(`Erro HTTP: ${response.status}`);
      }
      
      const text = await response.text();

      data = JSON.parse(text);
      console.log("data ==> ", data.content.length);

      cachedData = data;
    } catch (error) {
      console.error("Erro ao buscar produtos:", error);
    } finally {
      if (spinner) spinner.style.display = "none";
    }
  } else {
    data = cachedData;
  }
 
  
  if (!data || !Array.isArray(data.content)) {
    console.warn("Erro ao carregar produtos da API.");
    return;
  }
        console.log("data.content",  data.content)    
const items = data.content.filter(
  (item) =>
    item?.categoria?.toLowerCase() === categoria?.nome?.toLowerCase()
);

console.log("ITEMS", items)
  items.sort((a, b) => {
    const nameA = a.subcategoria?.toLowerCase() || "";
    const nameB = b.subcategoria?.toLowerCase() || "";

    if (currentOrder === "asc") {
      return nameA.localeCompare(nameB);
    } else {
      return nameB.localeCompare(nameA);
    }
  });
  if (items.length === 0) {
    display.innerHTML = `
      <div class="alert alert-warning text-center fw-bold my-4" role="alert">
        Nenhum produto encontrado para <strong>${categoria.nome}</strong> na <strong>${slug}</strong>.
      </div>
    `;

    if (orderSelectContainer) orderSelectContainer.innerHTML = "";
    return;
  }

  items.forEach((item) => {
    item.peso = item.peso || parseFloat((Math.random() * 2 + 1).toFixed(2));
  });

  appendData(items, display, handleAddToCart, handleRemoveOne, dadosCarrinho);
  showTotal(dadosCarrinho, totalFood);
  updateCartSummaryBar(dadosCarrinho);
  updatePesoInfo();
}

function handleAddToCart(item) {
  const itemExiste = dadosCarrinho.find((i) => i.id === item.id);
  const currentQtde = itemExiste?.qtde || 0;

  if (currentQtde >= item.maximo_por_cliente) {
    notifyDiv.innerHTML = notify(
      "warning",
      `Limite de ${item.maximo_por_cliente} unidades por cliente para este item.`
    );
    showNotification("success", "Item adicionado com sucesso!");

    return;
  }

  const pesoTotal = calculateCartWeight();
  const pesoAcrescentado = item.peso || 0;
  if (pesoTotal + pesoAcrescentado > PESO_MAX) {
    notifyDiv.innerHTML = notify(
      "danger",
      `Limite de peso excedido! Máximo: ${PESO_MAX}kg`
    );
    showToast();
    return;
  }

  if (itemExiste) {
    itemExiste.qtde += 1;
  } else {
    dadosCarrinho.push({ ...item, qtde: 1 });
  }

  saveCart();
  notifyDiv.innerHTML = notify("success", "Item adicionado com sucesso!");
  showToast();

  displayItems(slugPenitenciaria, currentCategory);
}

function handleRemoveOne(item) {
  const index = dadosCarrinho.findIndex((i) => i.id === item.id);
  if (index !== -1) {
    dadosCarrinho[index].qtde -= 1;
    if (dadosCarrinho[index].qtde <= 0) {
      dadosCarrinho.splice(index, 1);
    }

    saveCart();
    displayItems(slugPenitenciaria, currentCategory);
  }
}

function saveCart() {

  localStorage.setItem("dadosCarrinho", JSON.stringify(dadosCarrinho));
  showTotal(dadosCarrinho, totalFood);
  updateCartSummaryBar(dadosCarrinho);
  updatePesoInfo();
}
// Inicialização

function clearCartData() {
  dadosCarrinho = [];
  saveCart();
  appendCartData([], display, qtdeTotal);
  displayItems(slugPenitenciaria, currentCategory);
}

function calculateCartWeight() {
  return dadosCarrinho.reduce(
    (acc, curr) => acc + (curr.peso || 0) * (curr.qtde || 1),
    0
  );
}

function updatePesoInfo() {
  // Inicialização
console.log("teste");
  const pesoTotal = calculateCartWeight();
  if (pesoInfo) {
    pesoInfo.innerText = `Peso total do carrinho: ${pesoTotal.toFixed(
      2
    )}kg (máximo: ${PESO_MAX}kg)`;
  } else {
    const insertAfter = document.querySelector("#totalFood");
    if (insertAfter) {
      const pesoDiv = document.createElement("div");
      pesoDiv.id = "pesoTotalInfo";
      pesoDiv.className = "alert alert-info mt-2 fw-bold";
      pesoDiv.innerText = `Peso total do carrinho: ${pesoTotal.toFixed(
        2
      )}kg (máximo: ${PESO_MAX}kg)`;
      insertAfter.parentElement.insertBefore(pesoDiv, insertAfter.nextSibling);
    }
  }
}

function showToast() {
  const toastEl = document.getElementById("liveToast");
  if (toastEl) new bootstrap.Toast(toastEl).show();
}
async function carregarCategorias() {
console.log("carregarCategorias");
  
  const container = document.getElementById("trendingBtnDiv");
  container.innerHTML = `<div class="text-muted">Carregando categorias...</div>`;

  try {
    const res = await apiFetch(`/get-categories`);
    const json = await res.json();

    if (!json.success || !Array.isArray(json.categories)) throw new Error("Formato inválido");

    container.innerHTML = "";

json.categories
  .filter(c => c.qtde_produtos > 0)
  .forEach(cat => {
    console.log("cat ==> ", cat);
    const btn = document.createElement("button");
    btn.setAttribute("nome", "trendingBtn"); // <-- correção aqui
    btn.type = "button";
    btn.className = "btn btn-outline-primary";
    btn.value = cat.nome;
    btn.textContent = cat.nome === "Uncategorized" ? "Outros" : cat.nome;
    
    // Adiciona classe ativa se for a categoria atual
    if (currentCategory.nome === cat.nome) {
      btn.classList.add("active", "fw-bold");
    }
    
    btn.addEventListener("click", () => {
      // Remove "active" de todos os botões antes de aplicar no atual
      document.querySelectorAll('button[nome="trendingBtn"]').forEach(b => {
        b.classList.remove("active", "fw-bold");
      });

      btn.classList.add("active", "fw-bold");
      currentCategory = { nome: cat.nome, slug: cat.slug };
      console.log("currentCategory ==> ", currentCategory);
      cachedData = null;
      displayItems(slugPenitenciaria, currentCategory);
    });

    container.appendChild(btn);
  });


  } catch (err) {
    console.error("Erro ao carregar categorias:", err);
    container.innerHTML = `<div class="text-danger">Erro ao carregar categorias.</div>`;
  }
}

// Inicialização
console.log("teste de inicialização");
carregarCategorias(); 
displayItems(slugPenitenciaria, currentCategory);
updatePesoInfo();


