<?php

/**
 * `CREATE` statement.
 *
 * @package    SqlParser
 * @subpackage Statements
 */
namespace SqlParser\Statements;

use SqlParser\Parser;
use SqlParser\Statement;
use SqlParser\Token;
use SqlParser\TokensList;
use SqlParser\Components\ArrayObj;
use SqlParser\Components\DataType;
use SqlParser\Components\CreateDefinition;
use SqlParser\Components\PartitionDefinition;
use SqlParser\Components\Expression;
use SqlParser\Components\OptionsArray;
use SqlParser\Components\ParameterDefinition;
use SqlParser\Statements\SelectStatement;

/**
 * `CREATE` statement.
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class CreateStatement extends Statement
{

    /**
     * Options for `CREATE` statements.
     *
     * @var array
     */
    public static $OPTIONS = array(

        // CREATE TABLE
        'TEMPORARY'                     => 1,

        // CREATE VIEW
        'OR REPLACE'                    => array(2, 'var='),
        'ALGORITHM'                     => array(3, 'var='),
        // `DEFINER` is also used for `CREATE FUNCTION / PROCEDURE`
        'DEFINER'                       => array(4, 'expr='),
        'SQL SECURITY'                  => array(5, 'var'),

        'DATABASE'                      => 6,
        'EVENT'                         => 6,
        'FUNCTION'                      => 6,
        'INDEX'                         => 6,
        'UNIQUE INDEX'                  => 6,
        'FULLTEXT INDEX'                => 6,
        'SPATIAL INDEX'                 => 6,
        'PROCEDURE'                     => 6,
        'SERVER'                        => 6,
        'TABLE'                         => 6,
        'TABLESPACE'                    => 6,
        'TRIGGER'                       => 6,
        'USER'                          => 6,
        'VIEW'                          => 6,

        // CREATE TABLE
        'IF NOT EXISTS'                 => 7,
    );

    /**
     * All database options.
     *
     * @var array
     */
    public static $DB_OPTIONS = array(
        'CHARACTER SET'                 => array(1, 'var='),
        'CHARSET'                       => array(1, 'var='),
        'DEFAULT CHARACTER SET'         => array(1, 'var='),
        'DEFAULT CHARSET'               => array(1, 'var='),
        'DEFAULT COLLATE'               => array(2, 'var='),
        'COLLATE'                       => array(2, 'var='),
    );

    /**
     * All table options.
     *
     * @var array
     */
    public static $TABLE_OPTIONS = array(
        'ENGINE'                        => array(1, 'var='),
        'AUTO_INCREMENT'                => array(2, 'var='),
        'AVG_ROW_LENGTH'                => array(3, 'var'),
        'CHARACTER SET'                 => array(4, 'var='),
        'CHARSET'                       => array(4, 'var='),
        'DEFAULT CHARACTER SET'         => array(4, 'var='),
        'DEFAULT CHARSET'               => array(4, 'var='),
        'CHECKSUM'                      => array(5, 'var'),
        'DEFAULT COLLATE'               => array(6, 'var='),
        'COLLATE'                       => array(6, 'var='),
        'COMMENT'                       => array(7, 'var='),
        'CONNECTION'                    => array(8, 'var'),
        'DATA DIRECTORY'                => array(9, 'var'),
        'DELAY_KEY_WRITE'               => array(10, 'var'),
        'INDEX DIRECTORY'               => array(11, 'var'),
        'INSERT_METHOD'                 => array(12, 'var'),
        'KEY_BLOCK_SIZE'                => array(13, 'var'),
        'MAX_ROWS'                      => array(14, 'var'),
        'MIN_ROWS'                      => array(15, 'var'),
        'PACK_KEYS'                     => array(16, 'var'),
        'PASSWORD'                      => array(17, 'var'),
        'ROW_FORMAT'                    => array(18, 'var'),
        'TABLESPACE'                    => array(19, 'var'),
        'STORAGE'                       => array(20, 'var'),
        'UNION'                         => array(21, 'var'),
    );

    /**
     * All function options.
     *
     * @var array
     */
    public static $FUNC_OPTIONS = array(
        'COMMENT'                       => array(1, 'var='),
        'LANGUAGE SQL'                  => 2,
        'DETERMINISTIC'                 => 3,
        'NOT DETERMINISTIC'             => 3,
        'CONTAINS SQL'                  => 4,
        'NO SQL'                        => 4,
        'READS SQL DATA'                => 4,
        'MODIFIES SQL DATA'             => 4,
        'SQL SECURITY DEFINER'          => array(5, 'var'),
    );

    /**
     * All trigger options.
     *
     * @var array
     */
    public static $TRIGGER_OPTIONS = array(
        'BEFORE'                        => 1,
        'AFTER'                         => 1,
        'INSERT'                        => 2,
        'UPDATE'                        => 2,
        'DELETE'                        => 2,
    );

    /**
     * The name of the entity that is created.
     *
     * Used by all `CREATE` statements.
     *
     * @var Expression
     */
    public $name;

    /**
     * The options of the entity (table, procedure, function, etc.).
     *
     * Used by `CREATE TABLE`, `CREATE FUNCTION` and `CREATE PROCEDURE`.
     *
     * @var OptionsArray
     *
     * @see static::$TABLE_OPTIONS
     * @see static::$FUNC_OPTIONS
     * @see static::$TRIGGER_OPTIONS
     */
    public $entityOptions;

    /**
     * If `CREATE TABLE`, a list of columns and keys.
     * If `CREATE VIEW`, a list of columns.
     *
     * Used by `CREATE TABLE` and `CREATE VIEW`.
     *
     * @var CreateDefinition[]|ArrayObj
     */
    public $fields;

    /**
     * If `CREATE TABLE ... SELECT`
     *
     * Used by `CREATE TABLE`
     *
     * @var SelectStatement
     */
    public $select;

    /**
     * If `CREATE TABLE ... LIKE`
     *
     * Used by `CREATE TABLE`
     *
     * @var Expression
     */
    public $like;

    /**
     * Expression used for partitioning.
     *
     * @var string
     */
    public $partitionBy;

    /**
     * The number of partitions.
     *
     * @var int
     */
    public $partitionsNum;

    /**
     * Expression used for subpartitioning.
     *
     * @var string
     */
    public $subpartitionBy;

    /**
     * The number of subpartitions.
     *
     * @var int
     */
    public $subpartitionsNum;

    /**
     * The partition of the new table.
     *
     * @var PartitionDefinition[]
     */
    public $partitions;

    /**
     * If `CREATE TRIGGER` the name of the table.
     *
     * Used by `CREATE TRIGGER`.
     *
     * @var Expression
     */
    public $table;

    /**
     * The return data type of this routine.
     *
     * Used by `CREATE FUNCTION`.
     *
     * @var DataType
     */
    public $return;

    /**
     * The parameters of this routine.
     *
     * Used by `CREATE FUNCTION` and `CREATE PROCEDURE`.
     *
     * @var ParameterDefinition[]
     */
    public $parameters;

    /**
     * The body of this function or procedure. For views, it is the select
     * statement that gets the
     *
     * Used by `CREATE FUNCTION`, `CREATE PROCEDURE` and `CREATE VIEW`.
     *
     * @var Token[]|string
     */
    public $body = array();

    /**
     * @return string
     */
    public function build()
    {
        $fields = '';
        if (!empty($this->fields)) {
            if (is_array($this->fields)) {
                $fields = CreateDefinition::build($this->fields) . ' ';
            } elseif ($this->fields instanceof ArrayObj) {
                $fields = ArrayObj::build($this->fields);
            }
        }
        if ($this->options->has('DATABASE')) {
            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . OptionsArray::build($this->entityOptions);
        } elseif ($this->options->has('TABLE') && !is_null($this->select)) {
            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . $this->select->build();
        } elseif ($this->options->has('TABLE') && !is_null($this->like)) {
            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' LIKE '
                . Expression::build($this->like);
        } elseif ($this->options->has('TABLE')) {
            $partition = '';

            if (!empty($this->partitionBy)) {
                $partition .= "\nPARTITION BY " . $this->partitionBy;
            }
            if (!empty($this->partitionsNum)) {
                $partition .= "\nPARTITIONS " . $this->partitionsNum;
            }
            if (!empty($this->subpartitionBy)) {
                $partition .= "\nSUBPARTITION BY " . $this->subpartitionBy;
            }
            if (!empty($this->subpartitionsNum)) {
                $partition .= "\nSUBPARTITIONS " . $this->subpartitionsNum;
            }
            if (!empty($this->partitions)) {
                $partition .= "\n" . PartitionDefinition::build($this->partitions);
            }

            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . $fields
                . OptionsArray::build($this->entityOptions)
                . $partition;
        } elseif ($this->options->has('VIEW')) {
            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . $fields . ' AS ' . TokensList::build($this->body) . ' '
                . OptionsArray::build($this->entityOptions);
        } elseif ($this->options->has('TRIGGER')) {
            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . OptionsArray::build($this->entityOptions) . ' '
                . 'ON ' . Expression::build($this->table) . ' '
                . 'FOR EACH ROW ' . TokensList::build($this->body);
        } elseif (($this->options->has('PROCEDURE'))
            || ($this->options->has('FUNCTION'))
        ) {
            $tmp = '';
            if ($this->options->has('FUNCTION')) {
                $tmp = 'RETURNS ' . DataType::build($this->return);
            }
            return 'CREATE '
                . OptionsArray::build($this->options) . ' '
                . Expression::build($this->name) . ' '
                . ParameterDefinition::build($this->parameters) . ' '
                . $tmp . ' ' . TokensList::build($this->body);
        }
        return 'CREATE '
            . OptionsArray::build($this->options) . ' '
            . Expression::build($this->name) . ' '
            . TokensList::build($this->body);
    }

    /**
     * @param Parser     $parser The instance that requests parsing.
     * @param TokensList $list   The list of tokens to be parsed.
     *
     * @return void
     */
    public function parse(Parser $parser, TokensList $list)
    {
        ++$list->idx; // Skipping `CREATE`.

        // Parsing options.
        $this->options = OptionsArray::parse($parser, $list, static::$OPTIONS);
        ++$list->idx; // Skipping last option.

        // Parsing the field name.
        $this->name = Expression::parse(
            $parser,
            $list,
            array(
                'parseField' => 'table',
                'breakOnAlias' => true,
            )
        );

        if ((!isset($this->name)) || ($this->name === '')) {
            $parser->error(
                __('The name of the entity was expected.'),
                $list->tokens[$list->idx]
            );
        } else {
            ++$list->idx; // Skipping field.
        }

        /**
         * Token parsed at this moment.
         *
         * @var Token $token
         */
        $token = $list->tokens[$list->idx];
        $nextidx = $list->idx + 1;
        while ($nextidx < $list->count && $list->tokens[$nextidx]->type == Token::TYPE_WHITESPACE) {
            $nextidx++;
        }

        if ($this->options->has('DATABASE')) {
            $this->entityOptions = OptionsArray::parse(
                $parser,
                $list,
                static::$DB_OPTIONS
            );
        } elseif ($this->options->has('TABLE')
            && ($token->type == Token::TYPE_KEYWORD)
            && ($token->value == 'SELECT')
        ) {
            /* CREATE TABLE ... SELECT */
            $this->select = new SelectStatement($parser, $list);
        } elseif ($this->options->has('TABLE')
            && ($token->type == Token::TYPE_KEYWORD) && ($token->value == 'AS')
            && ($list->tokens[$nextidx]->type == Token::TYPE_KEYWORD)
            && ($list->tokens[$nextidx]->value == 'SELECT')
        ) {
            /* CREATE TABLE ... AS SELECT */
            $list->idx = $nextidx;
            $this->select = new SelectStatement($parser, $list);
        } elseif ($this->options->has('TABLE')
            && $token->type == Token::TYPE_KEYWORD
            && $token->value == 'LIKE'
        ) {
            /* CREATE TABLE `new_tbl` LIKE 'orig_tbl' */
            $list->idx = $nextidx;
            $this->like = Expression::parse(
                $parser,
                $list,
                array(
                    'parseField' => 'table',
                    'breakOnAlias' => true,
                )
            );
            // The 'LIKE' keyword was found, but no table_name was found next to it
            if ($this->like == null) {
                $parser->error(
                    __('A table name was expected.'),
                    $list->tokens[$list->idx]
                );
            }
        } elseif ($this->options->has('TABLE')) {
            $this->fields = CreateDefinition::parse($parser, $list);
            if (empty($this->fields)) {
                $parser->error(
                    __('At least one column definition was expected.'),
                    $list->tokens[$list->idx]
                );
            }
            ++$list->idx;

            $this->entityOptions = OptionsArray::parse(
                $parser,
                $list,
                static::$TABLE_OPTIONS
            );

            /**
             * The field that is being filled (`partitionBy` or
             * `subpartitionBy`).
             *
             * @var string $field
             */
            $field = null;

            /**
             * The number of brackets. `false` means no bracket was found
             * previously. At least one bracket is required to validate the
             * expression.
             *
             * @var int|bool $brackets
             */
            $brackets = false;

            /*
             * Handles partitions.
             */
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

                // Skipping comments.
                if ($token->type === Token::TYPE_COMMENT) {
                    continue;
                }

                if (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'PARTITION BY')) {
                    $field = 'partitionBy';
                    $brackets = false;
                } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'SUBPARTITION BY')) {
                    $field = 'subpartitionBy';
                    $brackets = false;
                } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'PARTITIONS')) {
                    $token = $list->getNextOfType(Token::TYPE_NUMBER);
                    --$list->idx; // `getNextOfType` also advances one position.
                    $this->partitionsNum = $token->value;
                } elseif (($token->type === Token::TYPE_KEYWORD) && ($token->value === 'SUBPARTITIONS')) {
                    $token = $list->getNextOfType(Token::TYPE_NUMBER);
                    --$list->idx; // `getNextOfType` also advances one position.
                    $this->subpartitionsNum = $token->value;
                } elseif (!empty($field)) {
                    /*
                     * Handling the content of `PARTITION BY` and `SUBPARTITION BY`.
                     */

                    // Counting brackets.
                    if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                        // This is used instead of `++$brackets` because,
                        // initially, `$brackets` is `false` cannot be
                        // incremented.
                        $brackets = $brackets + 1;
                    } elseif (($token->type === Token::TYPE_OPERATOR) && ($token->value === ')')) {
                        --$brackets;
                    }

                    // Building the expression used for partitioning.
                    $this->$field .= ($token->type === Token::TYPE_WHITESPACE) ? ' ' : $token->token;

                    // Last bracket was read, the expression ended.
                    // Comparing with `0` and not `false`, because `false` means
                    // that no bracket was found and at least one must is
                    // required.
                    if ($brackets === 0) {
                        $this->$field = trim($this->$field);
                        $field = null;
                    }
                } elseif (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                    if (!empty($this->partitionBy)) {
                        $this->partitions = ArrayObj::parse(
                            $parser,
                            $list,
                            array(
                                'type' => 'SqlParser\\Components\\PartitionDefinition'
                            )
                        );
                    }
                    break;
                }
            }
        } elseif (($this->options->has('PROCEDURE'))
            || ($this->options->has('FUNCTION'))
        ) {
            $this->parameters = ParameterDefinition::parse($parser, $list);
            if ($this->options->has('FUNCTION')) {
                $token = $list->getNextOfType(Token::TYPE_KEYWORD);
                if ($token->value !== 'RETURNS') {
                    $parser->error(
                        __('A "RETURNS" keyword was expected.'),
                        $token
                    );
                } else {
                    ++$list->idx;
                    $this->return = DataType::parse(
                        $parser,
                        $list
                    );
                }
            }
            ++$list->idx;

            $this->entityOptions = OptionsArray::parse(
                $parser,
                $list,
                static::$FUNC_OPTIONS
            );
            ++$list->idx;

            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                $this->body[] = $token;
            }
        } elseif ($this->options->has('VIEW')) {
            $token = $list->getNext(); // Skipping whitespaces and comments.

            // Parsing columns list.
            if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                --$list->idx; // getNext() also goes forward one field.
                $this->fields = ArrayObj::parse($parser, $list);
                ++$list->idx; // Skipping last token from the array.
                $list->getNext();
            }

            // Parsing the `AS` keyword.
            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                if ($token->type === Token::TYPE_DELIMITER) {
                    break;
                }
                $this->body[] = $token;
            }
        } elseif ($this->options->has('TRIGGER')) {
            // Parsing the time and the event.
            $this->entityOptions = OptionsArray::parse(
                $parser,
                $list,
                static::$TRIGGER_OPTIONS
            );
            ++$list->idx;

            $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'ON');
            ++$list->idx; // Skipping `ON`.

            // Parsing the name of the table.
            $this->table = Expression::parse(
                $parser,
                $list,
                array(
                    'parseField' => 'table',
                    'breakOnAlias' => true,
                )
            );
            ++$list->idx;

            $list->getNextOfTypeAndValue(Token::TYPE_KEYWORD, 'FOR EACH ROW');
            ++$list->idx; // Skipping `FOR EACH ROW`.

            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                $this->body[] = $token;
            }
        } else {
            for (; $list->idx < $list->count; ++$list->idx) {
                $token = $list->tokens[$list->idx];
                if ($token->type === Token::TYPE_DELIMITER) {
                    break;
                }
                $this->body[] = $token;
            }
        }
    }
}
