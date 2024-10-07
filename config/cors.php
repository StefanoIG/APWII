<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Aquí puedes configurar los ajustes para el "CORS". Esto determina qué
    | operaciones cross-origin pueden ejecutarse en los navegadores web.
    |
    | Para más información: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['*'],  // O puedes limitarlo solo a las rutas necesarias, como 'api/*'

    'allowed_methods' => ['*'],  // Permitir todos los métodos o especificar solo POST, GET, etc.

    'allowed_origins' => ['*'],  // Especifica el dominio de tu frontend

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],  // Puedes restringir los headers si lo prefieres

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,  // Habilitar soporte para credenciales
];
