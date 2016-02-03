<?php
/**
 * Load class autoloader
 *
 * @package PhpMyAdmin
 */

require_once './libraries/Psr4Autoloader.php';

// instantiate the loader
$loader = \PMA\Psr4Autoloader::getInstance();

// register the autoloader
$loader->register();

// register the base directories for the namespace prefix
$loader->addNamespace('PMA', '.');
$loader->addNamespace('SqlParser', './libraries/sql-parser/src');
$loader->addNamespace('phpseclib', PHPSECLIB_INC_DIR);
