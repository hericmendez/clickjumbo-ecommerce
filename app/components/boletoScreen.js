export const boletoScreen = ({ orderId, amount, boletoUrl, linhaDigitavel }) => `
  <div id="boletoPayment" class="card p-3 mb-4">
    <h3 class="mb-3">Pague com Boleto</h3>
    <div class="text-muted mb-2">Pedido #${orderId ?? "-"} • Valor: <strong>R$ ${Number(amount||0).toFixed(2)}</strong></div>

    <div class="mb-3">
      <a href="${boletoUrl || '#'}" target="_blank" rel="noreferrer" class="btn btn-primary">
        <i class="bi bi-file-earmark-pdf me-1"></i> Abrir Boleto (PDF)
      </a>
    </div>

    <div class="mb-2 fw-semibold">Linha digitável</div>
    <div class="form-floating">
      <textarea id="boletoLinha" class="form-control" style="height: 100px" readonly>${linhaDigitavel || ''}</textarea>
      <label for="boletoLinha">Linha digitável</label>
    </div>
    <div class="mt-2">
      <button id="btnBoletoCopy" class="btn btn-secondary">
        <i class="bi bi-clipboard me-1"></i> Copiar linha digitável
      </button>
    </div>

    <div id="boletoStatusBox" class="alert alert-info d-flex align-items-center gap-2 mt-3 mb-0" role="alert">
      <i class="bi bi-hourglass-split"></i>
      <div>Aguardando pagamento do boleto. Assim que compensar, confirmamos automaticamente.</div>
    </div>
  </div>
`;
