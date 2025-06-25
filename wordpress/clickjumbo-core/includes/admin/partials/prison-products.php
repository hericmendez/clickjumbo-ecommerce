<?php
function render_products_panel()
{
    echo '<div id="painel-produtos" style="margin-top: 40px; display: none;">
        <h2 id="titulo-produtos">Produtos da penitenciária</h2>
        <table style="width: 100%;" class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria/Subcategoria</th>

                    <th>Preço</th>
                    <th>Peso</th>
                    <th>SKU</th>
                    <th>Máx. por cliente</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="produtos-da-penitenciaria">
                <tr><td colspan="8">Selecione uma penitenciária para ver os produtos.</td></tr>
            </tbody>
        </table>
    </div>';
}
