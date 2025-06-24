<?php

function clickjumbo_render_product_form_table($dados, $penitenciarias, $categorias)
{
    // Garantir que todas as chaves existam
    $dados = array_merge([
        'nome' => '',
        'preco' => '',
        'peso' => '',
        'penitenciaria' => '',
        'categoria_id' => '',
        'subcategoria' => '',
        'maxUnitsPerClient' => 1,
        'sku' => ''
    ], $dados);
    ?>
    <table class="form-table"
        style="max-width:700px; background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:6px;">
        <!-- Nome do produto -->
        <tr>
            <th><label for="nome">Nome do Produto</label></th>
            <td><input name="nome" id="nome" type="text" class="regular-text" required placeholder="Ex: Arroz 5kg"
                    value="<?= esc_attr($dados['nome']) ?>"></td>
        </tr>

        <!-- Preço -->
        <tr>
            <th><label for="preco">Preço (R$)</label></th>
            <td><input name="preco" id="preco" type="number" step="0.01" min="0.01" class="regular-text" required
                    placeholder="Ex: 12.99" value="<?= esc_attr($dados['preco']) ?>"></td>
        </tr>

        <!-- Peso -->
        <tr>
            <th><label for="peso">Peso (kg)</label></th>
            <td><input name="peso" id="peso" type="number" step="0.01" min="0.01" class="regular-text" required
                    placeholder="Ex: 0.5" value="<?= esc_attr($dados['peso']) ?>"></td>
        </tr>

        <!-- Penitenciária -->
        <tr>
            <th><label for="penitenciaria">Penitenciária</label></th>
            <td>
                <select name="penitenciaria" id="penitenciaria" class="regular-text" required>
                    <option value="">Selecione uma penitenciária...</option>
                    <?php foreach ($penitenciarias as $p): ?>
                        <option value="<?= esc_attr($p->slug) ?>" <?= selected($dados['penitenciaria'], $p->slug, false) ?>>
                            <?= esc_html($p->name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>

        <!-- Categoria principal -->
        <tr>
            <th><label for="categoria">Categoria</label></th>
            <td>
                <select name="categoria" id="categoria" required>
                    <option value="">Selecione uma categoria...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <?php if ($cat->parent == 0): ?>
                            <option value="<?= esc_attr($cat->term_id) ?>" <?= selected($dados['categoria_id'], $cat->term_id, false) ?>>
                                <?= esc_html($cat->name) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>

                </select>
            </td>
        </tr>

        <!-- Subcategoria (livre) -->
        <tr>
            <th><label for="subcategoria">Subcategoria (opcional)</label></th>
            <td><input type="text" name="subcategoria" id="subcategoria" class="regular-text"
                    placeholder="Ex: Massas, Refrigerantes, etc." value="<?= esc_attr($dados['subcategoria']) ?>"></td>
        </tr>

        <!-- Limite por cliente -->
        <tr>
            <th><label for="maxUnitsPerClient">Limite por cliente</label></th>
            <td><input name="maxUnitsPerClient" id="maxUnitsPerClient" type="number" min="1" class="regular-text" required
                    placeholder="Ex: 3" value="<?= esc_attr($dados['maxUnitsPerClient']) ?>"></td>
        </tr>

        <!-- SKU -->
        <tr>
            <th><label for="sku">SKU (opcional)</label></th>
            <td><input name="sku" id="sku" type="text" class="regular-text"
                    placeholder="Deixe em branco para gerar automaticamente" value="<?= esc_attr($dados['sku']) ?>"></td>
        </tr>

        <!-- Imagem -->
        <tr>
            <th><label for="imagem">Imagem (mock)</label></th>
            <td><input name="imagem" id="imagem" type="file" class="regular-text"></td>
        </tr>
    </table>
    <?php
}
