function updateCartSummaryBar(cart) {
    const produtosCount = document.getElementById("produtosCount");
    const pesoResumo = document.getElementById("pesoResumo");
    const valorResumo = document.getElementById("valorResumo");
  
    const totalProdutos = cart.reduce((acc, curr) => acc + (curr.qty || 1), 0);
    const pesoTotal = cart.reduce(
      (acc, curr) => acc + (curr.weight || 0) * (curr.qty || 1),
      0
    );
    const valorTotal = cart.reduce(
      (acc, curr) => acc + (curr.price || 0) * (curr.qty || 1),
      0
    );
  
    produtosCount.textContent = `Produtos: ${totalProdutos}`;
    pesoResumo.textContent = `Peso total: ${pesoTotal.toFixed(2)}kg/12kg`;
    valorResumo.textContent = `Total: R$${valorTotal.toFixed(2).replace(".", ",")}`;
  }
  

  export default updateCartSummaryBar;