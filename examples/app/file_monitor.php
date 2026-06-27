<?php

declare(strict_types=1);

use React\EventLoop\Loop;
use ReactX\Worker\Worker;

function start_file_monitor(string $monitorDir): void
{
    if (Worker::$daemonize) {
        Worker::log(sprintf('[%s] daemon mode, skip file monitor', date('H:i:s')));
        return;
    }

    Worker::log(sprintf('[%s] debug mode, watching PHP files in %s', date('H:i:s'), $monitorDir));

    $lastMtime = time();

    Loop::addPeriodicTimer(1, static function () use ($monitorDir, &$lastMtime): void {
        try {
            $changed = check_files_change($monitorDir, $lastMtime);
            if ($changed !== null) {
                Worker::log("{$changed['path']} updated, reloading workers");
                posix_kill(posix_getppid(), SIGUSR1);
                $lastMtime = $changed['mtime'];
            }
        } catch (\Throwable $e) {
            Worker::log(sprintf('[%s] file monitor error: %s', date('H:i:s'), $e->getMessage()));
        }
    });
}

/** @return array{path: string, mtime: int}|null */
function check_files_change(string $monitorDir, int $lastMtime): ?array
{
    $dirIterator = new \RecursiveDirectoryIterator($monitorDir);
    $iterator = new \RecursiveIteratorIterator($dirIterator);

    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        if ($lastMtime < $file->getMTime()) {
            return [
                'path' => $file->getPathname(),
                'mtime' => $file->getMTime(),
            ];
        }
    }

    return null;
}
