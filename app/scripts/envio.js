import { getItem, setItem } from "../functions/localStorage.js";
import { montarPayloadFrete } from "../validations/montarPayload.js";
import { API_URL } from "./baseUrl.js";

// DOM Elements
const btnCalcFrete = document.getElementById("btnCalcFrete");
const freteContainer = document.getElementById("freteContainer");
const freteCardsContainer = document.getElementById("freteCardsContainer");
const campoPenitenciaria = document.getElementById("campoPenitenciaria");

// Dados iniciais
let dadosCarrinho = getItem("dadosCarrinho") || [];
const dadosPenitenciaria = getItem("dadosPenitenciaria");
let dadosFrete = {}; // Salvo no localStorage

// Exibe penitenciária (se existir campo)
if (campoPenitenciaria) {
  campoPenitenciaria.value = dadosPenitenciaria.nome;
}

function showSpinner() {
  document.getElementById("loadingSpinner").style.display = "flex";
}
function hideSpinner() {
  document.getElementById("loadingSpinner").style.display = "none";
}
    const remetente = {
      nome: document.getElementById("nomeVisitante")?.value,
      email: document.getElementById("emailVisitante")?.value,
      telefone: document.getElementById("telefoneVisitante")?.value,
      rua: document.getElementById("ruaVisitante")?.value,
      cidade: document.getElementById("cidadeVisitante")?.value,
      estado: document.getElementById("estadoVisitante")?.value,
      cep_origem: document.getElementById("cepVisitante")?.value,
      nome_detento: document.getElementById("nomeDetento")?.value,
      matricula_detento: document.getElementById("matriculaDetento")?.value,
      raio_detento: document.getElementById("raioDetento")?.value,
      cela_detento: document.getElementById("celaDetento")?.value,
      nome_penitenciaria: dadosPenitenciaria.nome,
      slug_penitenciaria: dadosPenitenciaria.slug,
    };
    const btnClienteForm = document.getElementById("btnClienteForm");
    btnClienteForm.addEventListener("click", (e)=>{
      e.preventDefault();
    
    })

    
if (btnCalcFrete) {
  btnCalcFrete.addEventListener("click", async () => {
    showSpinner();

    const token = getItem("token");
    if (!token) {
      alert("Faça login para continuar.");
      window.location.href = "login.html";
      return hideSpinner();
    }

    if (!dadosCarrinho.length) {
      alert("Carrinho vazio.");
      return hideSpinner();
    }

    // Calcula peso total do carrinho
    const pesoTotal = dadosCarrinho.reduce(
      (acc, item) => acc + (item.peso || 0) * (item.peso || 1),
      0
    );
    if (pesoTotal > 12) {
      alert(`Peso total (${pesoTotal.toFixed(2)}kg) excede o limite de 12kg.`);
      return hideSpinner();
    }

    // Coleta dados do formulário de envio


    const payload = {
      carrinho: dadosCarrinho.map((item) => ({
        id: item.id,
        peso: item.peso || 1,
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
      setItem("carrinhoValido", validateJson);
    } catch (error) {
      alert("Erro ao validar carrinho:", error);
      return hideSpinner();
    }

    try {
      // 🚚 Calcula frete
      const res = await fetch(
        `${API_URL}/prison-details/${dadosPenitenciaria.slug}`
      );
      const penitenciaria = await res.json();

      const freteRes = await fetch(
        `${API_URL}/calculate-shipping`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            cep_origem: remetente.cep_origem,
            cep_destino: penitenciaria.content.cep,
            peso: Number(pesoTotal.toFixed(2)),
            comprimento: 25,
            largura: 15,
            altura: 10,
          }),
        }
      );
      const freteJson = await freteRes.json();

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
        wrapper.innerHTML = `
          <input type="radio" name="freteMetodo" id="${id}" value="${metodo}" class="form-check-input d-none" ${index === 0 ? "checked" : ""}>
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

          dadosFrete = {
            ...dados,
            metodo,
            cep_destino: dadosPenitenciaria.slug,
            cep_origem: remetente.cep_origem,
          };
          setItem("dadosFrete", dadosFrete);
        });

        if (index === 0) {
          wrapper.classList.add("border-success");
          dadosFrete = {
            ...dados,
            metodo,
            cep_destino: dadosPenitenciaria.slug,
            cep_origem: remetente.cep_origem,
          };
          setItem("dadosFrete", dadosFrete); // salva o primeiro frete por padrão
        }

        freteCardsContainer.appendChild(wrapper);
      });

      setItem("dadosUsuario", remetente);
      freteContainer.style.display = "block";
    } catch (err) {
      console.error("Erro ao calcular frete:", err);
      alert("Erro inesperado. Tente novamente.");
    } finally {
      hideSpinner();
    }
  });
}


document.addEventListener("DOMContentLoaded", function() {
  const infoPenitenciariaBtn = document.getElementById("infoPenitenciariaBtn");
  if (infoPenitenciariaBtn && dadosPenitenciaria) {
    infoPenitenciariaBtn.innerHTML = renderPenitenciariaInfo(dadosPenitenciaria);
  }

  const btnRadioPenitenciaria = document.getElementById("btnRadioPenitenciaria");
  const btnRadioOutro = document.getElementById("btnRadioOutro");
  const formEnvio = document.getElementById("formEnvio");

  function toggleOutroEndereco() {
    if (btnRadioOutro?.checked) {
      formEnvio.style.display = "block";
    } else if (formEnvio) {
      formEnvio.style.display = "none";
    }
  }

  btnRadioPenitenciaria && btnRadioPenitenciaria.addEventListener("change", toggleOutroEndereco);
  btnRadioOutro && btnRadioOutro.addEventListener("change", toggleOutroEndereco);
  toggleOutroEndereco();
});

function renderPenitenciariaInfo(dados) {
  return `
    <div class="d-flex flex-column">
      <strong>${dados.nome}</strong>
      <p>${dados.logradouro}, Nº ${dados.numero}, ${dados.bairro} - ${dados.cidade}/${dados.estado} - CEP ${dados.cep}</p>
    </div>
  `;
}
