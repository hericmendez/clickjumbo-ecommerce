<?php
function render_prison_form()
{
    echo '<div id="painel-formulario" style="margin-top: 20px; display: none;">
        <h2 id="form-title">Cadastrar nova penitenciária</h2>
        <form id="form-cadastro-prison">
            <table class="form-table">
                <tr><th><label for="nome">Nome</label></th><td><input required type="text" id="nome" class="regular-text" required></td></tr>
                <tr><th><label for="cidade">Cidade</label></th><td><input required type="text" id="cidade" class="regular-text" required></td></tr>
                <tr><th><label for="estado">Estado</label></th><td><input required type="text" id="estado" class="regular-text" maxlength="2" required></td></tr>
                <tr><th><label for="cep">CEP</label></th><td><input required type="text" id="cep" class="regular-text" maxlength="8" required></td></tr>
            </table>
            <p><button type="submit" class="button button-primary" id="submit-button">Cadastrar</button></p>
            <div id="mensagem"></div>
        </form>
    </div>';
}
