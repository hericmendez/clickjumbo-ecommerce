
<?php

add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/mp-locate', [
        'methods'  => 'GET',
        'callback' => function () {
            $report = [];

            // Candidatos de autoload
            $candidates = [
                // override por constante (se definida no wp-config.php)
                defined('CJ_MP_VENDOR') ? CJ_MP_VENDOR : null,
                // vendor no plugin
                dirname(__DIR__, 1) . '/vendor/autoload.php',                 // .../clickjumbo-core/vendor/autoload.php (ajuste se este arquivo for noutro diretório)
                dirname(__DIR__, 2) . '/vendor/autoload.php',
                // wp-content
                WP_CONTENT_DIR . '/vendor/autoload.php',
                // raiz do WP (no seu caso costuma ser /public_html/wp/vendor)
                ABSPATH . 'vendor/autoload.php',
                // um nível acima do WP (ex.: /public_html/vendor)
                dirname(ABSPATH) . '/vendor/autoload.php',
            ];
            $candidates = array_values(array_filter($candidates));

            $found = null;
            foreach ($candidates as $p) {
                $exists = file_exists($p);
                $report[] = ['path' => $p, 'exists' => $exists];
                if ($exists && !$found) {
                    $found = $p;
                }
            }

            // Tenta carregar o primeiro encontrado
            if ($found) {
                require_once $found;
            }

            $class_ok = class_exists('MercadoPago\\SDK');

            return new WP_REST_Response([
                'candidates' => $report,
                'used'       => $found,
                'class_exists_MercadoPago\\SDK' => $class_ok,
                'ABSPATH'    => ABSPATH,
                'WP_CONTENT_DIR' => WP_CONTENT_DIR,
                'plugin_file'    => __FILE__,
                'hint'       => $found ? 'Se "class_exists" vier false, o Composer não instalou o pacote certo (mercadopago/dx-php) ou o autoload não está completo.' : 'Nenhum autoload encontrado: rode composer no lugar certo ou suba a pasta vendor.',
            ], 200);
        },
        'permission_callback' => '__return_true',
    ]);
});
