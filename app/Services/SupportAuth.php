<?php

namespace App\Services;

final class SupportAuth
{
    public const SESSION_KEY = 'support_auth';

    /** La sesion de Soporte/TI caduca a las 12 horas. */
    private const TTL = 12 * 3600;

    public static function isConfigured(): bool
    {
        $hash = (string) (new SettingsService())->get('support_pin_hash', '');
        return $hash !== '';
    }

    public static function isGranted(): bool
    {
        $auth = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($auth) || empty($auth['at'])) {
            return false;
        }
        return time() - (int) $auth['at'] <= self::TTL;
    }

    public static function attempt(string $pin): bool
    {
        if ($pin === '' || !self::isConfigured()) {
            return false;
        }
        $hash = (string) (new SettingsService())->get('support_pin_hash', '');
        return password_verify($pin, $hash);
    }

    public static function login(): void
    {
        $_SESSION[self::SESSION_KEY] = ['at' => time()];
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }
}