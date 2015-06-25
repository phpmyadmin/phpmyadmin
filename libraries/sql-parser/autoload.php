<?php

/**
 * The autoloader used for loading sql-parser's components.
 *
 * This file is based on Composer's autoloader.
 */

if (!class_exists('Composer\\Autoload\\ClassLoader')) {
    include_once './libraries/sql-parser/composer/ClassLoader.php';
}

use Composer\Autoload\ClassLoader;

/**
 * Initializes Composer's autoloader.
 *
 * @package SqlParser
 */
class ComposerAutoloaderInit
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
            $loader->setPsr4($namespace, $path);
        }

        $loader->register(true);

        return $loader;
    }
}

// Initializing Composer's autoloader
return ComposerAutoloaderInit::getLoader(
    array(
        'SqlParser\\' => array(dirname(__FILE__) . '/src'),
    )
);
