<?php
if (!defined('ABSPATH')) exit;

function cj_get_order_details_modal_html() {
  ob_start(); ?>
  <!-- CJ: Modal Detalhes do Pedido -->
  <div class="modal fade" id="modal-detalhes" tabindex="-1" aria-labelledby="modalDetalhesLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content" id="pedido-modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Pedido #<span id="pedido-id"></span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>

        <div class="modal-body">
          <!-- Cliente -->
          <h6 class="mt-1">Cliente</h6>
          <p><strong>Nome:</strong> <span id="pedido-cliente"></span></p>
          <p><strong>Email:</strong> <span id="pedido-email"></span></p>
          <p><strong>Endereço:</strong> <span id="pedido-endereco"></span></p>

          <!-- Detento -->
          <h6 class="mt-4">Detento</h6>
          <p><strong>Nome:</strong> <span id="detento-nome"></span></p>
          <p><strong>Matrícula:</strong> <span id="detento-matricula"></span></p>
          <p><strong>Raio/Cela:</strong> <span id="detento-raio"></span> / <span id="detento-cela"></span></p>
          <p><strong>Penitenciária (detento):</strong> <span id="detento-peni"></span></p>

          <!-- Penitenciária do pedido -->
          <h6 class="mt-4">Penitenciária (pedido)</h6>
          <p><strong>Nome:</strong> <span id="pedido-prison"></span></p>
          <p><strong>Endereço:</strong> <span id="pedido-prison-endereco"></span></p>

          <!-- Produtos -->
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

          <!-- Envio -->
          <h6 class="mt-4">Envio</h6>
          <p><strong>Forma de envio:</strong> <span id="pedido-envio-forma"></span></p>
          <p><strong>Enviar para penitenciária:</strong> <span id="pedido-envio-para-peni"></span></p>
          <p><strong>Remetente:</strong> <span id="pedido-envio-remetente"></span></p>
          <p><strong>Destinatário:</strong> <span id="pedido-envio-destinatario"></span></p>
          <p><strong>Frete:</strong> R$ <span id="pedido-frete"></span></p>

          <!-- Resumo -->
          <h6 class="mt-4">Resumo</h6>
          <p><strong>Valor Carrinho:</strong> R$ <span id="pedido-carrinho"></span></p>
          <p><strong>Total:</strong> R$ <span id="pedido-total"></span></p>
          <p><strong>Status Pagamento:</strong> <span id="pedido-pagamento-status"></span></p>
          <p><strong>Método Pagamento:</strong> <span id="pedido-pagamento-metodo"></span></p>
          <p id="linha-comprovante" style="display:none;">
            <strong>Comprovante:</strong>
            <a id="pedido-pagamento-comprovante" href="" target="_blank" rel="noopener noreferrer"></a>
          </p>
          <p><strong>Data:</strong> <span id="pedido-data"></span></p>
        </div>

        <div class="modal-footer">
<button type="button" class="btn btn-primary" id="btn-modal-pdf" data-html2canvas-ignore="true">
  Baixar PDF
</button>

          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- html2pdf (gera PDF do HTML do modal) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" referrerpolicy="no-referrer"></script>

  <script>
  function abrirModalDetalhesPedido(pedido) {
    try {
      function fmtMoney(v){ var n=Number(v); if(isNaN(n)) n=0; return n.toLocaleString('pt-BR',{minimumFractionDigits:2,maximumFractionDigits:2}); }
      function setTxt(id,val){ var el=document.getElementById(id); if(el) el.textContent=(val!==undefined&&val!==null&&val!=='')?val:'—'; }
      function fmtEndereco(x){
        if(!x) return '—';
        var rua = x.rua || x.logradouro || '';
        var num = x.numero ? ' ' + x.numero : '';
        var bairro = x.bairro ? ', ' + x.bairro : '';
        var ciduf = (x.cidade || x.estado) ? ', ' + (x.cidade || '') + (x.estado ? ' - ' + x.estado : '') : '';
        var cep = x.cep ? ', CEP ' + x.cep : '';
        var comp = x.complemento ? ' (' + x.complemento + ')' : '';
        var s = (rua + num + bairro + ciduf + cep + comp).replace(/^,\s*/,'').trim();
        return s || '—';
      }

      // Cliente
      setTxt('pedido-id', pedido?.id ?? '—');
      setTxt('pedido-cliente', pedido?.cliente?.nome ?? '—');
      setTxt('pedido-email',   pedido?.cliente?.email ?? '—');
      setTxt('pedido-endereco', pedido?.cliente?.endereco ?? '—');

      // Detento
      setTxt('detento-nome', pedido?.detento?.nome ?? '—');
      setTxt('detento-matricula', pedido?.detento?.matricula ?? '—');
      setTxt('detento-raio', pedido?.detento?.raio ?? '—');
      setTxt('detento-cela', pedido?.detento?.cela ?? '—');
      setTxt('detento-peni', pedido?.detento?.nome_penitenciaria ?? '—');

      // Penitenciária (pedido)
      setTxt('pedido-prison',  (pedido?.penitenciaria?.nome || pedido?.penitenciaria?.slug || '—'));
      setTxt('pedido-prison-endereco', fmtEndereco(pedido?.penitenciaria));

      // Produtos
      var tbody = document.getElementById('pedido-produtos');
      var somaCarrinho = 0;
      if (tbody) {
        tbody.innerHTML = '';
        (pedido?.produtos || []).forEach(function(prod){
          var qtd  = Number(prod.quantidade ?? prod.qtd ?? prod.qtde ?? 1);
          var unit = Number(prod.preco_unitario ?? prod.preco ?? 0);
          var sub  = Number(prod.subtotal ?? (qtd * unit));
          somaCarrinho += sub;
          var tr = document.createElement('tr');
          tr.innerHTML =
            '<td style="padding:6px;">'+ (prod.nome || prod.titulo || '—') +'</td>'+
            '<td style="text-align:center; padding:6px;">'+ qtd +'</td>'+
            '<td style="text-align:right; padding:6px;">R$ '+ fmtMoney(unit) +'</td>'+
            '<td style="text-align:right; padding:6px;">R$ '+ fmtMoney(sub) +'</td>';
          tbody.appendChild(tr);
        });
      }

      // Envio
      var envio = pedido?.envio || {};
      setTxt('pedido-envio-forma', envio.forma_envio || envio.method || envio.metodo || '—');
      setTxt('pedido-envio-para-peni', (String(envio.enviar_para_penitenciaria) === '1') ? 'Sim' : 'Não');
      setTxt('pedido-envio-remetente', fmtEndereco(envio.remetente || envio.remtente));
      setTxt('pedido-envio-destinatario', fmtEndereco(envio.destinatario));
      setTxt('pedido-frete', fmtMoney(envio.frete_valor || envio.frete));

      // Resumo
      setTxt('pedido-carrinho', fmtMoney(somaCarrinho));
      setTxt('pedido-total', fmtMoney(pedido?.total ?? pedido?.valorTotal));
      setTxt('pedido-pagamento-status', pedido?.pagamento?.status ?? '—');
      setTxt('pedido-pagamento-metodo', pedido?.pagamento?.metodo ?? pedido?.pagamento?.method ?? '—');

      // Comprovante
      var linhaComp = document.getElementById('linha-comprovante');
      var aComp = document.getElementById('pedido-pagamento-comprovante');
      var url = pedido?.pagamento?.comprovante_url;
      if (url && linhaComp && aComp) {
        aComp.href = url;
        aComp.textContent = 'Abrir comprovante';
        linhaComp.style.display = '';
      } else if (linhaComp) {
        linhaComp.style.display = 'none';
      }

      // Data
      var dataStr = pedido?.data;
      if (dataStr) {
        var parsed = new Date(dataStr);
        setTxt('pedido-data', isNaN(parsed.getTime()) ? dataStr : parsed.toLocaleString('pt-BR'));
      } else setTxt('pedido-data','—');

      // Botão PDF
      var btnPdf = document.getElementById('btn-modal-pdf');
      if (btnPdf) {
        btnPdf.onclick = function(){ exportarModalParaPDF(pedido); };
      }

      // Abrir modal
      var el = document.querySelector('#modal-detalhes.modal') || document.getElementById('modal-detalhes');
      if (!el) return;
      if (window.bootstrap && window.bootstrap.Modal) {
        var inst = window.bootstrap.Modal.getInstance(el); if (inst) inst.dispose();
        new window.bootstrap.Modal(el, { focus:false, backdrop:true, keyboard:true }).show();
      } else if (window.jQuery && jQuery.fn?.modal) {
        jQuery(el).modal({ show:true, focus:false, backdrop:true, keyboard:true });
      } else {
        var t=document.getElementById('cj-open-modal-trigger');
        if(!t){ t=document.createElement('button'); t.id='cj-open-modal-trigger'; t.hidden=true; t.setAttribute('data-bs-toggle','modal'); t.setAttribute('data-bs-target','#modal-detalhes'); document.body.appendChild(t); }
        t.click();
      }
    } catch(e) {
      console.error('Erro ao abrir modal de detalhes:', e);
      document.body.classList.remove('modal-open');
      document.querySelectorAll('.modal-backdrop').forEach(function(n){ n.remove(); });
    }
  }

  // Exporta o conteúdo visível do modal para PDF
  function exportarModalParaPDF(pedido){
    try{
      if(!window.html2pdf){ alert('Gerador de PDF indisponível.'); return; }
      var src = document.getElementById('pedido-modal-content');
      if(!src){ alert('Conteúdo do modal não encontrado.'); return; }

      // Clona para evitar cortes de scroll e captura completa
      var clone = src.cloneNode(true);
      clone.style.maxHeight = 'none';
      clone.style.overflow = 'visible';
      // remove o botão "Fechar" do clone (opcional)
      var cloneFooter = clone.querySelector('.modal-footer');
      if (cloneFooter) {
        var closeBtn = cloneFooter.querySelector('[data-bs-dismiss="modal"]');
        if (closeBtn) closeBtn.remove();
      }

      var temp = document.createElement('div');
      temp.style.position='fixed'; temp.style.left='-9999px'; temp.style.top='0'; temp.style.background='#fff';
      temp.appendChild(clone);
      document.body.appendChild(temp);

      var opt = {
        margin: 10,
        filename: 'pedido_'+ (pedido?.id ?? 'sem_id') +'.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff' },
        jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
        pagebreak: { mode: ['css', 'legacy'] }
      };

      html2pdf().set(opt).from(clone).save().then(function(){
        document.body.removeChild(temp);
      }).catch(function(e){
        document.body.removeChild(temp);
        console.error('Erro ao gerar PDF:', e);
        alert('Não foi possível gerar o PDF.');
      });
    }catch(e){
      console.error('Erro ao gerar PDF:', e);
      alert('Não foi possível gerar o PDF.');
    }
  }
  </script>
  <?php
  return ob_get_clean();
}
