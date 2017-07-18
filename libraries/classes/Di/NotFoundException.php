<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Di\NotFoundException class
 *
 * @package PhpMyAdmin\Di
 */
namespace PhpMyAdmin\Di;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 *
 * @package PhpMyAdmin\Di
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{

}
