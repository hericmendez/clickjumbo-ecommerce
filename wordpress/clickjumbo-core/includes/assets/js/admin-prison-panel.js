// admin-prison-panel.js

const $ = document.querySelector.bind(document);
const painelLista = $("#painel-lista");
const painelForm = $("#painel-formulario");
const btnVerLista = $("#btn-ver-lista");
const btnCadastrar = $("#btn-cadastrar-nova");
const form = $("#form-cadastro-prison");
const msg = $("#mensagem");
const formTitle = $("#form-title");
const submitButton = $("#submit-button");

let modoEdicao = null;

btnVerLista.onclick = (e) => (e.preventDefault(), mostrarLista());
btnCadastrar.onclick = (e) => (e.preventDefault(), iniciarCadastro());
document.addEventListener("DOMContentLoaded", mostrarLista);
document.addEventListener("DOMContentLoaded", () => {
    mostrarLista();

    const cepInput = document.getElementById("cep");

    cepInput.addEventListener("input", (e) => {
        // Remove tudo que não for número
        let val = e.target.value.replace(/\D/g, '').slice(0, 8);
        // Aplica a máscara 11111-111
        if (val.length > 5) {
            val = val.replace(/^(\d{5})(\d{1,3})/, "$1-$2");
        }
        e.target.value = val;
    });

    // Evita digitação de letras (redundância defensiva para navegadores mais antigos)
    cepInput.addEventListener("keypress", (e) => {
        if (!/[0-9]/.test(e.key)) {
            e.preventDefault();
        }
    });
});


function iniciarCadastro() {
  modoEdicao = null;
  formTitle.textContent = "Cadastrar nova penitenciária";
  submitButton.textContent = "Cadastrar";
  form.reset();
  msg.innerHTML = "";
  painelLista.style.display = "none";
  painelForm.style.display = "block";
}

function mostrarLista() {
  painelLista.style.display = "block";
  painelForm.style.display = "none";
  carregarTabela();
}

async function carregarTabela() {
  const body = $("#prison-table-body");
  body.innerHTML = `<tr><td colspan="5">Carregando...</td></tr>`;
  try {
    const res = await fetch("/wp-json/clickjumbo/v1/prison-list-full");
    const { content } = await res.json();
    body.innerHTML = content
      .map(
        (p) => `
            <tr>
                <td>${p.nome}</td>
                <td>${p.cidade}</td>
                <td>${p.estado}</td>
                <td>${p.cep}</td>
                <td>
                    <div class="dropdown">
                        <button class="button">&#x22EE;</button>
                        <div class="dropdown-content">
                            <a href="#" onclick="verProdutos('${p.slug}')">📦 Ver Produtos</a>
                            <a href="/wp-admin/admin.php?page=clickjumbo-novo-produto&penitenciaria=${p.slug}">➕ Novo Produto</a>
                            <a href="#" onclick="editPrison('${p.slug}')">✏️ Editar</a>
                            <a href="#" onclick="deletePrison('${p.slug}')">🗑️ Excluir</a>
                        </div>
                    </div>
                </td>
            </tr>
        `
      )
      .join("");
  } catch (e) {
    body.innerHTML = `<tr><td colspan="5">Erro ao carregar penitenciárias.</td></tr>`;
  }
}

async function verProdutos(slug) {
  const painel = document.getElementById("painel-produtos");
  const titulo = document.getElementById("titulo-produtos");
  const tbody = document.getElementById("produtos-da-penitenciaria");

  painel.style.display = "block";
  titulo.textContent = "Produtos da penitenciária (carregando...)";
  tbody.innerHTML = `<tr><td colspan="3">Carregando...</td></tr>`;

  try {
    const resPrison = await fetch(
      `/wp-json/clickjumbo/v1/prison-details/${slug}`
    );
    const prisonData = await resPrison.json();
    const nomePenitenciaria = prisonData.content?.nome || slug;

    const res = await fetch(
      `/wp-json/clickjumbo/v1/product-list/prison?slug=${slug}`,
      {
        credentials: "include",
      }
    );
    if (!res.ok) throw new Error("Erro ao buscar produtos");

    const { content } = await res.json();
    titulo.textContent = `Produtos da penitenciária ${nomePenitenciaria} (${content.length})`;

    if (!content.length) {
      tbody.innerHTML = `<tr><td colspan="3">Nenhum produto encontrado.</td></tr>`;
      return;
    }

    tbody.innerHTML = content
      .map(
        (prod) => `
            <tr id="produto-${prod.id}">
                <td>${prod.name}</td>
                <td>${prod.price || "—"}</td>
                <td>${prod.category || "—"}</td>
                <td>
                    <div class="dropdown">
                        <button class="button">&#x22EE;</button>
                        <div class="dropdown-content">
                            <a href="#" onclick="verDetalhesProduto('${
                              prod.id
                            }')">🔍 Ver Detalhes</a>
                            <a href="#" onclick="editProduct('${
                              prod.id
                            }', '${slug}')">✏️ Editar Produto</a>
                            <a href="#" onclick="deleteProduct('${
                              prod.id
                            }')">🗑️ Excluir Produto</a>
                        </div>
                    </div>
                </td>
            </tr>
        `
      )
      .join("");
  } catch (err) {
    titulo.textContent = "Erro ao carregar produtos.";
    tbody.innerHTML = `<tr><td colspan="3">Falha na requisição.</td></tr>`;
  }
}

