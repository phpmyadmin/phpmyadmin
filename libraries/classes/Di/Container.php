<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Di\Container class
 *
 * @package PhpMyAdmin\Di
 */
namespace PhpMyAdmin\Di;

use Psr\Container\ContainerInterface;

/**
 * Class Container
 *
 * @package PhpMyAdmin\Di
 */
class Container implements ContainerInterface
{
    /**
     * @var Item[] $content
     */
    protected $content = array();

    /**
     * @var Container
     */
    protected static $defaultContainer;

    /**
     * Create a dependency injection container
     *
     * @param Container $base Container
     */
    public function __construct(Container $base = null)
    {
        if (isset($base)) {
            $this->content = $base->content;
        } else {
            $this->alias('container', 'Container');
        }
        $this->set('Container', $this);
    }

    /**
     * Get an object with given name and parameters
     *
     * @param string $name   Name
     * @param array  $params Parameters
     *
     * @throws NotFoundException  No entry was found for **this** identifier.
     * @throws ContainerException Error while retrieving the entry.
     *
     * @return mixed
     */
    public function get($name, array $params = array())
    {
        if (!$this->has($name)) {
            throw new NotFoundException("No entry was found for $name identifier.");
        }

        if (isset($this->content[$name])) {
            return $this->content[$name]->get($params);
        } elseif (isset($GLOBALS[$name])) {
            return $GLOBALS[$name];
        } else {
            throw new ContainerException("Error while retrieving the entry.");
        }
    }

    /**
     * Returns true if the container can return an entry for the given identifier.
     * Returns false otherwise.
     *
     * `has($name)` returning true does not mean that `get($name)` will not throw an exception.
     * It does however mean that `get($name)` will not throw a `NotFoundException`.
     *
     * @param string $name Identifier of the entry to look for.
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->content[$name]) || isset($GLOBALS[$name]);
    }

    /**
     * Remove an object from container
     *
     * @param string $name Name
     *
     * @return void
     */
    public function remove($name)
    {
        unset($this->content[$name]);
    }

    /**
     * Rename an object in container
     *
     * @param string $name    Name
     * @param string $newName New name
     *
     * @return void
     */
    public function rename($name, $newName)
    {
        $this->content[$newName] = $this->content[$name];
        $this->remove($name);
    }

    /**
     * Set values in the container
     *
     * @param string|array $name  Name
     * @param mixed        $value Value
     *
     * @return void
     */
    public function set($name, $value = null)
    {
        if (is_array($name)) {
            foreach ($name as $key => $val) {
                $this->set($key, $val);
            }
            return;
        }
        $this->content[$name] = new ValueItem($value);
    }

    /**
     * Register a service in the container
     *
     * @param string $name    Name
     * @param mixed  $service Service
     *
     * @return void
     */
    public function service($name, $service = null)
    {
        if (!isset($service)) {
            $service = $name;
        }
        $this->content[$name] = new ServiceItem($this, $service);
    }

    /**
     * Register a factory in the container
     *
     * @param string $name    Name
     * @param mixed  $factory Factory
     *
     * @return void
     */
    public function factory($name, $factory = null)
    {
        if (!isset($factory)) {
            $factory = $name;
        }
        $this->content[$name] = new FactoryItem($this, $factory);
    }

    /**
     * Register an alias in the container
     *
     * @param string $name   Name
     * @param string $target Target
     *
     * @return void
     */
    public function alias($name, $target)
    {
        // The target may be not defined yet
        $this->content[$name] = new AliasItem($this, $target);
    }

    /**
     * Get the global default container
     *
     * @return Container
     */
    public static function getDefaultContainer()
    {
        if (!isset(static::$defaultContainer)) {
            static::$defaultContainer = new Container();
        }
        return static::$defaultContainer;
    }
}
