<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Twig\Node\Expression\TransExpression;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class I18nExtension extends AbstractExtension
{
    /** @inheritdoc */
    public function getFunctions(): array
    {
        return [new TwigFunction('t', null, ['node_class' => TransExpression::class])];
    }
}
