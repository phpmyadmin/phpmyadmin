<?php

/**
 * `UNION` keyword builder.
 *
 * @package    SqlParser
 * @subpackage Components
 */
namespace SqlParser\Components;

use SqlParser\Component;
use SqlParser\Statements\SelectStatement;

/**
 * `UNION` keyword builder.
 *
 * @category   Keywords
 * @package    SqlParser
 * @subpackage Components
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class UnionKeyword extends Component
{

    /**
     * @param SelectStatement[] $component The component to be built.
     * @param array             $options   Parameters for building.
     *
     * @return string
     */
    public static function build($component, array $options = array())
    {
        $tmp = array();
        foreach ($component as $component) {
            $tmp[] = $component[0] . ' ' . $component[1];
        }
        return implode(' ', $tmp);
    }
}
