<?php

declare(strict_types=1);

namespace Biblingo\Services;

/**
 * Aviso "hay trabajo nuevo" al worker de domain_events via un socket Unix de
 * datagramas, para que reaccione al instante en vez de esperar el proximo poll
 * (ver bin/process_events.php). Es solo una optimizacion de latencia: la tabla
 * domain_events sigue siendo la fuente de verdad, y el worker hace poll cada
 * cierto tiempo igual como red de seguridad si el aviso se pierde o el worker
 * no esta escuchando (por eso notify() nunca lanza, solo intenta y sigue).
 */
class EventWakeup {
    public const SOCKET_PATH = '/tmp/biblingo_domain_events.sock';

    public static function notify(): void {
        // El handler global de errores (ver public/index.php) convierte cualquier
        // warning en ErrorException, incluso con @ delante — si el worker no esta
        // escuchando, stream_socket_client() emite un warning que si no se atrapa
        // aca se propaga como excepcion real hacia el llamador (que puede estar
        // fuera de su propia transaccion ya commiteada). Por eso el try/catch,
        // no solo el @.
        try {
            $socket = @stream_socket_client('udg://' . self::SOCKET_PATH, $errno, $errstr, 0.1);
            if ($socket === false) {
                return;
            }
            @fwrite($socket, '1');
            fclose($socket);
        } catch (\Throwable $e) {
            // Best-effort: perder el aviso no es grave, el poll del worker cubre el caso.
        }
    }
}
