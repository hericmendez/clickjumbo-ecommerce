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
<!-- Categoria -->
<tr>
    <th><label for="categoria">Categoria</label></th>
    <td>
        <select name="categoria" id="categoria" required>
            <option value="">Selecione uma categoria...</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= esc_attr($cat->term_id) ?>"
                    <?= selected($dados['categoria_id'], $cat->term_id, false) ?>>
                    <?= esc_html($cat->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </td>
</tr>

<!-- Subcategoria (campo livre) -->
<tr>
    <th><label for="subcategoria">Subcategoria (opcional)</label></th>
    <td>
        <input type="text" name="subcategoria" id="subcategoria" class="regular-text"
               value="<?= esc_attr($dados['subcategoria'] ?? '') ?>">
    </td>
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
