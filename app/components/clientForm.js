export const clientForm = () => `
<div class="card p-4">
    <div id="formPagamento" novalidate>
        <h5>Dados do Visitante </h5>
        <div class="row g-3 mb-4">
            <div class="col-md-12">
                <input id="nomeVisitante" name="nomeVisitante" type="text"
                    class="form-control" placeholder="Nome do visitante" required />
                <div class="invalid-feedback">Informe o nome do visitante.</div>
            </div>
            <div class="col-md-4">
                <input id="emailVisitante" name="emailVisitante" type="email"
                    class="form-control" placeholder="Email" required />
                <div class="invalid-feedback">Informe um email válido.</div>
            </div>
            <div class="col-4">
                <input id="telefoneVisitante" name="telefoneVisitante" type="tel"
                    class="form-control" placeholder="Telefone" required />
                <div class="invalid-feedback">Informe o telefone do visitante.</div>
            </div>
            <div class="col-md-4">
                <input id="numeroCarteirinha" name="numeroCarteirinha" type="text"
                    class="form-control" placeholder="Nº da Carteirinha" required />
                <div class="invalid-feedback">Informe o número da carteirinha.</div>
            </div>
            <div class="col-md-8">
                <input id="ruaVisitante" name="ruaVisitante" type="text"
                    class="form-control" placeholder="Rua" required />
                <div class="invalid-feedback">Informe a rua do visitante.</div>
            </div>
            <div class="col-md-2">
                <input id="numeroVisitante" name="numeroVisitante" type="text"
                    class="form-control" placeholder="Nº" required />
                <div class="invalid-feedback">Informe o número do endereço.</div>
            </div>
                        <div class="col-md-2">
                <input id="complementoVisitante" name="complementoVisitante" type="text"
                    class="form-control" placeholder="Complemento" required />
                <div class="invalid-feedback">Informe o número do endereço.</div>
            </div>
                        <div class="col-md-3">
                <input id="bairroVisitante" name="bairroVisitante" type="text"
                    class="form-control" placeholder="Bairro" required />
                <div class="invalid-feedback">Informe o bairro.</div>
            </div>
            <div class="col-md-3">
                <input id="cidadeVisitante" name="cidadeVisitante" type="text"
                    class="form-control" placeholder="Cidade" required />
                <div class="invalid-feedback">Informe a cidade.</div>
            </div>
            <div class="col-md-3">
                <input id="estadoVisitante" name="estadoVisitante" type="text"
                    class="form-control" placeholder="Estado" required />
                <div class="invalid-feedback">Informe o estado.</div>
            </div>
            <div class="col-md-3">
                <input id="cepVisitante" name="cepVisitante" type="text"
                    class="form-control" placeholder="CEP" required />
                <div class="invalid-feedback">Informe o CEP.</div>
            </div>
        </div>
        <hr>
        <h5>Dados do Beneficiário (Detento)</h5>
        <div class="row g-3 mb-4 ">
            <div class="col-md-12">
                <input id="nomeDetento" name="nomeDetento" type="text" class="form-control"
                    placeholder="Nome" required />
                <div class="invalid-feedback">Informe o nome do detento.</div>
            </div>
            <div class="col-md-4">
                <input id="matriculaDetento" name="matriculaDetento" type="text"
                    class="form-control" placeholder="Matrícula" required />
                <div class="invalid-feedback">Informe a matrícula do detento.</div>
            </div>
            <div class="col-4">
                <input id="raioDetento" name="raioDetento" type="tel" class="form-control"
                    placeholder="Raio" required />
                <div class="invalid-feedback">Informe o raio.</div>
            </div>
            <div class="col-md-4">
                <input id="celaDetento" name="celaDetento" type="text" class="form-control"
                    placeholder="Cela" required />
                <div class="invalid-feedback">Informe a cela.</div>
            </div>
        </div>
        <hr>
    </div>
</div>

`