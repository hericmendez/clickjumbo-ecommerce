<?php


function clickjumbo_render_nova_penitenciaria_form() {
    $slug = $_GET['slug'] ?? '';
    echo '<div class="wrap">';
   echo '<h1 style="margin-bottom: 20px;" id="form-title">';
echo $slug ? 'Editar Penitenciária' : 'Cadastrar Nova Penitenciária';
echo '</h1>';


    echo '<form id="penitenciaria-form" class="d-flex flex-column " style="max-width: 720px; padding: 20px; background: #fff; border: 1px solid #dee2e6; border-radius: .5rem;">';
    ?>


    <div class="mb-3">
     
        <input name="nome" id="nome" type="text" class="form-control" required>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="cidade" class="form-label">Cidade</label>
            <input name="cidade" id="cidade" type="text" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="estado" class="form-label">Estado</label>
            <input name="estado" id="estado" type="text" class="form-control" required>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label for="cep" class="form-label">CEP</label>
            <input name="cep" id="cep" type="text" class="form-control" required>
        </div>
        <div class="col-md-6 mb-3">
            <label for="numero" class="form-label">Número</label>
            <input name="numero" id="numero" type="text" class="form-control" required>
        </div>
    </div>

    <div class="mb-3">
        <label for="logradouro" class="form-label">Logradouro</label>
        <input name="logradouro" id="logradouro" type="text" class="form-control" required>
    </div>

    <div class="mb-3">
        <label for="complemento" class="form-label">Complemento</label>
        <input name="complemento" id="complemento" type="text" class="form-control">
    </div>

    <div class="mb-3">
        <label for="referencia" class="form-label">Referência</label>
        <input name="referencia" id="referencia" type="text" class="form-control">
    </div>
<input type="hidden" name="slug" id="slug-original" value="">
    <div>
            <button type="submit" class="btn btn-primary">Salvar Penitenciária</button>
    <a href="/wp/wp-admin/admin.php?page=clickjumbo-prisons" class="btn btn-outline-secondary ms-2">Voltar para lista</a>
    </div>


    <div id="mensagem-penitenciaria" class="mt-3"></div>

    </form>

<?php wp_nonce_field('wp_rest', '_wpnonce'); ?>
<script>
const slug = "<?= $slug ?>";

if (slug) {
    fetch(`https://clickjumbo.com.br/wp/wp-json/clickjumbo/v1/prison-details/${slug}`)
        .then(res => res.json())
        .then(data => {
            if (!data?.content) throw new Error("Dados inválidos");
            const p = data.content;

            document.getElementById('form-title').textContent = `Editar penitenciária: ${p.nome}`;
            document.getElementById('nome').value = p.nome || '';
            document.getElementById('cidade').value = p.cidade || '';
            document.getElementById('estado').value = p.estado || '';
            document.getElementById('cep').value = p.cep || '';
            document.getElementById('logradouro').value = p.logradouro || '';
            document.getElementById('numero').value = p.numero || '';
            document.getElementById('complemento').value = p.complemento || '';
            document.getElementById('referencia').value = p.referencia || '';

            // Para uso posterior no envio
            document.getElementById('slug-original').value = slug;
        })
        .catch(err => {
            console.error("Erro ao carregar penitenciária:", err);
            alert("Erro ao carregar penitenciária para edição.");
        });
}
</script>

<script>
document.getElementById('penitenciaria-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const msgBox = document.getElementById('mensagem-penitenciaria');
    msgBox.innerHTML = 'Enviando...';

    const formData = {
        nome: document.getElementById('nome').value.trim(),
        cidade: document.getElementById('cidade').value.trim(),
        estado: document.getElementById('estado').value.trim(),
        cep: document.getElementById('cep').value.trim(),
        numero: document.getElementById('numero').value.trim(),
        logradouro: document.getElementById('logradouro').value.trim(),
        complemento: document.getElementById('complemento').value.trim(),
        referencia: document.getElementById('referencia').value.trim(),
    };

    try {
const slugOriginal = document.getElementById('slug-original').value;
const metodo = slugOriginal ? 'PUT' : 'POST';

const res = await fetch(`/wp/wp-json/clickjumbo/v1/save-prison${slugOriginal ? '/' + slugOriginal : ''}`, {
    method: metodo,
    headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': document.querySelector('[name="_wpnonce"]').value
    },
    body: JSON.stringify({ ...formData, slug: slugOriginal }),
    credentials: 'same-origin'
});


        const json = await res.json();

        if (json.success) {
            msgBox.innerHTML = '<div class="alert alert-success">Penitenciária salva com sucesso!</div>';
            document.getElementById('penitenciaria-form').reset();
        } else {
            msgBox.innerHTML = `<div class="alert alert-danger">${json.message}</div>`;
        }
    } catch (err) {
        console.error(err);
        msgBox.innerHTML = '<div class="alert alert-danger">Erro ao salvar penitenciária.</div>';
    }
});
</script>
<?php
    echo '</div>';
}
