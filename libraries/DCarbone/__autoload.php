<?php

spl_autoload_register(function($className) {
    if($className[0] == '\\') {
        $className = substr($className, 1);
    }
    
    // Leave if class should not be handled by this autoloader
    if(strpos($className, 'DCarbone') !== 0) return;
    
    $classPath = strtr(substr($className, strlen('DCarbone')), '\\', '/') . '.php';
    
    require(__DIR__ . $classPath);
});
