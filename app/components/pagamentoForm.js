export const pagamentoForm = () => `

        <div class="container-fluid " style="margin-top: 5vh !important">
            <div class="container ">


                <div class="d-flex justify-content-center flex-column-reverse flex-md-row">

                    <div class="col-lg-8">
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
                                        <label class="form-check-label" for="boleto">Boleto
                                            bancário</label>
                                    </div>
                                    <!--    <div class="form-check mb-4">
    <input
        id="card"
        name="paymentMethod"
        type="radio"
        class="form-check-input"
        value="card"
        required
    />
    <label class="form-check-label" for="card"
        >Cartão de crédito/débito</label
    >
    </div

    <div id="card-details" class="mb-4" style="display: none">
    <div class="row g-3">
        <div class="col-md-6">
        <label class="form-label">Nome no cartão</label>
        <input
            type="text"
            class="form-control"
            name="cardName"
        />
        </div>
        <div class="col-md-6">
        <label class="form-label">Número do cartão</label>
        <input
            type="text"
            class="form-control"
            name="cardNumber"
            placeholder="1234 5678 9012 3456"
        />
        </div>
        <div class="col-md-6">
        <label class="form-label">Validade</label>
        <input
            type="text"
            class="form-control"
            name="cardExpiration"
            placeholder="MM/AA"
        />
        </div>
        <div class="col-md-6">
        <label class="form-label">CVV</label>
        <input
            type="text"
            class="form-control"
            name="cardCVV"
            placeholder="123"
        />
        </div>
    </div>
    </div>

    <div id="pix-instructions" class="alert alert-success d-none">
    Pagamento via <strong>Pix</strong>. QR Code será gerado após
    a confirmação.
    </div>
    <div
    id="boleto-instructions"
    class="alert alert-secondary d-none"
    >
    Pagamento via <strong>Boleto</strong>. Gerado com vencimento
    em 3 dias úteis.
    </div>

    <hr class="my-4" />
    <h4 class="mb-3">Dados do Envio</h4>
    <ul class="list-group mb-3" id="resumo-envio"> -->
                                    <!-- Populado via JS -->
                                    </ul>
                                    <!--               <div class="d-grid mb-4">
                                        <a href="cart.html" class="btn btn-outline-secondary">Alterar
                                            envio</a>
                                    </div> -->


                                </div>
                                <button type="button" id="btnFinalizarPedido"
                                    class="next btn btn-success w-100">
                                    Finalizar Pedido
                                </button>

                            </div>

                        </div>

                    </div>

                    <!-- Carrinho e resumo -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h4 class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="text-muted">Resumo da Compra</span>
                                    <span class="badge bg-secondary rounded-pill"
                                        id="qtde-carrinho">0</span>
                                </h4>

                                <button class="btn btn-outline-primary mb-3 w-100" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#cart-collapse">
                                    Ver produtos <i class="bi bi-chevron-down"></i>
                                </button>
                                <div class="collapse" id="cart-collapse">
                                    <ul class="list-group mb-3" id="itens-carrinho">
                                        <!-- Itens via JS -->
                                    </ul>
                                </div>

                                <ul class="list-group" id="resumo-carrinho">
                                    <!-- Resumo via JS -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
`;



