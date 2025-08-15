<?php
if (!defined('ABSPATH'))
    exit;


add_action('admin_enqueue_scripts', function ($hook) {
    if (isset($_GET['page']) && $_GET['page'] === 'clickjumbo-orders') {
        // Se algum outro lugar registrou bootstrap, limpe:
        wp_deregister_script('bootstrap');
        wp_deregister_script('bootstrap-bundle');
        wp_deregister_script('bootstrap-modal');
        // Enfileire UMA única versão (ex: v5 bundle local do plugin)
        wp_enqueue_style('cj-bootstrap-5', plugins_url('assets/bootstrap.min.css', __FILE__), [], '5.3.3');
        wp_enqueue_script('cj-bootstrap-5', plugins_url('assets/bootstrap.bundle.min.js', __FILE__), [], '5.3.3', true);
    }
}, 20);

function clickjumbo_render_orders_panel()
{
    ?>
    <?php wp_nonce_field('wp_rest'); ?>
    <script>
        window.clickjumbo_data = {
            nonce: "<?php echo wp_create_nonce('wp_rest'); ?>"
        };
    </script>
<style>
  th.sortable { cursor: pointer; user-select: none; }
  th.sortable .sort-icon { margin-left: .25rem; opacity: .7; font-size: .85em; }
  th.sortable.active { color: var(--bs-primary); }
</style>

    <div class="wrap">
        <h1 class="mt-4 mb-4 fw-bold">Painel de Pedidos</h1>
    <hr class="mt-0 p-0"/>
<input type="text" id="search-input" class="form-control mb-3" placeholder="Buscar por cliente ou penitenciária...">
<div class="table-responsive">  <table class="table table-striped table-hover align-middle">
<thead>
  <tr>
    <th class="sortable" data-col="id" onclick="ordenarPor('id')">ID <span class="sort-icon"></span></th>
    <th class="sortable" data-col="cliente" onclick="ordenarPor('cliente')">Cliente <span class="sort-icon"></span></th>
    <th class="sortable" data-col="penitenciaria" onclick="ordenarPor('penitenciaria')">Penitenciária <span class="sort-icon"></span></th>
    <th class="sortable" data-col="total" onclick="ordenarPor('total')">Total <span class="sort-icon"></span></th>
    <th class="sortable" data-col="status" onclick="ordenarPor('status')">Status <span class="sort-icon"></span></th>
    <th class="sortable" data-col="data" onclick="ordenarPor('data')">Data <span class="sort-icon"></span></th>
    <th>Ações</th>
  </tr>
</thead>


            <tbody id="tabela-pedidos"></tbody>
        </table>
        <div class="d-flex flex-wrap align-items-center gap-2 justify-content-between mt-2" id="pager-wrap">
  <div class="d-flex align-items-center gap-2">
    <label for="page-size" class="form-label m-0">Itens por página:</label>
    <select id="page-size" class="form-select form-select-sm" style="width:auto;">
      <option value="10" selected>10</option>
      <option value="25">25</option>
      <option value="50">50</option>
      <option value="100">100</option>
    </select>
    <span id="pager-info" class="text-muted ms-2 small"></span>
  </div>
  <nav aria-label="Paginação de pedidos">
    <ul id="pager" class="pagination pagination-sm m-0"></ul>
  </nav>
</div>

        </div>

      
    </div>

    <div id="modal-detalhes"
        style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; padding:20px; z-index:1000; max-width: 600px; overflow:auto">
        <button onclick="fecharModal()" style="float:right">Fechar</button>
        <pre id="modal-conteudo"></pre>
    </div>


<script>
const wpNonce = '<?php echo wp_create_nonce("wp_rest"); ?>';
function atualizarIconesHeader() {
  document.querySelectorAll('th.sortable').forEach(th => {
    const col = th.dataset.col;
    const span = th.querySelector('.sort-icon');
    if (!span) return;

    if (col === ordemAtual.coluna) {
      span.textContent = ordemAtual.direcao === 'asc' ? '▲' : '▼';
      th.classList.add('active');
    } else {
      span.textContent = '↕';
      th.classList.remove('active');
    }
  });
}

// ===== helpers de status (PT + classes de badge) =====
const STATUS_LABEL = {
  pending: 'Pendente',
  processing: 'Processando',
  cancelled: 'Cancelado',
  completed: 'Concluído',
  sent: 'Enviado',
  awaiting_shipment: 'Aguardando envio',
  failed: 'Falhou',
  refunded: 'Reembolsado'
};
const STATUS_BADGE = {
  pending: 'warning',
  processing: 'primary',
  cancelled: 'danger',
  sent: 'info',
  awaiting_shipment: 'outline info',
  completed: 'success',
  failed: 'dark',
  refunded: 'secondary'
};
const labelStatus = s => STATUS_LABEL[s] || (s || '—');
const badgeClasse = s => 'text-bg-' + (STATUS_BADGE[s] || 'secondary');

function formatarData(dataStr) {
  const d = new Date(dataStr);
  return d.toLocaleString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit'
  });
}
const clienteNome = p =>
  (typeof p.cliente === 'string' ? p.cliente : (p.cliente?.nome || ''));

