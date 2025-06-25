<?php
function clickjumbo_render_novo_produto_form()
{
    $penitenciarias = get_terms(['taxonomy' => 'penitenciaria', 'hide_empty' => false]);
    $categorias = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0]);
    $id = isset($_GET['produto_id']) ? intval($_GET['produto_id']) : 0;
    $categoriaSelecionada = $_GET['categoria'] ?? '';
    $penitenciariaSelecionada = $_GET['penitenciaria'] ?? '';

    echo '<div class="wrap">';
    echo '<h1 style="margin-bottom: 20px;">Cadastrar novo produto</h1>';
    echo '<form id="produto-form">';
?>
<table class="form-table" style="max-width:700px; background:#fff; padding:20px; border:1px solid #ccd0d4; border-radius:6px;">
    <tr>
        <th><label for="nome">Nome do Produto</label></th>
        <td><input name="nome" id="nome" type="text" class="regular-text" required></td>
    </tr>
    <tr>
        <th><label for="preco">Preço (R$)</label></th>
        <td><input name="preco" id="preco" type="number" step="0.01" min="0.01" class="regular-text" required></td>
    </tr>
    <tr>
        <th><label for="peso">Peso (kg)</label></th>
        <td><input name="peso" id="peso" type="number" step="0.01" min="0.01" class="regular-text" required></td>
    </tr>
    <tr>
        <th><label for="penitenciaria">Penitenciária</label></th>
        <td>
            <select name="penitenciaria" id="penitenciaria" required>
                <option value="">Selecione uma penitenciária...</option>
                <?php foreach ($penitenciarias as $p): ?>
                    <option value="<?= esc_attr($p->slug) ?>" <?= $penitenciariaSelecionada === $p->slug ? 'selected' : '' ?>>
                        <?= esc_html($p->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
    </tr>
    <tr>
        <th><label for="categoria">Categoria</label></th>
        <td>
            <select name="categoria" id="categoria">
                <option value="">Selecione uma categoria...</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= esc_attr($cat->term_id) ?>" <?= $categoriaSelecionada === $cat->name ? 'selected' : '' ?>>
                        <?= esc_html($cat->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="button" id="btn-nova-categoria">+ Nova</button>
            <div id="campo-nova-categoria" style="margin-top: 10px; display:none;">
                <input type="text" id="nova_categoria" placeholder="Nome da nova categoria">
                <button type="button" class="button" id="btn-confirmar-categoria">Cadastrar</button>
                <span id="status-categoria" style="margin-left:10px;"></span>
            </div>
        </td>
    </tr>
    <tr>
        <th><label for="subcategoria">Subcategoria (opcional)</label></th>
        <td><input type="text" name="subcategoria" id="subcategoria" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="maxUnitsPerClient">Limite por cliente</label></th>
        <td><input name="maxUnitsPerClient" id="maxUnitsPerClient" type="number" min="1" class="regular-text" required></td>
    </tr>
    <tr>
        <th><label for="sku">SKU (opcional)</label></th>
        <td><input name="sku" id="sku" type="text" class="regular-text"></td>
    </tr>
    <tr>
        <th><label for="thumb">Imagem do Produto</label></th>
        <td>
            <input type="file" name="thumb" id="thumb" accept="image/*">
            <div id="preview-thumb" style="margin-top:10px;"></div>
        </td>
    </tr>
</table>
<div>
<p style="margin-top: 15px;"><button class="button button-primary" type="submit">Salvar Produto</button></p>

</div>

</form>
<a href="/wp-admin/admin.php?page=clickjumbo-prisons" style="margin-top: 15px;"><button class="button button-primary" >Voltar para Produtos</button></a>
<div id="mensagem-produto" style="margin-top:15px;"></div>
</div>

<?php wp_nonce_field('wp_rest', '_wpnonce'); ?>

<script>
const produtoId = <?= $id ?>;

if (produtoId > 0) {
    fetch(`/wp-json/clickjumbo/v1/product-details/${produtoId}`)
        .then(res => res.json())
        .then(data => {
            const c = data.content;
            document.getElementById('nome').value = c.name;
            document.getElementById('preco').value = c.price || '';
            document.getElementById('peso').value = c.weight || '';
            document.getElementById('subcategoria').value = c.subcategoria || '';
            document.getElementById('maxUnitsPerClient').value = c.maxUnitsPerClient || '';
            document.getElementById('sku').value = c.sku || '';
            document.getElementById('categoria').value = c.categoria_id || '';
            document.getElementById('penitenciaria').value = c.penitenciaria || '';
            if (c.thumb) {
                document.getElementById('preview-thumb').innerHTML = `<img src="${c.thumb}" style="max-width:150px;">`;
            }
        })
        .catch(err => console.error("Erro ao carregar produto:", err));
}

document.getElementById('btn-nova-categoria').addEventListener('click', () => {
    const campo = document.getElementById('campo-nova-categoria');
    campo.style.display = campo.style.display === 'none' ? 'block' : 'none';
});

document.getElementById('btn-confirmar-categoria').addEventListener('click', async () => {
    const nome = document.getElementById('nova_categoria').value.trim();
    const status = document.getElementById('status-categoria');
    status.textContent = '...';

    if (!nome) {
        status.textContent = 'Nome obrigatório';
        return;
    }

    try {
        const res = await fetch('/wp-json/clickjumbo/v1/register-category', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': document.querySelector('[name="_wpnonce"]').value
            },
            body: JSON.stringify({ name: nome })
        });

        const json = await res.json();
        if (json.id) {
            const opt = document.createElement('option');
            opt.value = json.id;
            opt.textContent = json.name;
            opt.selected = true;
            document.getElementById('categoria').appendChild(opt);
            status.textContent = 'Categoria adicionada!';
        } else {
            status.textContent = json.message || 'Erro';
        }
    } catch (err) {
        console.error(err);
        status.textContent = 'Erro ao conectar.';
    }
});

document.getElementById('produto-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msgBox = document.getElementById('mensagem-produto');
    msgBox.innerHTML = 'Enviando...';

    const form = e.target;
    const formData = new FormData(form);
    formData.append('produto_id', produtoId || 0);

    try {
        const res = await fetch('/wp-json/clickjumbo/v1/save-product', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': document.querySelector('[name="_wpnonce"]').value
            },
            body: formData,
            credentials: 'same-origin'
        });

        const json = await res.json();

        if (json.success) {
            msgBox.innerHTML = '<div class="notice notice-success"><p>Produto salvo com sucesso!</p></div>';
            form.reset();
            document.getElementById('preview-thumb').innerHTML = '';
        } else {
            msgBox.innerHTML = '<div class="notice notice-error"><p>' + (json.message || 'Erro ao salvar.') + '</p></div>';
        }
    } catch (err) {
        msgBox.innerHTML = '<div class="notice notice-error"><p>Erro de conexão.</p></div>';
        console.error(err);
    }
});
</script>
<?php
}
