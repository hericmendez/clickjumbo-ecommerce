export const clientForm = () => `
<div class="card p-4">
    <div id="formPagamento" novalidate>
        <h5>Dados do Visitante </h5>
        <div class="row g-3 mb-4">
            <div class="col-md-12">
                <input id="nomeVisitante" value="José Placeholder" name="nomeVisitante" type="text"
                    class="form-control" placeholder="Nome do visitante" required />
                <div class="invalid-feedback">Informe o nome do visitante.</div>
            </div>
            <div class="col-md-4">
                <input id="emailVisitante" value="jose2025@gmail.com" name="emailVisitante" type="email"
                    class="form-control" placeholder="Email" required />
                <div class="invalid-feedback">Informe um email válido.</div>
            </div>
            <div class="col-4">
                <input id="telefoneVisitante" value="16991234567" name="telefoneVisitante" type="tel"
                    class="form-control" placeholder="Telefone" required />
                <div class="invalid-feedback">Informe o telefone do visitante.</div>
            </div>
            <div class="col-md-4">
                <input id="numeroCarteirinha" value="8520147" name="numeroCarteirinha" type="text"
                    class="form-control" placeholder="Nº da Carteirinha" required />
                <div class="invalid-feedback">Informe o número da carteirinha.</div>
            </div>
            <div class="col-md-8">
                <input id="ruaVisitante" value="Rua Teste" name="ruaVisitante" type="text"
                    class="form-control" placeholder="Rua" required />
                <div class="invalid-feedback">Informe a rua do visitante.</div>
            </div>
            <div class="col-md-2">
                <input id="numeroVisitante" value="333" name="numeroVisitante" type="text"
                    class="form-control" placeholder="Nº" required />
                <div class="invalid-feedback">Informe o número do endereço.</div>
            </div>
                        <div class="col-md-2">
                <input id="complementoVisitante" name="complementoVisitante" type="text"
                    class="form-control" placeholder="Complemento" required />
                <div class="invalid-feedback">Informe o número do endereço.</div>
            </div>
            <div class="col-md-3">
                <input id="bairroVisitante" value="Groenlândia 37" name="bairroVisitante" type="text"
                    class="form-control" placeholder="Bairro" required />
                <div class="invalid-feedback">Informe o bairro.</div>
            </div>
            <div class="col-md-3">
                <input id="cidadeVisitante" value="Townsville" name="cidadeVisitante" type="text"
                    class="form-control" placeholder="Cidade" required />
                <div class="invalid-feedback">Informe a cidade.</div>
            </div>
            <div class="col-md-3">
                <input id="estadoVisitante" value="SP" name="estadoVisitante" type="text"
                    class="form-control" placeholder="Estado" required />
                <div class="invalid-feedback">Informe o estado.</div>
            </div>
            <div class="col-md-3">
                <input id="cepVisitante" value="22222222" name="cepVisitante" type="text"
                    class="form-control" placeholder="CEP" required />
                <div class="invalid-feedback">Informe o CEP.</div>
            </div>
        </div>
        <hr>
        <h5>Dados do Beneficiário (Detento)</h5>
        <div class="row g-3 mb-4 ">
            <div class="col-md-12">
                <input id="nomeDetento" value="José Silva" name="nomeDetento" type="text" class="form-control"
                    placeholder="Nome" required />
                <div class="invalid-feedback">Informe o nome do detento.</div>
            </div>
            <div class="col-md-4">
                <input id="matriculaDetento" value="12345" name="matriculaDetento" type="text"
                    class="form-control" placeholder="Matrícula" required />
                <div class="invalid-feedback">Informe a matrícula do detento.</div>
            </div>
            <div class="col-4">
                <input id="raioDetento" value="123" name="raioDetento" type="tel" class="form-control"
                    placeholder="Raio" required />
                <div class="invalid-feedback">Informe o raio.</div>
            </div>
            <div class="col-md-4">
                <input id="celaDetento" value="1213" name="celaDetento" type="text" class="form-control"
                    placeholder="Cela" required />
                <div class="invalid-feedback">Informe a cela.</div>
            </div>
        </div>
        <!--
        <hr>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="saveClientInfo" />
                    <label class="form-check-label" for="saveClientInfo">
                        Salvar informações na sua conta
                    </label>
                </div>
                -->
    </div>
</div>

`

export const salvarDadosCliente = (dados) => {
  const userPayload = {
    user_id: dados.user_id,
    detento: dados?.detento || {},
    endereco_usuario: {},
    endereco_visitante: {}
  }
  const checked = document.getElementById('saveClientInfo').checked
  if (checked) {
  }
}

export const carregarDadosCliente = () => {}
