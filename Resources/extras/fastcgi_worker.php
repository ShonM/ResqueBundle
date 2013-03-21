<?php

if (! isset($_SERVER['RESQUE_JOB'])) {
    header('Status: 500 No Job');
    return;
}

use Symfony\Component\ClassLoader\ApcClassLoader;

$loader = require_once $_SERVER['BASE_DIR'] . '/bootstrap.php.cache';
$loader = new ApcClassLoader('chess_sf2', $loader);
$loader->register(true);

require $_SERVER['BASE_DIR'] . '/ChessKernel.php';
require $_SERVER['BASE_DIR'] . '/ChessCache.php';

umask(0002);

$kernel = new ChessKernel('dev', true);
$kernel->loadClassCache();

// Boot the application kernel
$kernel->boot();

// We have to fetch our resque object first to make sure events get hooked
$kernel->getContainer()->get('resque');

try {
    $job = unserialize(urldecode($_SERVER['RESQUE_JOB']));
    $job->worker->perform($job);
} catch (\Exception $e) {
    if (isset($job)) {
        $job->fail($e);
    } else {
        header('Status: 500');
    }
}

?>
