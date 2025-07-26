


export const envioForm = () => `
             <div class="card p-4">
                                 <h3>Endereço de Entrega</h3>

                            <div class="btn-group d-flex flex-column" role="group" aria-label="Escolher endereço">
                                <div class="mb-2">
                                    <input type="radio" class="btn-check" name="opcaoEndereco"
                                        id="btnRadioPenitenciaria" value="penitenciaria" autocomplete="off" checked>
                                    <label class="btn btn-outline-primary w-100 text-start" for="btnRadioPenitenciaria">
                                        <span id="infoPenitenciariaBtn">
                                            <!-- Dados da penitenciária aqui -->
                                        </span>
                                    </label>
                                </div>

                                <div>
                                    <input type="radio" class="btn-check" name="opcaoEndereco" id="btnRadioOutro"
                                        value="outro" autocomplete="off">
                                    <label class="btn btn-outline-primary w-100 text-start" for="btnRadioOutro">
                                        Enviar para outro endereço
                                    </label>
                                </div>

                            </div>

                            <div id="formEnvio" class="mt-3" style="display: none;">
                                <div class="row g-2">
                                    <div class="col-12 mt-2">
                                        <input type="text" class="form-control" id="destinatario"
                                            placeholder="Nome do Destinatário">
                                    </div>
                                    <div class="col-md-10 mt-2">
                                        <input type="text" class="form-control" id="logradouroDestinatario"
                                            placeholder="Rua/Avenida">
                                    </div>
                                    <div class="col-md-2 mt-2">
                                        <input type="text" class="form-control" id="numeroDestinatario" placeholder="Número">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <input type="text" class="form-control" id="bairroDestinatario" placeholder="Bairro">
                                    </div>
                                    <div class="col-md-6 mt-2">
                                        <input type="text" class="form-control" id="complementoDestinatario"
                                            placeholder="Complemento">
                                    </div>
                                    <div class="col-md-6">
                                        <input type="text" class="form-control" id="cidadeDestinatario" placeholder="Cidade">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" id="estadoDestinatario" placeholder="Estado">
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" class="form-control" id="cepDestinatario" placeholder="CEP">
                                    </div>
                                </div>
                            </div>
   <button type="button" id="btnCalcFrete" class="btn btn-success w-100 mt-4">
                            Calcular Frete
                        </button>
                        </div>

`;
