<?php

namespace SqlParser;

/**
 * A fragment (of a statement) is a part of a statement that is common to
 * multiple query types.
 */
abstract class Fragment
{

    /**
     * Array which contains all tokens used to popoluate data inside this
     * fragment.
     *
     * @var array
     */
    public $tokens = array();

    /**
     * Parses the tokens given by the lexer in the context of the given parser.
     *
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return array
     */
    abstract public static function parse(Parser $parser, TokensList $list, array $options = array());
}
