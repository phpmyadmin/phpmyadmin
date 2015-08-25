<?php

require_once './libraries/Psr4Autoloader.php';

// instantiate the loader
$loader = new \PMA\Psr4Autoloader;

// register the autoloader
$loader->register();

// register the base directories for the namespace prefix
$loader->addNamespace('PMA', '.');
$loader->addNamespace('SqlParser', './libraries/sql-parser/src');
