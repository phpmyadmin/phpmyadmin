<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\DI\ReflectorItem class
 *
 * @package PMA
 */

namespace PMA\DI;

require_once 'libraries/di/Item.int.php';

/**
 * Reflector manager
 *
 * @package PMA\DI
 */
abstract class ReflectorItem implements Item
{

    /** @var Container */
    private $_container;

    /** @var \Reflector */
    private $_reflector;

    /**
     * Constructor
     *
     * @param Container $container  Container
     * @param mixed     $definition Definition
     */
    public function __construct(Container $container, $definition)
    {
        $this->_container = $container;
        $this->_reflector = self::_resolveReflector($definition);
    }

    /**
     * Invoke the reflector with given parameters
     *
     * @param array $params Parameters
     * @return mixed
     */
    protected function invoke($params = array())
    {
        $args = array();
        $reflector = $this->_reflector;
        if ($reflector instanceof \ReflectionClass) {
            $constructor = $reflector->getConstructor();
            if (isset($constructor)) {
                $args = $this->_resolveArgs(
                    $constructor->getParameters(),
                    $params
                );
            }
            return $reflector->newInstanceArgs($args);
        }
        /** @var \ReflectionFunctionAbstract $reflector */
        $args = $this->_resolveArgs(
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
     * @param \ReflectionParameter[] $required Arguments
     * @param array                  $params   Parameters
     *
*@return array
     */
    private function _resolveArgs($required, $params = array())
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
                $content = $this->_container->get($name);
                if (isset($content)) {
                    $args[] = $content;
                } elseif (is_string($type)) {
                    $args[] = $this->_container->get($type);
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
     * @param mixed $definition Definition
     *
     * @return \Reflector
     */
    private static function _resolveReflector($definition)
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
