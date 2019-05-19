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

    public function __construct(ContainerBuilder $containerBuilder)
    {
        $this->containerBuilder = $containerBuilder;
    }

    /**
     * Get the instance of the service
     *
     * @param string $key
     * @param        $value
     *
     * @return void
     */
    public function setGlobal(string $key, $value)
    {
        $GLOBALS[$key] = $value;
        $this->containerBuilder->setParameter($key, $value);
    }
}
