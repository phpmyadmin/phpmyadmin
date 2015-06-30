<?php
namespace PMA\DI;

require_once 'libraries/di/Item.int.php';

abstract class ReflectorItem implements Item
{

    /** @var Container */
    private $container;

    /** @var \Reflector */
    private $reflector;

    /**
     * Constructor
     *
     * @param Container $container
     * @param mixed $definition
     */
    public function __construct(Container $container, $definition)
    {
        $this->container = $container;
        $this->reflector = self::resolveReflector($definition);
    }

    /**
     * Invoke the reflector with given parameters
     *
     * @param array $params
     * @return mixed
     */
    protected function invoke($params = array())
    {
        $args = array();
        $reflector = $this->reflector;
        if ($reflector instanceof \ReflectionClass) {
            $constructor = $reflector->getConstructor();
            if (isset($constructor)) {
                $args = $this->resolveArgs(
                    $constructor->getParameters(),
                    $params
                );
            }
            return $reflector->newInstanceArgs($args);
        }
        /** @var \ReflectionFunctionAbstract $reflector */
        $args = $this->resolveArgs(
            $reflector->getParameters(),
            $params
        );
        if ($reflector instanceof \ReflectionMethod) {
            /** @var \ReflectionMethod $reflector */
            return $reflector->invokeArgs(null, $args);
        }
        /** @var \ReflectionFunction $reflector */
        return $reflector->invokeArgs($args);
    }

    /**
     * Getting required arguments with given parameters
     *
     * @param \ReflectionParameter[] $required
     * @param array $params
     * @return array
     */
    private function resolveArgs($required, $params = array())
    {
        $args = array();
        foreach ($required as $param) {
            $name = $param->getName();
            $type = $param->getClass();
            if (isset($type)) {
                $type = $type->getName();
            }
            if (isset($params[$name])) {
                $args[] = $params[$name];
            } elseif (is_string($type) && isset($params[$type])) {
                $args[] = $params[$type];
            } else {
                $content = $this->container->get($name);
                if (isset($content)) {
                    $args[] = $content;
                } elseif (is_string($type)) {
                    $args[] = $this->container->get($type);
                } else {
                    $args[] = null;
                }
            }
        }
        return $args;
    }

    /**
     * Resolve the reflection
     *
     * @param mixed $definition
     * @return \Reflector
     */
    private static function resolveReflector($definition)
    {
        if (function_exists($definition)) {
            return new \ReflectionFunction($definition);
        }
        if (is_string($definition)) {
            $definition = explode('::', $definition);
        }
        if (!isset($definition[1])) {
            return new \ReflectionClass($definition[0]);
        }
        return new \ReflectionMethod($definition[0], $definition[1]);
    }
}