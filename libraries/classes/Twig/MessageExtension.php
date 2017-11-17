<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\MessageExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;
use PhpMyAdmin\Message;

/**
 * Class MessageExtension
 *
 * @package PhpMyAdmin\Twig
 */
class MessageExtension extends Twig_Extension
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
                'Message_notice',
                function ($string) {
                    return Message::notice($string)->getDisplay();
                },
                array('is_safe' => array('html'))
            ),
            new Twig_SimpleFunction(
                'Message_error',
                function ($string) {
                    return Message::error($string)->getDisplay();
                },
                array('is_safe' => array('html'))
            ),
        );
    }
}
