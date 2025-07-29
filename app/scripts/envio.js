// envio.js
import { getItem, setItem } from "../functions/localStorage.js";
import { montarPayloadFrete } from "../validations/montarPayload.js";
import { API_URL } from "./baseUrl.js";

const token = getItem("token");
const dadosPenitenciaria = getItem("dadosPenitenciaria");

function showSpinner() {
  const spinner = document.getElementById("loadingSpinner");
  if (spinner) spinner.style.display = "block";
}

function hideSpinner() {
  const spinner = document.getElementById("loadingSpinner");
  if (spinner) spinner.style.display = "none";
}

export async function calcularFrete(payloadFrete) {
  console.log("calcularFrete()")
  try {
    showSpinner();

    const res = await fetch(`${API_URL}/calculate-shipping`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
      },
      body: JSON.stringify(payloadFrete),
    });

    const freteJson = await res.json();
    console.log("freteJson ==> ", freteJson);

    if (!freteJson.success || !freteJson.frete) {
      console.warn("Resposta inválida da API:", freteJson);
      alert("Erro ao calcular frete.");
      return null;
    }

    renderizarCardsFrete(freteJson.frete, payloadFrete.cep_origem, dadosPenitenciaria.slug);

    
    return freteJson;
  } catch (err) {
    console.error("Erro ao calcular frete:", err);
    alert("Erro inesperado. Tente novamente.");
    return null;
  } finally {
    hideSpinner();
  }
}

export function inicializarEnvioForm() {
  const campoPenitenciaria = document.getElementById("campoPenitenciaria");
  const formEnvio = document.getElementById("formEnvio");
  const btnRadioOutro = document.getElementById("btnRadioOutro");
  const btnRadioPenitenciaria = document.getElementById("btnRadioPenitenciaria");
  const infoPenitenciariaBtn = document.getElementById("infoPenitenciariaBtn");

  if (campoPenitenciaria && dadosPenitenciaria) {
    campoPenitenciaria.value = dadosPenitenciaria.nome;
  }

  const toggleOutroEndereco = () => {
    if (formEnvio) formEnvio.style.display = btnRadioOutro?.checked ? "block" : "none";
    console.log("btnRadioOutro?.checked ==> ", btnRadioOutro?.checked);
  };

  btnRadioPenitenciaria?.addEventListener("change", toggleOutroEndereco);
  btnRadioOutro?.addEventListener("change", toggleOutroEndereco);
  toggleOutroEndereco();

  if (infoPenitenciariaBtn && dadosPenitenciaria) {
    infoPenitenciariaBtn.innerHTML = renderPenitenciariaInfo(dadosPenitenciaria);
  }

  // 🧩 ADICIONE ISSO AQUI:
  const btnCalcFrete = document.getElementById("btnCalcFrete");
  if (btnCalcFrete) {
    btnCalcFrete.addEventListener("click", async () => {
      console.log("👉 CLICOU EM CALCULAR FRETE");

      const payload = montarPayloadFrete();
      const {remetente, destinatario, peso_carrinho} = payload.envio;
      console.log("remetente, destinatario, peso_carrinho ==> ", remetente, destinatario, peso_carrinho);

      const miniPayload = {

        cep_origem: remetente.cep,
        cep_destino: destinatario.cep,
        peso: peso_carrinho,
      }

      console.log("payload inicializarEnvioForm ==> ", payload);
            console.log("miniPayload ==> ", miniPayload);
      if (!payload || !payload.envio) {
        alert("Erro ao montar o payload de frete.");
        return;
      }

      const resultado = await calcularFrete(miniPayload);
      if (!resultado) {
        alert("Erro ao calcular frete. Verifique os dados e tente novamente.");
      }
    });
  } else {
    console.warn("❌ Botão #btnCalcFrete não encontrado!");
  }
}

function renderizarCardsFrete(fretes, cep_origem, cep_destino) {
  const freteCardsContainer = document.getElementById("freteCardsContainer");
  if (!freteCardsContainer) return;

  freteCardsContainer.innerHTML = "";

  Object.entries(fretes).forEach(([metodo, dados]) => {
    const id = `frete_${metodo}`;
    const wrapper = document.createElement("div");
    wrapper.className = "card p-3 frete-card";
    wrapper.style.cursor = "pointer";
    wrapper.innerHTML = `
      <input type="radio" name="freteMetodo" id="${id}" value="${metodo}" class="form-check-input d-none">
      <label for="${id}" class="d-flex justify-content-between align-items-center mb-0 w-100">
        <div>
          <strong>${metodo}</strong><br/>
          Valor: R$ ${dados?.valor.toFixed(2)}<br/>
          Prazo: ${dados?.prazo} dias úteis
        </div>
        <i class="bi bi-truck" style="font-size: 1.5rem;"></i>
      </label>
    `;

    wrapper.addEventListener("click", () => {
      // Remove destaque de todos os cards
      document.querySelectorAll(".frete-card").forEach((c) =>
        c.classList.remove("border-success", "border-danger")
      );

      // Marca este como selecionado
      wrapper.classList.add("border-success");
      wrapper.querySelector("input").checked = true;

      const freteSelecionado = { ...dados, metodo, cep_origem, cep_destino };
      setItem("dadosFrete", freteSelecionado);
      const totalCarrinho = getItem("totalCarrinho");
      totalCarrinho.frete = freteSelecionado.valor;
      setItem("totalCarrinho", totalCarrinho)
      // Habilita o botão de avançar
      document.getElementById("btnAvancarStep3").disabled = false;
    });

    freteCardsContainer.appendChild(wrapper);
  });

  // Desabilita botão "Próximo" por padrão
  document.getElementById("btnAvancarStep3").disabled = true;
}


function renderPenitenciariaInfo(dados) {
  return `
    <div class="d-flex flex-column">
      <strong>${dados.nome}</strong>
      <p>${dados.logradouro}, Nº ${dados.numero}, ${dados.bairro} - ${dados.cidade}/${dados.estado} - CEP ${dados.cep}</p>
    </div>
  `;
}
