<?php
/**
 * Plugin Name: ClickJumbo Core
 * Description: Plugin central que gerencia autenticação, validações e utilitários da loja ClickJumbo.
 * Version: 1.0.0
 * Author: Héric Mendes
 */

if (!defined('ABSPATH')) exit;
add_action('rest_api_init', function () {
    // Permitir qualquer origem durante o desenvolvimento (ajuste para produção!)
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
}, 15);

add_action('init', function () {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Authorization, Content-Type");
        exit;
    }
});

// Carrega arquivos automaticamente
function clickjumbo_core_load_modules() {
    $folders = ['validations', 'auth', 'functions'];
    $base_dir = plugin_dir_path(__FILE__) . 'includes/';
    // Carrega utils antes de tudo
    $utils = $base_dir . 'utils.php';
    if (file_exists($utils)) {
        require_once $utils;
    }
    foreach ($folders as $folder) {
        $dir = $base_dir . $folder . '/';
        foreach (glob($dir . '*.php') as $file) {
            require_once $file;
        }
    }

    // Utils
    $utils = $base_dir . 'utils.php';
    if (file_exists($utils)) {
        require_once $utils;
    }
}
add_action('plugins_loaded', 'clickjumbo_core_load_modules');
