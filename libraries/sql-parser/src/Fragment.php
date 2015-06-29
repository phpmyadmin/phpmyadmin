<?php

/**
 * Defines a fragment that is later extended to parse fragments or keywords.
 *
 * There is a small difference between *Fragment and *Keyword classes: usually,
 * *Fragment parsers can be reused in multiple  situations and *Keyword parsers
 * count on the *Fragment classes to do their job.
 *
 * @package SqlParser
 */
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
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        return null;
    }

    /**
     * Builds the string representation of a fragment of this type.
     *
     * @param Fragment $fragment The fragment to be built.
     *
     * @return string
     */
    public static function build(Fragment $fragment)
    {
        return null;
    }
}