async function verDetalhesProduto(id) {
  const modal = document.getElementById("modal-detalhes");
  const overlay = document.getElementById("modal-overlay");
  const conteudo = document.getElementById("modal-conteudo");

  conteudo.innerHTML = "Carregando...";

  try {
    const res = await fetch(`/wp-json/clickjumbo/v1/product-details/${id}`, {
      credentials: "include",
      headers: { "X-WP-Nonce": clickjumbo_data.nonce },
    });

    if (!res.ok) throw new Error("Erro ao carregar dados");

    const { content } = await res.json();

    conteudo.innerHTML = `
            <p><strong>Nome:</strong> ${content.name}</p>
            <p><strong>Preço:</strong> R$ ${content.price}</p>
            <p><strong>Peso:</strong> ${content.weight} kg</p>
            <p><strong>SKU:</strong> ${content.sku || "—"}</p>
            <p><strong>Categoria:</strong> ${content.categoria || "—"}</p>
            <p><strong>Subcategoria:</strong> ${content.subcategoria || "—"}</p>
            <p><strong>Limite por cliente:</strong> ${
              content.maxUnitsPerClient || "—"
            }</p>
        `;

    modal.style.display = "block";
    overlay.style.display = "block";
  } catch (err) {
    conteudo.innerHTML = `<p style="color:red;">Erro ao carregar os dados.</p>`;
  }
}

function fecharModal() {
  document.getElementById("modal-detalhes").style.display = "none";
  document.getElementById("modal-overlay").style.display = "none";
}

function editProduct(id, slug = "") {
  const url = new URL(window.location.origin + "/wp-admin/admin.php");
  url.searchParams.set("page", "clickjumbo-novo-produto");
  url.searchParams.set("editar_produto", id);
  if (slug) url.searchParams.set("penitenciaria", slug);
  location.href = url.toString();
}

function deleteProduct(id) {
  if (!confirm("Tem certeza que deseja excluir este produto?")) return;

  fetch(`/wp-json/clickjumbo/v1/delete-product/${id}`, {
    method: "DELETE",
    credentials: "include",
    headers: { "X-WP-Nonce": clickjumbo_data.nonce },
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        alert("Produto excluído com sucesso.");
        const linha = document.getElementById(`produto-${id}`);
        if (linha) linha.remove();
      } else {
        alert("Erro ao excluir: " + (data.message || "Erro desconhecido."));
      }
    })
    .catch((err) => {
      console.error(err);
      alert("Erro de comunicação com o servidor.");
    });
}

async function editPrison(slug) {
  const res = await fetch(`/wp-json/clickjumbo/v1/prison-details/${slug}`, {
    credentials: "include",
    headers: { "X-WP-Nonce": clickjumbo_data.nonce },
  });

  if (!res.ok) {
    const error = await res.json();
    alert("Erro: " + (error.message || "Não foi possível carregar os dados"));
    return;
  }

  const { content } = await res.json();
  $("#nome").value = content.nome;
  $("#cidade").value = content.cidade;
  $("#estado").value = content.estado;
  $("#cep").value = content.cep;

  modoEdicao = slug;
  formTitle.textContent = `Editando: ${content.nome}`;
  submitButton.textContent = "Salvar";
  painelLista.style.display = "none";
  painelForm.style.display = "block";
}

async function deletePrison(slug) {
  if (!confirm("Tem certeza que deseja excluir esta penitenciária?")) return;

  await fetch(`/wp-json/clickjumbo/v1/delete-prison/${slug}`, {
    method: "DELETE",
    credentials: "include",
    headers: { "X-WP-Nonce": clickjumbo_data.nonce },
  });

  msg.innerHTML = `<p style="color:green;">Penitenciária excluída com sucesso!</p>`;
  modoEdicao = null;
  painelForm.style.display = "none";
  painelLista.style.display = "block";
  carregarTabela();
}

form.onsubmit = async (e) => {
  e.preventDefault();
  const dados = {
    nome: $("#nome").value.trim(),
    cidade: $("#cidade").value.trim(),
    estado: $("#estado").value.trim(),
    cep: $("#cep").value.trim(),
  };

  // Validações
  if (!dados.nome || !dados.cidade || !dados.estado || !dados.cep) {
    msg.innerHTML = `<p style="color:red;">Preencha todos os campos obrigatórios.</p>`;
    return;
  }

  msg.innerHTML = "Enviando...";

  const url = modoEdicao
    ? `/wp-json/clickjumbo/v1/update-prison/${modoEdicao}`
    : `/wp-json/clickjumbo/v1/register-prison`;
  const method = modoEdicao ? "PUT" : "POST";

  try {
    const res = await fetch(url, {
      method,
      headers: {
        "Content-Type": "application/json",
        "X-WP-Nonce": clickjumbo_data.nonce,
      },
      body: JSON.stringify(dados),
      credentials: "include",
    });

    const data = await res.json();
    if (data.success) {
      msg.innerHTML = `<p style="color:green;">${
        data.message || "Sucesso!"
      }</p>`;
      form.reset();
      mostrarLista();
    } else {
      msg.innerHTML = `<p style="color:red;">${
        data.message || "Erro ao salvar."
      }</p>`;
    }
  } catch (err) {
    msg.innerHTML = `<p style="color:red;">Erro inesperado.</p>`;
  }
};
