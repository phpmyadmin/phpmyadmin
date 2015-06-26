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
use SqlParser\Fragments\CreateDefFragment;
use SqlParser\Fragments\DataTypeFragment;
use SqlParser\Fragments\FieldDefFragment;
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

        'TEMPORARY'                     => 2,
        'IF NOT EXISTS'                 => 3,
        'DEFINER'                       => array(4, 'var'),
    );

    /**
     * The name of the enw table.
     *
     * @var CreateDefFragment
     */
    public $name;

    /**
     * The options of the entity (table, procedure, function, etc.).
     *
     * @var OptionsFragment
     *
     * @see CreateDefFragment::$TABLE_OPTIONS
     * @see CreateDefFragment::$FUNC_OPTIONS
     */
    public $entityOptions;

    /**
     * Field created by this statement.
     *
     * @var FieldDefFragment[]
     */
    public $fields;

    /**
     * The return data type of this routine.
     *
     * @var DataTypeFragment
     */
    public $return;

    /**
     * The parameters of this routine.
     *
     * @var ParamDefFragment[]
     */
    public $parameters;

    /**
     * The body of this function or procedure.
     *
     * @var Token[]
     */
    public $body;

    /**
     * Parsing the `CREATE` statement.
     *
     * @param Parser     $parser The instance that requests parsing.
     * @param TokensList $list   The list of tokens to be parsed.
     * @param Token      $token  The token that is being parsed.
     *
     * @return void
     */
    public function before(Parser $parser, TokensList $list, Token $token)
    {
        ++$list->idx;
        $this->name = CreateDefFragment::parse($parser, $list);
        if ($this->options->has('TABLE')) {
            ++$list->idx;
            $this->fields = FieldDefFragment::parse($parser, $list);
            ++$list->idx;
            $this->entityOptions = OptionsFragment::parse(
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
            $this->entityOptions = OptionsFragment::parse(
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
        }
    }
}
