<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\I18nExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Twig\I18n\TokenParserTrans;
use Twig_Extensions_Extension_I18n;
use Twig_SimpleFilter;

/**
 * Class I18nExtension
 *
 * @package PhpMyAdmin\Twig
 */
class I18nExtension extends Twig_Extensions_Extension_I18n
{
    /**
     * Returns the token parser instances to add to the existing list.
     *
     * @return Twig_TokenParserInterface[]
     */
    public function getTokenParsers()
    {
        return array(new TokenParserTrans());
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return Twig_SimpleFilter[]
     */
    public function getFilters()
    {
        return array(
             new Twig_SimpleFilter('trans', '_gettext'),
        );
    }
}
