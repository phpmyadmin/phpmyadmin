<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PMA\libraries\di\NotFoundException class
 *
 * @package PMA
 */
namespace PMA\libraries\di;

use Psr\Container\NotFoundExceptionInterface;

/**
 * Class NotFoundException
 *
 * @package PMA\libraries\di
 */
class NotFoundException extends ContainerException implements NotFoundExceptionInterface
{

}
