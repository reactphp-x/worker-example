<?php

declare(strict_types=1);

use React\EventLoop\Loop;
use ReactX\Worker\Worker;

/**
 * react/socket only supports stream (TCP/TLS) connections.
 * UDP uses PHP datagram sockets integrated with the React event loop.
 */
function start_udp_server(Worker $worker, string $address = '127.0.0.1:6703'): void
{
    $server = stream_socket_server('udp://' . $address, $errno, $errstr, STREAM_SERVER_BIND);
    if ($server === false) {
        throw new RuntimeException("udp bind failed on $address: $errstr ($errno)");
    }

    stream_set_blocking($server, false);

    Loop::addReadStream($server, static function () use ($server): void {
        $peer = '';
        $data = @stream_socket_recvfrom($server, 65507, 0, $peer);
        if ($data === false || $data === '') {
            return;
        }

        Worker::log(sprintf('udp received from %s: %s', $peer, rtrim($data)));
        @stream_socket_sendto($server, 'echo: ' . $data, 0, $peer);
    });

    Worker::log(sprintf('udp server listening on %s (worker #%d, pid %d)', $address, $worker->id, posix_getpid()));

    $worker->onWorkerStop = static function () use ($server): void {
        Loop::removeReadStream($server);
        fclose($server);
    };
}
