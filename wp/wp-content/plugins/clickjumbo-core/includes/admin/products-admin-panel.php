<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_render_products_panel() {
    if (!current_user_can('manage_options')) {
        echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
        return;
    }
    ?>

    <div class="wrap">
        <h1 class="mb-4 mt-4 fw-bold">Gerenciar Produtos</h1>

        <div class="border border-secondary p-4 shadow-sm mb-4" >
            <h2 class="h5">Filtros de busca </h2>
            <form id="filtro-produtos" class="row g-3 align-items-end w-100">
                <div class=" col-md-10">
                    <label class="form-label">Nome do Produto</label>
                    <input type="text" id="filtro-nome" class="form-control" placeholder="Nome do produto...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Penitenciária</label>
                    <select id="filtro-penitenciaria" class="form-select">
                        <option value="">Todas</option> 
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categoria</label>
                    <select id="filtro-categoria" class="form-select">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordenar por</label>
                    <select id="filtro-ordenacao" class="form-select">
                        <option value="id|desc">Mais recente</option>
                        <option value="id|asc">Mais antigo</option>
                        <option value="nome|asc">Nome A-Z</option>
                        <option value="nome|desc">Nome Z-A</option>
                    </select>
                </div>
                <div>
<button type="button" class="btn btn-primary w-25" onclick="carregarProdutos(1)">Buscar</button>
                </div>
            </form>
        </div>

       <div class="d-flex flex-row justify-content-between align-items-center">
                            <div class="text-muted mb-2" id="info-itens">Exibindo 0 de 0 itens.</div>
                            
                    <a class="btn btn-primary ms-2" href="#" onclick="newPrison()">Cadastrar novo Produto</a>
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th onclick="ordenar('nome')">Produto</th>
                        <th>Categoria/Subcategoria</th>
                        <th>Penitenciárias</th>
                        <th onclick="ordenar('preco')">Preço</th>
                        <th>Peso</th>
                
                        <th>Máx. por cliente</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tabela-produtos">
                    <tr><td colspan="9">Carregando produtos...</td></tr>
                </tbody>
            </table>
            <nav class="d-flex justify-content-between align-items-center mt-4">
  <ul id="paginacao" class="pagination mb-0"></ul>
  <div class="ms-3 d-flex align-items-center">
    <label for="filtro-itens-por-pagina" class="me-2 mb-0">Itens por página:</label>
    <select id="filtro-itens-por-pagina" class="form-select form-select-sm" style="width: auto;">
      <option value="5">5</option>
      <option value="10" selected>10</option>
      <option value="20">20</option>
      <option value="50">50</option>
    </select>
  </div>
  <div class="modal fade" id="modalDetalhesProduto" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes do Produto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <dl class="row" id="detalhes-produto-content">
          <div class="text-muted">Carregando...</div>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

</nav>

        </div>
    </div>
<?php wp_nonce_field('wp_rest'); ?>
<script>
const wpApiSettings = {
    nonce: '<?php echo wp_create_nonce("wp_rest"); ?>'
};
</script>

    <script>
        
function verDetalhesProduto(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalhesProduto'));
    const content = document.getElementById('detalhes-produto-content');
    content.innerHTML = '<div class="text-muted">Carregando...</div>';

    fetch(`/wp/wp-json/clickjumbo/v1/product-details/${id}`)
        .then(res => res.json())
        .then(json => {
            const p = json.content;
            if (!p) throw new Error("Dados não encontrados");

            const pris = p.penitenciarias?.map(p => p.label).join(', ') || '—';

            content.innerHTML = `
                <dt class="col-sm-3">ID</dt><dd class="col-sm-9">${p.id}</dd>
                <dt class="col-sm-3">Nome</dt><dd class="col-sm-9">${p.nome}</dd>
                <dt class="col-sm-3">Categoria</dt><dd class="col-sm-9">${p.categoria} / ${p.subcategoria || '—'}</dd>
                <dt class="col-sm-3">Penitenciárias</dt><dd class="col-sm-9">${pris}</dd>
                <dt class="col-sm-3">Peso</dt><dd class="col-sm-9">${p.peso.toFixed(2)} kg</dd>
                <dt class="col-sm-3">Preço</dt><dd class="col-sm-9">R$ ${p.preco.toFixed(2).replace('.', ',')}</dd>
                <dt class="col-sm-3">SKU</dt><dd class="col-sm-9">${p.sku || '—'}</dd>
                <dt class="col-sm-3">Máximo por cliente</dt><dd class="col-sm-9">${p.maximo_por_cliente || '—'}</dd>
                <dt class="col-sm-3">Premium</dt><dd class="col-sm-9">${p.premium ? 'Sim' : 'Não'}</dd>
                <dt class="col-sm-3">Padrão</dt><dd class="col-sm-9">${p.padrao ? 'Sim' : 'Não'}</dd>
                <dt class="col-sm-3">Criado em</dt><dd class="col-sm-9">${p.criado_em ? new Date(p.criado_em).toLocaleString('pt-BR') : '—'}</dd>
            `;
        })
        .catch(err => {
            console.error(err);
            content.innerHTML = '<div class="text-danger">Erro ao carregar detalhes.</div>';
        });

    modal.show();
}        
    
let paginaAtual = 1;
let limitePorPagina = 10; // valor inicial padrão

let totalItens = 0;

    let ordenacao = {
        campo: 'id',
        direcao: 'desc'
    };

    async function fetchOptions(endpoint, selectId) {
        const res = await fetch(endpoint);
        const json = await res.json();
        const data = json.content || json.categories || json;
        const select = document.getElementById(selectId);
        if (!Array.isArray(data)) return;
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.slug || item.term_id;
            opt.textContent = item.name || item.label;
            select.appendChild(opt);
        });
    }
