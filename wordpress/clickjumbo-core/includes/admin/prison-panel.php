<?php
// includes/admin/prison-panel.php
require_once __DIR__ . '/partials/prison-header.php';
require_once __DIR__ . '/partials/prison-table.php';
require_once __DIR__ . '/partials/prison-form.php';
require_once __DIR__ . '/partials/prison-products.php';
require_once __DIR__ . '/partials/prison-styles.php';
require_once __DIR__ . '/partials/prison-nonce.php';
require_once __DIR__ . '/partials/prison-styles.php';


function clickjumbo_render_prison_panel()
{
    render_prison_panel_header();
    render_prison_table();
    render_prison_form();
    render_products_panel();
    render_custom_styles();
    render_nonce_and_scripts();
}
