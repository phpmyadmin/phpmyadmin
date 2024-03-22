<?php

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Theme\ThemeManager;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class AssetExtension extends AbstractExtension
{
    private ThemeManager|null $themeManager = null;

    /** @return TwigFunction[] */
    public function getFunctions(): array
    {
        return [new TwigFunction('image', $this->getImagePath(...))];
    }

    public function getImagePath(string|null $filename = null, string|null $fallback = null): string
    {
        if ($this->themeManager === null) {
            $themeManager = ContainerBuilder::getContainer()->get(ThemeManager::class);
            if (! $themeManager instanceof ThemeManager) {
                return '';
            }

            $this->themeManager = $themeManager;
        }

        return $this->themeManager->theme->getImgPath($filename, $fallback);
    }
}