// ===== dataset & estado =====
let pedidos = [];
let ordemAtual = { coluna: 'data', direcao: 'desc' };

let filtroTermo = '';

let paginaAtual = 1;
let tamanhoPagina = 10;
let listaAtual = []; // lista filtrada + ordenada (base para a paginação)

// ===== render =====
function gerarAcoesDropdown(pedido) {
  return `
    <div class="dropdown">
      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
        Ações
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="#" onclick="verDetalhes(${pedido.id});return false;">Ver detalhes</a></li>
        <li><h6 class="dropdown-header">Mudar status</h6></li>
        <li><a class="dropdown-item" href="#" onclick="mudarStatus(${pedido.id}, 'pending');return false;">Pendente</a></li>
        <li><a class="dropdown-item" href="#" onclick="mudarStatus(${pedido.id}, 'processing');return false;">Processando</a></li>
        <li><a class="dropdown-item" href="#" onclick="mudarStatus(${pedido.id}, 'cancelled');return false;">Cancelado</a></li>
        <li><a class="dropdown-item" href="#" onclick="mudarStatus(${pedido.id}, 'completed');return false;">Concluído</a></li>
        <li><a class="dropdown-item" href="#" onclick="mudarStatus(${pedido.id}, 'refunded');return false;">Reembolsado</a></li>
        <li><a class="dropdown-item" href="#" onclick="mudarStatus(${pedido.id}, 'awaiting_shipment');return false;">Aguardando envio</a></li>
        <li><a class="dropdown-item" href="#" onclick="mudarStatus(${pedido.id}, 'sent');return false;">Enviado</a></li>
        <li><a class="dropdown-item" href="#" onclick="mudarStatus(${pedido.id}, 'completed');return false;">Concluído</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="#" onclick="deletarPedido(${pedido.id});return false;">Excluir</a></li>
      </ul>
    </div>`;
}

function linhaPedido(p) {
  const badge = badgeClasse(p.status);
  const label = labelStatus(p.status);
  return `
    <tr data-id="${p.id}">
      <td>${p.id}</td>
      <td>${clienteNome(p) || '—'}</td>
      <td>${p.penitenciaria?.nome || '—'}</td>
      <td>R$ ${p.total}</td>
      <td><span class="badge ${badge}">${label}</span></td>
      <td>${formatarData(p.data)}</td>
      <td>${gerarAcoesDropdown(p)}</td>
    </tr>`;
}

function renderizarPedidos(lista) {
  const tbody = document.getElementById('tabela-pedidos');
  tbody.innerHTML = '';
  if (!lista.length) {
    tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Nenhum pedido encontrado.</td></tr>';
    return;
  }
  lista.forEach(p => tbody.insertAdjacentHTML('beforeend', linhaPedido(p)));
}

