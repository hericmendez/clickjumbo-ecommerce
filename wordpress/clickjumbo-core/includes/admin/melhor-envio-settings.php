<?php
if (!defined('ABSPATH')) exit;

function clickjumbo_render_melhor_envio_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('Acesso negado.');
    }

    if (isset($_POST['melhor_envio_token'])) {
        check_admin_referer('salvar_token_melhor_envio');
        $token = sanitize_text_field($_POST['melhor_envio_token']);
        update_option('melhor_envio_token', $token);
        echo '<div class="notice notice-success"><p>Token atualizado com sucesso.</p></div>';
    }

    $token = get_option('melhor_envio_token', '');
    ?>
    <div class="wrap">
        <h1>Configurações - Melhor Envio</h1>
        <form method="post">
            <?php wp_nonce_field('salvar_token_melhor_envio'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="melhor_envio_token">Token de Acesso (Bearer)</label></th>
                    <td>
                        <input readonly type="text" id="melhor_envio_token" name="melhor_envio_token" value="<?php echo esc_attr($token); ?>" class="regular-text" />
                        <p class="description">Cole aqui o token gerado em <a href="https://www.melhorenvio.com.br/" target="_blank">melhorenvio.com.br</a>.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Salvar Token'); ?>
        </form>
    </div>
    <?php
}
