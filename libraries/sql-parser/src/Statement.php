<?php

namespace SqlParser;

use SqlParser\Parser;
use SqlParser\Statement;
use SqlParser\Token;
use SqlParser\Fragments\CallKeyword;
use SqlParser\Fragments\CreateDefFragment;
use SqlParser\Fragments\DataTypeFragment;
use SqlParser\Fragments\FieldDefFragment;
use SqlParser\Fragments\FromKeyword;
use SqlParser\Fragments\OptionsFragment;
use SqlParser\Fragments\ParamDefFragment;
use SqlParser\Fragments\RenameKeyword;
use SqlParser\Fragments\SelectKeyword;

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
         * Whether otpions were parsed or not.
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

            if (!empty(Parser::$KEYWORD_PARSERS[$token->value])) {
                $class = Parser::$KEYWORD_PARSERS[$token->value]['class'];
                $field = Parser::$KEYWORD_PARSERS[$token->value]['field'];
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
            } else if ($class === null) {
                // There is no parser for this keyword and isn't the beggining
                // of a statement (so no options) either.
                $parser->error(
                    'Unrecognized keyword "' . $token->value . '".',
                    $token
                );
                continue;
            }

            // Special cases.
            if ($token->value === 'CREATE') {
                ++$list->idx;
                $this->name = CreateDefFragment::parse($parser, $list);
                if ($this->options->has('TABLE')) {
                    ++$list->idx;
                    $this->fields = FieldDefFragment::parse($parser, $list);
                    ++$list->idx;
                    $this->tableOptions = OptionsFragment::parse(
                        $parser,
                        $list,
                        CreateDefFragment::$TABLE_OPTIONS
                    );
                } elseif (($this->options->has('PROCEDURE'))
                    || ($this->options->has('FUNCTION'))
                ) {
                    ++$list->idx;
                    $this->parameters = ParamDefFragment::parse($parser, $list);
                    if ($this->options->has('FUNCTION')) {
                        $token = $list->getNextOfType(Token::TYPE_KEYWORD);
                        if ($token->value !== 'RETURNS') {
                            $parser->error(
                                '\'RETURNS\' keyword was expected.',
                                $token
                            );
                        } else {
                            ++$list->idx;
                            $this->return = DataTypeFragment::parse(
                                $parser,
                                $list
                            );
                        }
                    }
                    ++$list->idx;
                    $this->funcOptions = OptionsFragment::parse(
                        $parser,
                        $list,
                        CreateDefFragment::$FUNC_OPTIONS
                    );
                    ++$list->idx;
                    $this->body = array();
                    for (; $list->idx < $list->count; ++$list->idx) {
                        $token = $list->tokens[$list->idx];
                        $this->body[] = $token;
                        if (($token->type === Token::TYPE_KEYWORD)
                            && ($token->value === 'END')
                        ) {
                            break;
                        }
                    }
                    $class = null; // The statement has been processed here.
                }
            } else if ($token->value === 'RENAME') {
                $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'TABLE');
            }

            if ($class !== null) {
                ++$list->idx; // Skipping keyword.
                $this->$field = $class::parse($parser, $list, array());
            }
        }

        $this->last = --$list->idx; // Go back to last used token.
    }
}
