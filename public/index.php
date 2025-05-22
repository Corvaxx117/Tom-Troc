<?php

// Charge les dépendances via l’autoloader
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/config.php';

use Metroid\Launcher;

$basePath = dirname(__DIR__) . '/';
$routesFile = $basePath . 'config/route.yaml';

// Créer une instance de la classe Launcher
$launcher = new Launcher($basePath, $routesFile);

// Démarrer l'application
$launcher->run();
