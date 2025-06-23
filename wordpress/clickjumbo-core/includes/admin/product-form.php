<?php
// includes/admin/product-create-form.php
require_once __DIR__ . '/partials/product-form-handler.php';
require_once __DIR__ . '/partials/product-form-table.php';


function clickjumbo_render_novo_produto_form()
{



    $editando = isset($_GET['editar_produto']);
    $produto_id = $editando ? intval($_GET['editar_produto']) : null;
    $dados_produto = clickjumbo_get_dados_produto($produto_id);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_produto'])) {
        $erro = clickjumbo_handle_product_form($_POST, $produto_id);
        if (!$erro) {
            wp_redirect(admin_url('admin.php?page=clickjumbo-prisons&produto=ok'));
            exit;
        }

        echo '<div class="notice notice-error"><p>' . esc_html($erro) . '</p></div>';
    }

    $penitenciarias = get_terms(['taxonomy' => 'penitenciaria', 'hide_empty' => false]);
    $categorias = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false
    ]);



    echo '<div class="wrap">';
    echo '<h1 style="margin-bottom: 20px;">' . ($editando ? 'Editar Produto' : 'Cadastrar novo produto') . '</h1>';

    echo '<form method="POST" enctype="multipart/form-data">';
    clickjumbo_render_product_form_table($dados_produto, $penitenciarias, $categorias);
    echo '<p style="margin-top: 15px;"><button class="button button-primary" type="submit" name="cadastrar_produto">';
    echo $editando ? 'Salvar Alterações' : 'Cadastrar Produto';
    echo '</button></p>';
    echo '</form></div>';
}
