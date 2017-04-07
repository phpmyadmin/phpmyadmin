<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\libraries\twig\I18nExtension class
 *
 * @package PMA\libraries\twig
 */
namespace PMA\libraries\twig;

use Twig_Extensions_Extension_I18n;
use Twig_SimpleFilter;

/**
 * Class I18nExtension
 *
 * @package PMA\libraries\twig
 */
class I18nExtension extends Twig_Extensions_Extension_I18n
{
    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return array(new i18n\TokenParserTrans());
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return array(
             new Twig_SimpleFilter('trans', '_gettext'),
        );
    }
}
