<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Theme\Theme;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AssetExtension extends AbstractExtension
{
    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [new TwigFunction('image', $this->getImagePath(...))];
    }

    public function getImagePath(string|null $filename = null, string|null $fallback = null): string
    {
        $GLOBALS['theme'] ??= null;

        if (! $GLOBALS['theme'] instanceof Theme) {
            return '';
        }

        return $GLOBALS['theme']->getImgPath($filename, $fallback);
    }
}