// ===== filtro + ordenação (gera listaAtual) =====
function aplicarFiltroEOrdenacao() {
  let lista = [...pedidos];

  if (filtroTermo) {
    const t = filtroTermo.toLowerCase();
    lista = lista.filter(p =>
      (clienteNome(p) || '').toLowerCase().includes(t) ||
      (p.penitenciaria?.nome || '').toLowerCase().includes(t)
    );
  }

  lista.sort((a, b) => {
    let va, vb;
    switch (ordemAtual.coluna) {
      case 'cliente':
        va = (clienteNome(a) || '').toLowerCase(); vb = (clienteNome(b) || '').toLowerCase(); break;
      case 'penitenciaria':
        va = (a.penitenciaria?.nome || '').toLowerCase();
        vb = (b.penitenciaria?.nome || '').toLowerCase();
        break;
      case 'total':
        va = parseFloat(a.total) || 0; vb = parseFloat(b.total) || 0; break;
      case 'status':
        va = (labelStatus(a.status) || '').toLowerCase(); vb = (labelStatus(b.status) || '').toLowerCase(); break;
      case 'data':
        va = new Date(a.data).getTime() || 0; vb = new Date(b.data).getTime() || 0; break;
      default: // 'id'
        va = Number(a.id) || 0; vb = Number(b.id) || 0;
    }
    const cmp = va > vb ? 1 : (va < vb ? -1 : 0);
    return ordemAtual.direcao === 'asc' ? cmp : -cmp;
  });

  listaAtual = lista;
  atualizarIconesHeader();
  renderPage();
}

function ordenarPor(coluna) {
  if (ordemAtual.coluna === coluna) {
    ordemAtual.direcao = ordemAtual.direcao === 'asc' ? 'desc' : 'asc';
  } else {
    ordemAtual.coluna = coluna;
    ordemAtual.direcao = 'asc';
  }
  paginaAtual = 1;
  atualizarIconesHeader();
  aplicarFiltroEOrdenacao();
}


// ===== paginação =====
function renderPage() {
  const totalItens = listaAtual.length;
  const totalPaginas = Math.max(1, Math.ceil(totalItens / tamanhoPagina));

  // Corrige página atual se necessário
  if (paginaAtual > totalPaginas) paginaAtual = totalPaginas;
  if (paginaAtual < 1) paginaAtual = 1;

  const inicio = (paginaAtual - 1) * tamanhoPagina;
  const fimExclusivo = Math.min(inicio + tamanhoPagina, totalItens);

  renderizarPedidos(listaAtual.slice(inicio, fimExclusivo));

  // Info "Mostrando X–Y de Z"
  const infoEl = document.getElementById('pager-info');
  if (infoEl) {
    infoEl.textContent = totalItens
      ? `Mostrando ${inicio + 1}–${fimExclusivo} de ${totalItens}`
      : 'Sem registros';
  }

  construirPaginacao(totalPaginas);
}

function construirPaginacao(totalPaginas) {
  const ul = document.getElementById('pager');
  if (!ul) return;
  ul.innerHTML = '';

  const criarItem = (label, page, disabled = false, active = false) => {
    const li = document.createElement('li');
    li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.setAttribute('aria-label', `Página ${page}`);
    a.textContent = label;
    a.addEventListener('click', (e) => {
      e.preventDefault();
      if (disabled || active) return;
      paginaAtual = page;
      renderPage();
    });
    li.appendChild(a);
    return li;
  };

  // Prev
  ul.appendChild(criarItem('«', Math.max(1, paginaAtual - 1), paginaAtual === 1));

  // janela de páginas (máx 5)
  const maxVisiveis = 5;
  let ini = Math.max(1, paginaAtual - Math.floor(maxVisiveis / 2));
  let fim = Math.min(totalPaginas, ini + maxVisiveis - 1);
  ini = Math.max(1, Math.min(ini, Math.max(1, totalPaginas - maxVisiveis + 1)));

  for (let p = ini; p <= fim; p++) {
    ul.appendChild(criarItem(String(p), p, false, p === paginaAtual));
  }

  // Next
  ul.appendChild(criarItem('»', Math.min(totalPaginas, paginaAtual + 1), paginaAtual === totalPaginas));
}

// ===== ações =====
async function verDetalhes(id) {
  try {
    const res = await fetch(`https://clickjumbo.com.br/wp/wp-json/clickjumbo/v1/orders/${id}`);
    const pedido = await res.json();
    abrirModalDetalhesPedido(pedido);
  } catch (err) {
    console.error(err);
    alert("Erro ao carregar os detalhes do pedido.");
  }
}

