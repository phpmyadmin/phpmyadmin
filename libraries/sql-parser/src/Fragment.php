<?php

namespace SqlParser;

/**
 * A fragment (of a statement) is a part of a statement that is common to
 * multiple query types.
 */
abstract class Fragment
{

    /**
     * Parses the tokens given by the lexer in the context of the given parser.
     *
     * @param Parser $parser The parser that serves as context.
     * @param TokensList $list The list of tokens that are being parsed.
     * @param array $options Parameters for parsing.
     *
     * @return array
     */
    abstract public static function parse(Parser $parser, TokensList $list, array $options = array());
}
