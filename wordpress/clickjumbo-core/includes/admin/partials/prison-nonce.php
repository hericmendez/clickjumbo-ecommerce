<?php
function render_nonce_and_scripts()
{
    wp_nonce_field('wp_rest');
    echo '<script>
        window.clickjumbo_data = window.clickjumbo_data || {};
        window.clickjumbo_data.nonce = "' . wp_create_nonce('wp_rest') . '";
    </script>';
    echo '<script src="' . plugins_url('/assets/js/admin-prison-panel.js', dirname(__FILE__, 2)) . '"></script>';
}
