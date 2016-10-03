<?php

/**
 * `INSERT` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;
use SqlParser\Statement;
use SqlParser\Statements\SelectStatement;
use SqlParser\Components\IntoKeyword;
use SqlParser\Components\Array2d;
use SqlParser\Components\OptionsArray;
use SqlParser\Components\SetOperation;

/**
 * `INSERT` statement.
 *
 * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
 *     [INTO] tbl_name
 *     [PARTITION (partition_name,...)]
 *     [(col_name,...)]
 *     {VALUES | VALUE} ({expr | DEFAULT},...),(...),...
 *     [ ON DUPLICATE KEY UPDATE
 *       col_name=expr
 *         [, col_name=expr] ... ]
 *
 * or
 *
 * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
 *     [INTO] tbl_name
 *     [PARTITION (partition_name,...)]
 *     SET col_name={expr | DEFAULT}, ...
 *     [ ON DUPLICATE KEY UPDATE
 *       col_name=expr
 *         [, col_name=expr] ... ]
 *
 * or
 *
 * INSERT [LOW_PRIORITY | HIGH_PRIORITY] [IGNORE]
 *     [INTO] tbl_name
 *     [PARTITION (partition_name,...)]
 *     [(col_name,...)]
 *     SELECT ...
 *     [ ON DUPLICATE KEY UPDATE
 *       col_name=expr
 *         [, col_name=expr] ... ]
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class InsertStatement extends Statement
{

    /**
     * Options for `INSERT` statements.
     *
     * @var array
     */
    public static $OPTIONS = array(
        'LOW_PRIORITY'                  => 1,
        'DELAYED'                       => 2,
        'HIGH_PRIORITY'                 => 3,
        'IGNORE'                        => 4,
    );

    /**
     * Tables used as target for this statement.
     *
     * @var IntoKeyword
     */
    public $into;

    /**
     * Values to be inserted.
     *
     * @var Array2d
     */
    public $values;

    /**
     * If SET clause is present
     * holds the SetOperation
     *
     * @var SetOperation[]
     */
    public $set;

    /**
     * If SELECT clause is present
     * holds the SelectStatement
     *
     * @var SelectStatement
     */
    public $select;

    /**
     * If ON DUPLICATE KEY UPDATE clause is present
     * holds the SetOperation
     *
     * @var SetOperation[]
     */
    public $onDuplicateSet;

    /**
     * @return string
     */
    public function build()
    {
        $ret = 'INSERT ' . $this->options
            . ' INTO ' . $this->into;

        if ($this->values != NULL && count($this->values) > 0) {
            $ret .= ' VALUES ' . Array2d::build($this->values);
        } elseif ($this->set != NULL && count($this->set) > 0) {
            $ret .= ' SET ' . SetOperation::build($this->set);
        } elseif ($this->select != NULL && count($this->select) > 0) {
            $ret .= ' ' . $this->select->build();
        }

        if ($this->onDuplicateSet != NULL && count($this->onDuplicateSet) > 0) {
            $ret .= ' ON DUPLICATE KEY UPDATE ' . SetOperation::build($this->onDuplicateSet);
        }

        return $ret;

    }


    /**
     * @param Parser     $parser The instance that requests parsing.
     * @param TokensList $list   The list of tokens to be parsed.
     *
     * @return void
     */
    public function parse(Parser $parser, TokensList $list)
    {
        ++$list->idx; // Skipping `INSERT`.

        // parse any options if provided
        $this->options = OptionsArray::parse(
            $parser,
            $list,
            static::$OPTIONS
        );
        ++$list->idx;

        /**
         * The state of the parser.
         *
         * Below are the states of the parser.
         *
         *      0 ---------------------------------[ INTO ]----------------------------------> 1
         *
         *      1 -------------------------[ VALUES/VALUE/SET/SELECT ]-----------------------> 2
         *
         *      2 -------------------------[ ON DUPLICATE KEY UPDATE ]-----------------------> 3
         *
         * @var int $state
         */
        $state = 0;

        /**
         * For keeping track of semi-states on encountering
         * ON DUPLICATE KEY UPDATE ...
         *
         */
        $miniState = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             *
             * @var Token $token
             */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($state === 0) {
                if ($token->type === Token::TYPE_KEYWORD
                    && $token->value !== 'INTO'
                ) {
                    $parser->error(__('Unexpected keyword.'), $token);
                    break;
                } else {
                    ++$list->idx;
                    $this->into = IntoKeyword::parse(
                        $parser,
                        $list,
                        array('fromInsert' => true)
                    );
                }

                $state = 1;
            } elseif ($state === 1) {
                if ($token->type === Token::TYPE_KEYWORD) {
                    if ($token->value === 'VALUE'
                        || $token->value === 'VALUES'
                    ) {
                        ++$list->idx; // skip VALUES

                        $this->values = Array2d::parse($parser, $list);
                    } elseif ($token->value === 'SET') {
                        ++$list->idx; // skip SET

                        $this->set = SetOperation::parse($parser, $list);
                    } elseif ($token->value === 'SELECT') {
                        $this->select = new SelectStatement($parser, $list);
                    } else {
                        $parser->error(
                            __('Unexpected keyword.'),
                            $token
                        );
                        break;
                    }
                    $state = 2;
                    $miniState = 1;
                } else {
                    $parser->error(
                        __('Unexpected token.'),
                        $token
                    );
                    break;
                }
            } elseif ($state == 2) {
                $lastCount = $miniState;

                if ($miniState === 1 && $token->value === 'ON') {
                    $miniState++;
                } elseif ($miniState === 2 && $token->value === 'DUPLICATE') {
                    $miniState++;
                } elseif ($miniState === 3 && $token->value === 'KEY') {
                    $miniState++;
                } elseif ($miniState === 4 && $token->value === 'UPDATE') {
                    $miniState++;
                }

                if ($lastCount === $miniState) {
                    $parser->error(
                        __('Unexpected token.'),
                        $token
                    );
                    break;
                }

                if ($miniState === 5) {
                    ++$list->idx;
                    $this->onDuplicateSet = SetOperation::parse($parser, $list);
                    $state = 3;
                }
            }
        }

        --$list->idx;
    }
}
