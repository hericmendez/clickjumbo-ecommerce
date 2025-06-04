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
const btnCalcFrete = document.getElementById("btnCalcFrete");
const btnContinuar = document.getElementById("btnContinuar");
const freteContainer = document.getElementById("freteContainer");
const freteCardsContainer = document.getElementById("freteCardsContainer");
const prisonField = document.getElementById("prisonField");

let cartData = getItem("cartData") || [];
const prisonData = getItem("prisonData");

// Garante que a penitenciária foi selecionada
if (!prisonData) {
  alert("Selecione uma penitenciária.");
  window.location.href = "index.html";
}

// Exibe penitenciária no formulário
if (prisonField) {
  prisonField.value = prisonData.label;
}

let freteData = {}; // Armazena o frete selecionado

// Renderiza carrinho
appendCartData(cartData, display, totalAmount);
getTotalOrderAmount(cartData, totalAmount);
appendCartTotal(getItem("cartTotal"), totalAmount);

// Botão: Calcular Frete
btnCalcFrete.addEventListener("click", async () => {
  const token = getItem("token");
  if (!token) {
    alert("Faça login para continuar.");
    window.location.href = "login.html";
    return;
  }

  if (!cartData.length) {
    alert("Carrinho vazio.");
    return;
  }

  const totalWeight = cartData.reduce(
    (acc, item) => acc + (item.weight || 0) * (item.qty || 1),
    0
  );
  if (totalWeight > 12) {
    alert(`Peso total (${totalWeight.toFixed(2)}kg) excede o limite de 12kg.`);
    return;
  }

  const formData = new FormData(form);
  const address = {

    nome: formData.get("name"),
    email: formData.get("email"),
    mobile: formData.get("mobile"),
    street: formData.get("street"),
    city: formData.get("city"),
    state: formData.get("state"),
    cep_origem: formData.get("cep"),
    prison_name: prisonData.label,
    prison_slug: prisonData.slug,
  };
  console.log("address ==> ", address);
  const payload = {
    products: cartData.map((item) => ({
      id: item.id,
      qty: item.qty || 1,
    })),
  };

  try {
    // 🔐 Valida carrinho
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
      alert("Erro ao validar carrinho.");
      return;
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
          cep_origem: address.cep_origem,

          cep_destino: address.prison_slug, // slug
          peso: totalWeight.toFixed(2),
          comprimento: 25,
          largura: 15,
          altura: 10,
        }),
      }
    );

    const freteJson = await freteRes.json();
    if (!freteJson.success || !freteJson.frete) {
      alert("Erro ao calcular frete.");
      return;
    }

    // 💳 Exibe cards de envio
    freteCardsContainer.innerHTML = "";
    Object.entries(freteJson.frete).forEach(([metodo, dados], index) => {
      const id = `frete_${metodo}`;

      const wrapper = document.createElement("div");
      wrapper.className = "card p-3 frete-card";
      wrapper.style.cursor = "pointer";
      wrapper.innerHTML = `
        <input type="radio" name="freteMetodo" id="${id}" value="${metodo}" class="form-check-input d-none" ${
        index === 0 ? "checked" : ""
      }>
        <label for="${id}" class="d-flex justify-content-between align-items-center mb-0 w-100">
          <div>
            <strong>${metodo}</strong><br/>
            Valor: R$ ${dados.valor.toFixed(2)}<br/>
            Prazo: ${dados.prazo}
          </div>
          <i class="bi bi-truck" style="font-size: 1.5rem;"></i>
        </label>
      `;

      wrapper.addEventListener("click", () => {
        document
          .querySelectorAll(".frete-card")
          .forEach((c) => c.classList.remove("border-success"));
        wrapper.classList.add("border-success");
        wrapper.querySelector("input").checked = true;

        // Atualiza freteData
        freteData = {
          ...dados,
          metodo,
          cep_destino: prisonData.slug,
          cep_origem: address,cep_origem,
        };
      });

      if (index === 0) {
        wrapper.classList.add("border-success");
        freteData = {
          ...dados,
          metodo,
          cep_destino: prisonData.slug,
          cep_origem: address.cep_origem,
        };
      }

      freteCardsContainer.appendChild(wrapper);
    });
    setItem("userData", address);

    freteContainer.style.display = "block";
    btnContinuar.disabled = false;
  } catch (err) {
    console.error("Erro ao calcular frete:", err);
    alert("Erro inesperado. Tente novamente.");
  }
});

// Botão de continuar
form.addEventListener("submit", (e) => {
  e.preventDefault();

  if (!freteData?.metodo) {
    alert("Selecione um método de envio.");
    return;
  }

  setItem("freteData", freteData);
  window.location.href = "checkout.html";
});
