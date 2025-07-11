<?php
add_action('rest_api_init', function () {
    register_rest_route('clickjumbo/v1', '/ping', [
        'methods' => 'GET',
        'callback' => function () {
            return new WP_REST_Response(['pong' => true]);
        },
        'permission_callback' => '__return_true'
    ]);
});
