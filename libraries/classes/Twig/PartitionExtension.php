<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PhpMyAdmin\Twig\PartitionExtension class
 *
 * @package PhpMyAdmin\Twig
 */
namespace PhpMyAdmin\Twig;

use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Class PartitionExtension
 *
 * @package PhpMyAdmin\Twig
 */
class PartitionExtension extends Twig_Extension
{
    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return array(
            new Twig_SimpleFunction(
                'Partition_getPartitions',
                'PhpMyAdmin\Partition::getPartitions',
                array('is_safe' => array('html'))
            ),
        );
    }
}
