<?php

namespace SqlParser\Utils;

class Routine
{

    /**
     * Gets the parameters of a routine from the parse tree.
     *
     * @param Statement $tree
     *
     * @return array
     */
    public static function getParameters($tree)
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
