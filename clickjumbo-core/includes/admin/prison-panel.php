<?php
// includes/admin/prison-panel.php

function clickjumbo_register_admin_page()
{
    add_menu_page(
        'Penitenciárias',
        'Penitenciárias',
        'manage_options',
        'clickjumbo-prisons',
        'clickjumbo_render_prison_panel',
        'dashicons-building',

    );
    add_submenu_page(
        'clickjumbo-prisons',                // pai
        'Novo Produto',                      // título da página
        'Novo Produto',                      // rótulo do menu
        'manage_options',                   // permissão
        'clickjumbo-novo-produto',          // slug da URL
        'clickjumbo_render_novo_produto_form' // callback
    );

}
add_action('admin_menu', 'clickjumbo_register_admin_page');
require_once plugin_dir_path(__FILE__) . 'product-create-form.php';


function clickjumbo_render_prison_panel()
{
    ?>
    <div class="wrap">
        <h1>Gerenciar Penitenciárias</h1>
        <p>
            <a href="#" id="btn-ver-lista" class="button button-secondary">Ver todas</a>
            <a href="#" id="btn-cadastrar-nova" class="button button-primary">Cadastrar nova</a>
        </p>

        <div id="painel-lista" style="margin-top: 20px;">
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
                    <tr>
                        <td colspan="5">Carregando...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="painel-formulario" style="margin-top: 20px; display: none;">
            <h2 id="form-title">Cadastrar nova penitenciária</h2>
            <form id="form-cadastro-prison">
                <table class="form-table">
                    <tr>
                        <th><label for="nome">Nome</label></th>
                        <td><input type="text" id="nome" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="cidade">Cidade</label></th>
                        <td><input type="text" id="cidade" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="estado">Estado</label></th>
                        <td><input type="text" id="estado" class="regular-text" maxlength="2" required></td>
                    </tr>
                    <tr>
                        <th><label for="cep">CEP</label></th>
                        <td><input type="text" id="cep" class="regular-text" maxlength="8" required></td>
                    </tr>
                </table>
                <p><button type="submit" class="button button-primary" id="submit-button">Cadastrar</button></p>
                <div id="mensagem"></div>
            </form>
        </div>
    </div>
    <div id="painel-produtos" style="margin-top: 40px; display: none;">
        <h2 id="titulo-produtos">Produtos da penitenciária</h2>
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Preço</th>
                    <th>SKU</th>
                </tr>
            </thead>
            <tbody id="produtos-da-penitenciaria">
                <tr>
                    <td colspan="3">Selecione uma penitenciária para ver os produtos.</td>
                </tr>
            </tbody>
        </table>
    </div>


    <style>
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background: #fff;
            min-width: 140px;
            border: 1px solid #ccc;
            z-index: 10;
        }

        .dropdown-content a {
            display: block;
            padding: 8px 12px;
            text-decoration: none;
            color: #333;
        }

        .dropdown-content a:hover {
            background: #f0f0f0;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }
    </style>
    <?php wp_nonce_field('wp_rest'); ?>
    <script>
        window.clickjumbo_data = window.clickjumbo_data || {};
        window.clickjumbo_data.nonce = "<?php echo wp_create_nonce('wp_rest'); ?>";
    </script>


    <script>
        const $ = document.querySelector.bind(document);
        const painelLista = $("#painel-lista");
        const painelForm = $("#painel-formulario");
        const btnVerLista = $("#btn-ver-lista");
        const btnCadastrar = $("#btn-cadastrar-nova");
        const form = $("#form-cadastro-prison");
        const msg = $("#mensagem");
        const formTitle = $("#form-title");
        const submitButton = $("#submit-button");

        let modoEdicao = null;

        btnVerLista.onclick = e => (e.preventDefault(), mostrarLista());
        btnCadastrar.onclick = e => (e.preventDefault(), iniciarCadastro());
        document.addEventListener("DOMContentLoaded", mostrarLista);

        function iniciarCadastro() {
            modoEdicao = null;
            formTitle.textContent = "Cadastrar nova penitenciária";
            submitButton.textContent = "Cadastrar";
            form.reset();
            msg.innerHTML = "";
            painelLista.style.display = 'none';
            painelForm.style.display = 'block';
        }

        function mostrarLista() {
            painelLista.style.display = 'block';
            painelForm.style.display = 'none';
            carregarTabela();
        }

        async function carregarTabela() {
            const body = $("#prison-table-body");
            body.innerHTML = `<tr><td colspan="5">Carregando...</td></tr>`;
            try {
                const res = await fetch('/wp-json/clickjumbo/v1/prison-list-full');
                const { content } = await res.json();
                body.innerHTML = content.map(p => `
            <tr>
                <td>${p.nome}</td>
                <td>${p.cidade}</td>
                <td>${p.estado}</td>
                <td>${p.cep}</td>
                <td>
                    <div class="dropdown">
                        <button class="button">&#x22EE;</button>
                        <div class="dropdown-content">
                            <a href="#" onclick="verProdutos('${p.slug}')">Ver Produtos</a>
                            <a href="/wp-admin/admin.php?page=clickjumbo-novo-produto&penitenciaria=${p.slug}">Novo Produto</a>
                            <a href="#" onclick="editPrison('${p.slug}')">Editar</a>
                            <a href="#" onclick="deletePrison('${p.slug}')">Excluir</a>
                        </div>
                    </div>
                </td>
            </tr>
        `).join("");
            } catch (e) {
                body.innerHTML = `<tr><td colspan="5">Erro ao carregar penitenciárias.</td></tr>`;
            }
        }

        async function verProdutos(slug) {
            const painel = document.getElementById('painel-produtos');
            const titulo = document.getElementById('titulo-produtos');
            const tbody = document.getElementById('produtos-da-penitenciaria');

            painel.style.display = 'block';
            titulo.textContent = 'Produtos da penitenciária (carregando...)';
            tbody.innerHTML = `<tr><td colspan="3">Carregando...</td></tr>`;

            try {
                const res = await fetch(`/wp-json/clickjumbo/v1/product-list/prison?slug=${slug}`, {
                    credentials: 'include' // <-- isso é crucial
                });
                if (!res.ok) {
                    throw new Error('Erro ao buscar produtos');
                }
                const { content } = await res.json();

                titulo.textContent = `Produtos da penitenciária (${content.length})`;

                if (!content.length) {
                    tbody.innerHTML = `<tr><td colspan="3">Nenhum produto encontrado.</td></tr>`;
                    return;
                }

                tbody.innerHTML = content.map(prod => `
            <tr>
                <td>${prod.name}</td>
                <td>${prod.price || '—'}</td>
                <td>${prod.sku || '—'}</td>
            </tr>
        `).join('');
            } catch (err) {
                titulo.textContent = 'Erro ao carregar produtos.';
                tbody.innerHTML = `<tr><td colspan="3">Falha na requisição.</td></tr>`;
            }
        }


        function cadastrarProduto(slug) {
            location.href = `/wp-admin/post-new.php?post_type=product&penitenciaria=${slug}`;
        }

        async function editPrison(slug) {
            const res = await fetch(`/wp-json/clickjumbo/v1/prison-details/${slug}`, {
                credentials: 'include',
                headers: {
                    'X-WP-Nonce': clickjumbo_data.nonce
                }
            });

            if (!res.ok) {
                const error = await res.json();
                alert("Erro: " + (error.message || "Não foi possível carregar os dados"));
                return;
            }

            const { content } = await res.json();

            $("#nome").value = content.nome;
            $("#cidade").value = content.cidade;
            $("#estado").value = content.estado;
            $("#cep").value = content.cep;
            modoEdicao = slug;
            formTitle.textContent = `Editando: ${content.nome}`;
            submitButton.textContent = "Salvar";
            painelLista.style.display = 'none';
            painelForm.style.display = 'block';
        }

        async function deletePrison(slug) {
            if (!confirm("Tem certeza que deseja excluir esta penitenciária?")) return;
            await fetch(`/wp-json/clickjumbo/v1/delete-prison/${slug}`, {
                method: 'DELETE', credentials: 'include', headers: {
                    'X-WP-Nonce': clickjumbo_data.nonce
                }
            });
            msg.innerHTML = `<p style="color:green;">Penitenciária excluída com sucesso!</p>`;
            modoEdicao = null;
            painelForm.style.display = 'none';
            painelLista.style.display = 'block';
            carregarTabela();
        }

        form.onsubmit = async e => {
            e.preventDefault();
            const dados = {
                nome: $("#nome").value.trim(),
                cidade: $("#cidade").value.trim(),
                estado: $("#estado").value.trim(),
                cep: $("#cep").value.trim()
            };
            msg.innerHTML = 'Enviando...';
            const url = modoEdicao
                ? `/wp-json/clickjumbo/v1/update-prison/${modoEdicao}`
                : `/wp-json/clickjumbo/v1/register-prison`;
            const method = modoEdicao ? 'PUT' : 'POST';
            try {
                const res = await fetch(url, {
       
                    method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': clickjumbo_data.nonce
                    },
                    body: JSON.stringify(dados),
                    credentials: 'include'
                });
         console.log("res ==> ", res);

                const data = await res.json();
                if (data.success) {
                    msg.innerHTML = `<p style="color:green;">${data.message || 'Sucesso!'}</p>`;
                    form.reset();
                    mostrarLista();
                } else {
                    msg.innerHTML = `<p style="color:red;">${data.message || 'Erro ao salvar.'}</p>`;
                }
            } catch (err) {
                msg.innerHTML = `<p style="color:red;">Erro inesperado.</p>`;
            }
        };


    </script>


    <?php

}
