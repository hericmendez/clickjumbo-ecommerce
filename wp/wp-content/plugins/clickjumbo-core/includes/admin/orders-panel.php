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

    <div class="wrap">
        <h1 class="mt-4 mb-4 fw-bold">Painel de Pedidos</h1>
    <hr class="mt-0 p-0"/>
<input type="text" id="search-input" class="form-control mb-3" placeholder="Buscar por cliente ou penitenciária...">
<div class="table-responsive">  <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th onclick="ordenarPor('id')">ID</th>
                    <th onclick="ordenarPor('cliente')">Cliente</th>
                    <th onclick="ordenarPor('penitenciaria')">Penitenciária</th>
                    <th onclick="ordenarPor('total')">Total</th>
                    <th onclick="ordenarPor('status')">Status</th>
                    <th onclick="ordenarPor('data')">Data</th>

                    <th>Ações</th>
                </tr>
            </thead>

            <tbody id="tabela-pedidos"></tbody>
        </table></div>

      
    </div>

    <div id="modal-detalhes"
        style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; padding:20px; z-index:1000; max-width: 600px; overflow:auto">
        <button onclick="fecharModal()" style="float:right">Fechar</button>
        <pre id="modal-conteudo"></pre>
    </div>



 <script>
const wpNonce = '<?php echo wp_create_nonce("wp_rest"); ?>';

// ===== helpers de status (PT + classes de badge) =====
const STATUS_LABEL = {
  pending: 'Pendente',
  processing: 'Processando',
  cancelled: 'Cancelado',
  completed: 'Concluído',
  failed: 'Falhou',
  refunded: 'Reembolsado'
};
const STATUS_BADGE = {
  pending: 'warning',
  processing: 'primary',
  cancelled: 'danger',
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

// ===== dataset =====
let pedidos = [];
let ordemAtual = { coluna: 'id', direcao: 'asc' };
let filtroTermo = '';

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
  lista.forEach(p => tbody.insertAdjacentHTML('beforeend', linhaPedido(p)));
}

function aplicarFiltroEOrdenacao() {
  let lista = [...pedidos];

  if (filtroTermo) {
    const t = filtroTermo.toLowerCase();
    lista = lista.filter(p =>
      clienteNome(p).toLowerCase().includes(t) ||
      (p.penitenciaria?.nome || '').toLowerCase().includes(t)
    );
  }

  lista.sort((a, b) => {
    let va, vb;
    switch (ordemAtual.coluna) {
      case 'cliente':
        va = clienteNome(a).toLowerCase(); vb = clienteNome(b).toLowerCase(); break;
      case 'penitenciaria':
        va = (a.penitenciaria?.nome || '').toLowerCase();
        vb = (b.penitenciaria?.nome || '').toLowerCase();
        break;
      case 'total':
        va = parseFloat(a.total); vb = parseFloat(b.total); break;
      case 'status':
        va = labelStatus(a.status).toLowerCase(); vb = labelStatus(b.status).toLowerCase(); break;
      case 'data':
        va = new Date(a.data).getTime(); vb = new Date(b.data).getTime(); break;
      default: // 'id'
        va = Number(a.id); vb = Number(b.id);
    }
    const cmp = va > vb ? 1 : (va < vb ? -1 : 0);
    return ordemAtual.direcao === 'asc' ? cmp : -cmp;
  });

  renderizarPedidos(lista);
}

function ordenarPor(coluna) {
  if (ordemAtual.coluna === coluna) {
    ordemAtual.direcao = ordemAtual.direcao === 'asc' ? 'desc' : 'asc';
  } else {
    ordemAtual.coluna = coluna;
    ordemAtual.direcao = 'asc';
  }
  aplicarFiltroEOrdenacao();
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

    // remove do array + DOM (nada de undefined depois)
    const idx = pedidos.findIndex(p => p.id === id);
    if (idx !== -1) pedidos.splice(idx, 1);
    if (tr) tr.remove();

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

  async function carregarPedidos() {
    tbody.innerHTML = '<tr><td colspan="7">Carregando pedidos...</td></tr>';
    try {
      const res = await fetch('https://clickjumbo.com.br/wp/wp-json/clickjumbo/v1/orders');
      pedidos = await res.json();
      aplicarFiltroEOrdenacao();
    } catch (err) {
      console.error(err);
      tbody.innerHTML = '<tr><td colspan="7">Erro ao carregar pedidos</td></tr>';
    }
  }

  searchInput.addEventListener('input', () => {
    filtroTermo = searchInput.value;
    aplicarFiltroEOrdenacao();
  });

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
