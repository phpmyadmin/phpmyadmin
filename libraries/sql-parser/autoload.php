<?php

/**
 * The autoloader used for loading sql-parser's components.
 *
 * This file is based on Composer's autoloader.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * @package     SqlParser
 * @subpackage  Autoload
 */
namespace SqlParser\Autoload;

if (!class_exists('SqlParser\\Autoload\\ClassLoader')) {
    if (! file_exists('./libraries/sql-parser/ClassLoader.php')) {
        die('Invalid invocation');
    }
    include_once './libraries/sql-parser/ClassLoader.php';
}

use SqlParser\Autoload\ClassLoader;

/**
 * Initializes the autoloader.
 *
 * @package     SqlParser
 * @subpackage  Autoload
 */
class AutoloaderInit
{

    /**
     * The loader instance.
     *
     * @var ClassLoader
     */
    public static $loader;

    /**
     * Constructs and returns the class loader.
     *
     * @param  array $map Array containing path to each namespace.
     *
     * @return ClassLoader
     */
    public static function getLoader(array $map)
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        self::$loader = $loader = new ClassLoader();

        foreach ($map as $namespace => $path) {
            $loader->set($namespace, $path);
        }

        $loader->register(true);

        return $loader;
    }
}

// php-gettext is used to translate error messages.
// This must be included before any class of the parser is loaded because
// if there is no `__` function defined, the library defines a dummy one
// in `common.php`.
require_once './libraries/vendor_config.php';
require_once GETTEXT_INC;

// Initializing the autoloader.
return AutoloaderInit::getLoader(
    array(
        'SqlParser\\' => array(dirname(__FILE__) . '/src'),
    )
);
