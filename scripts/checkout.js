const cartData = JSON.parse(localStorage.getItem("cartData") || "[]");
const cartItemsContainer = document.getElementById("cart-items");
const cartCount = document.getElementById("cart-count");

let total = 0;

cartCount.textContent = cartData.length;



// Oculta/exibe campos de cartão e CPF
const radios = document.querySelectorAll('input[name="paymentMethod"]');
const cardSection = document.getElementById("card-details");

// Cria dinamicamente o campo CPF e container para código gerado
const cpfContainer = document.createElement("div");
cpfContainer.classList.add("mt-3");
cpfContainer.innerHTML = `
  <label class="form-label">CPF</label>
  <input type="text" class="form-control" id="cpf" placeholder="000.000.000-00" required>
  <div id="payment-code" class="mt-3" style="display: none;">
    <label class="form-label">Código gerado</label>
    <div class="input-group">
      <input type="text" id="generated-code" class="form-control" readonly>
      <button type="button" id="copy-code" class="btn btn-outline-secondary">Copiar</button>
    </div>
  </div>
`;

function isCPFRequired(method) {
  return method === "boleto" || method === "pix";
}

function showCPFField(show) {
  const form = document.getElementById("checkout-form");
  if (show && !form.contains(cpfContainer)) {
    form.insertBefore(cpfContainer, cardSection);
  } else if (!show && form.contains(cpfContainer)) {
    cpfContainer.remove();
  }
}

radios.forEach(radio => {
  radio.addEventListener("change", () => {
    const method = radio.value;
    cardSection.style.display = method === "card" ? "block" : "none";
    document.getElementById("pix-instructions").style.display = method === "pix" ? "block" : "none";
    document.getElementById("boleto-instructions").style.display = method === "boleto" ? "block" : "none";
    showCPFField(isCPFRequired(method));
  });
});

if (!document.getElementById("card").checked) {
  cardSection.style.display = "none";
}

document.addEventListener("DOMContentLoaded", () => {
  const cartData = JSON.parse(localStorage.getItem("cartData") || "[]");
  const cartItemsContainer = document.getElementById("cart-items");
  const cartCount = document.getElementById("cart-count");
  const form = document.getElementById("checkout-form");
  const cardSection = document.getElementById("card-details");
  const radios = document.querySelectorAll('input[name="paymentMethod"]');

  let total = 0;

  cartData.forEach(item => {
    const li = document.createElement("li");
    li.className = "list-group-item d-flex justify-content-between lh-condensed";
    li.innerHTML = `
      <div>
        <h6 class="my-0">${item.name}</h6>
        <small class="text-muted">${item.description || ""}</small>
      </div>
      <span class="text-muted">R$ ${item.price.toFixed(2)}</span>
    `;
    cartItemsContainer.appendChild(li);
    total += item.price;
  });

  cartCount.textContent = cartData.length;

  const totalEl = document.createElement("li");
  totalEl.className = "list-group-item d-flex justify-content-between";
  totalEl.innerHTML = `<strong>Total (R$)</strong><strong>R$ ${total.toFixed(2)}</strong>`;
  cartItemsContainer.appendChild(totalEl);

  // Mostrar ou ocultar campos de cartão
  const toggleCardSection = () => {
    const selectedMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value;
    cardSection.style.display = selectedMethod === "card" ? "block" : "none";
  };

  // Inicializa visibilidade do cartão
  toggleCardSection();

  radios.forEach(radio => {
    radio.addEventListener("change", toggleCardSection);
  });

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    e.stopPropagation();

    const data = new FormData(form);

    const user = {
      firstName: data.get("firstName"),
      lastName: data.get("lastName"),
      address: data.get("address"),
      complement: data.get("address2"),
      country: data.get("country"),
      state: data.get("state"),
      zip: data.get("zip"),
    };

    const selectedMethod = data.get("paymentMethod");

    const payment = {
      method: selectedMethod,
      data: {},
    };

    if (selectedMethod === "card") {
      payment.data = {
        cardName: data.get("cardName") || "",
        cardNumber: data.get("cardNumber") || "",
        cardExpiration: data.get("cardExpiration") || "",
        cardCVV: data.get("cardCVV") || "",
      };

      // Validação básica dos campos de cartão
      const allFilled = Object.values(payment.data).every(val => val.trim() !== "");
      if (!allFilled) {
        alert("Por favor, preencha todos os campos do cartão.");
        return;
      }
    }

    const cartItemsToSend = cartData.map((item) => ({
      id: item.id,
      qty: item.qty,
    }));

    const checkoutData = { cartItemsToSend, user, payment };
    console.log("checkoutData =>", checkoutData);

    // Aqui você pode enviar os dados com fetch()
    // fetch('/api/checkout', {
    //   method: 'POST',
    //   body: JSON.stringify(checkoutData),
    //   headers: { 'Content-Type': 'application/json' }
    // });
  });
});


// Botão "Copiar"
document.addEventListener("click", (e) => {
  if (e.target.id === "copy-code") {
    const codeInput = document.getElementById("generated-code");
    navigator.clipboard.writeText(codeInput.value).then(() => {
      e.target.textContent = "Copiado!";
      setTimeout(() => (e.target.textContent = "Copiar"), 2000);
    });
  }
});
