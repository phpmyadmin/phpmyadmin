<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Theme;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AssetExtension extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('image', [$this, 'getImagePath']),
        ];
    }

    public function getImagePath(?string $filename = null, ?string $fallback = null): string
    {
        global $PMA_Theme;

        if (! $PMA_Theme instanceof Theme) {
            return '';
        }

        return $PMA_Theme->getImgPath($filename, $fallback);
    }
}
