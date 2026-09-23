<?php

namespace App\Core;

final class Request
{
    public function __construct(
        private array $server,
        private array $query,
        private array $post,
        private array $files,
    ) {}

    public static function capture(): self
    {
        return new self($_SERVER, $_GET, $_POST, $_FILES);
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = rawurldecode(parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        return '/' . ltrim($uri, '/');
    }

    public function query(string $key, ?string $default = null): ?string
    {
        return $this->query[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->post[$key] ?? $this->query[$key] ?? null;
        return $value ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post);
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }
}