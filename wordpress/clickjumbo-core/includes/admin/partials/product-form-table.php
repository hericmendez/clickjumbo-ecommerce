<?php

function clickjumbo_render_product_form_table($dados, $penitenciarias, $categorias)
{
    ?>
    <table class="form-table" style="max-width:700px; background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:6px;">
        <tr>
            <th><label for="nome">Nome</label></th>
            <td><input name="nome" id="nome" type="text" class="regular-text" required value="<?= esc_attr($dados['nome']) ?>"></td>
        </tr>
        <tr>
            <th><label for="preco">Preço (R$)</label></th>
            <td><input name="preco" id="preco" type="number" step="0.01" min="0.01" class="regular-text" required value="<?= esc_attr($dados['preco']) ?>"></td>
        </tr>
        <tr>
            <th><label for="peso">Peso (kg)</label></th>
            <td><input name="peso" id="peso" type="number" step="0.01" min="0.01" class="regular-text" required value="<?= esc_attr($dados['peso']) ?>"></td>
        </tr>
        <tr>
            <th><label for="penitenciaria">Penitenciária</label></th>
            <td>
                <select name="penitenciaria" id="penitenciaria" class="regular-text" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($penitenciarias as $p): ?>
                        <option value="<?= esc_attr($p->slug) ?>" <?= selected($dados['penitenciaria'], $p->slug, false) ?>>
                            <?= esc_html($p->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="categoria">Categoria</label></th>
            <td>
                <select name="categoria" id="categoria" class="regular-text" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= esc_attr($c->term_id) ?>" <?= selected($dados['categoria'], $c->term_id, false) ?>>
                            <?= esc_html($c->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th><label for="subcategoria">Subcategoria</label></th>
            <td><input name="subcategoria" id="subcategoria" type="text" class="regular-text" value="<?= esc_attr($dados['subcategoria']) ?>"></td>
        </tr>
        <tr>
            <th><label for="maxUnitsPerClient">Limite por cliente</label></th>
            <td><input name="maxUnitsPerClient" id="maxUnitsPerClient" type="number" min="1" class="regular-text" required value="<?= esc_attr($dados['max']) ?>"></td>
        </tr>
        <tr>
            <th><label for="sku">SKU (deixe em branco para gerar)</label></th>
            <td><input name="sku" id="sku" type="text" class="regular-text" value="<?= esc_attr($dados['sku']) ?>"></td>
        </tr>
        <tr>
            <th><label for="imagem">Imagem (mock)</label></th>
            <td><input name="imagem" id="imagem" type="file"></td>
        </tr>
    </table>
    <?php
}
