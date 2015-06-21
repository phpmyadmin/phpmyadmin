<?php

namespace SqlParser\Utils;

use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Fragments\DataTypeFragment;
use SqlParser\Fragments\ParamDefFragment;
use SqlParser\Statements\CreateStatement;

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
        $param = DataTypeFragment::parse(new Parser(), $lexer->tokens);

        if ($param === null) {
            return array('', '', '', '', '');
        }

        $options = array();
        foreach ($param->options->options as $opt) {
            $options[] = is_string($opt) ? $opt : $opt['value'];
        }

        return array(
            '',
            '',
            $param->name,
            implode(',', $param->size),
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
        $param = ParamDefFragment::parse(new Parser(), $lexer->tokens);

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
            implode(',', $param->type->size),
            implode(' ', $options)
        );
    }

    /**
     * Gets the parameters of a routine from the parse tree.
     *
     * @param CreateStatement $tree The tree that was generated after parsing.
     *
     * @return array
     */
    public static function getParameters(CreateStatement $tree)
    {
        $retval = array(
            'num' => 0,
            'dir' => array(),
            'name' => array(),
            'type' => array(),
            'length' => array(),
            'opts' => array(),
        );

        $idx = 0;
        foreach ($tree->parameters as $param) {
            $retval['dir'][$idx] = $param->inOut;
            $retval['name'][$idx] = $param->name;
            $retval['type'][$idx] = $param->type->name;
            $retval['length'][$idx] = implode(',', $param->type->size);
            $retval['opts'][$idx] = array();
            foreach ($param->type->options->options as $opt) {
                $retval['opts'][$idx][] = is_string($opt) ?
                    $opt : $opt['value'];
            }
            $retval['opts'][$idx] = implode(' ', $retval['opts'][$idx]);
            ++$idx;
        }

        $retval['num'] = $idx;

        return $retval;
    }
}
