<?php
if (!defined('ABSPATH')) exit;
ob_start();
?>
<div id="modal-detalhes" style="display:none; position:fixed; top:5%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; padding:20px; z-index:1000; max-width:700px; width:90%; box-shadow:0 0 10px rgba(0,0,0,0.3);">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
    <h2 style="margin:0;">Pedido #<span id="pedido-id"></span></h2>
    <button onclick="fecharModal()" style="border:none; background:none; font-size:20px;">✖</button>
  </div>

  <p><strong>Cliente:</strong> <span id="pedido-cliente"></span></p>
  <p><strong>Email:</strong> <span id="pedido-email"></span></p>
  <p><strong>Penitenciária:</strong> <span id="pedido-prison"></span></p>
  <p><strong>Endereço:</strong> <span id="pedido-endereco"></span></p>

  <h3>Produtos</h3>
  <table style="width:100%; border-collapse:collapse;">
    <thead>
      <tr style="background:#f2f2f2;">
        <th style="text-align:left; padding:6px;">Produto</th>
        <th style="text-align:center; padding:6px;">Qtd</th>
        <th style="text-align:right; padding:6px;">Preço Unit.</th>
        <th style="text-align:right; padding:6px;">Subtotal</th>
      </tr>
    </thead>
    <tbody id="pedido-produtos"></tbody>
  </table>

  <h3 style="margin-top:20px;">Envio</h3>
  <p><strong>Método:</strong> <span id="pedido-envio-metodo"></span></p>
  <p><strong>Peso Total:</strong> <span id="pedido-envio-peso"></span> kg</p>
  <p><strong>Endereço Remetente:</strong> <span id="pedido-envio-remetente"></span></p>
  <p><strong>Frete:</strong> R$ <span id="pedido-frete"></span></p>

  <h3 style="margin-top:20px;">Resumo</h3>
  <p><strong>Valor Carrinho:</strong> R$ <span id="pedido-carrinho"></span></p>
  <p><strong>Total:</strong> R$ <span id="pedido-total"></span></p>
  <p><strong>Status Pagamento:</strong> <span id="pedido-pagamento-status"></span></p>
  <p><strong>Método Pagamento:</strong> <span id="pedido-pagamento-metodo"></span></p>
  <p><strong>Data:</strong> <span id="pedido-data"></span></p>
</div>

<script>
function abrirModalDetalhesPedido(pedido) {
  document.getElementById('pedido-id').textContent = pedido.id ?? '—';
  document.getElementById('pedido-cliente').textContent = pedido.cliente?.nome || '—';
  document.getElementById('pedido-email').textContent = pedido.cliente?.email || '—';
  document.getElementById('pedido-prison').textContent = pedido.penitenciaria?.nome || pedido.penitenciaria?.slug || '—';

  // Endereço do cliente (tratado como string única já formatada)
  document.getElementById('pedido-endereco').textContent = pedido.cliente?.endereco || '—';

  // Envio
  const shipping = pedido.shipping ?? {};
  document.getElementById('pedido-envio-metodo').textContent = shipping.method || '—';
  document.getElementById('pedido-envio-peso').textContent = shipping.cart_weight?.toString().replace('.', ',') || '—';

  if (shipping.sender_address) {
    const s = shipping.sender_address;
    document.getElementById('pedido-envio-remetente').textContent = `${s.rua}, ${s.cidade} - ${s.estado}, CEP ${s.cep}`;
  } else {
    document.getElementById('pedido-envio-remetente').textContent = '—';
  }

  document.getElementById('pedido-frete').textContent = (shipping.frete_valor ?? 0).toFixed(2).replace('.', ',');

  // Totais e pagamento
  document.getElementById('pedido-carrinho').textContent = pedido.valorCarrinho || '—';
  document.getElementById('pedido-total').textContent = pedido.total || pedido.valorTotal || '—';
  document.getElementById('pedido-pagamento-status').textContent = pedido.pagamento?.status || '—';
  document.getElementById('pedido-pagamento-metodo').textContent = pedido.pagamento?.metodo || '—';
  document.getElementById('pedido-data').textContent = new Date(pedido.data).toLocaleString('pt-BR');

  // Produtos
  const tbody = document.getElementById('pedido-produtos');
  tbody.innerHTML = '';
  (pedido.produtos || []).forEach(prod => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td style="padding:6px;">${prod.nome}</td>
      <td style="text-align:center; padding:6px;">${prod.quantidade}</td>
      <td style="text-align:right; padding:6px;">R$ ${prod.preco_unitario}</td>
      <td style="text-align:right; padding:6px;">R$ ${prod.subtotal}</td>
    `;
    tbody.appendChild(tr);
  });

  // Exibir modal
  document.getElementById('modal-detalhes').style.display = 'block';
}
</script>
