// Gera opções de frete
export function renderFreteOptions(freteData) {
    const container = document.getElementById("frete-opcoes");
    if (!container) return;
  
    container.innerHTML = "<p>Escolha o método de envio:</p>";
  
    Object.entries(freteData).forEach(([metodo, dados]) => {
      const wrapper = document.createElement("div");
      wrapper.className = "form-check";
  
      const input = document.createElement("input");
      input.type = "radio";
      input.name = "freteMetodo";
      input.value = metodo;
      input.id = `frete-${metodo}`;
      input.className = "form-check-input";
  
      const label = document.createElement("label");
      label.htmlFor = input.id;
      label.className = "form-check-label";
      label.textContent = `${metodo} – R$ ${dados.valor.toFixed(2)} – ${dados.prazo}`;
  
      wrapper.appendChild(input);
      wrapper.appendChild(label);
      container.appendChild(wrapper);
    });
  }
  
  // Retorna o método de frete selecionado
  export function getSelectedFrete() {
    const selected = document.querySelector("input[name='freteMetodo']:checked");
    return selected?.value || null;
  }

  