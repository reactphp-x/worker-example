<?php

declare(strict_types=1);

use Psr\Http\Message\ServerRequestInterface;
use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use ReactX\Worker\Worker;

function start_http_server(Worker $worker, string $address = '127.0.0.1:6702'): void
{
    $http = new HttpServer(function (ServerRequestInterface $request) use ($worker): Response {
        $body = sprintf(
            "Hello from reactphp-x-worker HTTP example\n\nworker: #%d\npid: %d\nmethod: %s\npath: %s\n",
            $worker->id,
            posix_getpid(),
            $request->getMethod(),
            $request->getUri()->getPath()
        );

        return Response::plaintext($body);
    });

    $socket = new SocketServer($address, [
        'tcp' => [
            'so_reuseport' => true,
        ],
    ]);
    $http->listen($socket);

    $socket->on('error', function (\Exception $e): void {
        Worker::log('http error: ' . $e->getMessage());
    });

    Worker::log(sprintf('http server listening on http://%s (worker #%d, pid %d)', $address, $worker->id, posix_getpid()));

    $worker->onWorkerStop = static function () use ($socket): void {
        $socket->close();
    };
}
