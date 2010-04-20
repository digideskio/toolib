<?php
    require_once(__DIR__ . '/../lib/SPL/SPLClassLoader.class.php');

    // Register phplibs
    $classLoader = new SplClassLoader(null, __DIR__ . DIRECTORY_SEPARATOR . '../lib/phplibs');
    $classLoader->setFileExtension('.class.php');
    $classLoader->register();

?>
