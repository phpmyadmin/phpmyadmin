<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\CoreExtension class
 *
 * @package PhpMyAdmin\Twig
 */
declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class CoreExtension
 *
 * @package PhpMyAdmin\Twig
 */
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
