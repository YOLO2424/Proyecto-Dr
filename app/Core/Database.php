<?php

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';

        $file = $config['database']['file'];
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        try {
            $pdo = new PDO('sqlite:' . $file, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            throw new \RuntimeException('No se pudo abrir la base de datos: ' . $e->getMessage());
        }

        if ($config['database']['foreign_keys']) {
            $pdo->exec('PRAGMA foreign_keys = ON;');
        }
        if ($config['database']['journal_mode'] === 'WAL') {
            $pdo->exec('PRAGMA journal_mode = WAL;');
        }
        $pdo->exec('PRAGMA synchronous = ' . $config['database']['synchronous'] . ';');
        $pdo->exec('PRAGMA busy_timeout = ' . (int) $config['database']['busy_timeout'] . ';');

        self::$pdo = $pdo;
        return $pdo;
    }
}