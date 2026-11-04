<?php
/**
 * Configuración general de la app.
 *
 * En local (XAMPP) usa SQLite, no requiere servidor de base de datos.
 * Cuando subas a un hosting con MySQL cambia 'driver' a 'mysql' y llena los datos.
 */
return [
    'db' => [
        'driver'      => 'sqlite',                              // sqlite | mysql
        'sqlite_path' => __DIR__ . '/data/promo.sqlite',
        'host'        => '127.0.0.1',
        'port'        => 3306,
        'name'        => 'promo_cabana',
        'user'        => 'root',
        'pass'        => '',
    ],

    // Ruta donde vive la app dentro del dominio. En local es /promo.
    // Si la subes a la raíz de un dominio, cambia a ''.
    'app_base' => '/promo',

    // Opcional: URL pública completa (ej. https://promo.micabana.com).
    // Si la dejas vacía se detecta automáticamente.
    'public_url' => '',

    'timezone' => 'America/Bogota',
];
