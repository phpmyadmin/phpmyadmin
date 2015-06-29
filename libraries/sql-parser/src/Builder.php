<?php

/**
 * This is one of the most important components, along with the lexer and the
 * parser.
 *
 * @package SqlParser
 */
namespace SqlParser;

use SqlParser\Exceptions\ParserException;

/**
 * Builds the string representation of a Statement.
 *
 * @category Parser
 * @package  SqlParser
 * @author   Dan Ungureanu <udan1107@gmail.com>
 * @license  http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Builder
{

    /**
     * Statement to be build.
     *
     * @var Statement
     */
    public $statement;

    /**
     * Built query.
     *
     * @var string
     */
    public $query;

    /**
     * Constructor.
     *
     * @param Statement $statement
     */
    public function __construct($statement = null)
    {
        $this->statement = $statement;

        if ($this->statement != null) {
            $this->build();
        }
    }

    /**
     * Builds the statement.
     *
     * @return void
     */
    public function build()
    {
        $statement = $this->statement;
        foreach ($statement::$CLAUSES as $clause) {
            $name = $clause[0];
            $type = $clause[1];

            if (empty(Parser::$KEYWORD_PARSERS[$name])) {
                continue;
            }

            $class = Parser::$KEYWORD_PARSERS[$name]['class'];
            $field = Parser::$KEYWORD_PARSERS[$name]['field'];

            if (empty($statement->$field)) {
                continue;
            }

            if ($type & 2) {
                $this->query .= $name . ' ';
            }

            if ($type & 1) {
                $this->query .= $class::build($statement->$field) . ' ';
            }
        }
    }
}
