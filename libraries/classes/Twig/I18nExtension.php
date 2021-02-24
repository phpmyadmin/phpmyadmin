<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Twig\Extensions\I18nExtension as TwigI18nExtension;
use PhpMyAdmin\Twig\I18n\TokenParserTrans;
use Twig\TokenParser\TokenParserInterface;
use Twig\TwigFilter;

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
