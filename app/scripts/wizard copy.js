
document.addEventListener("DOMContentLoaded", function () {
  // Enable Tooltips (ok)
  var tooltipTriggerList = [].slice.call(
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
  );
  var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
  });

  // Funções de validação por etapa
  function validarCarrinho() {
    // Exemplo: impede avançar se carrinho vazio
    const dadosCarrinho = localStorage.getItem("dadosCarrinho") || [];
    if (!dadosCarrinho.length) {
      alert("Seu carrinho está vazio!");
      return false;
    }
    console.log("Carrinho validado:", dadosCarrinho);
    return true;
  }

  function validarCliente() {
    // Exemplo: valida formulário de dados do cliente
/*     const form = document.querySelector("#step2 form");
    if (!form.checkValidity()) {
      form.classList.add("was-validated");
      return false;
    } */
    return true;
  }

  function validarEndereco() {
    // Exemplo: verifica se endereço foi preenchido corretamente
    // (adicione lógica para caso "outro endereço" esteja selecionado)
    // Exemplo básico:
    if (document.getElementById("btnRadioOutro")?.checked) {
      const camposObrigatorios = [
        "destinatario",
        "outroLogradouro",
        "outroNumero",
        "outroBairro",
        "outraCidade",
        "outroEstado",
        "outroCep",
      ];
      let valido = true;
      camposObrigatorios.forEach(id => {
        const el = document.getElementById(id);
        if (el && !el.value.trim()) {
          el.classList.add("is-invalid");
          valido = false;
        } else if (el) {
          el.classList.remove("is-invalid");
        }
      });
      if (!valido) {
        alert("Preencha todos os campos do endereço de entrega!");
        return false;
      }
    }
    // Para penitenciária não precisa validar nada, só seguir
    return true;
  }



  // Mapeia steps para função de validação
  const validacoes = {
    "step1": validarCarrinho,
    "step2": validarCliente,
    "step3": validarEndereco,
   // "step4": validarPagamento
    // step5 não precisa
  };




  // Botão Anterior (mantém igual)
  document.querySelectorAll(".previous").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const activeTab = document.querySelector(".nav-tabs .active");
      if (!activeTab) return;

      const li = activeTab.closest("li");
      if (!li || !li.previousElementSibling) return;

      const prevTabLink = li.previousElementSibling.querySelector("a");
      if (!prevTabLink) return;

      const prevTab = new bootstrap.Tab(prevTabLink);
      prevTab.show();
    });
  });
  // Passos do wizard em ordem
const steps = [
  "step1",
  "step2",
  "step3",
  "step4",
  "step5"
];
// Vai manter o maior passo validado
let maxStepValidado = 0;

function updateWizardTabs(activeIdx) {
  // Libera só até o maior validado, bloqueia os demais
  steps.forEach((stepId, idx) => {
    const tab = document.querySelector(`a[href="#${stepId}"]`);
    if (!tab) return;
    if (idx <= maxStepValidado || idx === activeIdx) {
      tab.classList.remove("disabled");
      tab.setAttribute("tabindex", "0");
      tab.style.pointerEvents = "auto";
    } else {
      tab.classList.add("disabled");
      tab.setAttribute("tabindex", "-1");
      tab.style.pointerEvents = "none";
    }
  });
}

// Sempre atualiza ao trocar de aba
document.querySelectorAll('.nav-tabs a[data-bs-toggle="tab"]').forEach(function(tab, idx) {
  tab.addEventListener('show.bs.tab', function(e) {
    updateWizardTabs(idx);
  });
});

// Impede clique em abas futuras
document.querySelectorAll('.nav-tabs a[data-bs-toggle="tab"]').forEach(function(tab, idx) {
  tab.addEventListener('click', function(e) {
    // Busca o maior step validado
    if (idx > maxStepValidado) {
      e.preventDefault();
      e.stopPropagation();
    }
    // Se for step futuro não liberado, bloqueia!
  });
});

// Ao avançar, libera próximo passo
document.querySelectorAll(".next").forEach(function (btn) {
  btn.addEventListener("click", function (e) {
    const activeTab = document.querySelector(".nav-tabs .active");
    if (!activeTab) return;
    const href = activeTab.getAttribute("href") || activeTab.dataset.bsTarget;
    const stepId = href ? href.replace("#", "") : null;
    const li = activeTab.closest("li");
    if (!li || !li.nextElementSibling) return;

    // Qual o índice do passo atual?
    const idxAtual = steps.indexOf(stepId);
    if (stepId && validacoes[stepId]) {
      const passou = validacoes[stepId]();
      if (!passou) {
        e.preventDefault();
        e.stopPropagation();
        return;
      } else {
        // Avançou? Marca como validado
        if (idxAtual + 1 > maxStepValidado) {
          maxStepValidado = idxAtual + 1;
        }
        updateWizardTabs(idxAtual + 1);
      }
    }
    // Mostra próxima aba
    const nextTabLink = li.nextElementSibling.querySelector("a");
    if (!nextTabLink) return;
    const nextTab = new bootstrap.Tab(nextTabLink);
    nextTab.show();
  });
});

// Ao voltar, deixa voltar normalmente (e não reduz o maxStepValidado)
document.querySelectorAll(".previous").forEach(function (btn) {
  btn.addEventListener("click", function () {
    const activeTab = document.querySelector(".nav-tabs .active");
    if (!activeTab) return;
    const href = activeTab.getAttribute("href") || activeTab.dataset.bsTarget;
    const stepId = href ? href.replace("#", "") : null;
    const li = activeTab.closest("li");
    if (!li || !li.previousElementSibling) return;
    const idxAtual = steps.indexOf(stepId);
    updateWizardTabs(idxAtual - 1);

    const prevTabLink = li.previousElementSibling.querySelector("a");
    if (!prevTabLink) return;
    const prevTab = new bootstrap.Tab(prevTabLink);
    prevTab.show();
  });
});

// Inicializa: só primeiro passo liberado
updateWizardTabs(0);

});


