<?php
// includes/admin/product-create-form.php

function clickjumbo_render_novo_produto_form()
{
    $editando = isset($_GET['editar_produto']);
    $produto_id = $editando ? intval($_GET['editar_produto']) : null;

    // Carregar dados se for edição
    $dados_produto = [
        'nome' => '',
        'preco' => '',
        'peso' => '',
        'penitenciaria' => '',
        'categoria' => '',
        'subcategoria' => '',
        'max' => 1
    ];

    if ($editando && get_post_type($produto_id) === 'product') {
        $post = get_post($produto_id);
        $dados_produto['nome'] = $post->post_title;
        $dados_produto['preco'] = get_post_meta($produto_id, '_price', true);
        $dados_produto['peso'] = get_post_meta($produto_id, '_weight', true);
        $dados_produto['max'] = get_post_meta($produto_id, 'maxUnitsPerClient', true);

        $pen_terms = wp_get_object_terms($produto_id, 'penitenciaria');
        $cat_terms = wp_get_object_terms($produto_id, 'product_cat');

        $dados_produto['penitenciaria'] = $pen_terms[0]->slug ?? '';
        $dados_produto['categoria'] = $cat_terms[0]->term_id ?? '';
        $dados_produto['subcategoria'] = $cat_terms[1]->name ?? '';
    }

    // Processa o POST
    if (isset($_POST['cadastrar_produto'])) {
        $nome = sanitize_text_field($_POST['nome']);
        $preco = floatval($_POST['preco']);
        $peso = floatval($_POST['peso']);
        $prison_slug = sanitize_title($_POST['penitenciaria']);
        $cat_id = intval($_POST['categoria']);
        $subcat = sanitize_text_field($_POST['subcategoria']);
        $max = intval($_POST['maxUnitsPerClient']);
$sku = sanitize_text_field($_POST['sku']);

if (empty($sku)) {
    $slug_base = sanitize_title($nome);
    $random = strtoupper(wp_generate_password(4, false, false));
    $sku = strtoupper(substr($slug_base, 0, 5)) . '-' . $random;

    // Garante que o SKU seja único
    while (wc_get_product_id_by_sku($sku)) {
        $random = strtoupper(wp_generate_password(4, false, false));
        $sku = strtoupper(substr($slug_base, 0, 5)) . '-' . $random;
    }
}


        if ($editando) {
            // Atualiza
            wp_update_post([
                'ID' => $produto_id,
                'post_title' => $nome,
                'post_status' => 'publish'
            ]);
        } else {
            // Cria novo
            $produto_id = wp_insert_post([
                'post_title' => $nome,
                'post_type' => 'product',
                'post_status' => 'publish'
            ]);
        }

        if (!is_wp_error($produto_id)) {
            wp_set_object_terms($produto_id, $prison_slug, 'penitenciaria');
            wp_set_object_terms($produto_id, [$cat_id], 'product_cat');

            if ($subcat) {
                $subcat_term = term_exists($subcat, 'product_cat');
                if (!$subcat_term) {
                    $subcat_term = wp_insert_term($subcat, 'product_cat', ['parent' => $cat_id]);
                }
                if (!is_wp_error($subcat_term)) {
                    wp_set_object_terms($produto_id, [$subcat_term['term_id']], 'product_cat', true);
                }
            }

            update_post_meta($produto_id, '_price', $preco);
            update_post_meta($produto_id, '_regular_price', $preco);
            update_post_meta($produto_id, '_weight', $peso);
            update_post_meta($produto_id, 'prison', $prison_slug);
            update_post_meta($produto_id, 'maxUnitsPerClient', $max);
            update_post_meta($produto_id, '_sku', $sku);

            update_post_meta($produto_id, '_stock_status', 'instock');
            update_post_meta($produto_id, '_manage_stock', 'no');
            update_post_meta($produto_id, '_visibility', 'visible');

            wc_update_product_stock_status($produto_id, 'instock');
            wp_update_post([
                'ID' => $produto_id,
                'post_status' => 'publish'
            ]);

            wp_redirect(admin_url('admin.php?page=clickjumbo-prisons&produto=ok'));
            exit;
        } else {
            wp_die('Erro ao salvar produto.');
        }
    }

    // Preenche selects
    $penitenciarias = get_terms(['taxonomy' => 'penitenciaria', 'hide_empty' => false]);
    $categorias = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0]);
    ?>

    <div class="wrap">
        <h1 style="margin-bottom: 20px;"><?= $editando ? 'Editar Produto' : 'Cadastrar novo produto' ?></h1>

        <form method="POST" enctype="multipart/form-data">
            <table class="form-table"
                style="max-width: 700px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 6px;">
                <tr>
                    <th><label for="nome">Nome</label></th>
                    <td><input name="nome" id="nome" type="text" class="regular-text"
                            value="<?= esc_attr($dados_produto['nome']) ?>" required></td>
                </tr>
                <tr>
                    <th><label for="preco">Preço</label></th>
                    <td><input name="preco" id="preco" type="number" step="0.01" class="regular-text"
                            value="<?= esc_attr($dados_produto['preco']) ?>" required></td>
                </tr>
                <tr>
                    <th><label for="peso">Peso (kg)</label></th>
                    <td><input name="peso" id="peso" type="number" step="0.01" class="regular-text"
                            value="<?= esc_attr($dados_produto['peso']) ?>" required></td>
                </tr>
                <tr>
                    <th><label for="penitenciaria">Penitenciária</label></th>
                    <td>
                        <select name="penitenciaria" id="penitenciaria" class="regular-text" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($penitenciarias as $p): ?>
                                <option value="<?= esc_attr($p->slug) ?>" <?= selected($dados_produto['penitenciaria'], $p->slug, false) ?>>
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
                                <option value="<?= esc_attr($c->term_id) ?>" <?= selected($dados_produto['categoria'], $c->term_id, false) ?>>
                                    <?= esc_html($c->name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="subcategoria">Subcategoria</label></th>
                    <td><input name="subcategoria" id="subcategoria" type="text" class="regular-text"
                            value="<?= esc_attr($dados_produto['subcategoria']) ?>"></td>
                </tr>
                <tr>
                    <th><label for="maxUnitsPerClient">Limite por cliente</label></th>
                    <td><input name="maxUnitsPerClient" id="maxUnitsPerClient" type="number"
                            value="<?= esc_attr($dados_produto['max']) ?>" class="regular-text"></td>
                </tr>
                <tr>
    <th><label for="sku">SKU (deixe em branco para gerar automático)</label></th>
    <td><input name="sku" id="sku" type="text" class="regular-text" value="<?= esc_attr($dados_produto['sku'] ?? '') ?>"></td>
</tr>

                <tr>
                    <th><label for="imagem">Imagem (mock)</label></th>
                    <td><input name="imagem" id="imagem" type="file" disabled></td>
                </tr>
            </table>

            <p style="margin-top: 15px;">
                <button class="button button-primary" type="submit" name="cadastrar_produto">
                    <?= $editando ? 'Salvar Alterações' : 'Cadastrar Produto' ?>
                </button>
            </p>
        </form>
    </div>

    <?php
}
