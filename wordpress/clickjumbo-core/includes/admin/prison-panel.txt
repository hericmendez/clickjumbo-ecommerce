<?php
// includes/admin/prison-panel.php



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
        <table style="width: 100%;" class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Preço</th>
                    <th>Categoria</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="produtos-da-penitenciaria">
                <tr>
                    <td colspan="3">Selecione uma penitenciária para ver os produtos.</td>
                </tr>
            </tbody>
        </table>
        <div id="modal-detalhes"
            style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%);
 background:#fff; padding:20px; border:1px solid #ccc; border-radius:6px; z-index:9999; max-width:500px; box-shadow:0 0 10px rgba(0,0,0,0.2);">
            <h2>Detalhes do Produto</h2>
            <div id="modal-conteudo"></div>
            <button onclick="fecharModal()" class="button">Fechar</button>
        </div>
        <div id="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; 
 background:rgba(0,0,0,0.4); z-index:9998;" onclick="fecharModal()"></div>

    </div>

<style>
    /* DROPDOWN */
    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background: #fff;
        min-width: 140px;
        border: 1px solid #ccc;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 10;
    }

    .dropdown-content a {
        display: block;
        padding: 8px 12px;
        text-decoration: none;
        color: #333;
        font-size: 14px;
        white-space: nowrap;
    }

    .dropdown-content a:hover {
        background: #f0f0f0;
    }

    .dropdown:hover .dropdown-content {
        display: block;
    }

    /* TABELA DE PRODUTOS */
    #painel-produtos {
        overflow-x: auto;
        margin-top: 40px;
    }

    #painel-produtos table {
        width: 98% !important;
      
        table-layout: fixed;
    }

    #painel-produtos th,
    #painel-produtos td {
        word-break: break-word;
        padding: 8px;
        vertical-align: middle;
    }

    #painel-produtos td:nth-child(4),
    #painel-produtos th:nth-child(4) {
        width: 100px;
        text-align: center;
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
                                          <a href="#" onclick="verProdutos('${p.slug}')">📦 Ver Produtos</a>
                    <a href="/wp-admin/admin.php?page=clickjumbo-novo-produto&penitenciaria=${p.slug}">➕ Novo Produto</a>
                    <a href="#" onclick="editPrison('${p.slug}')">✏️ Editar </a>
                    <a href="#" onclick="deletePrison('${p.slug}')">🗑️ Excluir </a>
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
        // 👉 Buscar nome da penitenciária
        const resPrison = await fetch(`/wp-json/clickjumbo/v1/prison-details/${slug}`);
        const prisonData = await resPrison.json();
        const nomePenitenciaria = prisonData.content?.nome || slug;

        // 👉 Buscar produtos
        const res = await fetch(`/wp-json/clickjumbo/v1/product-list/prison?slug=${slug}`, {
            credentials: 'include'
        });
        if (!res.ok) throw new Error('Erro ao buscar produtos');

        const { content } = await res.json();
        titulo.textContent = `Produtos da penitenciária ${nomePenitenciaria} (${content.length})`;

        if (!content.length) {
            tbody.innerHTML = `<tr><td colspan="3">Nenhum produto encontrado.</td></tr>`;
            return;
        }

        tbody.innerHTML = content.map(prod => `
            <tr id="produto-${prod.id}">
                <td>${prod.name}</td>
                <td>${prod.price || '—'}</td>
                <td>${prod.category || '—'}</td>
                <td>
                    <div class="dropdown">
                        <button class="button">&#x22EE;</button>
                        <div class="dropdown-content">
                            <a href="#" onclick="verDetalhesProduto('${prod.id}')">🔍 Ver Detalhes</a>
                            <a href="#" onclick="editProduct('${prod.id}', '${slug}')">✏️ Editar Produto</a>
                            <a href="#" onclick="deleteProduct('${prod.id}')">🗑️ Excluir Produto</a>
                        </div>
                    </div>
                </td>
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
async function verDetalhesProduto(id) {
    const modal = document.getElementById("modal-detalhes");
    const overlay = document.getElementById("modal-overlay");
    const conteudo = document.getElementById("modal-conteudo");

    conteudo.innerHTML = "Carregando...";

    try {
        const res = await fetch(`/wp-json/clickjumbo/v1/product-details/${id}`, {
     
            credentials: 'include',
            headers: { 'X-WP-Nonce': clickjumbo_data.nonce }
        });

        if (!res.ok) throw new Error("Erro ao carregar dados");

        const { content } = await res.json();

        conteudo.innerHTML = `
            <p><strong>Nome:</strong> ${content.name}</p>
            <p><strong>Preço:</strong> R$ ${content.price}</p>
            <p><strong>Peso:</strong> ${content.weight} kg</p>
            <p><strong>SKU:</strong> ${content.sku || '—'}</p>
            <p><strong>Categoria:</strong> ${content.categoria || '—'}</p>
            <p><strong>Subcategoria:</strong> ${content.subcategoria || '—'}</p>
            <p><strong>Limite por cliente:</strong> ${content.maxUnitsPerClient || '—'}</p>
        `;

        modal.style.display = 'block';
        overlay.style.display = 'block';

    } catch (err) {
        conteudo.innerHTML = `<p style="color:red;">Erro ao carregar os dados.</p>`;
    }
}

function fecharModal() {
    document.getElementById("modal-detalhes").style.display = 'none';
    document.getElementById("modal-overlay").style.display = 'none';
}


        function editProduct(id, metalSlug = '') {
            const url = new URL(window.location.origin + '/wp-admin/admin.php');
            url.searchParams.set('page', 'clickjumbo-novo-produto');
            url.searchParams.set('editar_produto', id);
            if (metalSlug) {
                url.searchParams.set('penitenciaria', metalSlug);
            }

            location.href = url.toString();
        }



        function deleteProduct(id) {
            if (confirm("Tem certeza que deseja excluir este produto?")) {
                fetch(`/wp-json/clickjumbo/v1/delete-product/${id}`, {
                    method: 'DELETE',
                    credentials: 'include',
                    headers: { 'X-WP-Nonce': clickjumbo_data.nonce }
                }).then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            alert("Produto excluído com sucesso.");
                            const linha = document.getElementById(`produto-${id}`);
                            if (linha) linha.remove();
                        } else {
                            alert("Erro ao excluir: " + (data.message || 'Erro desconhecido.'));
                        }
                    }).catch(err => {
                        console.error(err);
                        alert("Erro de comunicação com o servidor.");
                    });
            }
        }



    </script>


    <?php

}
