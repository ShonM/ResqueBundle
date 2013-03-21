<?php

if (! isset($_SERVER['RESQUE_JOB'])) {
    header('Status: 500 No Job');
    return;
}

$loader = require_once $_SERVER['BASE_DIR'] . '/bootstrap.php.cache';
$loader->register(true);

require $_SERVER['BASE_DIR'] . '/AppKernel.php';

umask(0002);

$kernel = new AppKernel($_SERVER['ENVIRONMENT'], true);
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
