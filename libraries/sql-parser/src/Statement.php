<?php

/**
 * The result of the parser is an array of statements are extensions of the
 * class defined here.
 *
 * A statement represents the result of parsing the lexemes.
 *
 * @package SqlParser.
 */
namespace SqlParser;

use SqlParser\Parser;
use SqlParser\Statement;
use SqlParser\Token;
use SqlParser\Fragments\OptionsFragment;

/**
 * Abstract statement definition.
 *
 * @category Statements
 * @package  SqlParser
 * @author   Dan Ungureanu <udan1107@gmail.com>
 * @license  http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
abstract class Statement
{

    /**
     * The clauses of this statement, in order.
     *
     * The value attributed to each clause is used by the builder and it may
     * have one of the following values:
     *
     *     - 1 = 01 - add the clause only
     *     - 2 = 10 - add the keyword
     *     - 3 = 11 - add both the keyword and the clause
     *
     * @var array
     */
    public static $CLAUSES;

    /**
     * The options of this query.
     *
     * @var OptionsFragment
     *
     * @see static::$OPTIONS
     */
    public $options;

    /**
     * The index of the first token used in this statement.
     *
     * @var int
     */
    public $first;

    /**
     * The index of the last token used in this statement.
     *
     * @var int
     */
    public $last;

    /**
     * Parses the statements defined by the tokens list.
     *
     * @param Parser     $parser The instance that requests parsing.
     * @param TokensList $list   The list of tokens to be parsed.
     *
     * @return void
     */
    public function parse(Parser $parser, TokensList $list)
    {
        /**
         * Whether options were parsed or not.
         * For statements that do not have any options this is set to `true` by
         * default.
         * @var bool
         */
        $parsedOptions = isset(static::$OPTIONS) ? false : true;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             * @var Token
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Only keywords are relevant here. Other parts of the query are
            // processed in the functions below.
            if ($token->type !== Token::TYPE_KEYWORD) {
                continue;
            }

            // Unions are parsed by the parser because they represent more than
            // one statement.
            if ($token->value === 'UNION') {
                break;
            }

            /**
             * The name of the class that is used for parsing.
             * @var string
             */
            $class = null;

            /**
             * The name of the field where the result of the parsing is stored.
             * @var string
             */
            $field = null;

            /**
             * Parser's options.
             * @var array
             */
            $options = array();

            if (!empty(Parser::$KEYWORD_PARSERS[$token->value])) {
                $class = Parser::$KEYWORD_PARSERS[$token->value]['class'];
                $field = Parser::$KEYWORD_PARSERS[$token->value]['field'];
                if (!empty(Parser::$KEYWORD_PARSERS[$token->value]['options'])) {
                    $options = Parser::$KEYWORD_PARSERS[$token->value]['options'];
                }
            }

            if (!empty(Parser::$STATEMENT_PARSERS[$token->value])) {
                if (!$parsedOptions) {
                    ++$list->idx; // Skipping keyword.
                    $this->options = OptionsFragment::parse(
                        $parser,
                        $list,
                        static::$OPTIONS
                    );
                    $parsedOptions = true;
                }
            } elseif ($class === null) {
                // There is no parser for this keyword and isn't the beggining
                // of a statement (so no options) either.
                $parser->error(
                    'Unrecognized keyword "' . $token->value . '".',
                    $token
                );
                continue;
            }

            $this->before($parser, $list, $token);

            // Parsing this keyword.
            if ($class !== null) {
                ++$list->idx; // Skipping keyword.
                $this->$field = $class::parse($parser, $list, $options);
            }

            $this->after($parser, $list, $token);
        }

        --$list->idx; // Go back to last used token.
    }

    /**
     * Function called before the token is processed.
     *
     * @param Parser     $parser The instance that requests parsing.
     * @param TokensList $list   The list of tokens to be parsed.
     * @param Token      $token  The token that is being parsed.
     *
     * @return void
     */
    public function before(Parser $parser, TokensList $list, Token $token)
    {

    }

    /**
     * Function called after the token was processed.
     *
     * @param Parser     $parser The instance that requests parsing.
     * @param TokensList $list   The list of tokens to be parsed.
     * @param Token      $token  The token that is being parsed.
     *
     * @return void
     */
    public function after(Parser $parser, TokensList $list, Token $token)
    {

    }
}
