<?php

namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses a list of options.
 */
class OptionsFragment extends Fragment
{

    /**
     * Array of selected options.
     *
     * @var array
     */
    public $options = array();

    /**
     * @param Parser $parser
     * @param TokensList $list
     * @param array $options
     *
     * @return OptionsFragment
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new OptionsFragment();

         /** @var int The ID that will be assigned to duplicate options. */
        $lastAssignedId = count($options) + 1;

        /** @var array The option that was processed last time. */
        $lastOption = null;
        $lastOptionId = 0;

        $brackets = 0;

        for (; $list->idx < $list->count; ++$list->idx) {
            /** @var Token Token parsed at this moment. */
            $token = $list->tokens[$list->idx];

            // End of statement.
            if ($token->type === Token::TYPE_DELIMITER) {
                break;
            }

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if (isset($options[$token->value])) {
                $lastOption = $options[$token->value];
                $lastOptionId = is_array($lastOption) ? $lastOption[0] : $lastOption;

                // Checking for option conflicts.
                // For example, in `SELECT` statements the keywords `ALL` and `DISTINCT`
                // conflict and if used together, they produce an invalid query.
                // Usually, tokens can be identified in the array by the option ID,
                // but if conflicts occur, a psuedo option ID is used.
                // The first pseudo duplicate ID is the maximum value of the real
                // options (e.g.  if there are 5 options, the first fake ID is 6).
                if (isset($ret->options[$lastOptionId])) {
                    $parser->error('This option conflicts with \'' . $ret->options[$lastOptionId] . '\'.', $token);
                    $lastOptionId = $lastAssignedId++;
                }
            } else {
                // There is no option to be processed.
                if ($lastOption === null) {
                    break;
                }

                // The only keywords that are expected are those which are
                // options.
                if ($token->type === Token::TYPE_KEYWORD) {
                    break;
                }

            }

            if (is_array($lastOption)) {
                if (empty($ret->options[$lastOptionId])) {
                    $ret->options[$lastOptionId] = array('name' => $token->value, 'value' => '');
                } else {
                    if ($token->value !== '=') {
                        if ($token->value === '(') {
                            ++$brackets;
                        } elseif ($token->value === ')') {
                            --$brackets;
                        } else {
                            $ret->options[$lastOptionId]['value'] .= $token->value;
                        }
                        if ($brackets === 0) {
                            $lastOption = null;
                        }
                    }
                }
            } else {
                $ret->options[$lastOptionId] = $token->value;
                $lastOption = null;
            }
            $ret->tokens[] = $token;

        }

        ksort($ret->options);

        --$list->idx;
        return $ret;
    }

    /**
     * Checks if it has the specified option and returns it value or true.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function has($key)
    {
        foreach ($this->options as $option) {
            if ((is_array($option)) && ($key === $option['name'])) {
                return $option['value'];
            } elseif ($key === $option) {
                return true;
            }
        }
        return false;
    }
}
