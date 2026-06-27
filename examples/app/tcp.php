<?php

declare(strict_types=1);

use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;
use ReactX\Worker\Worker;

function start_tcp_server(Worker $worker, string $address = '127.0.0.1:6701'): void
{
    $socket = new SocketServer($address);

    $socket->on('connection', function (ConnectionInterface $connection): void {
        $remote = $connection->getRemoteAddress();
        Worker::log("tcp connection from $remote");

        $connection->on('data', function (string $data) use ($connection): void {
            $connection->write('echo: ' . $data);
        });

        $connection->on('close', function () use ($remote): void {
            Worker::log("tcp connection closed: $remote");
        });
    });

    $socket->on('error', function (\Exception $e): void {
        Worker::log('tcp error: ' . $e->getMessage());
    });

    Worker::log(sprintf('tcp server listening on %s (worker #%d, pid %d)', $address, $worker->id, posix_getpid()));

    $worker->onWorkerStop = static function () use ($socket): void {
        $socket->close();
    };
}
