import { apiFetch } from "../api/apiFetch.js";
// import { API_URL } from "../api/baseUrl.js"; // não precisamos aqui





// ===== estado =====
const user = JSON.parse(localStorage.getItem("user") || "null");
const PAGE_SIZE = 10;
let orders = [];
let currentPage = 1;

// ===== helpers =====
const STATUS_LABEL = {
  pending: "Pendente",
  processing: "Processando",
  completed: "Concluído",
  cancelled: "Cancelado",
  failed: "Falhou",
  refunded: "Reembolsado",
};
const STATUS_BADGE = {
  pending: "bg-warning",
  processing: "bg-primary",
  completed: "bg-success",
  cancelled: "bg-danger",
  failed: "bg-dark",
  refunded: "bg-secondary",
};
const ptStatus = (s) => STATUS_LABEL[s] || (s || "—");
const badgeClass = (s) => "badge " + (STATUS_BADGE[s] || "bg-secondary");
const money = (v) =>
  Number(v || 0).toLocaleString("pt-BR", {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
function formatDate(s) {
  const d = new Date(s);
  if (!isNaN(d)) return d.toLocaleString("pt-BR");
  const m = s?.match(/^(\d{2})-(\d{2})-(\d{4})\s+(\d{2}:\d{2}:\d{2})$/);
  return m ? `${m[1]}/${m[2]}/${m[3]} ${m[4]}` : s || "—";
}
const elTbody = () => document.getElementById('ordersTableBody')
const elPagination = () => document.getElementById('ordersPagination')





// ===== tabela + paginação =====
function renderTablePage() {
  const tbody = elTbody();
  const start = (currentPage - 1) * PAGE_SIZE;
  const slice = orders.slice(start, start + PAGE_SIZE);

if (!slice.length) {
  tbody.innerHTML = `<tr><td colspan="6">Nenhum pedido encontrado.</td></tr>`
  return
}



  tbody.innerHTML = slice
    .map(
      (p) => `
      <tr data-id="${p.id}">
        <td>#${p.id}</td>
        <td>${p.penitenciaria?.nome || "—"}</td>
        <td><span class="${badgeClass(p.status)}">${ptStatus(p.status)}</span></td>
        <td>R$ ${money(p.total)}</td>
        <td>${formatDate(p.data)}</td>
        <td>
          <button class="btn btn-sm btn-primary js-view-order" data-id="${p.id}">
            Detalhes
          </button>
        </td>
      </tr>`
    )
    .join("");
}

function renderPagination() {
  const ul = elPagination();
  const totalPages = Math.ceil(orders.length / PAGE_SIZE) || 1;

  let html = `
    <li class="page-item ${currentPage === 1 ? "disabled" : ""}">
      <a class="page-link" href="#" data-page="${currentPage - 1}">&laquo;</a>
    </li>`;

  for (let p = 1; p <= totalPages; p++) {
    html += `
      <li class="page-item ${p === currentPage ? "active" : ""}">
        <a class="page-link" href="#" data-page="${p}">${p}</a>
      </li>`;
  }

  html += `
    <li class="page-item ${currentPage === totalPages ? "disabled" : ""}">
      <a class="page-link" href="#" data-page="${currentPage + 1}">&raquo;</a>
    </li>`;

  ul.innerHTML = html;
}

function redraw() {
  renderTablePage();
  renderPagination();
}

// ===== eventos =====
elTbody().addEventListener("click", (e) => {
  const btn = e.target.closest(".js-view-order");
  if (!btn) return;
  e.preventDefault();
  viewOrder(Number(btn.dataset.id));
});

elPagination().addEventListener("click", (e) => {
  const a = e.target.closest("a[data-page]");
  if (!a) return;
  e.preventDefault();
  const p = Number(a.dataset.page);
  const totalPages = Math.ceil(orders.length / PAGE_SIZE) || 1;
  if (p >= 1 && p <= totalPages) {
    currentPage = p;
    redraw();
  }
});

// ===== dados =====
async function loadOrders() {
  const tbody = elTbody();
  if (!user?.id) {
    tbody.innerHTML = `<tr><td colspan="6">Faça login para ver seus pedidos.</td></tr>`;
    return;
  }
tbody.innerHTML = `<tr><td colspan="6">Carregando...</td></tr>`

  try {
    const res = await apiFetch(`/orders/by-user?user_id=${user.id}`, {
      headers: { Accept: "application/json" },
    });
    const data = await res.json();
    orders = Array.isArray(data) ? data : [];
    // Ordena do mais novo para o mais antigo (opcional)
    orders.sort((a, b) => new Date(b.data) - new Date(a.data));
    currentPage = 1;
    redraw();
  } catch (err) {
    console.error(err);
    tbody.innerHTML = `<tr><td colspan="6">Erro ao carregar pedidos.</td></tr>`;
  }
}

// ===== modal =====
async function viewOrder(id) {
  try {
    const res = await apiFetch(`/orders/${id}`, {
      headers: { Accept: "application/json" },
    });
    const pedido = await res.json();

    const contentEl = document.getElementById("orderDetailsContent");
    if (!pedido?.id) {
      contentEl.innerHTML = "<p>Pedido não encontrado.</p>";
      bootstrap.Modal.getOrCreateInstance(
        document.getElementById("orderDetailsModal")
      ).show();
      return;
    }

    // Helpers
    const money = (v) =>
      Number(v || 0).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const STATUS_LABEL = {
      pending: "Pendente", processing: "Processando",
      completed: "Concluído", cancelled: "Cancelado",
      failed: "Falhou", refunded: "Reembolsado",
      approved: "Aprovado", paid: "Pago"
    };
    const STATUS_BADGE = {
      pending: "bg-warning", processing: "bg-primary",
      completed: "bg-success", cancelled: "bg-danger",
      failed: "bg-dark", refunded: "bg-secondary",
      approved: "bg-success", paid: "bg-success"
    };
    const statusCalc = (pedido.pagamento?.status || pedido.status || "").toLowerCase();
    const pt = STATUS_LABEL[statusCalc] || (pedido.pagamento?.status || pedido.status || "—");
    const badge = "badge " + (STATUS_BADGE[statusCalc] || "bg-secondary");
    const formaEnvio = pedido.envio?.forma_envio || pedido.envio?.method || pedido.envio?.metodo || "—";

    // Produtos
    const prods = (pedido.produtos || [])
      .map((p) => {
        const qtd  = Number(p.quantidade ?? 1);
        const unit = Number(p.preco_unitario ?? 0);
        const sub  = Number(p.subtotal ?? qtd * unit);
        return `
          <tr>
            <td>${(p.nome || "—").replace(/[<>&]/g, s => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[s]))}</td>
            <td class="text-center">${qtd}</td>
            <td class="text-end">R$ ${money(unit)}</td>
            <td class="text-end">R$ ${money(sub)}</td>
          </tr>`;
      })
      .join("");

    // Payment-specific bits
    const payMethod = (pedido.pagamento?.metodo || pedido.pagamento?.method || "").toLowerCase();
    const payLink =
      pedido.pagamento?.link_pagamento ||
      pedido.pagamento?.checkout_url ||
      null;
    const comprovante = pedido.pagamento?.comprovante_url || null;

    // PIX: tenta achar o “copia e cola” em vários campos comuns
    const pixCode =
      pedido.pagamento?.pix?.copia_cola ||
      pedido.pagamento?.pix_copia_cola ||
      pedido.pagamento?.qr_code ||
      pedido.pagamento?.qrcode ||
      pedido.pagamento?.pixCopiaCola ||
      null;

    // Boleto
    const boletoUrl =
      pedido.pagamento?.boleto_url ||
      pedido.pagamento?.boleto?.url ||
      null;

    // Monta seção de ações de pagamento
    let acaoHtml = "";
    if (statusCalc === "pending") {
      if (payMethod === "pix" && (pixCode || payLink)) {
        acaoHtml = `
          <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="bi bi-clock me-2"></i> Pagamento pendente via <strong class="ms-1">PIX</strong>.
          </div>
          ${
            pixCode
              ? `
            <div class="mb-2 d-flex gap-2">
              <button class="btn btn-outline-primary btn-sm" id="btnCopyPix">
                <i class="bi bi-clipboard"></i> Copiar código PIX
              </button>
              ${payLink ? `<a class="btn btn-primary btn-sm" href="${payLink}" target="_blank" rel="noopener">Pagar no app/banco</a>` : ""}
            </div>
            <div class="d-flex justify-content-center my-3">
              <div id="pixQrContainer"></div>
            </div>
            <label class="form-label small text-muted">PIX Copia e Cola</label>
            <pre class="small bg-light p-2 border rounded" id="pixCodeText" style="white-space:pre-wrap;word-break:break-all;"></pre>
          `
              : payLink
              ? `<a class="btn btn-primary" href="${payLink}" target="_blank" rel="noopener">Pagar com PIX</a>`
              : `<div class="alert alert-info">Aguardando instruções de pagamento.</div>`
          }
        `;
      } else if (payMethod === "boleto" && boletoUrl) {
        acaoHtml = `
          <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="bi bi-clock me-2"></i> Pagamento pendente via <strong class="ms-1">Boleto</strong>.
          </div>
          <a class="btn btn-primary" href="${boletoUrl}" target="_blank" rel="noopener">
            <i class="bi bi-file-earmark-pdf"></i> Baixar boleto
          </a>
        `;
      } else if (payLink) {
        acaoHtml = `
          <div class="alert alert-warning d-flex align-items-center" role="alert">
            <i class="bi bi-clock me-2"></i> Pagamento pendente.
          </div>
          <a class="btn btn-primary" href="${payLink}" target="_blank" rel="noopener">Pagar agora</a>
        `;
      }
    } else if (
      ["processing", "completed", "approved", "paid"].includes(statusCalc) &&
      comprovante
    ) {
      acaoHtml = `
        <a class="btn btn-success" href="${comprovante}" target="_blank" rel="noopener">
          <i class="bi bi-receipt"></i> Ver comprovante
        </a>
      `;
    }

    const html = `
      <p><strong>Status:</strong> <span class="${badge}">${pt}</span></p>
      <p><strong>Data:</strong> ${formatDate(pedido.data)}</p>

      <h6 class="mt-3">Penitenciária</h6>
      <p>${pedido.penitenciaria?.nome || "—"}
      ${pedido.penitenciaria?.cidade ? ` — ${pedido.penitenciaria.cidade}/${pedido.penitenciaria.estado || ""}` : ""}</p>

;<h6 class='mt-3'>Produtos</h6>

      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Produto</th>
              <th class="text-center">Qtd</th>
              <th class="text-end">Preço Unit.</th>
              <th class="text-end">Subtotal</th>
            </tr>
          </thead>
;<tbody>${prods}</tbody>

        </table>
      </div>

      <h6 class="mt-3">Envio</h6>
      <p><strong>Forma:</strong> ${formaEnvio}
         ${pedido.envio?.frete_valor ? ` — <strong>Frete:</strong> R$ ${money(pedido.envio.frete_valor)}` : ""}</p>

      <h6 class="mt-3">Total</h6>
      <p><strong>R$ ${money(pedido.total)}</strong></p>

      ${acaoHtml ? `<div class="mt-3">${acaoHtml}</div>` : ""}`;

contentEl.innerHTML = html


    // Pós-render: PIX extras (copiar + QR)
    if (statusCalc === "pending" && payMethod === "pix" && pixCode) {
      // Mostra o copia e cola no <pre>
      const pre = document.getElementById("pixCodeText");
      if (pre) pre.textContent = pixCode;

// Copiar
const btnCopy = document.getElementById('btnCopyPix')
if (btnCopy) {
  btnCopy.addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(pixCode)
      btnCopy.classList.remove('btn-outline-primary')
      btnCopy.classList.add('btn-success')
      btnCopy.innerHTML = '<i class="bi bi-check2"></i> Copiado!'
      setTimeout(() => {
        btnCopy.classList.remove('btn-success')
        btnCopy.classList.add('btn-outline-primary')
        btnCopy.innerHTML = '<i class="bi bi-clipboard"></i> Copiar código PIX'
      }, 1600)
    } catch {
      alert('Não foi possível copiar. Selecione o código e copie manualmente.')
    }
  })
}


      // QR Code (gera do próprio copia e cola)
      const wrap = document.getElementById("pixQrContainer");
      if (wrap && window.QRCode) {
        wrap.innerHTML = ""; // limpa, se reabrir
        // tamanho confortável para modal
        const size = Math.min(220, Math.max(160, Math.floor(window.innerWidth * 0.35)));
        new QRCode(wrap, { text: pixCode, width: size, height: size });
      }
    }

    bootstrap.Modal.getOrCreateInstance(
      document.getElementById("orderDetailsModal")
    ).show();
  } catch (error) {
    console.error("Erro ao buscar detalhes do pedido:", error);
    document.getElementById("orderDetailsContent").innerHTML =
      "<p>Erro ao buscar os detalhes do pedido.</p>";
    bootstrap.Modal.getOrCreateInstance(
      document.getElementById("orderDetailsModal")
    ).show();
  }
}


// start
loadOrders()