const slugPenitenciaria = new URLSearchParams(window.location.search).get('penitenciaria');

    async function carregarFiltros() {1
        await fetchOptions('/wp/wp-json/clickjumbo/v1/prison-list', 'filtro-penitenciaria');
        await fetchOptions('/wp/wp-json/clickjumbo/v1/get-categories', 'filtro-categoria');
    }
if (slugPenitenciaria) {
    document.getElementById('filtro-penitenciaria').value = slugPenitenciaria;
}

    function ordenar(campo) {
        if (ordenacao.campo === campo) {
            ordenacao.direcao = ordenacao.direcao === 'asc' ? 'desc' : 'asc';
        } else {
            ordenacao.campo = campo;
            ordenacao.direcao = 'asc';
        }
        carregarProdutos();
    }

    function formatPrisons(pens) {
        if (!pens || !Array.isArray(pens)) return 'Todas';
        return pens.map(p => p.label).join(', ');
    }

    function formatDecimal(n) {
        return `${n.toFixed(2).replace('.', ',')}`;
    }

    function formatData(dataStr) {
        if (!dataStr) return '—';
        const d = new Date(dataStr);
        if (isNaN(d)) return '—';
        return d.toLocaleString('pt-BR', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });
    }
function renderizarPaginacao(pagina, totalPaginas) {
    const pag = document.getElementById('paginacao');
    pag.innerHTML = '';

    if (totalPaginas <= 1) return;

    const criarBotao = (label, paginaAlvo, ativo = false, desabilitado = false) => {
        const li = document.createElement('li');
        li.className = `page-item${ativo ? ' active' : ''}${desabilitado ? ' disabled' : ''}`;
        const a = document.createElement('a');
        a.className = 'page-link';
        a.href = '#';
        a.textContent = label;
        if (!desabilitado) {
            a.onclick = e => {
                e.preventDefault();
                carregarProdutos(paginaAlvo);
            };
        }
        li.appendChild(a);
        return li;
    };


    // Botão anterior
    pag.appendChild(criarBotao('‹', pagina - 1, false, pagina === 1));

    const delta = 2;
    const start = Math.max(1, pagina - delta);
    const end = Math.min(totalPaginas, pagina + delta);

    if (start > 1) {
        pag.appendChild(criarBotao('1', 1, pagina === 1));
        if (start > 2) pag.appendChild(criarBotao('...', pagina - 1, false, true));
    }

    for (let i = start; i <= end; i++) {
        pag.appendChild(criarBotao(i, i, i === pagina));
    }

    if (end < totalPaginas) {
        if (end < totalPaginas - 1) pag.appendChild(criarBotao('...', pagina + 1, false, true));
        pag.appendChild(criarBotao(totalPaginas, totalPaginas, pagina === totalPaginas));
    }

    // Botão próximo
    pag.appendChild(criarBotao('›', pagina + 1, false, pagina === totalPaginas));
}


