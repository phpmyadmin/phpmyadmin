<?php

/**
 * Parses a list of options.
 *
 * @package    SqlParser
 * @subpackage Fragments
 */
namespace SqlParser\Fragments;

use SqlParser\Fragment;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Parses a list of options.
 *
 * @category   Fragments
 * @package    SqlParser
 * @subpackage Fragments
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
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
     * Constructor.
     *
     * @param array $options The array of options. Options that have a value
     *                       must be an array with two keys 'name' and 'value'.
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    /**
     * @param Parser     $parser  The parser that serves as context.
     * @param TokensList $list    The list of tokens that are being parsed.
     * @param array      $options Parameters for parsing.
     *
     * @return OptionsFragment
     */
    public static function parse(Parser $parser, TokensList $list, array $options = array())
    {
        $ret = new OptionsFragment();

         /**
          * The ID that will be assigned to duplicate options.
          * @var int
          */
        $lastAssignedId = count($options) + 1;

        /**
         * The option that was processed last time.
         * @var array
         */
        $lastOption = null;
        $lastOptionId = 0;

        $brackets = 0;

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

            // Skipping whitespaces and comments.
            if (($token->type === Token::TYPE_WHITESPACE) || ($token->type === Token::TYPE_COMMENT)) {
                continue;
            }

            if ($lastOption === null) {
                if (isset($options[strtoupper($token->value)])) {
                    $lastOption = $options[strtoupper($token->value)];
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
        }

        ksort($ret->options);

        --$list->idx;
        return $ret;
    }

    /**
     * @param OptionsFragment $fragment The fragment to be built.
     *
     * @return string
     */
    public static function build(OptionsFragment $fragment)
    {
        $options = array();
        foreach ($fragment->options as $option) {
            if (is_array($option)) {
                $options[] = $option['name'] . '=' . $option['value'];
            } else {
                $options[] = $option;
            }
        }
        return implode(' ', $options);
    }

    /**
     * Checks if it has the specified option and returns it value or true.
     *
     * @param string $key The key to be checked.
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

    /**
     * Merges the specified options with these ones. Values with same ID will be
     * replaced.
     *
     * @param array|OptionsFragment $options The options to be merged.
     *
     * @return void
     */
    public function merge($options)
    {
        if (is_array($options)) {
            $this->options = array_merge_recursive($this->options, $options);
        } elseif ($options instanceof OptionsFragment) {
            $this->options = array_merge_recursive($this->options, $options->options);
        }
    }
}
