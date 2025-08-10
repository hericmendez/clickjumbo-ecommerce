import { apiFetch } from "../api/apiFetch.js";
import { API_URL } from "../api/baseUrl.js";

function formatDate(dataString) {
  const [data, hora] = dataString.split(" ");
  const [ano, mes, dia] = data.split("-");
  return `${dia}/${mes}/${ano} ${hora}`;
}


    const user = JSON.parse(localStorage.getItem('user'))
 console.log("user ==> ", user.id);
async function viewOrder(id) {

   
 if (!user) {
      document.getElementById(
        "ordersTableBody"
      ).innerHTML = `<p>Pedido não encontrado.</p>`;
      return;
    }
  try {
    const res = await apiFetch(
      `${API_URL}/orders/${id}`,
      {
 
        headers: { Accept: "application/json" },
      }
    );

    const pedido = await res.json();

    if (!pedido || !pedido.id) {
      document.getElementById(
        "orderDetailsContent"
      ).innerHTML = `<p>Pedido não encontrado.</p>`;
      return;
    }

    const produtosHtml = pedido.produtos
      .map(
        (p) => `
      <tr>
        <td>${p.nome}</td>
        <td>${p.quantidade}</td>
        <td>R$ ${parseFloat(p.preco_unitario).toFixed(2)}</td>
        <td>R$ ${parseFloat(p.subtotal).toFixed(2)}</td>
      </tr>
    `
      )
      .join("");

    const html = `
      <p><strong>Status:</strong> ${pedido.status}</p>
      <p><strong>Data:</strong> ${pedido.data}</p>

      <h5>Penitenciária</h5>
      <p>${pedido.penitenciaria.nome} (${pedido.penitenciaria.slug})<br>
      ${pedido.penitenciaria.cidade} - ${pedido.penitenciaria.estado}, CEP: ${
      pedido.penitenciaria.cep
    }</p>

      <h5>Cliente</h5>
      <p>${pedido.cliente.nome} (${pedido.cliente.email})</p>

      <h5>Produtos</h5>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead>
            <tr>
              <th>Produto</th>
              <th>Qtd</th>
              <th>Preço Unit.</th>
              <th>Subtotal</th>
            </tr>
          </thead>
          <tbody>${produtosHtml}</tbody>
        </table>
      </div>

      <h5>Frete</h5>
      <p>
        Método: ${pedido.envio.method}<br>
        Peso: ${pedido.envio.peso_carrinho} kg<br>
        De: ${pedido.envio.remetente.rua}, ${
      pedido.envio.remetente.cidade
    } - ${pedido.envio.remetente.estado}<br>
        Valor: R$ ${parseFloat(pedido.envio.frete_valor).toFixed(2)}
      </p>

      <h5>Pagamento</h5>
      <p>Método: ${pedido.pagamento.metodo}<br>Status: ${
      pedido.pagamento.status
    }
    aaa
    </p>

<h5 class="text-end">Total: <strong>R$ ${parseFloat(pedido.total).toFixed(2)}</strong></h5>

${['pending', 'completed'].includes(pedido.pagamento.status) ? `
  <div class="text-end mt-3">
    <button class="btn btn-success" onclick="generateReceipt(${pedido.id})">
      Gerar Comprovante
    </button>
  </div>
` : ''}


    `;

    document.getElementById("orderDetailsContent").innerHTML = html;

    // Exibir o modal
    const modal = new bootstrap.Modal(
      document.getElementById("orderDetailsModal")
    );
    modal.show();
  } catch (error) {
    console.error("Erro ao buscar detalhes do pedido:", error);
    document.getElementById(
      "orderDetailsContent"
    ).innerHTML = `<p>Erro ao buscar os detalhes do pedido.</p>`;
  }
}
function generateReceipt(orderId) {
  const url = `${API_URL}/orders/${orderId}/receipt`;
  window.open(url, '_blank');
}


async function fetchUserOrders() {

    
  try {
    const response = await apiFetch(
      `/orders/by-user?user_id=${user.id}`,
      {

        headers: {
          Accept: "application/json",
        },
      }
    );
    const data = await response.json();
    console.log("orders ==> ", data);

    if (!Array.isArray(data)|| !user.id) {
      document.getElementById("ordersTableBody").innerHTML =
        '<tr><td colspan="6">Nenhum pedido encontrado.</td></tr>';
      return;
    }

    const rows = data
      .map((pedido) => {
        return `
          <tr>
            <td>#${pedido.id}</td>
            <td>${pedido.penitenciaria?.nome || "N/A"}</td>
            <td class="text-capitalize">${pedido.status}</td>
            <td>R$ ${parseFloat(pedido.total).toFixed(2)}</td>
            <td>${formatDate(pedido.data)}</td>
            <td><button class="btn btn-sm btn-primary" onclick="viewOrder(${
              pedido.id
            })">Detalhes</button></td>
          </tr>
        `;
      })
      .join("");
       
    document.getElementById("ordersTableBody").innerHTML = rows;
  } catch (err) {
    console.error("Erro ao carregar pedidos:", err);
    document.getElementById("ordersTableBody").innerHTML =
      '<tr><td colspan="6">Erro ao carregar pedidos.</td></tr>';
  }
}



fetchUserOrders();
