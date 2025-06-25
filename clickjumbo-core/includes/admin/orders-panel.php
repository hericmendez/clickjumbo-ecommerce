<?php
if (!defined('ABSPATH'))
    exit;

function clickjumbo_render_orders_panel()
{
    ?>
    <?php wp_nonce_field('wp_rest'); ?>
    <script>
        window.clickjumbo_data = {
            nonce: "<?php echo wp_create_nonce('wp_rest'); ?>"
        };
    </script>

    <div class="wrap">
        <h1>Painel de Pedidos</h1>

        <input type="text" id="search-input" placeholder="Buscar por cliente ou penitenciária..."
            style="margin-bottom: 10px; width: 100%; padding: 8px;">

        <table class="pedido-table wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th onclick="ordenarPor('id')">ID</th>
                    <th onclick="ordenarPor('cliente')">Cliente</th>
                    <th onclick="ordenarPor('penitenciaria')">Penitenciária</th>
                    <th onclick="ordenarPor('total')">Total</th>
                    <th onclick="ordenarPor('status')">Status</th>
                    <th onclick="ordenarPor('data')">Data</th>

                    <th>Ações</th>
                </tr>
            </thead>

            <tbody id="tabela-pedidos"></tbody>
        </table>
    </div>

    <div id="modal-detalhes"
        style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#fff; border:1px solid #ccc; padding:20px; z-index:1000; max-width: 600px; overflow:auto">
        <button onclick="fecharModal()" style="float:right">Fechar</button>
        <pre id="modal-conteudo"></pre>
    </div>

    <style>
        .badge {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            color: #fff;
        }

        .badge.processing {
            background: green;
        }

        .badge.pending {
            background: orange;
        }

        .badge.cancelled {
            background: red;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown button {
            background: #eee;
            border: 1px solid #ccc;
            padding: 4px 8px;
            cursor: pointer;
            border-radius: 4px;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: #fff;
            min-width: 160px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            padding: 8px 0;
            z-index: 1000;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .dropdown-content a {
            color: #0073aa;
            padding: 8px 16px;
            display: block;
            text-decoration: none;
        }

        .dropdown-content a:hover {
            background-color: #f0f0f0;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }
    </style>

    <script>
        const wpNonce = '<?php echo wp_create_nonce("wp_rest"); ?>';

        function formatarData(dataStr) {
            const d = new Date(dataStr);
            return d.toLocaleString('pt-BR', {
                day: '2-digit', month: '2-digit', year: 'numeric',
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        }

        async function verDetalhes(id) {
            try {
                const res = await fetch(`/wp-json/clickjumbo/v1/orders/${id}`);
                const pedido = await res.json();
                abrirModalDetalhesPedido(pedido);
                console.log("pedido ==> ", pedido);
            } catch (err) {
                console.error(err);
                alert("Erro ao carregar os detalhes do pedido.");
            }
        }


        function fecharModal() {
            document.getElementById('modal-detalhes').style.display = 'none';
        }

        async function baixarPDF(pedido) {
            console.log("pedido ==> ", pedido);
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setFontSize(14);
            doc.text(`Pedido #${pedido.id}`, 10, 10);
            doc.setFontSize(12);
            doc.text(`Cliente: ${pedido.cliente?.nome || '—'}`, 10, 20);
            doc.text(`Email: ${pedido.cliente?.email || '—'}`, 10, 30);
            doc.text(`Penitenciária: ${pedido.penitenciaria?.nome || '—'}`, 10, 40);
            doc.text(`Status: ${pedido.status}`, 10, 50);
            doc.text(`Data: ${new Date(pedido.data).toLocaleString('pt-BR')}`, 10, 60);

            let y = 70;
            doc.text('Produtos:', 10, y);
            y += 10;

            pedido.produtos?.forEach(prod => {
                doc.text(
                    `• ${prod.quantidade}x ${prod.nome} - R$ ${prod.preco_unitario} (Subtotal: R$ ${prod.subtotal})`,
                    10,
                    y
                );
                y += 10;
            });

            y += 5;
            doc.text(`Frete: R$ ${pedido.shipping?.frete_valor || '—'}`, 10, y); y += 10;
            doc.text(`Total: R$ ${pedido.total}`, 10, y); y += 10;
            doc.text(`Método de Pagamento: ${pedido.pagamento?.metodo}`, 10, y); y += 10;
            doc.text(`Status do Pagamento: ${pedido.pagamento?.status}`, 10, y); y += 10;

            doc.save(`pedido_${pedido.id}.pdf`);
        }
        async function baixarPDFPorId(id) {
            try {
                const res = await fetch(`/wp-json/clickjumbo/v1/orders/${id}`);
                const pedido = await res.json();
                baixarPDF(pedido); // chama sua função real
            } catch (err) {
                console.error("Erro ao carregar pedido:", err);
                alert("Erro ao gerar PDF.");
            }
        }


        function baixarComprovante(pedidoId) {
            alert(`Baixando comprovante mockado do pedido #${pedidoId}`);
        }

        function gerarAcoesDropdown(pedido) {
            return `
    <div class="dropdown">
      <button title="Ações do pedido">⋮</button>
      <div class="dropdown-content">
        <a href="#" onclick='verDetalhes(${pedido.id})'>Ver detalhes</a>
        <a href="#" onclick='alterarStatus(${pedido.id})'>Alterar status</a>
        <a href="#" onclick='baixarPDFPorId(${JSON.stringify(pedido.id)})'>Gerar PDF</a>
        <a href="#" onclick='baixarComprovante(${pedido.id})'>Comprovante</a>
        <a href="#" onclick='deletarPedido(${pedido.id})'>Excluir</a>
      </div>
    </div>`;
        }

        let pedidos = [];
        let ordemAtual = { coluna: 'id', direcao: 'asc' };


        function renderizarPedidos(lista) {
            const tbody = document.getElementById('tabela-pedidos');
            tbody.innerHTML = '';

            lista.forEach(pedido => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                        <td>${pedido.id}</td>
                        <td>${pedido.cliente || '—'}</td>
                        <td>${pedido.penitenciaria?.nome || '—'}</td>
                        <td>R$ ${pedido.total}</td>
                        <td><span class="badge ${pedido.status}">${pedido.status}</span></td>
                        <td>${formatarData(pedido.data)}</td>
                        <td>${gerarAcoesDropdown(pedido)}</td>
                    `;
                tbody.appendChild(tr);
            });
        }
        function getValue(obj, path) {
            return path.split('.').reduce((acc, part) => acc?.[part] ?? '', obj);
        }

        function atualizarSetas(colunaAtual) {
            const ths = document.querySelectorAll("th");
            ths.forEach(th => {
                const id = th.getAttribute("onclick")?.match(/ordenarPor\('(.+)'\)/)?.[1];
                if (!id) return;

                const textoBase = th.textContent.replace(/ 🔼| 🔽/, '');
                if (id === colunaAtual) {
                    const seta = ordemAtual.direcao === 'asc' ? ' 🔼' : ' 🔽';
                    th.textContent = textoBase + seta;
                } else {
                    th.textContent = textoBase;
                }
            });
        }


        function ordenarPor(coluna) {
            if (ordemAtual.coluna === coluna) {
                ordemAtual.direcao = ordemAtual.direcao === 'asc' ? 'desc' : 'asc';
            } else {
                ordemAtual.coluna = coluna;
                ordemAtual.direcao = 'asc';
            }

            const listaOrdenada = [...pedidos].sort((a, b) => {
                let valA = a[coluna];
                let valB = b[coluna];

                // Corrige campos aninhados
                if (coluna === 'penitenciaria') {
                    valA = a.penitenciaria?.nome || '';
                    valB = b.penitenciaria?.nome || '';
                }

                if (coluna === 'data') {
                    valA = new Date(a.data);
                    valB = new Date(b.data);
                }

                if (coluna === 'total') {
                    valA = parseFloat(a.total);
                    valB = parseFloat(b.total);
                }

                if (typeof valA === 'string') {
                    valA = valA.toLowerCase();
                    valB = valB.toLowerCase();
                }

                return ordemAtual.direcao === 'asc'
                    ? valA > valB ? 1 : -1
                    : valA < valB ? 1 : -1;
            });

            atualizarSetas(coluna); // <- adiciona ou remove 🔼🔽 no header
            renderizarPedidos(listaOrdenada); // <- atualiza visualmente
        }

        document.addEventListener('DOMContentLoaded', async () => {
            const tbody = document.getElementById('tabela-pedidos');
            const searchInput = document.getElementById('search-input');


            async function carregarPedidos() {
                tbody.innerHTML = '<tr><td colspan="7">Carregando pedidos...</td></tr>';
                try {
                    const res = await fetch('/wp-json/clickjumbo/v1/orders');
                    pedidos = await res.json();
                    renderizarPedidos(pedidos);
                } catch (err) {
                    console.error(err);
                    tbody.innerHTML = '<tr><td colspan="7">Erro ao carregar pedidos</td></tr>';
                }
            }

            function renderizarPedidos(lista) {
                const tbody = document.getElementById('tabela-pedidos');
                tbody.innerHTML = '';

                lista.forEach(pedido => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td>${pedido.id}</td>
                        <td>${pedido.cliente || '—'}</td>
                        <td>${pedido.penitenciaria?.nome || '—'}</td>
                        <td>R$ ${pedido.total}</td>
                        <td><span class="badge ${pedido.status}">${pedido.status}</span></td>
                        <td>${formatarData(pedido.data)}</td>
                        <td>${gerarAcoesDropdown(pedido)}</td>
                    `;
                    tbody.appendChild(tr);
                });
            }


            searchInput.addEventListener('input', () => {
                const termo = searchInput.value.toLowerCase();
                const filtrados = pedidos.filter(p =>
                    (p.cliente && p.cliente.toLowerCase().includes(termo)) ||
                    (p.penitenciaria?.nome && p.penitenciaria.nome.toLowerCase().includes(termo))
                );
                renderizarPedidos(filtrados);
            });

            carregarPedidos();
        });

        function alterarStatus(id) {
            const status = prompt("Digite o novo status:\n- pending\n- processing\n- cancelled");
            if (!status || !['pending', 'processing', 'cancelled'].includes(status)) {
                alert("Status inválido ou cancelado.");
                return;
            }

            fetch('/wp-json/clickjumbo/v1/cancel-order', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json', 'X-WP-Nonce': wpNonce,
                },
                body: JSON.stringify({ id, status })
            })
                .then(res => res.json())
                .then(data => {
                    alert(data.message || 'Status atualizado.');
                    location.reload();
                })
                .catch(err => {
                    console.error(err);
                    alert('Erro ao alterar status.');
                });
        }

        function deletarPedido(id) {
            if (!confirm("Tem certeza que deseja excluir este pedido?")) return;

            fetch(`/wp-json/clickjumbo/v1/orders/${id}`, {
                method: 'DELETE',
                credentials: 'include',
                headers: {
                    'X-WP-Nonce': window.clickjumbo_data.nonce
                }
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert("Pedido excluído com sucesso.");
                        location.reload();
                    } else {
                        alert("Erro: " + (data.message || 'Erro desconhecido.'));
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert("Erro de conexão.");
                });
        }

    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <?php
}
