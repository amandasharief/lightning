<?php
use Lightning\Autoloader\Autoloader;

// load composer autoload
require __DIR__ . '/../vendor/autoload.php';

// load our autoloader
require __DIR__ . '/../src/Autoloader/Autoloader.php';
$autoloader = new Autoloader(dirname(__DIR__));
$autoloader->addNamespaces([
    'App' => 'app',
    'Lightning' => 'src',
    'Lightning\\Test' => 'tests'
]);
$autoloader->register();
