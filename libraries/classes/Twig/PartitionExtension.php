<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\PartitionExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class PartitionExtension
 *
 * @package PhpMyAdmin\Twig
 */
class PartitionExtension extends AbstractExtension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return array(
            new TwigFunction(
                'Partition_getPartitions',
                'PhpMyAdmin\Partition::getPartitions',
                array('is_safe' => array('html'))
            ),
        );
    }
}
