// pagamentoForm.js
export const pagamentoForm = () => `
  <div class="container-fluid " style="margin-top: 5vh !important">
    <div class="container ">
      <div class="d-flex justify-content-center flex-column-reverse">
        <div>
          <div class="card shadow-sm">
            <div class="card-body">
              <div id="checkout-form">
                <h4 class="mb-3">Forma de pagamento</h4>

                <div class="form-check mb-2">
                  <input id="pix" name="paymentMethod" type="radio"
                         class="form-check-input" value="pix" checked required />
                  <label class="form-check-label" for="pix">Pix</label>
                </div>

                <div class="form-check mb-2">
                  <input id="boleto" name="paymentMethod" type="radio"
                         class="form-check-input" value="boleto" required />
                  <label class="form-check-label" for="boleto">Boleto bancário</label>
                </div>

                <!-- CPF para boleto (visível só quando "boleto" estiver selecionado) -->
                <div id="boletoCpfGroup" class="mb-3 d-none">
                  <label for="cpfBoleto" class="form-label">CPF (boleto)</label>
                  <input id="cpfBoleto" class="form-control"
                         inputmode="numeric" autocomplete="off"
                         placeholder="000.000.000-00" maxlength="14" />
                  <div class="form-text">Obrigatório para geração do boleto.</div>
                </div>

                <!-- (se futuramente habilitar cartão, insira aqui os campos) -->

                <!-- Você pode reativar os avisos se quiser -->
                <!--
                <div id="pix-instructions" class="alert alert-success d-none">
                  Pagamento via <strong>Pix</strong>. QR Code será gerado após a confirmação.
                </div>
                <div id="boleto-instructions" class="alert alert-secondary d-none">
                  Pagamento via <strong>Boleto</strong>. Gerado com vencimento em 3 dias úteis.
                </div>
                -->
              </div>
            </div>
          </div>
        </div>

        <!-- Carrinho e resumo -->
        <div>
          <div class="card shadow-sm">
            <div class="card-body">
              <h4 class="d-flex justify-content-between align-items-center mb-3">
                <span class="text-muted">Resumo da Compra</span>
                <span class="badge bg-secondary rounded-pill" id="qtde-carrinho">0</span>
              </h4>

              <button class="btn btn-outline-primary mb-3 w-100" type="button"
                      data-bs-toggle="collapse" data-bs-target="#cart-collapse">
                Ver produtos <i class="bi bi-chevron-down"></i>
              </button>

              <div class="collapse" id="cart-collapse">
                <ul class="list-group mb-3" id="itens-carrinho"><!-- Itens via JS --></ul>
              </div>

              <ul class="list-group" id="resumo-carrinho"><!-- Resumo via JS --></ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
`;
// pagamentoForm.init.js (ou dentro do seu arquivo principal)
export function initPagamentoForm(prefillCpf = '') {
  const pixRadio     = document.getElementById('pix');
  const boletoRadio  = document.getElementById('boleto');
  const cpfGroup     = document.getElementById('boletoCpfGroup');
  const cpfInput     = document.getElementById('cpfBoleto');

  const maskCPF = (v) => {
    const d = (v || '').replace(/\D+/g, '').slice(0, 11);
    const p1 = d.slice(0, 3), p2 = d.slice(3, 6), p3 = d.slice(6, 9), p4 = d.slice(9, 11);
    if (d.length > 9)  return `${p1}.${p2}.${p3}-${p4}`;
    if (d.length > 6)  return `${p1}.${p2}.${p3}`;
    if (d.length > 3)  return `${p1}.${p2}`;
    return p1;
  };

  const toggleCpf = () => {
    if (!cpfGroup) return;
    const isBoleto = boletoRadio?.checked;
    cpfGroup.classList.toggle('d-none', !isBoleto);
  };

  // listeners
  pixRadio?.addEventListener('change', toggleCpf);
  boletoRadio?.addEventListener('change', toggleCpf);

  cpfInput?.addEventListener('input', (e) => {
    const pos = e.target.selectionStart;
    const before = e.target.value;
    e.target.value = maskCPF(e.target.value);
    // tentativa simples de manter o caret “ok”
    const diff = e.target.value.length - before.length;
    e.target.selectionEnd = Math.max(0, (pos || 0) + diff);
  });

  // prefill opcional (ex.: do localStorage)
  if (prefillCpf && cpfInput) cpfInput.value = maskCPF(prefillCpf);

  // estado inicial
  toggleCpf();
}
