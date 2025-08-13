// components/pagamentoForm.js
// (ATUALIZADO) — único arquivo com template + init + Brick de Cartão

// ---------------- TEMPLATE ----------------

export const pagamentoForm = () => `
  <div class="container-fluid" style="margin-top: 5vh !important">
    <div class="container">
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

                <div class="form-check mb-3">
                  <input id="card" name="paymentMethod" type="radio"
                         class="form-check-input" value="card" required />
                  <label class="form-check-label" for="card">Cartão de crédito</label>
                </div>

                <!-- CPF para boleto (aparece só quando "boleto" está selecionado) -->
                <div id="boletoCpfGroup" class="mb-3 d-none">
                  <label for="cpfBoleto" class="form-label">CPF (boleto)</label>
                  <input id="cpfBoleto" class="form-control"
                         inputmode="numeric" autocomplete="off"
                         placeholder="000.000.000-00" maxlength="14" />
                  <div class="form-text">Obrigatório para geração do boleto.</div>
                </div>

                <!-- Brick de Cartão -->
                <div id="cardContainer" class="mb-3 d-none">
                  <div id="cardPaymentBrick_container"></div>
                  <div class="form-text mt-2">
                    Seus dados são processados com segurança pelo Mercado Pago.
                  </div>
                </div>

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

// ---------------- HELPERS ----------------
function onlyDigits(v) { return (v == null ? '' : String(v)).replace(/\D+/g, ''); }

function ensureMpSdk() {
  return new Promise((resolve, reject) => {
    if (window.MercadoPago) return resolve();
    const s = document.createElement('script');
    s.src = 'https://sdk.mercadopago.com/js/v2';
    s.onload = resolve;
    s.onerror = () => reject(new Error('Falha ao carregar SDK do Mercado Pago'));
    document.head.appendChild(s);
  });
}

// Tenta descobrir o total (amount) de forma resiliente
function resolveAmountFromContext(explicitAmount) {
  // 1) valor passado por argumento
  if (typeof explicitAmount === 'number' && isFinite(explicitAmount) && explicitAmount > 0) {
    return explicitAmount;
  }

  // 2) localStorage.totalCarrinho (padrão do seu projeto)
  try {
    const t = JSON.parse(localStorage.getItem('totalCarrinho') || '{}');
    const frete  = Number(t.frete ?? t.frete_valor ?? 0);
    const base   = Number(t.valorTotal ?? t.subtotal ?? 0);
    const total  = Number(base + frete);
    if (isFinite(total) && total > 0) return total;
  } catch {}

  // 3) DOM do Resumo (pega o <strong> do "Total")
  const strong = document.querySelector('#resumo-carrinho strong');
  if (strong) {
    const txt = strong.textContent || '';
    const num = Number(
      txt.replace(/[^\d,.-]/g, '').replace(/\./g, '').replace(',', '.')
    );
    if (isFinite(num) && num > 0) return num;
  }

  return 0;
}

// ---------------- INIT COM BRICK ----------------
/**
 * options:
 *  - prefillCpf         : string
 *  - mpPublicKey        : string (APP_USR-... em produção) — se não passar, usa window.MP_PUBLIC_KEY
 *  - amount             : number (total da compra; se não vier, tentamos deduzir)
 *  - payerEmail         : string
 *  - payerCPF           : string (sem máscara)
 *  - onApproved(orderId): function
 *  - onInProcess(orderId): function
 */
export async function initPagamentoForm(options = {}) {
  const {
    prefillCpf = '',
    mpPublicKey = window.MP_PUBLIC_KEY,
    amount,
    payerEmail = '',
    payerCPF = '',
    onApproved,
    onInProcess
  } = options;

  // elementos
  const pixRadio     = document.getElementById('pix');
  const boletoRadio  = document.getElementById('boleto');
const cardRadio = document.getElementById('card')

  const cpfGroup     = document.getElementById('boletoCpfGroup');
  const cpfInput     = document.getElementById('cpfBoleto');
const cardWrap = document.getElementById('cardContainer')
const cardTargetId = 'cardPaymentBrick_container'


// máscara de CPF

  const maskCPF = (v) => {
const d = onlyDigits(v).slice(0, 11)
const p1 = d.slice(0, 3),
  p2 = d.slice(3, 6),
  p3 = d.slice(6, 9),
  p4 = d.slice(9, 11)

    if (d.length > 9)  return `${p1}.${p2}.${p3}-${p4}`;
    if (d.length > 6)  return `${p1}.${p2}.${p3}`;
    if (d.length > 3)  return `${p1}.${p2}`;
    return p1;
  };

  const toggleUI = () => {
    const isBoleto = !!boletoRadio?.checked;
    const isCard   = !!cardRadio?.checked;
    cpfGroup?.classList.toggle('d-none', !isBoleto);
    cardWrap?.classList.toggle('d-none', !isCard);
  };

// listeners rádio + cpf
pixRadio?.addEventListener('change', toggleUI)
boletoRadio?.addEventListener('change', toggleUI)
cardRadio?.addEventListener('change', () => {
  toggleUI()
  if (cardRadio.checked) mountCardBrick()
})


  cpfInput?.addEventListener('input', (e) => {
    const pos = e.target.selectionStart;
    const before = e.target.value;
e.target.value = maskCPF(e.target.value)

    const diff = e.target.value.length - before.length;
    e.target.selectionEnd = Math.max(0, (pos || 0) + diff);
  });

  if (prefillCpf && cpfInput) cpfInput.value = maskCPF(prefillCpf);
  toggleUI();

  // ----- Card Brick (lazy) -----
  let bricksBuilder = null;
  let cardInstance  = null;

  async function mountCardBrick() {
    const amt = resolveAmountFromContext(amount);
    const container = document.getElementById(cardTargetId);

    if (!(amt > 0)) {
      cardWrap?.classList.remove('d-none');
      if (container) {
        container.innerHTML = `
          <div class="alert alert-warning">
            Total indisponível. Volte ao passo anterior e calcule o frete.
          </div>`;
      }
      console.warn('[MP Bricks] Amount indisponível ou inválido:', amt);
      return;
    }

    // carrega SDK se necessário
    await ensureMpSdk();

    if (!mpPublicKey) {
      console.error('[MP Bricks] Public Key ausente (window.MP_PUBLIC_KEY ou mpPublicKey).');
      if (container) {
        container.innerHTML = `
          <div class="alert alert-danger">
            Chave pública do Mercado Pago ausente.
          </div>`;
      }
      return;
    }

    // (re)cria builder
    const mp = new window.MercadoPago(mpPublicKey, { locale: 'pt-BR' });
    bricksBuilder = bricksBuilder || mp.bricks();

    // se já tem instância, desmonta antes de recriar
    if (cardInstance?.unmount) {
      try { cardInstance.unmount(); } catch {}
      cardInstance = null;
    }

    cardWrap?.classList.remove('d-none');

    try {
      cardInstance = await bricksBuilder.create('cardPayment', cardTargetId, {
        initialization: {
          amount: Number(amt) // <- obrigatório e numérico
          // Dica: o payer é opcional aqui; o Brick captura tudo no form.
        },
        callbacks: {
          onReady: () => {},
          onError: (e) => {
            console.error('[MP Card Brick] onError:', e);
            if (container) {
              container.innerHTML = `
                <div class="alert alert-danger">
                  Erro ao carregar o componente de cartão.
                </div>`;
            }
          },
          onSubmit: async ({ formData }) => {
            try {
              // Se você já criou pedido antes, passe o ID aqui:
              const orderId = window.currentOrderId || null;

              const resp = await fetch('/wp-json/clickjumbo/v1/mp/create-card-payment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                  formData,
                  order: {
                    id: orderId,
                    amount: Number(amt),
                    description: 'Pedido ClickJumbo'
                  }
                })
              });

              const text = await resp.text();
              const data = (() => { try { return JSON.parse(text); } catch { return { success:false, message:text }; } })();

              if (!resp.ok || !data?.success) {
                throw new Error(data?.message || 'Falha no pagamento');
              }

              const st  = String(data.payment?.status || '').toLowerCase();
              const oid = data.order_id || data.payment?.external_reference || orderId || null;

              if (['approved','authorized'].includes(st)) {
                if (typeof onApproved === 'function') onApproved(oid);
                else window.gotoStep?.(6);
              } else if (['in_process','pending'].includes(st)) {
                if (typeof onInProcess === 'function') onInProcess(oid);
                else {
                  const c = document.getElementById('paymentUiDiv');
                  if (c) {
                    c.innerHTML = `
                      <div class="alert alert-info d-flex align-items-center gap-2 mt-3">
                        <i class="bi bi-hourglass-split"></i>
                        <div>Pagamento em análise…</div>
                      </div>`;
                  }
                  window.gotoStep?.(5);
                }
              } else {
                alert('Pagamento não aprovado: ' + (data.payment?.status_detail || st || 'tente novamente'));
              }
            } catch (err) {
              console.error(err);
              alert('Erro no pagamento: ' + (err?.message || 'desconhecido'));
            }
          }
        }
      });
    } catch (e) {
      console.error('[MP Card Brick] create error:', e);
      if (container) {
        container.innerHTML = `
          <div class="alert alert-danger">
            Não foi possível inicializar o pagamento por cartão.
          </div>`;
      }
    }
  }

  // Se o usuário já entra no step4 com "cartão" marcado:
  if (cardRadio?.checked) setTimeout(mountCardBrick, 0);

  // Quando a aba do Step 4 abre, revalida e monta se necessário
  document.getElementById('step4-tab')?.addEventListener('shown.bs.tab', () => {
    if (cardRadio?.checked) mountCardBrick();
  });

  // Expor um driver simples para o Step 4 acionar manualmente (se quiser)
  window.__mpCard = {
    isReady: () => !!cardInstance,
    ensure:  () => mountCardBrick(),
    submit:  async () => {
      if (!cardInstance) await mountCardBrick();
      // o próprio Brick tem o botão de pagar; este submit dispara o onSubmit do componente
      try {
        return cardInstance?.submit?.();
      } catch (e) {
        console.error('[MP Card Brick] submit error:', e);
        alert('Não foi possível submeter o pagamento.');
      }
    }
  };
}
