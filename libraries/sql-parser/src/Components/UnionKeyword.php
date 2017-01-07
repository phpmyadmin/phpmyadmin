<?php

/**
 * `UNION` keyword builder.
 */

namespace SqlParser\Components;

use SqlParser\Component;
use SqlParser\Statements\SelectStatement;

/**
 * `UNION` keyword builder.
 *
 * @category   Keywords
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class UnionKeyword extends Component
{
    /**
     * @param SelectStatement[] $component the component to be built
     * @param array             $options   parameters for building
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
