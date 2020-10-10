<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Migration from home-made DI to Symfony DI
 *
 * @package PhpMyAdmin\Di
 */
declare(strict_types=1);

namespace PhpMyAdmin\Di;

use InvalidArgumentException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Migration from home-made DI to Symfony DI
 *
 * @package PhpMyAdmin\Di
 */
class Migration
{
    /** @var self */
    protected static $instance;

    /** @var ContainerBuilder */
    protected $containerBuilder;

    /**
     * Get instance of this class
     *
     * @param ContainerBuilder|null $containerBuilder ContainerBuilder object that should be used to store the data
     *
     * @return Migration
     */
    public static function getInstance(?ContainerBuilder $containerBuilder = null): self
    {
        if (null !== self::$instance) {
            return self::$instance;
        }

        if (null === $containerBuilder) {
            throw new InvalidArgumentException('Container builder should be sent for ' . self::class . ' creation');
        }

        return self::$instance = new self($containerBuilder);
    }

    /**
     * Migration constructor.
     *
     * @param ContainerBuilder $containerBuilder ContainerBuilder object that should be used to store the data
     */
    protected function __construct(ContainerBuilder $containerBuilder)
    {
        $this->containerBuilder = $containerBuilder;
    }

    /**
     * Get the instance of the service
     *
     * @param string $key   Key of data to store
     * @param mixed  $value Data to store
     *
     * @return void
     */
    public function setGlobal(string $key, $value)
    {
        $GLOBALS[$key] = $value;
        $this->containerBuilder->setParameter($key, $this->containerBuilder->getParameterBag()->escapeValue($value));
    }
}
