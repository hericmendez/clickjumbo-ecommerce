<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_render_products_panel() {
    if (!current_user_can('manage_options')) {
        echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
        return;
    }
    ?>

    <div class="wrap">
        <h1 class="mb-4">Gerenciar Produtos</h1>

        <div class="border border-secondary p-4 shadow-sm mb-4" >
            <h2 class="h5">Filtros de busca </h2>
            <form id="filtro-produtos" class="row g-3 align-items-end w-100">
                <div class=" col-md-10">
                    <label class="form-label">Nome do Produto</label>
                    <input type="text" id="filtro-nome" class="form-control" placeholder="Nome do produto...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Penitenciária</label>
                    <select id="filtro-penitenciaria" class="form-select">
                        <option value="">Todas</option> 
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Categoria</label>
                    <select id="filtro-categoria" class="form-select">
                        <option value="">Todas</option>
                    </select>
                </div>
      
                <div>
                    <button type="button" class="btn btn-primary w-25 " onclick="carregarProdutos()">Buscar</button>
                </div>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th onclick="ordenar('nome')">Produto</th>
                        <th>Categoria/Subcategoria</th>
                        <th>Penitenciárias</th>
                        <th onclick="ordenar('preco')">Preço</th>
                        <th>Peso</th>
                    
                        <th>Máx. por cliente</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody id="tabela-produtos">
                    <tr><td colspan="8">Carregando produtos...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    let ordenacao = {
        campo: 'nome',
        direcao: 'asc'
    };

    async function fetchOptions(endpoint, selectId) {
        const res = await fetch(endpoint);
        const json = await res.json();
        const data = json.content || json.categories || json;
        console.log(data);
        const select = document.getElementById(selectId);
        if (!Array.isArray(data)) return;
        
        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.slug || item.term_id;
            opt.textContent = item.name;
            select.appendChild(opt);
        });
    }

    async function carregarFiltros() {
        await fetchOptions('/wp/wp-json/clickjumbo/v1/prison-list-full', 'filtro-penitenciaria');
        await fetchOptions('/wp/wp-json/clickjumbo/v1/get-categories', 'filtro-categoria');

    }
    
    

    function ordenar(campo) {
        if (ordenacao.campo === campo) {
            ordenacao.direcao = ordenacao.direcao === 'asc' ? 'desc' : 'asc';
        } else {
            ordenacao.campo = campo;
            ordenacao.direcao = 'asc';
        }
        carregarProdutos();
    }
    
    function formatPrisons(variable){
        if(!variable){
        
            return "Todas"
        }
        if(Array.isArray(variable)){
            return variable.join(', ');
        }
        return variable;
        
        
    }

    function formatDecimal(n){
        return `${n.toFixed(2).replace('.',',')}`;
    }
    
    async function carregarProdutos() {
        const tbody = document.getElementById('tabela-produtos');
        tbody.innerHTML = '<tr><td colspan="8">Carregando produtos...</td></tr>';

        const params = new URLSearchParams({
            nome: document.getElementById('filtro-nome').value,
            penitenciaria: document.getElementById('filtro-penitenciaria').value,
            categoria: document.getElementById('filtro-categoria').value,

            ordenar_por: ordenacao.campo,
            direcao: ordenacao.direcao
        });

        const res = await fetch('/wp/wp-json/clickjumbo/v1/product-list?' + params.toString());
        const produtos = await res.json();
        const lista = produtos.content || produtos;
        console.log("produtos:", lista);
        tbody.innerHTML =lista.length ? lista.map(produto => `
            <tr>
                <td>${produto.nome}</td>
                <td>${produto.categoria}/${produto.subcategoria || '—'}</td>
                <td>${produto.penitenciaria?.length ? formatPrisons(produto.penitenciaria) : 'Todas'}</td>
                <td>R$ ${formatDecimal(produto.preco)}</td>
                <td>${formatDecimal(produto.peso) } kg</td>
               
                <td>${produto.maximo_por_cliente} ${produto.maximo_por_cliente>1? 'unidades':'unidade'}</td>
                <td>
                    <div class="dropdown">
                      <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Ações
                      </button>
                      <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="editarProduto(${produto.id})">Editar</a></li>
                        <li><a class="dropdown-item text-danger" href="#" onclick="excluirProduto(${produto.id})">Excluir</a></li>
                      </ul>
                    </div>
                </td>
            </tr>
        `).join('') : '<tr><td colspan="8">Nenhum produto encontrado.</td></tr>';
    }

function editarProduto(id) {
  window.location.href = `/wp/wp-admin/admin.php?page=clickjumbo-novo-produto&produto_id=${id}`;
}

    function excluirProduto(id) {
        if (!confirm('Tem certeza que deseja excluir este produto?')) return;
        fetch('/wp/wp-json/wp/v2/product/' + id, {
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': wpApiSettings.nonce
            }
        }).then(res => res.json()).then(data => {
            if (data.deleted) {
                carregarProdutos();
            } else {
                alert('Erro ao excluir produto.');
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        carregarFiltros();
      carregarProdutos();
    });
    </script>

    <?php
}
