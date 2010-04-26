<?php
    require_once(__DIR__ . '/../lib/vendor/phplibs/ClassLoader.class.php');

    // Register phplibs
    $loader = new ClassLoader();
    $loader->register_directory(__DIR__ . '/../lib/vendor/phplibs');
    $loader->set_file_extension('.class.php');
    $loader->register();

?>
