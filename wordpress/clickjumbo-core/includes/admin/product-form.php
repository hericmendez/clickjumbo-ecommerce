<?php
require_once __DIR__ . '/partials/product-form-table.php';

function clickjumbo_render_novo_produto_form()
{
    $penitenciarias = get_terms(['taxonomy' => 'penitenciaria', 'hide_empty' => false]);
    $categorias = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false]);

    echo '<div class="wrap">';
    echo '<h1 style="margin-bottom: 20px;">Cadastrar novo produto</h1>';
    echo '<form id="produto-form">';
    clickjumbo_render_product_form_table([], $penitenciarias, $categorias);
    echo '<p style="margin-top: 15px;"><button class="button button-primary" type="submit">Cadastrar Produto</button></p>';
    echo '</form>';
    echo '<div id="mensagem-produto" style="margin-top:15px;"></div>';
    echo '</div>';

    // Incluir o nonce e script
    wp_nonce_field('wp_rest', '_wpnonce');
    ?>

    <script>
        const form = document.getElementById('produto-form');
        const msgBox = document.getElementById('mensagem-produto');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            msgBox.innerHTML = 'Enviando...';

            const formData = new FormData(form);
            const data = {
                name: formData.get('nome'),
                weight: parseFloat(formData.get('peso')),
                price: parseFloat(formData.get('preco')),
                sku: formData.get('sku'),
                categoria: formData.get('categoria'),
                subcategory: formData.get('subcategoria'),
                penitenciaria: formData.get('penitenciaria'),
                maxUnitsPerClient: parseInt(formData.get('maxUnitsPerClient')),
                thumb: '' // ou: formData.get('thumb')
            };

            try {
                const res = await fetch('/wp-json/clickjumbo/v1/register-product-auth', {
           
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': document.querySelector('input[name="_wpnonce"]').value
                    },
                    body: JSON.stringify(data),
                    credentials: 'same-origin'
                });
     console.log("res ==> ", res);

                const json = await res.json();
                if (json.success) {
                    msgBox.innerHTML = '<div class="notice notice-success"><p>Produto cadastrado com sucesso!</p></div>';
                    form.reset();
                } else {
                    msgBox.innerHTML = '<div class="notice notice-error"><p>' + (json.message || 'Erro ao cadastrar produto.') + '</p></div>';
                }
            } catch (err) {
                msgBox.innerHTML = '<div class="notice notice-error"><p>Erro ao conectar com o servidor.</p></div>';
                console.error(err);
            }
        });
    </script>
    <?php
}
