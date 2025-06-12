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

        <table class="pedido-table wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cliente</th>
                    <th>Penitenciária</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="tabela-pedidos"></tbody>
        </table>
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
    </style>
    <script>
        const wpNonce = '<?php echo wp_create_nonce("wp_rest"); ?>';
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const tbody = document.getElementById('tabela-pedidos');
            tbody.innerHTML = '<tr><td colspan="7">Carregando pedidos...</td></tr>';

            try {
                const res = await fetch('/wp-json/clickjumbo/v1/orders');
                const pedidos = await res.json();

                if (!Array.isArray(pedidos)) {
                    tbody.innerHTML = '<tr><td colspan="7">Erro ao carregar pedidos</td></tr>';
                    return;
                }

                tbody.innerHTML = '';

                pedidos.forEach(pedido => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                <td>${pedido.id}</td>
                <td>${pedido.cliente || '—'}</td>
                <td>${pedido.penitenciaria || '—'}</td>
                <td>R$ ${parseFloat(pedido.total).toFixed(2)}</td>
                <td><span class="badge ${pedido.status}">${pedido.status}</span></td>
                <td>${pedido.data}</td>
                <td>
                    <button onclick="verDetalhes(${pedido.id})">🔍</button>
                    <button onclick="alterarStatus(${pedido.id})">📝</button>
                    <button onclick="deletarPedido(${pedido.id})">🗑️</button>


                </td>
            `;
                    tbody.appendChild(tr);
                });
            } catch (err) {
                console.error(err);
                tbody.innerHTML = '<tr><td colspan="7">Erro ao carregar pedidos</td></tr>';
            }
        });

        function verDetalhes(id) {
            alert(`Ver detalhes do pedido #${id}`);
        }

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
            document.querySelector(`#pedido-${id}`)?.remove(); // se tiver um ID na linha
            // ou recarregar lista:
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

    <?php
}
