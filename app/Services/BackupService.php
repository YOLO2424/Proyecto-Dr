<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use ZipArchive;

final class BackupService
{
    private array $config;

    public function __construct()
    {
        $this->config = require dirname(__DIR__, 2) . '/config/config.php';
    }

    /**
     * Snapshot WAL-safe de la base y empaquetado con los documentos.
     */
    public function create(string $label = 'manual'): string
    {
        $backupDir = $this->config['paths']['backups'];
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $stamp = date('Ymd-His');
        $zipName = 'backup_' . $label . '_' . $stamp . '.zip';
        $zipPath = $backupDir . '/' . $zipName;

        $tmpDb = $backupDir . '/snapshot_' . $stamp . '.sqlite';
        $pdo = Database::connection();
        $pdo->exec('PRAGMA wal_checkpoint(FULL);');
        $pdo->exec("VACUUM INTO '" . $tmpDb . "'");

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            @unlink($tmpDb);
            throw new \RuntimeException('No se pudo crear el archivo ZIP de respaldo.');
        }

        $zip->addFile($tmpDb, 'database/app.sqlite');
        $this->addRecursive($zip, $this->config['paths']['documents'], 'documents');
        $zip->close();
        @unlink($tmpDb);

        $this->applyRetention();

        (new AuditService())->log('BACKUP.CREADO', 'backups', null, ['archivo' => $zipName, 'size' => filesize($zipPath)]);

        return $zipName;
    }

    public function list(): array
    {
        $dir = $this->config['paths']['backups'];
        if (!is_dir($dir)) {
            return [];
        }
        $out = [];
        foreach (glob($dir . '/backup_*.zip') ?: [] as $file) {
            $out[] = [
                'name' => basename($file),
                'size' => filesize($file),
                'created_at' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }
        usort($out, fn ($a, $b) => strcmp($b['name'], $a['name']));
        return $out;
    }

    public function path(string $name): ?string
    {
        $dir = $this->config['paths']['backups'];
        $file = $dir . '/' . basename($name);
        return (is_file($file) && str_starts_with(basename($name), 'backup_')) ? $file : null;
    }

    public function delete(string $name): void
    {
        $file = $this->path($name);
        if ($file) {
            @unlink($file);
        }
    }

    private function addRecursive(ZipArchive $zip, string $dir, string $prefix): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($files as $file) {
            if ($file->isFile()) {
                $rel = $prefix . '/' . substr(str_replace('\\', '/', $file->getPathname()), strlen($dir) + 1);
                $zip->addFile($file->getPathname(), $rel);
            }
        }
    }

    private function applyRetention(): void
    {
        $settings = new SettingsService();
        $days = (int) ($settings->get('backup_retention_days') ?? $this->config['backups']['retention_days']);
        $dir = $this->config['paths']['backups'];
        $today = time();
        foreach (glob($dir . '/backup_*.zip') ?: [] as $file) {
            if ($today - filemtime($file) > $days * 86400) {
                @unlink($file);
            }
        }
    }
}