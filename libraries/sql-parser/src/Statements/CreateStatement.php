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
use SqlParser\Fragments\ArrayFragment;
use SqlParser\Fragments\DataTypeFragment;
use SqlParser\Fragments\FieldDefFragment;
use SqlParser\Fragments\FieldFragment;
use SqlParser\Fragments\OptionsFragment;
use SqlParser\Fragments\ParamDefFragment;

/**
 * `CREATE` statement.
 *
 * @category   Statements
 * @package    SqlParser
 * @subpackage Statements
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class CreateStatement extends Statement
{

    /**
     * Options for `CREATE` statements.
     *
     * @var array
     */
    public static $OPTIONS = array(

        'DATABASE'                      => 1,
        'EVENT'                         => 1,
        'FUNCTION'                      => 1,
        'INDEX'                         => 1,
        'PROCEDURE'                     => 1,
        'SERVER'                        => 1,
        'TABLE'                         => 1,
        'TABLESPACE'                    => 1,
        'TRIGGER'                       => 1,
        'USER'                          => 1,
        'VIEW'                          => 1,

        // CREATE TABLE
        'TEMPORARY'                     => 2,
        'IF NOT EXISTS'                 => 3,

        // CREATE FUNCTION / PROCEDURE and CREATE VIEW
        'DEFINER'                       => array(2, 'var'),

        // CREATE VIEW
        'OR REPLACE'                    => array(3, 'var'),
        'ALGORITHM'                     => array(4, 'var'),
        'DEFINER'                       => array(5, 'var'),
        'SQL SECURITY'                  => array(6, 'var'),
    );

    /**
     * All table options.
     *
     * @var array
     */
    public static $TABLE_OPTIONS = array(
        'ENGINE'                        => array(1, 'var'),
        'AUTO_INCREMENT'                => array(2, 'var'),
        'AVG_ROW_LENGTH'                => array(3, 'var'),
        'DEFAULT CHARACTER SET'         => array(4, 'var'),
        'CHARACTER SET'                 => array(4, 'var'),
        'CHECKSUM'                      => array(5, 'var'),
        'DEFAULT COLLATE'               => array(5, 'var'),
        'COLLATE'                       => array(6, 'var'),
        'COMMENT'                       => array(7, 'var'),
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
        'COMMENT'                      => array(1, 'var'),
        'LANGUAGE SQL'                 => 2,
        'DETERMINISTIC'                => 3,
        'NOT DETERMINISTIC'            => 3,
        'CONSTAINS SQL'                => 4,
        'NO SQL'                       => 4,
        'READS SQL DATA'               => 4,
        'MODIFIES SQL DATA'            => 4,
        'SQL SEQURITY DEFINER'         => array(5, 'var'),
    );

    /**
     * The name of the entity that is created.
     *
     * Used by all `CREATE` statements.
     *
     * @var FieldFragment
     */
    public $name;

    /**
     * The options of the entity (table, procedure, function, etc.).
     *
     * Used by `CREATE TABLE`, `CREATE FUNCTION` and `CREATE PROCEDURE`.
     *
     * @var OptionsFragment
     *
     * @see static::$TABLE_OPTIONS
     * @see static::$FUNC_OPTIONS
     */
    public $entityOptions;

    /**
     * If `CREATE TABLE`, a list of fields in the new table.
     * If `CREATE VIEW`, a list of columns.
     *
     * Used by `CREATE TABLE` and `CREATE VIEW`.
     *
     * @var FieldDefFragment[]|ArrayFragment
     */
    public $fields;

    /**
     * The `SELECT` statement that defines this view.
     *
     * Used by `CREATE VIEW`.
     *
     * @var SelectStatement
     */
    public $select;

    /**
     * The return data type of this routine.
     *
     * Used by `CREATE FUNCTION`.
     *
     * @var DataTypeFragment
     */
    public $return;

    /**
     * The parameters of this routine.
     *
     * Used by `CREATE FUNCTION` and `CREATE PROCEDURE`.
     *
     * @var ParamDefFragment[]
     */
    public $parameters;


    /**
     * The body of this function or procedure.
     *
     * Used by `CREATE FUNCTION` and `CREATE PROCEDURE`.
     *
     * @var Token[]
     */
    public $body;

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
        $this->options = OptionsFragment::parse($parser, $list, static::$OPTIONS);
        ++$list->idx; // Skipping last option.

        // Parsing the field name.
        $this->name = FieldFragment::parse(
            $parser,
            $list,
            array(
                'skipColumn' => true,
                'noBrackets' => true,
                'noAlias' => true,
            )
        );
        ++$list->idx; // Skipping field.

        if ($this->options->has('TABLE')) {
            $this->fields = FieldDefFragment::parse($parser, $list);
            ++$list->idx;
            $this->entityOptions = OptionsFragment::parse(
                $parser,
                $list,
                static::$TABLE_OPTIONS
            );
        } elseif (($this->options->has('PROCEDURE'))
            || ($this->options->has('FUNCTION'))
        ) {
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
            $this->entityOptions = OptionsFragment::parse(
                $parser,
                $list,
                static::$FUNC_OPTIONS
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
        } else if ($this->options->has('VIEW')) {
            $token = $list->getNext();

            // Parsing columns list.
            if (($token->type === Token::TYPE_OPERATOR) && ($token->value === '(')) {
                --$list->idx; // getNext() also goes forward one field.
                $this->fields = ArrayFragment::parse($parser, $list);
                ++$list->idx; // Skipping last token from the array.
                $token = $list->getNext();
            }

            // Parsing the 'SELECT' statement.
            $this->select = new SelectStatement($parser, $list);
        }
    }
}
