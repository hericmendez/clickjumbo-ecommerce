//boleto.js:

export function initBoletoScreen({ orderId, onPaid, pollFn, pollIntervalMs = 30000 } = {}) {
  const copyBtn   = document.getElementById('btnBoletoCopy');
  const linhaArea = document.getElementById('boletoLinha');
  const statusBox = document.getElementById('boletoStatusBox');

  if (copyBtn && linhaArea) {
    copyBtn.addEventListener('click', async () => {
      try { await navigator.clipboard.writeText(linhaArea.value||''); toast('Linha digitável copiada!', 'success'); }
      catch { toast('Não foi possível copiar. Copie manualmente.', 'warning'); }
    });
  }

  let pollTimer = null;
  const handleStatus = (st) => {
    const s = (st||'').toLowerCase();
    const paid = ['wc-processing','processing','completed'].includes(s);
    if (statusBox) {
      if (paid) {
        statusBox.className = 'alert alert-success d-flex align-items-center gap-2 mt-3 mb-0';
        statusBox.innerHTML = `<i class="bi bi-check-circle"></i><div>Boleto compensado! Redirecionando…</div>`;
      } else if (['wc-cancelled','cancelled','refunded','failed','rejected'].includes(s)) {
        statusBox.className = 'alert alert-danger d-flex align-items-center gap-2 mt-3 mb-0';
        statusBox.innerHTML = `<i class="bi bi-x-circle"></i><div>Pagamento não aprovado. Tente novamente.</div>`;
      } else {
        statusBox.className = 'alert alert-info d-flex align-items-center gap-2 mt-3 mb-0';
        statusBox.innerHTML = `<i class="bi bi-hourglass-split"></i><div>Aguardando compensação do boleto.</div>`;
      }
    }
    if (paid) { if (pollTimer) clearInterval(pollTimer); onPaid?.(orderId); }
  };

  const doPoll = async () => {
    if (typeof pollFn !== 'function') return;
    try { const r = await pollFn(orderId); handleStatus(r?.status||''); } catch {}
  };
  if (typeof pollFn === 'function') { pollTimer = setInterval(doPoll, pollIntervalMs); doPoll(); }

  function toast(msg, type='info') {
    const el = document.createElement('div');
    el.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3 shadow`;
    el.style.zIndex = 9999; el.textContent = msg; document.body.appendChild(el);
    setTimeout(()=>el.remove(), 2000);
  }
}