async function carregarProdutos(pagina = 1) {
    const urlParams = new URLSearchParams(window.location.search);
const slugPenitenciaria = urlParams.get('penitenciaria') || '';

    const tbody = document.getElementById('tabela-produtos');
    tbody.innerHTML = '<tr><td colspan="9">Carregando produtos...</td></tr>';
paginaAtual = pagina;
limitePorPagina = parseInt(document.getElementById('filtro-itens-por-pagina')?.value || 10);

    const selectOrdenacao = document.getElementById('filtro-ordenacao');
    const [campo, direcao] = selectOrdenacao.value.split('|');
    ordenacao = { campo, direcao };

    const params = new URLSearchParams({
        nome: document.getElementById('filtro-nome')?.value || '',
        penitenciaria: slugPenitenciaria || document.getElementById('filtro-penitenciaria')?.value || '',
        categoria: document.getElementById('filtro-categoria')?.value || '',
        ordenar_por: ordenacao.campo,
        direcao: ordenacao.direcao,
        pagina: paginaAtual,
        limite: limitePorPagina
    });


    const res = await fetch('/wp/wp-json/clickjumbo/v1/product-list?' + params.toString());
    const produtos = await res.json();
    const lista = produtos.content || produtos;

    tbody.innerHTML = lista.length ? lista.map(produto => `
        <tr>
            <td>#${produto.id}</td>
            <td>${produto.nome}</td>
            <td>${produto.categoria}/${produto.subcategoria || '—'}</td>
            <td>${formatPrisons(produto.penitenciarias)}</td>
            <td>R$ ${formatDecimal(produto.preco)}</td>
            <td>${formatDecimal(produto.peso)} kg</td>
            <td>${produto.maximo_por_cliente} ${produto.maximo_por_cliente > 1 ? 'unidades' : 'unidade'}</td>
            <td>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    Ações
                  </button>
                  <ul class="dropdown-menu">
                  <li><a class="dropdown-item" href="#" onclick="verDetalhesProduto(${produto.id})">Ver Detalhes</a></li>

                    <li><a class="dropdown-item" href="#" onclick="editarProduto(${produto.id})">Editar</a></li>
                    <li><a class="dropdown-item text-danger" href="#" onclick="excluirProduto(${produto.id}, '${produto.nome}')">Excluir</a></li>
                  </ul>
                </div>
            </td>
        </tr>
    `).join('') : '<tr><td colspan="9">Nenhum produto encontrado.</td></tr>';

totalItens = produtos.total_itens || lista.length;
document.getElementById('info-itens').textContent = `Exibindo ${lista.length} de ${produtos.total} itens — Página ${produtos.pagina} de ${produtos.total_paginas}`;

renderizarPaginacao(produtos.pagina, produtos.total_paginas);

}

document.getElementById('filtro-itens-por-pagina').addEventListener('change', (e) => {
    limitePorPagina = parseInt(e.target.value);
    carregarProdutos(1); // volta pra página 1
});

    function editarProduto(id) {
        window.location.href = `/wp/wp-admin/admin.php?page=clickjumbo-novo-produto&produto_id=${id}`;
    }

    function excluirProduto(id, nome) {
        if (!confirm(`Tem certeza que deseja excluir o produto "${nome}"?`)) return;
        fetch(`/wp/wp-json/clickjumbo/v1/delete-product/${id}`, {
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': wpApiSettings.nonce
            }
        }).then(res => res.json()).then(data => {
            if (data.success) {
                alert('Produto excluído com sucesso!');
                carregarProdutos();
            } else {
                alert('Erro ao excluir produto.');
            }
        }).catch(err => {
            console.error(err);
            alert('Erro inesperado.');
        });
    }

document.addEventListener('DOMContentLoaded', () => {
    carregarFiltros();
    carregarProdutos();

    document.getElementById('filtro-itens-por-pagina')?.addEventListener('change', (e) => {
        limitePorPagina = parseInt(e.target.value);
        carregarProdutos(1); // volta pra primeira página
    });
});

    </script>

    <?php
}
