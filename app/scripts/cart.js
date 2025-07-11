import { getItem, setItem } from "../functions/localStorage.js";
import {
  appendCartData,
  appendCartTotal,
  getTotalOrderAmount,
} from "../functions/appendCartData.js";
import { API_URL } from "./baseUrl.js";

// DOM Elements
const display = document.getElementById("display");
const totalAmount = document.getElementById("totalAmount");
const form = document.getElementById("form");
const btnCalcFrete = document.getElementById("btnCalcFrete");
const btnContinuar = document.getElementById("btnContinuar");
const freteContainer = document.getElementById("freteContainer");
const freteCardsContainer = document.getElementById("freteCardsContainer");
const prisonField = document.getElementById("prisonField");
function showSpinner() {
  document.getElementById("loadingSpinner").style.display = "flex";
}

function hideSpinner() {
  document.getElementById("loadingSpinner").style.display = "none";
}

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
  showSpinner(); // Mostra o spinner

  const token = getItem("token");
  if (!token) {
    alert("Faça login para continuar.");
    window.location.href = "login.html";
    return hideSpinner();
  }

  if (!cartData.length) {
    alert("Carrinho vazio.");
    return hideSpinner();
  }

  const totalWeight = cartData.reduce(
    (acc, item) => acc + (item.weight || 0) * (item.qty || 1),
    0
  );
  if (totalWeight > 12) {
    alert(`Peso total (${totalWeight.toFixed(2)}kg) excede o limite de 12kg.`);
    return hideSpinner();
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

  const payload = {
    cart: cartData.map((item) => ({
      id: item.id,
      qty: item.qty || 1,
    })),
  };

  try {
    // 🔐 Valida carrinho
    const validateRes = await fetch(
      `${API_URL}/validate-cart`,
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
    setItem("cartValidated", validateJson);
  } catch (error) {
    alert("Erro ao validar carrinho:", error);
    return hideSpinner();
  }

  try {
    // 🚚 Calcula frete
    const res = await fetch(
      `${API_URL}/prison-details/${prisonData.slug}`
    );
    const prison = await res.json();
    console.log("prison ==> ", prison.content.cep);

    const freteRes = await fetch(
      `${API_URL}/calculate-shipping`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          Authorization: `Bearer ${token}`,
        },
        body: JSON.stringify({
          cep_origem: address.cep_origem,
          cep_destino: prison.content.cep,
          peso: Number(totalWeight.toFixed(2)),
          comprimento: 25,
          largura: 15,
          altura: 10,
        }),
      }
    );
    console.log("freteRes ==> ", freteRes);
    const freteJson = await freteRes.json();
    console.log("freteJson ==> ", freteJson);

    if (!freteJson.success || !freteJson.frete) {
      alert("Erro ao calcular frete.");
      return hideSpinner();
    }

    // 💳 Exibe cards de envio
    freteCardsContainer.innerHTML = "";
    Object.entries(freteJson.frete).forEach(([metodo, dados], index) => {
      const id = `frete_${metodo}`;

      const wrapper = document.createElement("div");
      wrapper.className = "card p-3 frete-card";
      wrapper.style.cursor = "pointer";
    //  wrapper.style.display = dados.valor > 0 ? "block" : "none";
      wrapper.innerHTML = `
        <input type="radio" name="freteMetodo" id="${id}" value="${metodo}" class="form-check-input d-none" ${
        index === 0 ? "checked" : ""
      }>
        <label for="${id}" class="d-flex justify-content-between align-items-center mb-0 w-100">
          <div>
            <strong>${metodo}</strong><br/>
            Valor: R$ ${dados.valor.toFixed(2)}<br/>
            Prazo: ${dados.prazo} dias úteis
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

        freteData = {
          ...dados,
          metodo,
          cep_destino: prisonData.slug,
          cep_origem: address.cep_origem,
        };
        setItem("freteData", freteData);
        console.log("Saved on localstorage:", freteData); // ← salva imediatamente no localStorage
      });
      console.log("Método selecionado:", freteData.metodo);
      console.log("Valor do frete:", freteData.valor);

      if (index === 0) {
        wrapper.classList.add("border-success");
        freteData = {
          ...dados,
          metodo,
          cep_destino: prisonData.slug,
          cep_origem: address.cep_origem,
        };
        setItem("freteData", freteData); // salva o primeiro frete por padrão
      }

      freteCardsContainer.appendChild(wrapper);
    });

    setItem("userData", address);
    console.log("address ==> ", address);
    freteContainer.style.display = "block";
    btnContinuar.disabled = false;
  } catch (err) {
    console.error("Erro ao calcular frete:", err);
    alert("Erro inesperado. Tente novamente.");
  } finally {
    hideSpinner(); // Garante que o spinner desaparece sempre
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
