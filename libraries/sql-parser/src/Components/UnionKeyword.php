<?php

/**
 * `UNION` keyword builder.
 *
 * @package    SqlParser
 * @subpackage Components
 */
namespace SqlParser\Components;

use SqlParser\Component;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

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
     *
     * @return string
     */
    public static function build($component)
    {
        $ret = array();
        foreach ($component as $c) {
            $ret[] = $c->build();
        }
        return implode(" UNION ", $ret);
    }
}
