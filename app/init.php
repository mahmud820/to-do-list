<?php

// Zona waktu aplikasi (dipakai untuk "hari ini", deadline terlambat, dsb.)
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/config/config.php';

// Core
require_once __DIR__ . '/core/App.php';
require_once __DIR__ . '/core/Controller.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Helpers.php';

// Autoload untuk controllers dan models
spl_autoload_register(function ($class) {
    $controllerPath = __DIR__ . '/controllers/' . $class . '.php';
    $modelPath = __DIR__ . '/models/' . $class . '.php';

    if (file_exists($controllerPath)) {
        require_once $controllerPath;
    } elseif (file_exists($modelPath)) {
        require_once $modelPath;
    }
});
