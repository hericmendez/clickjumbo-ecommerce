<?php
function clickjumbo_render_import_csv_form()
{
    if (isset($_POST['importar_csv']) && isset($_FILES['csv_file'])) {
        $file = $_FILES['csv_file']['tmp_name'];
        $handle = fopen($file, 'r');

        if ($handle) {
            $header = fgetcsv($handle, 1000, ",");
            $required = ['name', 'category', 'subcategory', 'prison', 'weight', 'price', 'maxUnitsPerClient', 'thumb'];

            // Verifica se os campos obrigatórios estão no CSV
            if (array_diff($required, $header)) {
                echo '<div class="error"><p>CSV inválido. Verifique os campos.</p></div>';
                return;
            }

            $importados = 0;

            while (($row = fgetcsv($handle, 1000, ",")) !== false) {
                $data = array_combine($header, $row);

                // Validações básicas
                if (!$data['name'] || !$data['prison'] || $data['weight'] <= 0 || $data['price'] <= 0) continue;

                // Cria ou atualiza o produto
                $post_id = wp_insert_post([
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'post_title' => sanitize_text_field($data['name']),
                ]);

                if (is_wp_error($post_id)) continue;

                update_post_meta($post_id, '_price', floatval($data['price']));
                update_post_meta($post_id, '_regular_price', floatval($data['price']));
                update_post_meta($post_id, '_weight', floatval($data['weight']));
                update_post_meta($post_id, 'maxUnitsPerClient', intval($data['maxUnitsPerClient']));
                update_post_meta($post_id, 'prison', sanitize_text_field($data['prison']));
                update_post_meta($post_id, '_sku', strtoupper(substr(sanitize_title($data['name']), 0, 5)) . '-' . strtoupper(wp_generate_password(4, false)));
                update_post_meta($post_id, 'thumb', $data['thumb']);

                // Termos
                wp_set_object_terms($post_id, [$data['prison']], 'penitenciaria');
                wp_set_object_terms($post_id, [$data['category']], 'product_cat');

                if ($data['subcategory']) {
                    $subcat_term = term_exists($data['subcategory'], 'product_cat');
                    if (!$subcat_term) {
                        $subcat_term = wp_insert_term($data['subcategory'], 'product_cat', ['parent' => intval($data['category'])]);
                    }
                    if (!is_wp_error($subcat_term)) {
                        wp_set_object_terms($post_id, [$subcat_term['term_id']], 'product_cat', true);
                    }
                }

                $importados++;
            }

            fclose($handle);

            echo "<div class='updated'><p>$importados produtos importados com sucesso.</p></div>";
        } else {
            echo '<div class="error"><p>Erro ao ler o arquivo CSV.</p></div>';
        }
    }

    // Formulário
    ?>
    <div class="wrap">
        <h1>Importar Produtos via CSV</h1>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required />
            <p><button type="submit" name="importar_csv" class="button button-primary">Importar</button></p>
        </form>
    </div>
    <?php
}
