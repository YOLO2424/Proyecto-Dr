<?php

namespace App\Core;

final class Response
{
    public static function send(string $body, int $status = 200, array $headers = []): never
    {
        http_response_code($status);
        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }
        echo $body;
        exit;
    }

    public static function redirect(string $path): never
    {
        header('Location: ' . $path, true, 302);
        exit;
    }

    public static function redirectWith(string $path, string $type, string $message): never
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        self::redirect($path);
    }

    public static function json(mixed $data, int $status = 200): never
    {
        self::send(json_encode($data, JSON_UNESCAPED_UNICODE), $status, [
            'Content-Type' => 'application/json; charset=utf-8',
        ]);
    }

    public static function download(string $file, string $filename): never
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
}