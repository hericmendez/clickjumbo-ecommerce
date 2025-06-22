<?php
function render_products_panel()
{
    echo '<div id="painel-produtos" style="margin-top: 40px; display: none;">
        <h2 id="titulo-produtos">Produtos da penitenciária</h2>
        <table style="width: 100%;" class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria/subcategoria</th>
                    <th>Preço</th>
                   <th>Peso</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="produtos-da-penitenciaria">
                <tr><td colspan="3">Selecione uma penitenciária para ver os produtos.</td></tr>
            </tbody>
        </table>
        <div id="modal-detalhes" style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%);
            background:#fff; padding:20px; border:1px solid #ccc; border-radius:6px; z-index:9999; max-width:500px; box-shadow:0 0 10px rgba(0,0,0,0.2);">
            <h2>Detalhes do Produto</h2>
            <div id="modal-conteudo"></div>
            <button onclick="fecharModal()" class="button">Fechar</button>
        </div>
        <div id="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.4); z-index:9998;" onclick="fecharModal()"></div>
    </div>';
}
