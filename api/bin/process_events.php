#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/db.php';

use Biblingo\Services\DomainEventProcessor;
use Biblingo\Services\EventWakeup;

$isDaemon = in_array('--daemon', $argv, true) || in_array('-d', $argv, true) || in_array('--watch', $argv, true);
$sleepSeconds = 10;
$batchSize = 50;

echo "[" . date('Y-m-d H:i:s') . "] 📬 Iniciando worker de eventos de dominio...\n";

// Socket UDP unix para despertar al instante cuando el API avisa que hay un
// evento nuevo (ver EventWakeup::notify()), en vez de esperar el proximo poll.
// Si no se puede crear (permisos, socket viejo de otro usuario, dos workers
// corriendo a la vez, etc.), se falla fuerte en vez de degradar en silencio a
// solo-poll: supervisord reinicia el proceso y el error queda en los logs,
// para que la falla se note y se corrija en vez de quedar escondida.
$wakeupSocket = null;
if ($isDaemon) {
    if (file_exists(EventWakeup::SOCKET_PATH) && !@unlink(EventWakeup::SOCKET_PATH)) {
        fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] ❌ No se pudo borrar el socket de aviso existente en " . EventWakeup::SOCKET_PATH . " (revisar permisos/dueño).\n");
        exit(1);
    }

    $wakeupSocket = stream_socket_server('udg://' . EventWakeup::SOCKET_PATH, $errno, $errstr, STREAM_SERVER_BIND);
    if ($wakeupSocket === false) {
        fwrite(STDERR, "[" . date('Y-m-d H:i:s') . "] ❌ No se pudo crear el socket de aviso: [{$errno}] {$errstr}\n");
        exit(1);
    }

    stream_set_blocking($wakeupSocket, false);
}

do {
    $result = DomainEventProcessor::processPending($batchSize);

    if ($result['total'] > 0) {
        echo sprintf(
            "[%s] Procesados: %d | Fallidos: %d | Total: %d\n",
            date('Y-m-d H:i:s'),
            $result['processed'],
            $result['failed'],
            $result['total']
        );
    }

    // Si el batch vino lleno probablemente queda backlog: seguir procesando de
    // inmediato en vez de esperar el timeout, para no perder tiempo en rafagas.
    $mayHaveMorePending = $result['total'] >= $batchSize;

    if ($isDaemon && !$mayHaveMorePending) {
        if ($wakeupSocket !== null) {
            $read = [$wakeupSocket];
            $write = null;
            $except = null;
            // Espera hasta sleepSeconds a que llegue un aviso; el poll normal sigue
            // corriendo igual al vencer el timeout, aunque nunca llegue ningun aviso.
            if (stream_select($read, $write, $except, $sleepSeconds) > 0) {
                // Drenar todos los avisos en cola para no reprocesar de mas por cada uno.
                while (@stream_socket_recvfrom($wakeupSocket, 65507) !== false) {
                }
            }
        } else {
            sleep($sleepSeconds);
        }
    }
} while ($isDaemon);

if ($wakeupSocket !== null) {
    fclose($wakeupSocket);
    @unlink(EventWakeup::SOCKET_PATH);
}

echo "[" . date('Y-m-d H:i:s') . "] ✅ Finalizado proceso de eventos.\n";
