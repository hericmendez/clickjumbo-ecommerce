import { setItem } from "../functions/localStorage.js";
import { API_URL } from "./baseUrl.js";

const select = document.getElementById("penitenciariaSelect");
const buscarBtn = document.getElementById("buscarBtn");
let penitenciariasData = [];

async function getPenitenciarias(page = 1, perPage = 50) {
  try {
    const response = await axios.get(`https://clickjumbo.com.br/wp/wp-json/clickjumbo/v1/prison-list-full?page=${page}&per_page=${perPage}`, {
      headers: { "Content-Type": "application/json" },
    });
    return response.data.content;
  } catch (error) {
    console.error("Erro na requisição:", error.response?.data || error.message);
    return [];
  }
}

async function carregarTodasPenitenciarias() {
  let page = 1;
  let resultados = [];

  while (true) {
    const penitenciarias = await getPenitenciarias(page);
    if (!penitenciarias.length) break;

    resultados = resultados.concat(penitenciarias);
    page++;
  }

  penitenciariasData = resultados;
  populatePenitenciariasSelect(resultados);
}

function populatePenitenciariasSelect(penitenciarias) {
  select.innerHTML = '<option value="" disabled selected>Escolha a penitenciária</option>';

  penitenciarias.forEach(penitenciaria => {
    const option = document.createElement("option");
    option.value = penitenciaria.slug;
    option.textContent = penitenciaria.nome;

    select.appendChild(option);
  });
}

buscarBtn.addEventListener("click", () => {
  const selectedSlug = select.value;

  if (!selectedSlug) {
    alert("Por favor, selecione uma penitenciária.");
    return;
  }

  const dadosPenitenciaria = penitenciariasData.find(obj => obj.slug === selectedSlug);
  if (!dadosPenitenciaria) {
    alert("Penitenciária inválida. Tente novamente.");
    return;
  }

  setItem("dadosPenitenciaria", dadosPenitenciaria);
  window.location.href = `shop.html?p=${encodeURIComponent(selectedSlug)}`;
});

// Inicialização
carregarTodasPenitenciarias();
