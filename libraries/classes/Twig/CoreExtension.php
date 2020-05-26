<?php
/**
 * hold PhpMyAdmin\Twig\CoreExtension class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CoreExtension extends AbstractExtension
{
    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return TwigFilter[]
     */
    public function getFilters()
    {
        return [
            new TwigFilter(
                'mime_default_function',
                'PhpMyAdmin\Core::mimeDefaultFunction',
                ['is_safe' => ['html']]
            ),
            new TwigFilter(
                'link',
                'PhpMyAdmin\Core::linkURL'
            ),
        ];
    }
}
