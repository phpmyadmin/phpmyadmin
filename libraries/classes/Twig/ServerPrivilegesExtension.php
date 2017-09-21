<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\ServerPrivilegesExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class ServerPrivilegesExtension
 *
 * @package PhpMyAdmin\Twig
 */
class ServerPrivilegesExtension extends Twig_Extension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'ServerPrivileges_formatPrivilege',
                'PhpMyAdmin\Server\Privileges::formatPrivilege',
                array('is_safe' => array('html'))
            ),
        );
    }
}
