<?php
if (!defined('ABSPATH')) exit;
ob_start();
?>
<?php if (!defined('ABSPATH')) exit; ?>
<div class="modal fade" id="modal-detalhes" tabindex="-1" aria-labelledby="modalDetalhesLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Pedido #<span id="pedido-id"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
      </div>
      <div class="modal-body">
        <p><strong>Cliente:</strong> <span id="pedido-cliente"></span></p>
        <p><strong>Email:</strong> <span id="pedido-email"></span></p>
        <p><strong>Penitenciária:</strong> <span id="pedido-prison"></span></p>
        <p><strong>Endereço:</strong> <span id="pedido-endereco"></span></p>

        <h6 class="mt-4">Produtos</h6>
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead>
              <tr>
                <th>Produto</th>
                <th class="text-center">Qtd</th>
                <th class="text-end">Preço Unit.</th>
                <th class="text-end">Subtotal</th>
              </tr>
            </thead>
            <tbody id="pedido-produtos"></tbody>
          </table>
        </div>

        <h6 class="mt-4">Envio</h6>
        <p><strong>Método:</strong> <span id="pedido-envio-metodo"></span></p>
        <p><strong>Peso Total:</strong> <span id="pedido-envio-peso"></span> kg</p>
        <p><strong>Endereço Remetente:</strong> <span id="pedido-envio-remetente"></span></p>
        <p><strong>Frete:</strong> R$ <span id="pedido-frete"></span></p>

        <h6 class="mt-4">Resumo</h6>
        <p><strong>Valor Carrinho:</strong> R$ <span id="pedido-carrinho"></span></p>
        <p><strong>Total:</strong> R$ <span id="pedido-total"></span></p>
        <p><strong>Status Pagamento:</strong> <span id="pedido-pagamento-status"></span></p>
        <p><strong>Método Pagamento:</strong> <span id="pedido-pagamento-metodo"></span></p>
        <p><strong>Data:</strong> <span id="pedido-data"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
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
document.getElementById('pedido-carrinho').textContent = pedido.total || '—';
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
const modal = new bootstrap.Modal(document.getElementById('modal-detalhes'));
modal.show();

}
</script>
