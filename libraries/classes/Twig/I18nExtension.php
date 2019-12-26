<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\I18nExtension class
 *
 * @package PhpMyAdmin\Twig
 */
declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Twig\I18n\TokenParserTrans;
use Twig\Extensions\I18nExtension as TwigI18nExtension;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;

/**
 * Class I18nExtension
 *
 * @package PhpMyAdmin\Twig
 */
class I18nExtension extends TwigI18nExtension
{
    /**
     * Returns the token parser instances to add to the existing list.
     *
     * @return TokenParserInterface[]
     */
    public function getTokenParsers()
    {
        return [new TokenParserTrans()];
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter('trans', '_gettext'),
        ];
    }
}
