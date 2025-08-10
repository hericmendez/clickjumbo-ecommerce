// app/components/pixScreen.js

// HTML do passo PIX (Bootstrap)
export const pixScreen = ({
  orderId,
  amount,           // number (BRL)
  qrCodeBase64,     // string sem prefixo data:image/png;base64,
  qrCodeText,       // copia e cola
  ticketUrl,        // opcional
  expiresAt         // ISO opcional (ex.: 2025-08-10T13:03:45-04:00)
}) => `
  <div id="pixPayment" class="card p-3 mb-4">
    <h3 class="mb-3">Pague com PIX</h3>
    <div class="text-muted mb-2">
      Pedido #${orderId ?? "-"} • Valor: <strong>R$ ${Number(amount||0).toFixed(2)}</strong>
    </div>

    ${expiresAt ? `
      <div class="d-flex align-items-center gap-2 mb-3">
        <span class="badge bg-warning text-dark d-flex align-items-center" style="gap:.5rem;">
          <i class="bi bi-clock"></i>
          <span>Expira em <span id="pixCountdown">--:--:--</span></span>
        </span>
      </div>
    ` : ''}

    <div class="row g-4">
      <div class="col-md-6 d-flex flex-column align-items-center">
        <div class="border rounded p-3 bg-white">
          <img id="pixQRImg"
               src="data:image/png;base64,${qrCodeBase64 || ''}"
               alt="QR Code PIX"
               class="img-fluid"
               style="max-width: 280px; max-height: 280px;"
               draggable="false">
        </div>
        <div class="d-flex flex-wrap gap-2 mt-3">
          <button id="btnPixDownload" class="btn btn-outline-primary">
            <i class="bi bi-download me-1"></i> Baixar QR
          </button>
          ${ticketUrl ? `
          <a href="${ticketUrl}" target="_blank" rel="noreferrer" class="btn btn-outline-secondary">
            <i class="bi bi-box-arrow-up-right me-1"></i> Abrir no Mercado Pago
          </a>` : ''}
        </div>
      </div>

      <div class="col-md-6">
        <div class="mb-2 fw-semibold">Copia e cola</div>
        <div class="form-floating">
          <textarea id="pixCopyText" class="form-control" style="height: 120px" readonly>${qrCodeText || ''}</textarea>
          <label for="pixCopyText">Código PIX</label>
        </div>
        <div class="mt-2 d-flex flex-wrap gap-2">
          <button id="btnPixCopy" class="btn btn-secondary">
            <i class="bi bi-clipboard me-1"></i> Copiar código
          </button>
          <button id="btnPixCheckNow" class="btn btn-outline-success d-none">
            <i class="bi bi-arrow-repeat me-1"></i> Verificar pagamento agora
          </button>
        </div>

        <div id="pixStatusBox" class="alert alert-info d-flex align-items-center gap-2 mt-3 mb-0" role="alert">
          <i class="bi bi-hourglass-split"></i>
          <div>Aguardando pagamento. Assim que aprovado, confirmamos automaticamente.</div>
        </div>
      </div>
    </div>
  </div>
`;
