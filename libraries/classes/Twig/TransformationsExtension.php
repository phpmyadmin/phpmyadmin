<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\TransformationsExtension class
 *
 * @package PhpMyAdmin\Twig
 */
declare(strict_types=1);

namespace PhpMyAdmin\Twig;

use PhpMyAdmin\Transformations;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class TransformationsExtension
 *
 * @package PhpMyAdmin\Twig
 */
class TransformationsExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        $transformations = new Transformations();
        return [
            new TwigFunction(
                'get_description',
                [
                    $transformations,
                    'getDescription',
                ]
            ),
            new TwigFunction(
                'get_name',
                [
                    $transformations,
                    'getName',
                ]
            ),
        ];
    }
}
