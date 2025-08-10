import { setItem } from '../functions/localStorage.js'
import { API_URL } from '../api/baseUrl.js'

const select = document.getElementById('penitenciariaSelect')
const buscarBtn = document.getElementById('buscarBtn')
let penitenciariasData = []

export async function getPenitenciarias(page = 1, perPage = 50) {
  const url = `${API_URL}/prison-list-full?page=${page}&per_page=${perPage}`;
  try {
    const res = await fetch(url);

    // Falha HTTP -> loga corpo como texto (ajuda a ver HTML/redirect)
    if (!res.ok) {
      const txt = await res.text();
      throw new Error(`HTTP ${res.status} – ${txt.slice(0,200)}`);
    }

    // Garante que é JSON antes de parsear
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
      const txt = await res.text();
      console.error('Resposta não-JSON:', txt.slice(0, 200));
      throw new Error('Resposta não-JSON (provável HTML/redirect/login).');
    }

    const data = await res.json();
    return data.content ?? data.data ?? data; // flexível, caso a chave mude
  } catch (err) {
    console.error('Erro na requisição:', err.message);
    return [];
  }
}

async function carregarTodasPenitenciarias () {
  let page = 1
  let resultados = []

  while (true) {
    const penitenciarias = await getPenitenciarias(page)
    if (!penitenciarias.length) break

    resultados = resultados.concat(penitenciarias)
    page++
  }

  penitenciariasData = resultados
  populatePenitenciariasSelect(resultados)
}

function populatePenitenciariasSelect (penitenciarias) {
  select.innerHTML =
    '<option value="" disabled selected>Escolha a penitenciária</option>'

  penitenciarias.forEach(penitenciaria => {
    const option = document.createElement('option')
    option.value = penitenciaria.slug
    option.textContent = penitenciaria.nome

    select.appendChild(option)
  })
}

buscarBtn.addEventListener('click', () => {
  const selectedSlug = select.value

  if (!selectedSlug) {
    alert('Por favor, selecione uma penitenciária.')
    return
  }

  const dadosPenitenciaria = penitenciariasData.find(
    obj => obj.slug === selectedSlug
  )
  if (!dadosPenitenciaria) {
    alert('Penitenciária inválida. Tente novamente.')
    return
  }

  setItem('dadosPenitenciaria', dadosPenitenciaria)
  window.location.href = `shop.html?p=${encodeURIComponent(selectedSlug)}`
})

// Inicialização
carregarTodasPenitenciarias()
