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
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
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
        return implode(' UNION ', $component);
    }
}
