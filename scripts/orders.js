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
    const res = await fetch(
      `https://clickjumbo.local/wp-json/clickjumbo/v1/orders/${id}`,
      {
        credentials: "include",
        headers: { Accept: "application/json" },
      }
    );

    const order = await res.json();

    if (!order || !order.id) {
      document.getElementById(
        "orderDetailsContent"
      ).innerHTML = `<p>Pedido não encontrado.</p>`;
      return;
    }

    const produtosHtml = order.produtos
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
      <p><strong>Status:</strong> ${order.status}</p>
      <p><strong>Data:</strong> ${order.data}</p>

      <h5>Penitenciária</h5>
      <p>${order.penitenciaria.nome} (${order.penitenciaria.slug})<br>
      ${order.penitenciaria.cidade} - ${order.penitenciaria.estado}, CEP: ${
      order.penitenciaria.cep
    }</p>

      <h5>Cliente</h5>
      <p>${order.cliente.nome} (${order.cliente.email})</p>

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
        Método: ${order.shipping.method}<br>
        Peso: ${order.shipping.cart_weight} kg<br>
        De: ${order.shipping.sender_address.rua}, ${
      order.shipping.sender_address.cidade
    } - ${order.shipping.sender_address.estado}<br>
        Valor: R$ ${parseFloat(order.shipping.frete_valor).toFixed(2)}
      </p>

      <h5>Pagamento</h5>
      <p>Método: ${order.pagamento.metodo}<br>Status: ${
      order.pagamento.status
    }
    aaa
    </p>

<h5 class="text-end">Total: <strong>R$ ${parseFloat(order.total).toFixed(2)}</strong></h5>

${['pending', 'completed'].includes(order.pagamento.status) ? `
  <div class="text-end mt-3">
    <button class="btn btn-success" onclick="generateReceipt(${order.id})">
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
  const url = `https://clickjumbo.local/wp-json/clickjumbo/v1/orders/${orderId}/receipt`;
  window.open(url, '_blank');
}


async function fetchUserOrders() {

    
  try {
    const response = await fetch(
      `https://clickjumbo.local/wp-json/clickjumbo/v1/orders/by-user?user_id=${user.id}`,
      {
        credentials: "include",
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
      .map((order) => {
        return `
          <tr>
            <td>#${order.id}</td>
            <td>${order.penitenciaria?.nome || "N/A"}</td>
            <td class="text-capitalize">${order.status}</td>
            <td>R$ ${parseFloat(order.total).toFixed(2)}</td>
            <td>${formatDate(order.data)}</td>
            <td><button class="btn btn-sm btn-primary" onclick="viewOrder(${
              order.id
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
