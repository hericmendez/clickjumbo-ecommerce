<?php
function clickjumbo_render_novo_produto_form()
{
    $penitenciarias = get_terms(['taxonomy' => 'penitenciaria', 'hide_empty' => false]);
    $categorias = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => false, 'parent' => 0]);
    $id = isset($_GET['produto_id']) ? intval($_GET['produto_id']) : 0;
    $categoriaSelecionada = $_GET['categoria'] ?? '';

    echo '<div class="wrap">';
echo '<h1 style="margin-bottom: 20px;" id="form-title">Cadastrar novo produto</h1>';

    echo '<form id="produto-form">';
?>
<div class="mb-3 " style="max-width: 800px">
<form id="produto-form" class="container" style="max-width: 720px; padding: 20px; background: #fff; border: 1px solid #dee2e6; border-radius: .5rem;">
    <div class="mb-3">
        <label for="nome" class="form-label">Nome do Produto</label>
        <input name="nome" id="nome" type="text" class="form-control" required>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="preco" class="form-label">Preço (R$)</label>
            <input name="preco" id="preco" type="number" step="0.01" min="0.01" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="peso" class="form-label">Peso (kg)</label>
            <input name="peso" id="peso" type="number" step="0.01" min="0.01" class="form-control" required>
        </div>
    </div>

    <div class="form-check form-switchs mb-3 d-flex flex-row align-items-center">
        <input class="form-check-input" type="checkbox" id="produto_global" name="produto_global" value="1">
        <label class="form-check-label ms-2" for="produto_global">Produto Padrão - Disponível para todas as penitenciárias</label>
    </div>
<div class="form-check form-switchs mb-3 d-flex flex-row align-items-center">
    <input class="form-check-input" type="checkbox" id="produto_premium" name="premium" value="1">
    <label class="form-check-label ms-2" for="produto_premium">Produto Premium</label>
