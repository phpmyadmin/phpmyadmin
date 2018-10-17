<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Di\ContainerException class
 *
 * @package PhpMyAdmin\Di
 */
declare(strict_types=1);

namespace PhpMyAdmin\Di;

use Exception;
use Psr\Container\ContainerExceptionInterface;

/**
 * Class ContainerException
 *
 * @package PhpMyAdmin\Di
 */
class ContainerException extends Exception implements ContainerExceptionInterface
{

}
