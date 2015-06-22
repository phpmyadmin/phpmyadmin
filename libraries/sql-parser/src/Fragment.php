<?php

namespace SqlParser;

/**
 * A fragment (of a statement) is a part of a statement that is common to
 * multiple query types.
 *
 * @category Fragments
 * @package  SqlParser
 * @author   Dan Ungureanu <udan1107@gmail.com>
 * @license  http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
abstract class Fragment
{

    /**
     * Parses the tokens contained in the given list in the context of the given
     * parser.
     *
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return mixed
     */
    abstract public static function parse(Parser $parser, TokensList $list,
        array $options = array()
    );
}
