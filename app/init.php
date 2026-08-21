<?php

// Manual load untuk file yang tidak bisa di-autoload
require_once 'config/config.php';

// Manual load core class
require_once 'core/App.php';
require_once 'core/Controller.php';
require_once 'core/Database.php';
require_once 'core/Flasher.php';

// Autoload untuk controllers dan models
spl_autoload_register(function($class) {
    $controllerPath = 'controllers/' . $class . '.php';
    $modelPath = 'models/' . $class . '.php';

    if (file_exists(__DIR__ . '/' . $controllerPath)) {
        require_once __DIR__ . '/' . $controllerPath;
    } elseif (file_exists(__DIR__ . '/' . $modelPath)) {
        require_once __DIR__ . '/' . $modelPath;
    }
});
