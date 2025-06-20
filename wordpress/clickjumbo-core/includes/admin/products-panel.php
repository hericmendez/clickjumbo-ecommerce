<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_render_products_panel() {
    ?>
    <div class="wrap">
        <h1>Lista de Produtos</h1>

        <p>
            <!-- Botão de Importação -->
            <a href="<?php echo admin_url('admin.php?page=clickjumbo-import-csv'); ?>" class="button button-primary">Importar Produtos</a>

            <!-- Botão de Exportação -->
            <a href="<?php echo admin_url('admin.php?page=clickjumbo-export-csv'); ?>" class="button button-secondary">Exportar Produtos</a>
        </p>
    </div>
    <?php
}
