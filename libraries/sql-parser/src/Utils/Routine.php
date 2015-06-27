<?php

/**
 * Routine utilities.
 *
 * @package    SqlParser
 * @subpackage Utils
 */
namespace SqlParser\Utils;

use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Statement;
use SqlParser\Fragments\DataTypeFragment;
use SqlParser\Fragments\ParamDefFragment;
use SqlParser\Statements\CreateStatement;

/**
 * Routine utilities.
 *
 * @category   Routines
 * @package    SqlParser
 * @subpackage Utils
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class Routine
{

    /**
     * Parses a parameter of a routine.
     *
     * @param string $param Parameter's definition.
     *
     * @return array
     */
    public function getReturnType($param)
    {
        $lexer = new Lexer($param);

        // A dummy parser is used for error reporting.
        $type = DataTypeFragment::parse(new Parser(), $lexer->list);

        if ($type === null) {
            return array('', '', '', '', '');
        }

        $options = array();
        foreach ($type->options->options as $opt) {
            $options[] = is_string($opt) ? $opt : $opt['value'];
        }

        return array(
            '',
            '',
            $type->name,
            implode(',', $type->parameters),
            implode(' ', $options)
        );
    }

    /**
     * Parses a parameter of a routine.
     *
     * @param string $param Parameter's definition.
     *
     * @return array
     */
    public function getParameter($param)
    {
        $lexer = new Lexer('(' . $param . ')');

        // A dummy parser is used for error reporting.
        $param = ParamDefFragment::parse(new Parser(), $lexer->list);

        if (empty($param[0])) {
            return array('', '', '', '', '');
        }

        $param = $param[0];

        $options = array();
        foreach ($param->type->options->options as $opt) {
            $options[] = is_string($opt) ? $opt : $opt['value'];
        }

        return array(
            empty($param->inOut) ? '' : $param->inOut,
            $param->name,
            $param->type->name,
            implode(',', $param->type->parameters),
            implode(' ', $options)
        );
    }

    /**
     * Gets the parameters of a routine from the parse tree.
     *
     * @param CreateStatement $statement The statement to be processed.
     *
     * @return array
     */
    public static function getParameters($statement)
    {
        $retval = array(
            'num' => 0,
            'dir' => array(),
            'name' => array(),
            'type' => array(),
            'length' => array(),
            'length_arr' => array(),
            'opts' => array(),
        );

        if (!empty($statement->parameters)) {
            $idx = 0;
            foreach ($statement->parameters as $param) {
                $retval['dir'][$idx] = $param->inOut;
                $retval['name'][$idx] = $param->name;
                $retval['type'][$idx] = $param->type->name;
                $retval['length'][$idx] = implode(',', $param->type->parameters);
                $retval['length_arr'][$idx] = $param->type->parameters;
                $retval['opts'][$idx] = array();
                foreach ($param->type->options->options as $opt) {
                    $retval['opts'][$idx][] = is_string($opt) ?
                        $opt : $opt['value'];
                }
                $retval['opts'][$idx] = implode(' ', $retval['opts'][$idx]);
                ++$idx;
            }

            $retval['num'] = $idx;
        }

        return $retval;
    }
}
