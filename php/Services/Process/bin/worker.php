<?php

use AsyncPHP\Doorman\Handler;
use AsyncPHP\Doorman\Task;

require_once __DIR__ . '/../../../config/app.php';
require_once __DIR__ . '/../../../vendor/autoload.php';
define('LIB_DIR', realpath(__DIR__ . '/../../../') . '/');
spl_autoload_register('autoload');
ini_set('unserialize_callback_func', 'autoload');

if (count($argv) < 2) {
    throw new InvalidArgumentException("Invalid call");
}

$script = array_shift($argv);

$task = array_shift($argv);

/**
 * We must account for the input data being malformed. That's why we use "@".
 */
$task = @unserialize(base64_decode($task));

if ($task instanceof Task) {
    $handler = $task->getHandler();

    $object = new $handler();

    if ($object instanceof Handler) {
        $object->handle($task);
    }
}
