<?php

return [
    'app' => [
        'name' => 'Sistema Clínico Local',
        'version' => '1.0.0',
        'locale' => 'es_ES',
        'timezone' => 'America/Mexico_City',
        'debug' => false,
    ],

    'paths' => [
        'root' => dirname(__DIR__),
        'storage' => dirname(__DIR__) . '/storage',
        'database' => dirname(__DIR__) . '/storage/database',
        'documents' => dirname(__DIR__) . '/storage/documents',
        'backups' => dirname(__DIR__) . '/storage/backups',
        'quarantine' => dirname(__DIR__) . '/storage/quarantine',
        'logs' => dirname(__DIR__) . '/storage/logs',
        'migrations' => dirname(__DIR__) . '/database/migrations',
        'views' => dirname(__DIR__) . '/resources/views',
    ],

    'database' => [
        'file' => dirname(__DIR__) . '/storage/database/app.sqlite',
        'journal_mode' => 'WAL',
        'synchronous' => 'NORMAL',
        'busy_timeout' => 5000,
        'foreign_keys' => true,
    ],

    'identity' => [
        'prefix' => 'PAC-',
        'digits' => 8,
        'start' => 1,
    ],

    'qr' => [
        'prefix' => 'PATIENT:',
        'token_bytes' => 24,
        'resolution_url' => '/qr/resolve',
    ],

    'documents' => [
        'max_size' => 15 * 1024 * 1024,
        'allowed' => ['application/pdf', 'image/jpeg', 'image/png'],
        'extensions' => ['pdf', 'jpg', 'jpeg', 'png'],
    ],

    'backups' => [
        'retention_days' => 30,
    ],

    'preregistration' => [
        'url' => '/registro',
    ],
];