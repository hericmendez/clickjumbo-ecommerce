  export function validarPagamento() {
    // Exemplo: garantir que o método de pagamento está selecionado
    const checked = document.querySelector('input[name="paymentMethod"]:checked');
    if (!checked) {
      alert("Selecione a forma de pagamento!");
      return false;
    }
    return true;
  }