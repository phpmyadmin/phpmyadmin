<?php

namespace SqlParser;

use SqlParser\Lexer;
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
     * @param Parser $parser
     * @param TokensList $list
     */
    public function parse(Parser $parser, TokensList $list)
    {
        for (; $list->idx < $list->count; ++$list->idx) {
            /** @var Token Token parsed at this moment. */
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

            /** @var string The name of the class that is used for parsing. */
            $class = null;

            if (!empty(Parser::$KEYWORD_PARSERS[$token->value])) {
                $class = Parser::$KEYWORD_PARSERS[$token->value];
            } elseif (!empty(Parser::$STATEMENT_PARSERS[$token->value])) {
                // The keyword we are processing right now is the beginning of a
                // statement and they are usually handled differently.
            } else {
                $parser->error(
                    'Unrecognized keyword "' . $token->value . '".',
                    $token
                );
                continue;
            }

            /** @var string The name of the field where the result is stored. */
            $field = strtolower($token->value);

            // Parsing options.
            if (($class == null) && (isset(static::$OPTIONS))) {
                ++$list->idx; // Skipping keyword.
                $this->options = OptionsFragment::parse($parser, $list, static::$OPTIONS);
            }

            // Keyword specific code.
            if ($token->value === 'CALL') {
                ++$list->idx;
                $this->call = CallKeyword::parse($parser, $list);
            } elseif ($token->value === 'CREATE') {
                ++$list->idx;
                $this->name = CreateDefFragment::parse($parser, $list);
                if ($this->options->has('TABLE')) {
                    ++$list->idx;
                    $this->fields = FieldDefFragment::parse($parser, $list);
                    ++$list->idx;
                    $this->tableOptions = OptionsFragment::parse($parser, $list, CreateDefFragment::$TABLE_OPTIONS);
                } elseif (($this->options->has('PROCEDURE')) || ($this->options->has('FUNCTION'))) {
                    ++$list->idx;
                    $this->parameters = ParamDefFragment::parse($parser, $list);
                    if ($this->options->has('FUNCTION')) {
                        $token = $list->getNextOfType(Token::TYPE_KEYWORD);
                        if ($token->value !== 'RETURNS') {
                            $parser->error('\'RETURNS\' keyword was expected.', $token);
                        } else {
                            ++$list->idx;
                            $this->return = DataTypeFragment::parse($parser, $list);
                        }
                    }
                    ++$list->idx;
                    $this->funcOptions = OptionsFragment::parse($parser, $list, CreateDefFragment::$FUNC_OPTIONS);
                    ++$list->idx;
                    $this->body = array();
                    for (; $list->idx < $list->count; ++$list->idx) {
                        $token = $list->tokens[$list->idx];
                        $this->body[] = $token;
                        if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'END')) {
                            break;
                        }
                    }
                    $class = null; // The statement has been processed here.
                }
            } elseif (($token->value === 'GROUP') || ($token->value === 'ORDER')) {
                $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'BY');
            } elseif ($token->value === 'RENAME') {
                $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'TABLE');
                ++$list->idx;
                $this->renames = RenameKeyword::parse($parser, $list);
            } elseif ($token->value === 'SELECT') {
                ++$list->idx; // Skipping last option.
                $this->expr = SelectKeyword::parse($parser, $list);
            } elseif ($token->value === 'UPDATE') {
                ++$list->idx; // Skipping last option.
                $this->from = FromKeyword::parse($parser, $list);
            }

            // Finally, processing the keyword (if possible).
            if ($class !== null) {
                ++$list->idx; // Skipping keyword.
                $this->$field = $class::parse($parser, $list, array());
            }
        }

        $this->last = --$list->idx; // Go back to last used token.
    }
}
