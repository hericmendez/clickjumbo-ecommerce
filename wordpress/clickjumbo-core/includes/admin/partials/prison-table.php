<?php
function render_prison_table()
{
    echo '<div id="painel-lista" style="margin-top: 20px;">
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Cidade</th>
                    <th>Estado</th>
                    <th>CEP</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="prison-table-body">
                <tr><td colspan="5">Carregando...</td></tr>
            </tbody>
        </table>
    </div>';
}
