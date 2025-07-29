export function validarPagamento() {
  const metodo = document.querySelector('input[name="paymentMethod"]:checked')?.value;

  if (!metodo) {
    alert("Selecione a forma de pagamento!");
    return false;
  }

  if (metodo === 'card') {
    const cardName = document.querySelector("input[name='cardName']").value.trim();
    const cardNumber = document.querySelector("input[name='cardNumber']").value.trim();
    const cardExpiration = document.querySelector("input[name='cardExpiration']").value.trim();
    const cardCVV = document.querySelector("input[name='cardCVV']").value.trim();

    if (!cardName || !cardNumber || !cardExpiration || !cardCVV) {
      alert("Preencha todos os campos do cartão.");
      return false;
    }

    // Validações básicas (exemplo)
    if (!/^\d{16}$/.test(cardNumber.replace(/\s/g, ""))) {
      alert("Número do cartão inválido.");
      return false;
    }

    if (!/^\d{2}\/\d{2}$/.test(cardExpiration)) {
      alert("Validade inválida. Use o formato MM/AA.");
      return false;
    }

    if (!/^\d{3}$/.test(cardCVV)) {
      alert("CVV inválido.");
      return false;
    }
  }

  return true;
}
