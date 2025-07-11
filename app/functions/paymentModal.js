document.addEventListener("DOMContentLoaded", () => {
  const submitBtn = document.getElementById("submitBtn");
  const modalElement = document.getElementById("paymentModal");
  const modalBody = document.getElementById("paymentModalBody");
  const confirmBtn = document.getElementById("confirmPaymentBtn");

  const modal = new bootstrap.Modal(modalElement, {
    backdrop: 'static',
    keyboard: false
  });

  submitBtn.addEventListener("click", () => {
    const method = document.querySelector('input[name="paymentMethod"]:checked')?.value;

    if (method === "pix") {
      modalBody.innerHTML = `
        <h5>Pagamento via Pix</h5>
        <p>Escaneie o QR Code abaixo ou copie o código Pix:</p>
        <img src="https://api.qrserver.com/v1/create-qr-code/?data=chave-pix-fake&size=200x200" alt="QR Code Pix" class="img-fluid mb-3"/>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="pixCode" value="00020101021226860014br.gov.bcb.pix2563...fakepixkey..." readonly>
          <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('pixCode')">Copiar</button>
        </div>
      `;
    } else if (method === "boleto") {
      modalBody.innerHTML = `
        <h5>Pagamento via Boleto</h5>
        <p>Baixe seu boleto ou copie o código de barras:</p>
        <a href="#" class="btn btn-primary mb-3">Baixar Boleto (fake)</a>
        <div class="input-group mb-2">
          <input type="text" class="form-control" id="boletoCode" value="34191.79001 01043.510047 91020.150008 8 90190000002000" readonly>
          <button class="btn btn-outline-secondary" type="button" onclick="copyToClipboard('boletoCode')">Copiar</button>
        </div>
      `;
    } else if (method === "card") {
      const success = Math.random() > 0.3;
      modalBody.innerHTML = success
        ? `<h5 class="text-success">Pagamento aprovado!</h5><p>Obrigado pela sua compra.</p>`
        : `<h5 class="text-danger">Pagamento recusado</h5><p>Houve um problema ao processar seu cartão.</p>`;
    }

    modal.show();
  });

  confirmBtn.addEventListener("click", () => {
    setTimeout(() => {
      modal.hide();
      window.location.href = "/"; // redireciona para a home

    }, 2000 );
  });
});

// Copiar texto
window.copyToClipboard = function(id) {
  const input = document.getElementById(id);
  input.select();
  document.execCommand("copy");
  alert("Copiado para a área de transferência!");
};
