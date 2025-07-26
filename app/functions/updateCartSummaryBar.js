function updateCartSummaryBar(carrinho) {
    const produtosCount = document.getElementById("produtosCount");
    const pesoResumo = document.getElementById("pesoResumo");
    const valorResumo = document.getElementById("valorResumo");
  
    const totalProdutos = carrinho.reduce((acc, curr) => acc + (curr.qtde || 1), 0);
    const pesoTotal = carrinho.reduce(
      (acc, curr) => acc + (curr.peso || 0) * (curr.qtde || 1),
      0
    );
    const valorTotal = carrinho.reduce(
      (acc, curr) => acc + (curr.preco || 0) * (curr.qtde || 1),
      0
    );
  
    produtosCount.textContent = `Produtos: ${totalProdutos}`;
    pesoResumo.textContent = `Peso total: ${pesoTotal.toFixed(2)}kg/12kg`;
    valorResumo.textContent = `Total: R$${valorTotal.toFixed(2).replace(".", ",")}`;
  }
  

  export default updateCartSummaryBar;