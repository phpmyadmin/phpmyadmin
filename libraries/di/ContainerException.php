<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\libraries\di\ContainerException class
 *
 * @package PMA
 */
namespace PMA\libraries\di;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Class ContainerException
 *
 * @package PMA\libraries\di
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{

}
