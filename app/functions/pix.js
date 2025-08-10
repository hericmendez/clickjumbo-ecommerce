export function initPixScreen({ orderId, qrCodeBase64, qrCodeText, expiresAt, onPaid, enableManualCheck, pollFn, pollIntervalMs = 15000 } = {}) {
  const copyBtn   = document.getElementById('btnPixCopy');
  const copyArea  = document.getElementById('pixCopyText');
  const statusBox = document.getElementById('pixStatusBox');
  const downloadBtn = document.getElementById('btnPixDownload');
  const manualBtn = document.getElementById('btnPixCheckNow');

  if (copyBtn && copyArea) {
    copyBtn.addEventListener('click', async () => {
      try { await navigator.clipboard.writeText(copyArea.value || ''); toast('Código PIX copiado!', 'success'); }
      catch { toast('Não foi possível copiar. Copie manualmente.', 'warning'); }
    });
  }
  if (downloadBtn && qrCodeBase64) {
    downloadBtn.addEventListener('click', () => {
      const a = document.createElement('a');
      a.href = `data:image/png;base64,${qrCodeBase64}`;
      a.download = `pix-order-${orderId||'pedido'}.png`;
      document.body.appendChild(a); a.click(); a.remove();
    });
  }
  if (expiresAt) {
    const el = document.getElementById('pixCountdown');
    const exp = new Date(expiresAt);
    const tick = () => {
      const s = Math.max(0, Math.floor((exp.getTime()-Date.now())/1000));
      const h = String(Math.floor(s/3600)).padStart(2,'0');
      const m = String(Math.floor((s%3600)/60)).padStart(2,'0');
      const ss= String(s%60).padStart(2,'0');
      if (el) el.textContent = `${h}:${m}:${ss}`;
      if (s<=0 && statusBox) {
        statusBox.className = 'alert alert-warning d-flex align-items-center gap-2 mt-3 mb-0';
        statusBox.innerHTML = `<i class="bi bi-exclamation-triangle"></i><div>Este QR Code expirou. Gere um novo para continuar.</div>`;
        clearInterval(t);
      }
    };
    const t = setInterval(tick, 1000); tick();
  }

  let pollTimer = null;
  const handleStatus = (st) => {
    const s = (st||'').toLowerCase();
    const paid = ['wc-processing','processing','completed'].includes(s);
    if (statusBox) {
      if (paid) {
        statusBox.className = 'alert alert-success d-flex align-items-center gap-2 mt-3 mb-0';
        statusBox.innerHTML = `<i class="bi bi-check-circle"></i><div>Pagamento confirmado! Redirecionando…</div>`;
      } else if (['wc-cancelled','cancelled','refunded','failed','rejected'].includes(s)) {
        statusBox.className = 'alert alert-danger d-flex align-items-center gap-2 mt-3 mb-0';
        statusBox.innerHTML = `<i class="bi bi-x-circle"></i><div>Pagamento não aprovado. Tente novamente.</div>`;
      } else {
        statusBox.className = 'alert alert-info d-flex align-items-center gap-2 mt-3 mb-0';
        statusBox.innerHTML = `<i class="bi bi-hourglass-split"></i><div>Aguardando pagamento. Assim que aprovado, confirmamos automaticamente.</div>`;
      }
    }
    if (paid) { if (pollTimer) clearInterval(pollTimer); onPaid?.(orderId); }
  };
  const doPoll = async () => {
    if (typeof pollFn !== 'function') return;
    try { const r = await pollFn(orderId); handleStatus(r?.status||''); } catch {}
  };
  if (typeof pollFn === 'function') { pollTimer = setInterval(doPoll, pollIntervalMs); doPoll(); }

  if (enableManualCheck && manualBtn) {
    manualBtn.classList.remove('d-none');
    manualBtn.addEventListener('click', async () => {
      manualBtn.disabled = true;
      manualBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Verificando...`;
      try { await doPoll(); } finally {
        manualBtn.disabled = false;
        manualBtn.innerHTML = `<i class="bi bi-arrow-repeat me-1"></i> Verificar pagamento agora`;
      }
    });
  }

  function toast(msg, type='info') {
    const el = document.createElement('div');
    el.className = `alert alert-${type} position-fixed top-0 start-50 translate-middle-x mt-3 shadow`;
    el.style.zIndex = 9999; el.textContent = msg; document.body.appendChild(el);
    setTimeout(()=>el.remove(), 2000);
  }
}
