<?php

use Lightning\Autoloader\Autoloader;
use Lightning\Dotenv\Dotenv;

require __DIR__ . '/../src/Autoloader/Autoloader.php';
$autoloader = new Autoloader(dirname(__DIR__));
$autoloader->addNamespaces([
    'App' => 'app',
    'Lightning' => 'src',
    'Lightning\\Test' => 'tests'
]);
$autoloader->register();

(new Dotenv(dirname(__DIR__)))->load();