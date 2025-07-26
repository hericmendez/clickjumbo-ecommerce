// checkout.js atualizado para preencher dados do envio, controlar exibição condicional de campos e finalizar pedido

import { API_URL } from "./baseUrl.js";
import { getItem, removeItem } from "../functions/localStorage.js";

const dadosCarrinho = getItem("dadosCarrinho") || [];
const freteInfo = getItem("dadosFrete");
const token = getItem("token");
const dadosUsuario = getItem("dadosUsuario");
console.log("dadosUsuario ==> ", dadosUsuario);
const cartItemsContainer = document.getElementById("itens-carrinho");
const qtdeCarrinho = document.getElementById("qtde-carinho");
console.log("qtdeCarrinho ==> ", qtdeCarrinho);
const resumoCarrinho = document.getElementById("resumo-carrinho");
const resumoEnvio = document.getElementById("resumo-envio");
const submitBtn = document.getElementById("submitBtn");

const modal = new bootstrap.Modal(document.getElementById("paymentModal"));
const modalBody = document.getElementById("paymentModalBody");
const confirmBtn = document.getElementById("confirmPaymentBtn");

function renderCart() {
  let total = 0;
  let peso = 0;
  cartItemsContainer.innerHTML = "";
  resumoCarrinho.innerHTML = "";

  dadosCarrinho.forEach((item) => {
    const li = document.createElement("li");
    li.className = "list-group-item d-flex justify-content-between lh-sm";
    li.innerHTML = `
      <div>
        <strong>${item.nome}</strong><br />
        <small>${item.peso || 0}kg x ${item.qtde}</small>
      </div>
      <span>R$ ${(item.preco * item.qtde).toFixed(2)}</span>
    `;
    cartItemsContainer.appendChild(li);
    total += item.preco * item.qtde;
    peso += item.peso * item.qtde;
  });

  qtdeCarrinho.textContent = dadosCarrinho.length;

  const items = [
    [`Peso total`, `${peso.toFixed(2)} kg`],
    [`Frete (${freteInfo?.metodo})`, `R$ ${freteInfo?.valor.toFixed(2)}`],
    [`Total`, `R$ ${(total + freteInfo?.valor).toFixed(2)}`],
  ];
  items.forEach(([label, value]) => {
    const li = document.createElement("li");
    li.className = "list-group-item d-flex justify-content-between lh-sm";
    li.innerHTML = `<span>${label}</span><strong>${value}</strong>`;
    resumoCarrinho.appendChild(li);
  });
}

function renderShipping() {
  resumoEnvio.innerHTML = "";
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
    resumoEnvio.appendChild(li);
  });
}

function togglePaymentInstructions() {
  const metodoPagamento = document.querySelector(
    "input[name='paymentMethod']:checked"
  )?.value;
  document.getElementById("card-details").style.display =
    metodoPagamento === "card" ? "block" : "none";
  document
    .getElementById("pix-instructions")
    .classList.toggle("d-none", metodoPagamento !== "pix");
  document
    .getElementById("boleto-instructions")
    .classList.toggle("d-none", metodoPagamento !== "boleto");
}

async function gerarPagamento(metodo, valor, dadosUsuario) {
  if (metodo === "pix") {
    return {
      success: true,
      pix: {
        codigo: "000201...mocked-pix",
        qr_code_url: `https://api.qrserver.com/v1/create-qr-code/?data=mocked&size=200x200`,
      },
    };
  }

  if (metodo === "boleto") {
    const res = await fetch(`${API_URL}/generate-boleto`, {
      metodoPagamento: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify({
        user: {
          nome: dadosUsuario.nome,
          email: dadosUsuario.email,
        },
        valor_total: valor,
      }),
    });
    console.log("res.json() ==> ", res);
    return await res.json();
  }
}
async function processarPedido(metodo, valorTotal, dadosUsuario) {
  const payload = {
    user: {
      nome: dadosUsuario.nome,
      email: dadosUsuario.email,
    },
    carrinho: {
      produtos: dadosCarrinho.map((item) => ({
        id: item.id,
        qtde: item.qtde,
      })),
    },
    envio: {
      nome_penitenciaria: dadosPenitenciaria.label,
      peso_carrinho: dadosCarrinho.reduce(
        (acc, curr) => acc + curr.qtde * curr.peso,
        0
      ),
      metodoPagamento: freteInfo.metodo,
      remetente: {
        cep: freteInfo.cep_origem,
        rua: dadosUsuario.rua,
        cidade: dadosUsuario.cidade,
        estado: dadosUsuario.estado,
      },
      frete_valor: freteInfo.valor,
    },
    pagamento: {
      metodoPagamento: metodo,
      dados_pagamento: {
        valor_recebido: valorTotal,
        id_transacao: `TRANS_${Date.now()}`,
      },
    },
  };

  const res = await fetch(`${API_URL}/process-order`, {
    metodoPagamento: "POST",
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

  if (!paymentMethod) {
    alert("Selecione uma forma de pagamento.");
    return;
  }

  try {
    const valorProdutos = dadosCarrinho.reduce(
      (acc, item) => acc + item.preco * item.qtde,
      0
    );
    const valorFrete = freteInfo?.valor || 0;
    const valorTotal = valorProdutos + valorFrete;

    const pagamento = await gerarPagamento(paymentMethod, valorTotal, dadosUsuario);
    if (!pagamento.success) {
      alert("Erro ao gerar pagamento.");
      return;
    }

    modalBody.innerHTML = "";

    if (paymentMethod === "pix") {
      modalBody.innerHTML = `
        <p>Escaneie o QR Code ou copie o código Pix:</p>
        <img src="${pagamento.pix?.qr_code_url}" class="img-fluid" />
        <div class="input-group mt-3">
          <input type="text" class="form-control" value="${pagamento.pix?.codigo}" readonly />
          <button class="btn btn-outline-secondary" id="copyPix">Copiar</button>
        </div>
      `;
    }
    if (paymentMethod === "boleto") {
      modalBody.innerHTML = `
        <p>Baixe o PDF ou copie o número do código de barras::</p>
        <a href="${
          pagamento.boleto?.pdf_url
        }" download="clickjumbo_${Date.now()}" rel="noopener noreferrer" target="_blank">Download link</a>
        <div class="input-group mt-3">
          <input type="text" class="form-control" value="${
            pagamento.boleto.linha_digitavel
          }" readonly />
          <button class="btn btn-outline-secondary" id="copyPix">Copiar</button>
        </div>
      `;
    }
    modal.show();

    confirmBtn.onclick = async () => {
      const pedido = await processarPedido(paymentMethod, valorTotal);
      if (pedido.success) {
        alert("✅ Pedido finalizado com sucesso!");
        removeItem("dadosCarrinho");
        removeItem("carrinhoValido");
        removeItem("dadosFrete");
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

if (dadosCarrinho.length === 0) {
  cartItemsContainer.innerHTML =
    "<li class='list-group-item'>Carrinho vazio</li>";
  submitBtn.disabled = true;
}
