<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Twig\Extensions\I18nExtension as TwigI18nExtension;
use PhpMyAdmin\Twig\Extensions\Node\TransNode;
use PhpMyAdmin\Twig\Node\Expression\TransExpression;
use Twig\TwigFilter;
use Twig\TwigFunction;

use function _gettext;

class I18nExtension extends TwigI18nExtension
{
    public function __construct()
    {
        TransNode::$notesLabel = '// l10n: ';
        TransNode::$enableMoTranslator = true;
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            // This is just a performance override
            new TwigFilter('trans', _gettext(...)),
        ];
    }

    /** @inheritdoc */
    public function getFunctions(): array
    {
        return [new TwigFunction('t', null, ['node_class' => TransExpression::class])];
    }
}
