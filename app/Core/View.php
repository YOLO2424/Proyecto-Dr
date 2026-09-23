<?php

namespace App\Core;

final class View
{
    public static function render(string $view, array $data = [], ?string $layout = 'app'): string
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $views = $config['paths']['views'];

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        $settings = (new \App\Services\SettingsService())->all();
        $defaults = [
            'title' => 'Inicio',
            'clinicName' => $settings['clinic_name'] ?? 'Consultorio',
            'appVersion' => $config['app']['version'] ?? '1.0.0',
            'flash' => $flash,
        ];
        $data = array_merge($defaults, $data);

        extract($data, EXTR_SKIP);

        ob_start();
        require $views . '/' . $view . '.php';
        $content = ob_get_clean();

        // Modo fragmento: devuelve solo el contenido (para actualizacion en vivo sin recargar).
        $liveFragment = ($_GET['live'] ?? null) === '1';
        $isStandalone = str_starts_with(ltrim($content), '<!DOCTYPE');
        if ($layout === null || $isStandalone || $liveFragment) {
            return $content;
        }

        ob_start();
        require $views . '/layouts/' . $layout . '.php';
        return ob_get_clean();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function date(?string $value): string
    {
        if (!$value) {
            return '—';
        }
        $ts = strtotime($value);
        return $ts ? date('d/m/Y', $ts) : $value;
    }

    public static function datetime(?string $value): string
    {
        if (!$value) {
            return '—';
        }
        $ts = strtotime($value);
        return $ts ? date('d/m/Y H:i', $ts) : $value;
    }
}