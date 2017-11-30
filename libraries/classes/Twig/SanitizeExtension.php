<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\SanitizeExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class SanitizeExtension
 *
 * @package PhpMyAdmin\Twig
 */
class SanitizeExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction(
                'Sanitize_escapeJsString',
                'PhpMyAdmin\Sanitize::escapeJsString',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Sanitize_jsFormat',
                'PhpMyAdmin\Sanitize::jsFormat',
                array('is_safe' => array('html'))
            ),
            new TwigFunction(
                'Sanitize_sanitize',
                'PhpMyAdmin\Sanitize::sanitize',
                array('is_safe' => array('html'))
            ),
        );
    }
}
