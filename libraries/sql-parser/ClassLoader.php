<?php

/**
 * This file is based on Composer's autoloader.
 *
 * (c) Nils Adermann <naderman@naderman.de>
 *     Jordi Boggiano <j.boggiano@seld.be>
 *
 * @package     SqlParser
 * @subpackage  Autoload
 */
namespace SqlParser\Autoload;

/**
 * ClassLoader implements a PSR-4 class loader,
 *
 * This class is loosely based on the Symfony UniversalClassLoader.
 * This class is a stripped version of Composer's ClassLoader.
 *
 * @package     SqlParser
 * @subpackage  Autoload
 * @author      Fabien Potencier <fabien@symfony.com>
 * @author      Jordi Boggiano <j.boggiano@seld.be>
 */
class ClassLoader
{
    public $prefixLengths = array();
    public $prefixDirs = array();
    public $fallbackDirs = array();

    public $classMap = array();

    public $classMapAuthoritative = false;

    /**
     * @param array $classMap Class to filename map
     *
     * @return void
     */
    public function addClassMap(array $classMap)
    {
        if (!empty($this->classMap)) {
            $this->classMap = array_merge($this->classMap, $classMap);
        } else {
            $this->classMap = $classMap;
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace, either
     * appending or prepending to the ones previously set for this namespace.
     *
     * @param string       $prefix  The prefix/namespace, with trailing '\\'
     * @param array|string $paths   The PSR-0 base directories
     * @param bool         $prepend Whether to prepend the directories
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function add($prefix, $paths, $prepend = false)
    {
        if (!$prefix) {
            // Register directories for the root namespace.
            if ($prepend) {
                $this->fallbackDirs = array_merge(
                    (array) $paths,
                    $this->fallbackDirs
                );
            } else {
                $this->fallbackDirs = array_merge(
                    $this->fallbackDirs,
                    (array) $paths
                );
            }
        } elseif (!isset($this->prefixDirs[$prefix])) {
            // Register directories for a new namespace.
            $length = strlen($prefix);
            if ('\\' !== $prefix[$length - 1]) {
                throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }
            $this->prefixLengths[$prefix[0]][$prefix] = $length;
            $this->prefixDirs[$prefix] = (array) $paths;
        } elseif ($prepend) {
            // Prepend directories for an already registered namespace.
            $this->prefixDirs[$prefix] = array_merge(
                (array) $paths,
                $this->prefixDirs[$prefix]
            );
        } else {
            // Append directories for an already registered namespace.
            $this->prefixDirs[$prefix] = array_merge(
                $this->prefixDirs[$prefix],
                (array) $paths
            );
        }
    }

    /**
     * Registers a set of PSR-4 directories for a given namespace,
     * replacing any others previously set for this namespace.
     *
     * @param string       $prefix The prefix/namespace, with trailing '\\'
     * @param array|string $paths  The PSR-4 base directories
     *
     * @throws \InvalidArgumentException
     *
     * @return void
     */
    public function set($prefix, $paths)
    {
        if (!$prefix) {
            $this->fallbackDirs = (array) $paths;
        } else {
            $length = strlen($prefix);
            if ('\\' !== $prefix[$length - 1]) {
                throw new \InvalidArgumentException("A non-empty PSR-4 prefix must end with a namespace separator.");
            }
            $this->prefixLengths[$prefix[0]][$prefix] = $length;
            $this->prefixDirs[$prefix] = (array) $paths;
        }
    }

    /**
     * Registers this instance as an autoloader.
     *
     * @param bool $prepend Whether to prepend the autoloader or not
     *
     * @return void
     */
    public function register($prepend = false)
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    /**
     * Unregisters this instance as an autoloader.
     *
     * @return void
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * Loads the given class or interface.
     *
     * @param  string $class The name of the class
     *
     * @return bool|null True if loaded, null otherwise
     */
    public function loadClass($class)
    {
        if ($file = $this->findFile($class)) {
            includeFile($file);

            return true;
        }
    }

    /**
     * Finds the path to the file where the class is defined.
     *
     * @param string $class The name of the class
     *
     * @return string|false The path if found, false otherwise
     */
    public function findFile($class)
    {
        // work around for PHP 5.3.0 - 5.3.2 https://bugs.php.net/50731
        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }

        // class map lookup
        if (isset($this->classMap[$class])) {
            return $this->classMap[$class];
        }
        if ($this->classMapAuthoritative) {
            return false;
        }

        $file = $this->findFileWithExtension($class, '.php');

        // Search for Hack files if we are running on HHVM
        if ($file === null && defined('HHVM_VERSION')) {
            $file = $this->findFileWithExtension($class, '.hh');
        }

        if ($file === null) {
            // Remember that this class does not exist.
            return $this->classMap[$class] = false;
        }

        return $file;
    }

    /**
     * Finds a file that defines the specified class and has the specified
     * extension.
     *
     * @param  string $class The name of the class
     * @param  string $ext   The extension of the file
     *
     * @return string|false The path if found, false otherwise
     */
    public function findFileWithExtension($class, $ext)
    {
        $logicalPath = strtr($class, '\\', DIRECTORY_SEPARATOR) . $ext;

        $first = $class[0];
        if (isset($this->prefixLengths[$first])) {
            foreach ($this->prefixLengths[$first] as $prefix => $length) {
                if (0 === strpos($class, $prefix)) {
                    foreach ($this->prefixDirs[$prefix] as $dir) {
                        if (is_file($file = $dir . DIRECTORY_SEPARATOR . substr($logicalPath, $length))) {
                            return $file;
                        }
                    }
                }
            }
        }

        foreach ($this->fallbackDirs as $dir) {
            if (is_file($file = $dir . DIRECTORY_SEPARATOR . $logicalPath)) {
                return $file;
            }
        }

        return false;
    }
}

if (!function_exists('SqlParser\\Autoload\\includeFile')) {

    /**
     * Scope isolated include.
     *
     * Prevents access to $this/self from included files.
     *
     * @param string $file The name of the file
     *
     * @return void
     */
    function includeFile($file)
    {
        include $file;
    }
}