</div>

    <div class="mb-3" id="linha-penitenciarias">
        <label for="cj_prisons" class="form-label">Penitenciárias</label>
        <select name="cj_prisons[]" id="cj_prisons" class="form-select" multiple size="6">
            <?php foreach ($penitenciarias as $p): ?>
                <option value="<?= esc_attr($p->slug) ?>">
                    <?= esc_html($p->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <div class="form-text">Segure Ctrl (Windows) ou Command (Mac) para selecionar várias.</div>
    </div>

    <div class="mb-3">
        <label for="categoria" class="form-label">Categoria</label>
        <select name="categoria" id="categoria" class="form-select">
            <option value="">Selecione uma categoria...</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= esc_attr($cat->term_id) ?>" <?= $categoriaSelecionada === $cat->name ? 'selected' : '' ?>>
                    <?= esc_html($cat->name) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="button" class="btn btn-secondary btn-sm mt-2" id="btn-nova-categoria">+ Nova</button>
        <div id="campo-nova-categoria" class="mt-2" style="display:none;">
            <div class="input-group">
                <input type="text" id="nova_categoria" class="form-control" placeholder="Nome da nova categoria">
                <button type="button" class="btn btn-outline-primary" id="btn-confirmar-categoria">Cadastrar</button>
            </div>
            <small id="status-categoria" class="text-muted ms-2"></small>
        </div>
    </div>

    <div class="mb-3">
        <label for="subcategoria" class="form-label">Subcategoria (opcional)</label>
        <input type="text" name="subcategoria" id="subcategoria" class="form-control">
    </div>

    <div class="mb-3">
        <label for="maximo_por_cliente" class="form-label">Limite por cliente</label>
        <input name="maximo_por_cliente" id="maximo_por_cliente" type="number" min="1" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="sku" class="form-label">SKU (opcional)</label>
        <input name="sku" id="sku" type="text" class="form-control">
    </div>

    <div class="mb-3">
        <label for="thumb" class="form-label">Imagem do Produto</label>
        <input type="file" name="thumb" id="thumb" class="form-control" accept="image/*">
        <div id="preview-thumb" class="mt-2"></div>
    </div>

    <button type="submit" class="btn btn-primary">Salvar Produto</button>
    <a href="/wp/wp-admin/admin.php?page=clickjumbo-prisons" class="btn btn-outline-secondary ms-2">Voltar para Produtos</a>

    <div id="mensagem-produto" class="mt-3"></div>
    <div id="debug-formdata" style="display:none; position:fixed; bottom:0; left:0; right:0; background:#111; color:#0f0; padding:10px; font-size:12px; max-height:200px; overflow:auto; z-index:9999; border-top:2px solid #0f0;">
  <strong>DEBUG FORM DATA:</strong>
  <pre id="debug-content" style="white-space:pre-wrap; word-break:break-word;"></pre>
</div>

</form>

</div>



</form>

<div id="mensagem-produto" style="margin-top:15px;"></div>
</div>

<?php wp_nonce_field('wp_rest', '_wpnonce'); ?>

<script>
const produtoId = <?= $id ?>;

function atualizarCampoPenitenciarias() {
    const check = document.getElementById('produto_global');
    const linhaPenit = document.getElementById('linha-penitenciarias');
    const select = document.getElementById('cj_prisons');

    if (check.checked) {
        linhaPenit.style.opacity = 0.5;
        select.disabled = true;
        Array.from(select.options).forEach(opt => opt.selected = false);
    } else {
        linhaPenit.style.opacity = 1;
        select.disabled = false;
    }
}

document.getElementById('produto_global').addEventListener('change', atualizarCampoPenitenciarias);
document.addEventListener('DOMContentLoaded', atualizarCampoPenitenciarias);

if (produtoId > 0) {
    fetch(`https://clickjumbo.com.br/wp/wp-json/clickjumbo/v1/product-details/${produtoId}`)
        .then(res => res.json())
        .then(data => {
            console.log("product details:", data)
            const c = data.content;
                        document.getElementById('form-title').textContent = `Editar produto: ${c.nome}`;

            document.getElementById('nome').value = c.nome;
            document.getElementById('preco').value = c.preco || '';
            document.getElementById('peso').value = c.peso || '';
            document.getElementById('subcategoria').value = c.subcategoria || '';
            document.getElementById('maximo_por_cliente').value = c.maximo_por_cliente || '';
            document.getElementById('sku').value = c.sku || '';
            // Encontra o option que tem esse texto e marca como selecionado
const categoriaSelect = document.getElementById('categoria');
const categoriaNome = c.categoria || '';
Array.from(categoriaSelect.options).forEach(opt => {
  if (opt.textContent.trim() === categoriaNome) {
    opt.selected = true;
  }
});
document.getElementById("produto_premium").checked = c.premium === true || c.premium === "yes";

if (Array.isArray(c.penitenciarias)) {
  const isGlobal = c.penitenciarias.some(p => p.slug === 'todas');
  document.getElementById('produto_global').checked = isGlobal;

  if (!isGlobal) {
    const select = document.getElementById('cj_prisons');
    Array.from(select.options).forEach(option => {
      if (c.penitenciarias.some(p => p.slug === option.value)) {
        option.selected = true;
      }
    });
  }

  atualizarCampoPenitenciarias();
}

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
        const res = await fetch('https://clickjumbo.com.br/wp/wp-json/clickjumbo/v1/register-category', {
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
// Debug visual do formData
function mostrarDebug(formData) {
  const debugBox = document.getElementById("debug-formdata");
  const content = document.getElementById("debug-content");
  let texto = "";

  formData.forEach((value, key) => {
    if (key === "penitenciaria") {
      try {
        const json = JSON.parse(value);
        texto += `${key}: ` + JSON.stringify(json, null, 2) + "\n\n";
      } catch {
        texto += `${key}: ${value}\n\n`;
      }
    } else if (value instanceof File) {
      texto += `${key}: [File: ${value.name}]\n\n`;
    } else {
      texto += `${key}: ${value}\n\n`;
    }
  });

  content.textContent = texto;
  debugBox.style.display = "block";
}

document.getElementById('produto-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msgBox = document.getElementById('mensagem-produto');
    msgBox.innerHTML = 'Enviando...';

  const form = e.target;
  
const isPadrao = document.getElementById('produto_global').checked;
let penitenciarias = [];

if (isPadrao) {
  penitenciarias = [{ slug: 'todas', label: 'Todas as Penitenciárias' }];
} else {
  const select = document.getElementById('cj_prisons');
  penitenciarias = Array.from(select.selectedOptions).map(opt => ({
    slug: opt.value,
    label: opt.textContent.trim()
  }));
}
  

const formData = new FormData();

// Adiciona campos manualmente
formData.append("nome", document.getElementById("nome").value);
formData.append("preco", document.getElementById("preco").value);
formData.append("peso", document.getElementById("peso").value);
formData.append("subcategoria", document.getElementById("subcategoria").value);
formData.append("maximo_por_cliente", document.getElementById("maximo_por_cliente").value);
formData.append("sku", document.getElementById("sku").value);
formData.append("categoria", document.getElementById("categoria").value);
formData.append("produto_id", produtoId || 0);

// Imagem
const thumbFile = document.getElementById("thumb").files[0];
if (thumbFile) formData.append("thumb", thumbFile);

// Flags
const isGlobal = document.getElementById("produto_global").checked;
const isPremium = document.getElementById("produto_premium").checked;
formData.append("premium", isPremium ? "true" : "false");
formData.append("padrao", isGlobal ? "true" : "false");


// Penitenciárias (em JSON)
const select = document.getElementById("cj_prisons");
if (isGlobal) {
  formData.append("penitenciaria", JSON.stringify([{ slug: "todas", label: "Todas" }]));
} else {
  const selected = Array.from(select.selectedOptions);
  const prisArray = selected.map(opt => ({
    slug: opt.value,
    label: opt.textContent.trim()
  }));

  if (prisArray.length === 0) {
    msgBox.innerHTML = '<div class="notice notice-error"><p>Selecione amenos uma penitenciária.</p></div>';
    return;
  }

prisArray.forEach((item, index) => {
  formData.append(`penitenciaria[${index}][slug]`, item.slug);
  formData.append(`penitenciaria[${index}][label]`, item.label);
});

}
const categoriaSelect = document.getElementById('categoria');
const categoriaSelecionada = categoriaSelect.options[categoriaSelect.selectedIndex]?.textContent?.trim();
formData.set('categoria', categoriaSelecionada || '');

mostrarDebug(formData);


    try {
        const res = await fetch('https://clickjumbo.com.br/wp/wp-json/clickjumbo/v1/save-product', {
            method: 'POST',
            headers: {
                'X-WP-Nonce': document.querySelector('[name="_wpnonce"]').value
            },
            body: formData,
            credentials: 'same-origin'
        });
        const text = await res.text();
console.log("RAW response:\n", text);

let jsonRes;
try {
  jsonRes = JSON.parse(text);
} catch (err) {
  msgBox.innerHTML = `<pre class="alert alert-danger">Erro ao analisar resposta JSON:\n\n${text}</pre>`;
  throw err;
}
    



            msgBox.innerHTML = '<div class="notice notice-success"><p>Produto salvo com sucesso!</p></div>';
            form.reset();
            document.getElementById('preview-thumb').innerHTML = '';
            atualizarCampoPenitenciarias(); // limpa e bloqueia o select novamente
            if(produtoId>0){
                window.location.href = '/wp/wp-admin/admin.php?page=clickjumbo-products'
            }
       
    } catch (err) {
        msgBox.innerHTML = `<div class="notice notice-error"><p>Ocorreu um erro ao salvar o produto.</p><details>${err}</details></div>`;
        console.error(err);
    }
});
</script>
<?php
}
?>