async function mudarStatus(id, novoStatus) {
  const idx = pedidos.findIndex(p => p.id === id);
  if (idx === -1) return;

  // otimista: atualiza badge já
  const anterior = pedidos[idx].status;
  pedidos[idx].status = novoStatus;
  const tr = document.querySelector(`tr[data-id="${id}"]`);
  if (tr) {
    const badge = tr.querySelector('.badge');
    if (badge) {
      badge.className = 'badge ' + badgeClasse(novoStatus);
      badge.textContent = labelStatus(novoStatus);
    }
  }

  try {
    const res = await fetch(
      `https://clickjumbo.com.br/wp/wp-json/clickjumbo/v1/orders/${id}/status`,
      {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': wpNonce
        },
        body: JSON.stringify({ status: novoStatus })
      }
    );
    const data = await res.json();
    if (!res.ok || data.success === false) throw new Error(data.message || 'Falha ao atualizar');
  } catch (e) {
    // rollback na UI
    pedidos[idx].status = anterior;
    if (tr) {
      const badge = tr.querySelector('.badge');
      if (badge) {
        badge.className = 'badge ' + badgeClasse(anterior);
        badge.textContent = labelStatus(anterior);
      }
    }
    console.error(e);
    alert('Erro ao atualizar status.');
  }
}

async function deletarPedido(id) {
  if (!confirm("Tem certeza que deseja excluir este pedido?")) return;

  const tr = document.querySelector(`tr[data-id="${id}"]`);
  if (tr) tr.style.opacity = '0.5';

  try {
    const res = await fetch(`https://clickjumbo.com.br/wp/wp-json/clickjumbo/v1/orders/${id}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: { 'X-WP-Nonce': window.clickjumbo_data.nonce }
    });
    const data = await res.json();
    if (!res.ok || data.success === false) throw new Error(data.message || 'Falha ao excluir');

    // remove do array e re-renderiza respeitando paginação/filtro/ordem
    const idx = pedidos.findIndex(p => p.id === id);
    if (idx !== -1) pedidos.splice(idx, 1);

    aplicarFiltroEOrdenacao(); // recalcula listaAtual e refaz paginação

  } catch (err) {
    if (tr) tr.style.opacity = '';
    console.error(err);
    alert("Erro ao excluir pedido.");
  }
}

// ===== boot =====
document.addEventListener('DOMContentLoaded', async () => {
  const tbody = document.getElementById('tabela-pedidos');
  const searchInput = document.getElementById('search-input');
  const pageSizeSelect = document.getElementById('page-size');

  async function carregarPedidos() {
    tbody.innerHTML = '<tr><td colspan="7">Carregando pedidos...</td></tr>';
    try {
      const res = await fetch('https://clickjumbo.com.br/wp/wp-json/clickjumbo/v1/orders');
      pedidos = await res.json();
      paginaAtual = 1;
      aplicarFiltroEOrdenacao();
    } catch (err) {
      console.error(err);
      tbody.innerHTML = '<tr><td colspan="7">Erro ao carregar pedidos</td></tr>';
    }
  }

  searchInput.addEventListener('input', () => {
    filtroTermo = searchInput.value || '';
    paginaAtual = 1; // volta pra primeira página ao buscar
    aplicarFiltroEOrdenacao();
  });

  if (pageSizeSelect) {
    pageSizeSelect.addEventListener('change', () => {
      const v = parseInt(pageSizeSelect.value, 10);
      tamanhoPagina = Number.isNaN(v) ? 10 : v;
      paginaAtual = 1;
      renderPage(); // não precisa reordenar/filtrar de novo
    });
  }
atualizarIconesHeader();

  carregarPedidos();
});
</script>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<?php
// garante que o HTML+JS do modal entra no final do painel
if (function_exists('cj_get_order_details_modal_html')) {
    echo cj_get_order_details_modal_html();
} else {
    // se a função estiver em outro arquivo, inclua e chame
    require_once plugin_dir_path(__FILE__) . 'order-details-modal.php';
    echo cj_get_order_details_modal_html();
}
?>
    <?php
}
