<?php
/**
 * Plugin Name: ClickJumbo Core
 * Description: Plugin central que gerencia autenticação, validações e utilitários da loja ClickJumbo.
 * Version: 1.0.0
 * Author: Héric Mendes
 */

if (!defined('ABSPATH')) exit;

/**
 * 🔐 Habilita CORS apenas para REST API (ambiente local)
 */
add_action('rest_api_init', function () {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
}, 15);

// Suporte para requisições OPTIONS
add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Authorization, Content-Type");
        exit;
    }
});

/**
 * 🔐 Expõe nonce para uso em scripts JS
 */
add_action('admin_enqueue_scripts', function () {
    wp_localize_script('jquery', 'clickjumbo_data', [
        'nonce' => wp_create_nonce('wp_rest')
    ]);
});

/**
 * 📦 Autoloader dos arquivos PHP do plugin
 */
add_action('plugins_loaded', 'clickjumbo_core_load_modules');

function clickjumbo_core_load_modules()
{
    $base_dir = plugin_dir_path(__FILE__) . 'includes/';

    // Carrega pastas principais (utils, auth, validations)
    $autoload_folders = ['utils', 'auth', 'validations'];
    foreach ($autoload_folders as $folder) {
        $folder_path = $base_dir . $folder . '/';
        if (is_dir($folder_path)) {
            foreach (glob($folder_path . '*.php') as $file) {
                require_once $file;
            }
        }
    }

    // Carrega funções agrupadas por categoria (exceto admin)
    $function_categories = ['prisons', 'orders', 'products', 'utils', 'users']; //faltou inccluir users
    foreach ($function_categories as $category) {
        $category_path = $base_dir . "functions/$category/";
        if (is_dir($category_path)) {
            foreach (glob($category_path . '*.php') as $file) {
                require_once $file;
            }
        }
    }

    // Carrega arquivos do painel administrativo apenas se estiver no admin
    if (is_admin()) {
        $admin_path = $base_dir . 'admin/';
        if (is_dir($admin_path)) {
            foreach (glob($admin_path . '*.php') as $file) {
                require_once $file;
            }
        }
    }
}
