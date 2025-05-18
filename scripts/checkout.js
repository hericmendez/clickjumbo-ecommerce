// checkout.js atualizado para preencher dados do envio, controlar exibição condicional de campos e finalizar pedido

import { API_URL } from "./baseUrl.js";
import { getItem, removeItem } from "../functions/localStorage.js";

const cartData = getItem("cartData") || [];
const cartValidation = getItem("cartValidated");
const freteInfo = getItem("freteData");
const token = getItem("token");

const cartItemsContainer = document.getElementById("cart-items");
const cartCount = document.getElementById("cart-count");
const cartSummary = document.getElementById("cart-summary");
const shippingSummary = document.getElementById("shipping-summary");
const submitBtn = document.getElementById("submitBtn");
const form = document.getElementById("checkout-form");

const modal = new bootstrap.Modal(document.getElementById("paymentModal"));
const modalBody = document.getElementById("paymentModalBody");
const confirmBtn = document.getElementById("confirmPaymentBtn");

function renderCart() {
  let total = 0;
  let weight = 0;
  cartItemsContainer.innerHTML = "";
  cartSummary.innerHTML = "";

  cartData.forEach((item) => {
    const li = document.createElement("li");
    li.className = "list-group-item d-flex justify-content-between lh-sm";
    li.innerHTML = `
      <div>
        <strong>${item.name}</strong><br />
        <small>${item.weight || 0}kg x ${item.qty}</small>
      </div>
      <span>R$ ${(item.price * item.qty).toFixed(2)}</span>
    `;
    cartItemsContainer.appendChild(li);
    total += item.price * item.qty;
    weight += item.weight * item.qty;
  });

  cartCount.textContent = cartData.length;

  const items = [
    [`Peso total`, `${weight.toFixed(2)} kg`],
    [`Frete (${freteInfo?.metodo})`, `R$ ${freteInfo?.valor.toFixed(2)}`],
    [`Total`, `R$ ${(total + freteInfo?.valor).toFixed(2)}`],
  ];
  items.forEach(([label, value]) => {
    const li = document.createElement("li");
    li.className = "list-group-item d-flex justify-content-between lh-sm";
    li.innerHTML = `<span>${label}</span><strong>${value}</strong>`;
    cartSummary.appendChild(li);
  });
}

function renderShipping() {
  shippingSummary.innerHTML = "";
  const dados = [
    [`Método`, freteInfo?.metodo || "PAC"],
    [`CEP destino`, freteInfo?.cep_destino || "-"],
    [`CEP origem`, freteInfo?.cep_origem || "-"],
    [`Valor`, `R$ ${freteInfo?.valor?.toFixed(2)}`],
    [`Prazo`, freteInfo?.prazo || "-"],
  ];
  dados.forEach(([label, value]) => {
    const li = document.createElement("li");
    li.className = "list-group-item d-flex justify-content-between lh-sm";
    li.innerHTML = `<span>${label}</span><span>${value}</span>`;
    shippingSummary.appendChild(li);
  });
}

function togglePaymentInstructions() {
  const method = document.querySelector(
    "input[name='paymentMethod']:checked"
  )?.value;
  document.getElementById("card-details").style.display =
    method === "card" ? "block" : "none";
  document
    .getElementById("pix-instructions")
    .classList.toggle("d-none", method !== "pix");
  document
    .getElementById("boleto-instructions")
    .classList.toggle("d-none", method !== "boleto");
}

async function gerarPagamento(metodo, valor, cpf) {
  const endpoint = metodo === "pix" ? "generate-pix" : "generate-boleto";
  const payload =
    metodo === "pix"
      ? { valor, txid: `pedido-${Date.now()}` }
      : { valor, nome: "Cliente", cpf };
  const res = await fetch(`${API_URL}/${endpoint}`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });
  return await res.json();
}

async function processarPedido(metodo, valorTotal) {
  const payload = {
    cart: cartValidation.itens.map((i) => ({
      id: i.id,
      quantidade: i.quantidade,
    })),
    shipping: {
      method: freteInfo.metodo || "PAC",
      value: freteInfo.valor,
    },
    payment: {
      method: metodo,
      value: valorTotal,
    },
  };
  const res = await fetch(`${API_URL}/process-order`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify(payload),
  });
  return await res.json();
}

submitBtn.addEventListener("click", async () => {
  const paymentMethod = document.querySelector(
    "input[name='paymentMethod']:checked"
  )?.value;
  const cpf = document.getElementById("cpf")?.value || "";

  if (!paymentMethod) {
    alert("Selecione uma forma de pagamento.");
    return;
  }

  try {
    const valorTotal = cartValidation.total + freteInfo.valor;
    const pagamento = await gerarPagamento(paymentMethod, valorTotal, cpf);
    if (!pagamento.success) {
      alert("Erro ao gerar pagamento.");
      return;
    }

    modalBody.innerHTML = "";
    if (paymentMethod === "pix") {
      modalBody.innerHTML = `
        <p>Escaneie o QR Code ou copie o código Pix:</p>
        <img src="${pagamento.qr_code_url}" class="img-fluid" />
        <div class="input-group mt-3">
          <input type="text" class="form-control" value="${pagamento.codigo_pix}" readonly />
          <button class="btn btn-outline-secondary" id="copyPix">Copiar</button>
        </div>
      `;
    } else if (paymentMethod === "boleto") {
      modalBody.innerHTML = `
        <p>Boleto gerado com sucesso:</p>
        <a href="${pagamento.url_boleto}" class="btn btn-primary" target="_blank">Ver Boleto</a>
      `;
    }

    modal.show();

    confirmBtn.onclick = async () => {
      const pedido = await processarPedido(paymentMethod, valorTotal);
      if (pedido.success) {
        alert("✅ Pedido finalizado com sucesso!");
        removeItem("cartData");
        removeItem("cartValidated");
        removeItem("freteData");
        window.location.href = "index.html";
      } else {
        alert("❌ Erro ao processar pedido.");
      }
    };
  } catch (err) {
    console.error(err);
    alert("Erro durante o checkout. Tente novamente.");
  }
});

document.addEventListener("change", (e) => {
  if (e.target.name === "paymentMethod") togglePaymentInstructions();
});

document.addEventListener("click", (e) => {
  if (e.target.id === "copyPix") {
    const code = e.target.previousElementSibling.value;
    navigator.clipboard.writeText(code).then(() => {
      e.target.textContent = "Copiado!";
      setTimeout(() => (e.target.textContent = "Copiar"), 2000);
    });
  }
});

renderCart();
renderShipping();
togglePaymentInstructions();

if (cartData.length === 0) {
  cartItemsContainer.innerHTML =
    "<li class='list-group-item'>Carrinho vazio</li>";
  submitBtn.disabled = true;
}
