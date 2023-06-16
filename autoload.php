<?php
function wcsquared_autoload($class_name) {
    // Convert the class name to a file path
    $file_path = __DIR__ . '/classes/' . str_replace('\\', '/', $class_name) . '.php';

    // Check if the file exists and load it
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

// Register the autoload function
spl_autoload_register('wcsquared_autoload');
