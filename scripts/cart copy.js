import { getItem, setItem } from "../functions/localStorage.js";
import {
  appendCartData,
  appendCartTotal,
  getTotalOrderAmount,
} from "../functions/appendCartData.js";

// DOM Elements
const display = document.getElementById("display");
const totalAmount = document.getElementById("totalAmount");
const form = document.getElementById("form");
const freteContainer = document.getElementById("frete-opcoes");
const freteSelect = document.getElementById("freteSelect");
const confirmBtn = document.getElementById("confirmar-frete");

// Inicializa carrinho
const cartData = getItem("cartData") || [];
appendCartData(cartData, display, totalAmount);
getTotalOrderAmount(cartData, totalAmount);
appendCartTotal(getItem("cartTotal"), totalAmount);

// Envio do formulário para validar carrinho e calcular frete
form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const token = getItem("token");
  if (!token) {
    alert("Faça login para continuar.");
    return (window.location.href = "login.html");
  }

  if (!cartData.length) {
    return alert("Carrinho vazio.");
  }

  const totalWeight = cartData.reduce(
    (acc, item) => acc + (item.weight || 0) * (item.qty || 1),
    0
  );

  if (totalWeight > 12) {
    return alert(
      `Peso total (${totalWeight.toFixed(2)}kg) excede o limite de 12kg.`
    );
  }

  const formData = new FormData(form);
  const address = {
    nome: formData.get("name"),
    email: formData.get("email"),
    mobile: formData.get("mobile"),
    street: formData.get("street"),
    city: formData.get("city"),
    state: formData.get("state"),
    cep: formData.get("pincode"),
  };

  const payload = {
    products: cartData.map((item) => ({
      id: item.id,
      qty: item.qty || 1,
    })),
  };

  try {
    // 🔒 Valida carrinho
    const validateRes = await fetch(
      "http://clickjumbo.local/wp-json/clickjumbo/v1/validate-cart",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify(payload),
      }
    );

    const validateJson = await validateRes.json();
    if (!validateJson.success) {
      return alert("Erro ao validar carrinho.");
    }

    setItem("cartValidated", validateJson);

    // 🚚 Calcula frete
    const freteRes = await fetch(
      "http://clickjumbo.local/wp-json/clickjumbo/v1/calculate-shipping",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          cep_origem: "01001-000",
          cep_destino: address.cep,
          peso: totalWeight.toFixed(2),
          comprimento: 25,
          largura: 15,
          altura: 10,
        }),
      }
    );

    const freteJson = await freteRes.json();
    if (!freteJson.success || !freteJson.frete) {
      return alert("Erro ao calcular frete.");
    }

    // 🧠 Exibe dropdown com opções
    freteSelect.innerHTML = "";
    // Remove classe 'hidden' para exibir elementos
    freteContainer.classList.remove("hidden");
    confirmBtn.classList.remove("hidden");

    Object.entries(freteJson.frete).forEach(([metodo, dados]) => {
      const option = document.createElement("option");
      option.value = metodo;
      option.textContent = `${metodo} – R$ ${dados.valor.toFixed(2)} – ${
        dados.prazo
      }`;
      freteSelect.appendChild(option);
    });

    // Armazena temporariamente
    window._freteJson = freteJson;
    window._address = address;
  } catch (err) {
    console.error("Erro durante validação:", err);
    alert("Erro inesperado. Tente novamente.");
  }
});

// Confirma envio e redireciona
confirmBtn.addEventListener("click", () => {
  const metodo = freteSelect.value;
  if (!metodo) {
    return alert("Selecione um método de envio.");
  }

  const dadosFrete = window._freteJson.frete[metodo];
  const endereco = window._address;

  setItem("freteData", {
    ...dadosFrete,
    metodo,
    cep_destino: endereco.cep,
    cep_origem: "01001-000",
  });

  window.location.href = "checkout.html";
});
