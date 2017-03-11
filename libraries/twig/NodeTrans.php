<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\libraries\twig\NodeTrans class
 *
 * @package PMA\libraries\twig
 */
namespace PMA\libraries\twig;

/**
 * Class NodeTrans
 *
 * @package PMA\libraries\twig
 */
class NodeTrans extends \Twig_Extensions_Node_Trans
{
    /**
     * @param bool $plural Return plural or singular function to use
     *
     * @return string
     */
    protected function getTransFunction($plural)
    {
        return $plural ? '_ngettext' : '_gettext';
    }
}
