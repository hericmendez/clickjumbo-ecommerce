<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_render_prisons_panel() {
    if (!current_user_can('manage_options')) {
        echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
        return;
    }
    ?>
    <div class="wrap">

                    <h1 class="mb-4 mt-4 fw-bold">Gerenciar Penitenciárias</h1>


        <div class="border border-secondary p-4 shadow-sm mb-4">
            <form id="filtro-penitenciarias" class="row g-3 align-items-end w-100">
                <div class="col-md-6">
                    <label class="form-label">Buscar por Nome</label>
                    <input type="text" id="filtro-nome" class="form-control" placeholder="Nome da penitenciária...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Ordenar por</label>
                    <select id="filtro-ordenacao" class="form-select">
                        <option value="id|desc">ID Decrescente</option>
                        <option value="id|asc">ID Crescente</option>
                        <option value="nome|asc">Nome A-Z</option>
                        <option value="nome|desc">Nome Z-A</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-primary w-100" onclick="carregarPenitenciarias(1)">Buscar</button>
                </div>
            </form>
        </div>


       <div class="d-flex flex-row justify-content-between align-items-center">
                            <div class="text-muted mb-2" id="info-itens">Exibindo 0 de 0 itens.</div>
                            
                    <a class="btn btn-primary ms-2" href="#" onclick="newPrison()">Cadastrar nova Penitenciária</a>
        </div>
<hr/>
        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Cidade</th>
                        <th>CEP</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tabela-penitenciarias">
                    <tr><td colspan="5">Carregando penitenciárias...</td></tr>
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
                    </select>
                </div>
            </nav>
        </div>
        <div class="modal fade" id="modalDetalhesPrison" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalhes da Penitenciária</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <dl class="row" id="detalhes-prison-content">
          <!-- Detalhes serão preenchidos via JS -->
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

    </div>

    <?php wp_nonce_field('wp_rest'); ?>
    <script>
    let paginaAtual = 1;
    let limitePorPagina = 10;
function verDetalhesPrison(slug) {
    const modal = new bootstrap.Modal(document.getElementById('modalDetalhesPrison'));
    const content = document.getElementById('detalhes-prison-content');
    content.innerHTML = '<div class="text-muted">Carregando...</div>';

    fetch(`/wp/wp-json/clickjumbo/v1/prison-details/${slug}`)
        .then(res => res.json())
        .then(json => {
            const p = json.content;
            if (!p) throw new Error("Dados não encontrados");

            content.innerHTML = `
                <dt class="col-sm-3">ID</dt><dd class="col-sm-9">${p.id}</dd>
                <dt class="col-sm-3">Nome</dt><dd class="col-sm-9">${p.nome}</dd>
                <dt class="col-sm-3">Cidade</dt><dd class="col-sm-9">${p.cidade}</dd>
                <dt class="col-sm-3">Estado</dt><dd class="col-sm-9">${p.estado}</dd>
                <dt class="col-sm-3">CEP</dt><dd class="col-sm-9">${p.cep}</dd>
                <dt class="col-sm-3">Logradouro</dt><dd class="col-sm-9">${p.logradouro}</dd>
                <dt class="col-sm-3">Número</dt><dd class="col-sm-9">${p.numero}</dd>
                <dt class="col-sm-3">Complemento</dt><dd class="col-sm-9">${p.complemento}</dd>
                <dt class="col-sm-3">Referência</dt><dd class="col-sm-9">${p.referencia}</dd>
                <dt class="col-sm-3">Criado em</dt><dd class="col-sm-9">${new Date(p.criado_em).toLocaleString('pt-BR')|| 'N/A'}</dd>
            `;
        })
        .catch(err => {
            console.error(err);
            content.innerHTML = '<div class="text-danger">Erro ao carregar detalhes.</div>';
        });

    modal.show();
}

    async function carregarPenitenciarias(pagina = 1) {
        const tbody = document.getElementById('tabela-penitenciarias');
        tbody.innerHTML = '<tr><td colspan="5">Carregando...</td></tr>';

        paginaAtual = pagina;
        limitePorPagina = parseInt(document.getElementById('filtro-itens-por-pagina').value || 10);
        const [campo, direcao] = document.getElementById('filtro-ordenacao').value.split('|');

        const params = new URLSearchParams({
            search: document.getElementById('filtro-nome').value,
            order_by: campo,
            order: direcao,
            page: pagina,
            per_page: limitePorPagina
        });

        const res = await fetch('/wp/wp-json/clickjumbo/v1/prison-list-full?' + params.toString());
        const json = await res.json();
        const lista = json.content || [];

        tbody.innerHTML = lista.length ? lista.map(item => `
            <tr>
                <td>#${item.id}</td>
                <td>${item.nome}</td>
                <td>${item.cidade}</td>
                <td>${item.cep}</td>
                <td>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            Ações
                        </button>
                        <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="verDetalhesPrison('${item.slug}')">Ver Detalhes</a></li>
<li><a class="dropdown-item" href="/wp/wp-admin/admin.php?page=clickjumbo-products&penitenciaria=${item.slug}">Ver Produtos</a></li>

                            <li><a class="dropdown-item" href="#" onclick="editarPrison('${item.slug}')">Editar</a></li>
                            <li><a class="dropdown-item text-danger" href="#" onclick="excluirPrison('${item.slug}', '${item.nome}')">Excluir</a></li>
                        </ul>
                    </div>
                </td>
            </tr>
        `).join('') : '<tr><td colspan="5">Nenhuma penitenciária encontrada.</td></tr>';

        document.getElementById('info-itens').textContent = `Exibindo ${lista.length} de ${json.total_itens} itens — Página ${json.pagina_atual} de ${json.total_paginas}`;
        renderizarPaginacao(json.pagina_atual, json.total_paginas);
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
                    carregarPenitenciarias(paginaAlvo);
                };
            }
            li.appendChild(a);
            return li;
        };

        pag.appendChild(criarBotao('‹', pagina - 1, false, pagina === 1));
        const delta = 2;
        const start = Math.max(1, pagina - delta);
        const end = Math.min(totalPaginas, pagina + delta);

        if (start > 1) {
            pag.appendChild(criarBotao('1', 1));
            if (start > 2) pag.appendChild(criarBotao('...', pagina - 1, false, true));
        }

        for (let i = start; i <= end; i++) {
            pag.appendChild(criarBotao(i, i, i === pagina));
        }

        if (end < totalPaginas) {
            if (end < totalPaginas - 1) pag.appendChild(criarBotao('...', pagina + 1, false, true));
            pag.appendChild(criarBotao(totalPaginas, totalPaginas));
        }

        pag.appendChild(criarBotao('›', pagina + 1, false, pagina === totalPaginas));
    }

    function editarPrison(slug) {
        window.location.href = `/wp/wp-admin/admin.php?page=clickjumbo-nova-penitenciaria&slug=${slug}`;
    }
    function newPrison() {
        window.location.href = `/wp/wp-admin/admin.php?page=clickjumbo-nova-penitenciaria`;
    }
    function excluirPrison(slug, nome) {
        if (!confirm(`Deseja realmente excluir a penitenciária "${nome}"?`)) return;
        fetch(`/wp/wp-json/clickjumbo/v1/delete-prison/${slug}`, {
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': '<?php echo wp_create_nonce("wp_rest"); ?>'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Penitenciária excluída com sucesso!');
                carregarPenitenciarias(paginaAtual);
            } else {
                alert('Erro ao excluir penitenciária.');
            }
        }).catch(err => {
            console.error(err);
            alert('Erro inesperado.');
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        carregarPenitenciarias();
        document.getElementById('filtro-itens-por-pagina')?.addEventListener('change', () => carregarPenitenciarias(1));
    });
    </script>
<?php
}
