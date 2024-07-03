<?php

use Lightning\Dotenv\Dotenv;
use Lightning\Container\Container;

// Load composer autoload and our autoloader
require __DIR__ . '/autoload.php';

(new Dotenv(dirname(__DIR__)))->load();

(new \NunoMaduro\Collision\Provider())->register();

$container = new Container(include __DIR__ . '/services.php');
