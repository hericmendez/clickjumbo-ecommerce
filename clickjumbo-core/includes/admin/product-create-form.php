<?php
// includes/admin/product-create-form.php

function clickjumbo_render_novo_produto_form() {
    if (isset($_POST['cadastrar_produto'])) {
        $nome = sanitize_text_field($_POST['nome']);
        $preco = floatval($_POST['preco']);
        $peso = floatval($_POST['peso']);
        $prison_slug = sanitize_title($_POST['penitenciaria']);
        $cat_id = intval($_POST['categoria']);
        $subcat = sanitize_text_field($_POST['subcategoria']);
        $max = intval($_POST['maxUnitsPerClient']);

        $post_id = wp_insert_post([
            'post_title' => $nome,
            'post_type' => 'product',
            'post_status' => 'publish'
        ]);

        if (!is_wp_error($post_id)) {
            wp_set_object_terms($post_id, [$cat_id], 'product_cat');
            if ($subcat) {
                $subcat_term = term_exists($subcat, 'product_cat');
                if (!$subcat_term) {
                    $subcat_term = wp_insert_term($subcat, 'product_cat', ['parent' => $cat_id]);
                }
                if (!is_wp_error($subcat_term)) {
                    wp_set_object_terms($post_id, [$subcat_term['term_id']], 'product_cat', true);
                }
            }

            update_post_meta($post_id, '_price', $preco);
            update_post_meta($post_id, '_regular_price', $preco);
            update_post_meta($post_id, '_weight', $peso);
            update_post_meta($post_id, 'prison', $prison_slug);
            update_post_meta($post_id, 'maxUnitsPerClient', $max);

            wp_redirect(admin_url('admin.php?page=clickjumbo-prisons&produto=ok'));
            exit;
        } else {
            wp_die('Erro ao cadastrar produto.');
        }
    }

    $penitenciarias = get_terms(['taxonomy' => 'penitenciaria', 'hide_empty' => false]);
    $categorias = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0]);
    ?>
    <div class="wrap">
        <h1 style="margin-bottom: 20px;">Cadastrar novo produto</h1>
        <form method="POST" enctype="multipart/form-data">
            <table class="form-table" style="max-width: 700px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 6px;">
                <tr><th><label for="nome">Nome</label></th><td><input name="nome" id="nome" type="text" class="regular-text" required></td></tr>
                <tr><th><label for="preco">Preço</label></th><td><input name="preco" id="preco" type="number" step="0.01" class="regular-text" required></td></tr>
                <tr><th><label for="peso">Peso (kg)</label></th><td><input name="peso" id="peso" type="number" step="0.01" class="regular-text" required></td></tr>
                <tr><th><label for="penitenciaria">Penitenciária</label></th>
                    <td>
                        <select name="penitenciaria" id="penitenciaria" class="regular-text" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($penitenciarias as $p): ?>
                                <option value="<?= esc_attr($p->slug) ?>"><?= esc_html($p->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr><th><label for="categoria">Categoria</label></th>
                    <td>
                        <select name="categoria" id="categoria" class="regular-text" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($categorias as $c): ?>
                                <option value="<?= esc_attr($c->term_id) ?>"><?= esc_html($c->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr><th><label for="subcategoria">Subcategoria</label></th><td><input name="subcategoria" id="subcategoria" type="text" class="regular-text"></td></tr>
                <tr><th><label for="maxUnitsPerClient">Limite por cliente</label></th><td><input name="maxUnitsPerClient" id="maxUnitsPerClient" type="number" value="1" class="regular-text"></td></tr>
                <tr><th><label for="imagem">Imagem (mock)</label></th><td><input name="imagem" id="imagem" type="file" disabled></td></tr>
            </table>
            <p style="margin-top: 15px;">
                <button class="button button-primary" type="submit" name="cadastrar_produto">Cadastrar Produto</button>
            </p>
        </form>
    </div>
    <?php
}
