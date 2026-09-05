#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

use Biblingo\Services\DomainEventProcessor;

$isDaemon = in_array('--daemon', $argv, true) || in_array('-d', $argv, true) || in_array('--watch', $argv, true);
$sleepSeconds = 10;

echo "[" . date('Y-m-d H:i:s') . "] 📬 Iniciando worker de eventos de dominio...\n";

do {
    $result = DomainEventProcessor::processPending(50);

    if ($result['total'] > 0) {
        echo sprintf(
            "[%s] Procesados: %d | Fallidos: %d | Total: %d\n",
            date('Y-m-d H:i:s'),
            $result['processed'],
            $result['failed'],
            $result['total']
        );
    }

    if ($isDaemon) {
        sleep($sleepSeconds);
    }
} while ($isDaemon);

echo "[" . date('Y-m-d H:i:s') . "] ✅ Finalizado proceso de eventos.\n";
