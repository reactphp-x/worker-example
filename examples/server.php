<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use ReactX\Worker\Worker;

$tcpWorker = new Worker(function (Worker $worker): void {
    require_once __DIR__ . '/app/tcp.php';
    start_tcp_server($worker);
});
$tcpWorker->name = 'tcp:9501';
$tcpWorker->count = 1;

$httpWorker = new Worker(function (Worker $worker): void {
    require_once __DIR__ . '/app/http.php';
    start_http_server($worker);
});
$httpWorker->name = 'http:9502';
$httpWorker->count = 12;

$udpWorker = new Worker(function (Worker $worker): void {
    require_once __DIR__ . '/app/udp.php';
    start_udp_server($worker);
});
$udpWorker->name = 'udp:9503';
$udpWorker->count = 1;

$monitorDir = realpath(__DIR__) ?: __DIR__;

$fileMonitorWorker = new Worker(function () use ($monitorDir): void {
    require_once __DIR__ . '/app/file_monitor.php';
    start_file_monitor($monitorDir);
});
$fileMonitorWorker->name = 'FileMonitor';
$fileMonitorWorker->reloadable = false;

Worker::runAll();
