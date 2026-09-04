<?php

declare(strict_types=1);

// Cargar variables de entorno desde .env si el archivo existe (entorno local sin Docker)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2) + [1 => ''];
        $name  = trim($name);
        $value = trim($value);

        // No sobreescribir variables ya definidas en el entorno (ej. Portainer, Docker)
        if (getenv($name) === false && !isset($_ENV[$name])) {
            putenv("{$name}={$value}");
            $_ENV[$name]    = $value;
            $_SERVER[$name] = $value;
        }
    }
}

function getEnvVar(string $key, string $default = ''): string {
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return (string)$val;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string)$_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return (string)$_SERVER[$key];
    }
    return $default;
}
