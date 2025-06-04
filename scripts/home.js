import { setItem } from "../functions/localStorage.js";
import { API_URL } from "./baseUrl.js";
const datalist = document.getElementById("penitenciariaOptions");
const input = document.getElementById("penitenciariaInput");
const buscarBtn = document.getElementById("buscarBtn");

async function getToken(endpoint) {
  try {
    const response = await axios.get(`${API_URL}${endpoint}`, {
      headers: {
        "Content-Type": "application/json",
      },
    });
    return response.data;
  } catch (error) {
    if (error.response) {
      console.error(`Erro (${error.response.status}):`, error.response.data);
    } else {
      console.error("Erro na requisição:", error.message);
    }
    return null;
  }
}

async function carregarPenitenciarias() {
  const data = await getToken("/prison-list");
  if (!data) return;

  console.log("Penitenciárias:", data.content);
  populatePenitenciariasList(data);
}

carregarPenitenciarias();
let penitenciariasMap = {};

function populatePenitenciariasList(data) {
  const penitenciarias = data.content;
  datalist.innerHTML = "";

  penitenciariasMap = {}; // zera o mapa
  penitenciarias.forEach((prison) => {
    const option = document.createElement("option");
    option.value = prison.label;
    penitenciariasMap[prison.label] = prison.slug;
    datalist.appendChild(option);
  });
}

buscarBtn.addEventListener("click", () => {
  const selectedLabel = input.value.trim();
  console.log("input ==> ", input.value);

  if (selectedLabel) {
    const slug = penitenciariasMap[selectedLabel]; // aqui recupera o slug
    console.log("slug ==> ", slug);
    console.log("selectedLabel ==> ", selectedLabel);
    if (!slug) {
      alert("Penitenciária inválida. Selecione uma da lista.");
      return;
    }
    const prisonData = {
      label: selectedLabel,
      slug: slug,
    };
    console.log("prisonData ==> ", prisonData);
    setItem("prisonData", prisonData);
    ("");
    const encoded = encodeURIComponent(slug);
    window.location.href = `shop.html?p=${encoded}`;
  } else {
    alert("Por favor, selecione uma penitenciária.");
  }
});
