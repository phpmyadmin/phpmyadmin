<?php

/**
 * Utilities that are used for formatting queries.
 */

namespace SqlParser\Utils;

use SqlParser\Lexer;
use SqlParser\Parser;
use SqlParser\Token;
use SqlParser\TokensList;

/**
 * Utilities that are used for formatting queries.
 *
 * @category   Misc
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class Formatter
{
    /**
     * The formatting options.
     *
     * @var array
     */
    public $options;

    /**
     * Clauses that must be inlined.
     *
     * These clauses usually are short and it's nicer to have them inline.
     *
     * @var array
     */
    public static $INLINE_CLAUSES = array(
        'CREATE' => true,
        'LIMIT' => true,
        'PARTITION BY' => true,
        'PARTITION' => true,
        'PROCEDURE' => true,
        'SUBPARTITION BY' => true,
        'VALUES' => true,
    );

    /**
     * Constructor.
     *
     * @param array $options the formatting options
     */
    public function __construct(array $options = array())
    {
        $this->options = $this->getMergedOptions($options);
    }

    /**
     * The specified formatting options are merged with the default values.
     *
     * @param array $options
     *
     * @return array
     */
    private function getMergedOptions(array $options)
    {
        $options = array_merge(
            $this->getDefaultOptions(),
            $options
        );

        if (isset($options['formats'])) {
            $options['formats'] = self::mergeFormats($this->getDefaultFormats(), $options['formats']);
        } else {
            $options['formats'] = $this->getDefaultFormats();
        }

        if (is_null($options['line_ending'])) {
            $options['line_ending'] = $options['type'] === 'html' ? '<br/>' : "\n";
        }

        if (is_null($options['indentation'])) {
            $options['indentation'] = $options['type'] === 'html' ? '&nbsp;&nbsp;&nbsp;&nbsp;' : '    ';
        }

        // `parts_newline` requires `clause_newline`
        $options['parts_newline'] &= $options['clause_newline'];

        return $options;
    }

    /**
     * The default formatting options.
     *
     * @return array
     */
    protected function getDefaultOptions()
    {
        return array(
            /*
             * The format of the result.
             *
             * @var string The type ('text', 'cli' or 'html')
             */
            'type' => php_sapi_name() === 'cli' ? 'cli' : 'text',

            /*
             * The line ending used.
             * By default, for text this is "\n" and for HTML this is "<br/>".
             *
             * @var string
             */
            'line_ending' => null,

            /*
             * The string used for indentation.
             *
             * @var string
             */
            'indentation' => null,

            /*
             * Whether comments should be removed or not.
             *
             * @var bool
             */
            'remove_comments' => false,

            /*
             * Whether each clause should be on a new line.
             *
             * @var bool
             */
            'clause_newline' => true,

            /*
             * Whether each part should be on a new line.
             * Parts are delimited by brackets and commas.
             *
             * @var bool
             */
            'parts_newline' => true,

            /*
             * Whether each part of each clause should be indented.
             *
             * @var bool
             */
            'indent_parts' => true,
        );
    }

    /**
     * The styles used for HTML formatting.
     * array($type, $flags, $span, $callback).
     *
     * @return array
     */
    protected function getDefaultFormats()
    {
        return array(
            array(
                'type' => Token::TYPE_KEYWORD,
                'flags' => Token::FLAG_KEYWORD_RESERVED,
                'html' => 'class="sql-reserved"',
                'cli' => "\x1b[35m",
                'function' => 'strtoupper',
            ),
            array(
                'type' => Token::TYPE_KEYWORD,
                'flags' => 0,
                'html' => 'class="sql-keyword"',
                'cli' => "\x1b[95m",
                'function' => 'strtoupper',
            ),
            array(
                'type' => Token::TYPE_COMMENT,
                'flags' => 0,
                'html' => 'class="sql-comment"',
                'cli' => "\x1b[37m",
                'function' => '',
            ),
            array(
                'type' => Token::TYPE_BOOL,
                'flags' => 0,
                'html' => 'class="sql-atom"',
                'cli' => "\x1b[36m",
                'function' => 'strtoupper',
            ),
            array(
                'type' => Token::TYPE_NUMBER,
                'flags' => 0,
                'html' => 'class="sql-number"',
                'cli' => "\x1b[92m",
                'function' => 'strtolower',
            ),
            array(
                'type' => Token::TYPE_STRING,
                'flags' => 0,
                'html' => 'class="sql-string"',
                'cli' => "\x1b[91m",
                'function' => '',
            ),
            array(
                'type' => Token::TYPE_SYMBOL,
                'flags' => 0,
                'html' => 'class="sql-variable"',
                'cli' => "\x1b[36m",
                'function' => '',
            ),
        );
    }

    private static function mergeFormats(array $formats, array $newFormats)
    {
        $added = array();
        $integers = array('flags', 'type');
        $strings = array('html', 'cli', 'function');

        /* Sanitize the array so that we do not have to care later */
        foreach ($newFormats as $j => $new) {
            foreach ($integers as $name) {
                if (!isset($new[$name])) {
                    $newFormats[$j][$name] = 0;
                }
            }
            foreach ($strings as $name) {
                if (!isset($new[$name])) {
                    $newFormats[$j][$name] = '';
                }
            }
        }

        /* Process changes to existing formats */
        foreach ($formats as $i => $original) {
            foreach ($newFormats as $j => $new) {
                if ($new['type'] === $original['type']
                    && $original['flags'] === $new['flags']
                ) {
                    $formats[$i] = $new;
                    $added[] = $j;
                }
            }
        }

        /* Add not already handled formats */
        foreach ($newFormats as $j => $new) {
            if (!in_array($j, $added)) {
                $formats[] = $new;
            }
        }

        return $formats;
    }

    /**
     * Formats the given list of tokens.
     *
     * @param TokensList $list the list of tokens
     *
     * @return string
     */
    public function formatList($list)
    {
        /**
         * The query to be returned.
         *
         * @var string
         */
        $ret = '';

        /**
         * The indentation level.
         *
         * @var int
         */
        $indent = 0;

        /**
         * Whether the line ended.
         *
         * @var bool
         */
        $lineEnded = false;

        /**
         * Whether current group is short (no linebreaks).
         *
         * @var bool
         */
        $shortGroup = false;

        /**
         * The name of the last clause.
         *
         * @var string
         */
        $lastClause = '';

        /**
         * A stack that keeps track of the indentation level every time a new
         * block is found.
         *
         * @var array
         */
        $blocksIndentation = array();

        /**
         * A stack that keeps track of the line endings every time a new block
         * is found.
         *
         * @var array
         */
        $blocksLineEndings = array();

        /**
         * Whether clause's options were formatted.
         *
         * @var bool
         */
        $formattedOptions = false;

        /**
         * Previously parsed token.
         *
         * @var Token
         */
        $prev = null;

        // In order to be able to format the queries correctly, the next token
        // must be taken into consideration. The loop below uses two pointers,
        // `$prev` and `$curr` which store two consecutive tokens.
        // Actually, at every iteration the previous token is being used.
        for ($list->idx = 0; $list->idx < $list->count; ++$list->idx) {
            /**
             * Token parsed at this moment.
             *
             * @var Token $curr
             */
            $curr = $list->tokens[$list->idx];

            if ($curr->type === Token::TYPE_WHITESPACE) {
                // Whitespaces are skipped because the formatter adds its own.
                continue;
            }

            if ($curr->type === Token::TYPE_COMMENT && $this->options['remove_comments']) {
                // Skip Comments if option `remove_comments` is enabled
                continue;
            }

            // Checking if pointers were initialized.
            /**
             * Previous Token.
             *
             * @var Token $prev
             */
            if ($prev !== null) {
                // Checking if a new clause started.
                if (static::isClause($prev) !== false) {
                    $lastClause = $prev->value;
                    $formattedOptions = false;
                }

                // The options of a clause should stay on the same line and everything that follows.
                if ($this->options['parts_newline']
                    && !$formattedOptions
                    && empty(self::$INLINE_CLAUSES[$lastClause])
                    && (
                        $curr->type !== Token::TYPE_KEYWORD
                        || (
                            $curr->type === Token::TYPE_KEYWORD
                            && $curr->flags & Token::FLAG_KEYWORD_FUNCTION
                        )
                    )
                ) {
                    $formattedOptions = true;
                    $lineEnded = true;
                    ++$indent;
                }

                // Checking if this clause ended.
                if ($tmp = static::isClause($curr)) {
                    if ($tmp == 2 || $this->options['clause_newline']) {
                        $lineEnded = true;
                        if ($this->options['parts_newline']) {
                            --$indent;
                        }
                    }
                }

                // Indenting BEGIN ... END blocks.
                if ($prev->type === Token::TYPE_KEYWORD && $prev->value === 'BEGIN') {
                    $lineEnded = true;
                    array_push($blocksIndentation, $indent);
                    ++$indent;
                } elseif ($curr->type === Token::TYPE_KEYWORD && $curr->value === 'END') {
                    $lineEnded = true;
                    $indent = array_pop($blocksIndentation);
                }

                // Formatting fragments delimited by comma.
                if ($prev->type === Token::TYPE_OPERATOR && $prev->value === ',') {
                    // Fragments delimited by a comma are broken into multiple
                    // pieces only if the clause is not inlined or this fragment
                    // is between brackets that are on new line.
                    if (end($blocksLineEndings) === true
                        || (
                            empty(self::$INLINE_CLAUSES[$lastClause])
                            && !$shortGroup
                            && $this->options['parts_newline']
                        )
                    ) {
                        $lineEnded = true;
                    }
                }

                // Handling brackets.
                // Brackets are indented only if the length of the fragment between
                // them is longer than 30 characters.
                if ($prev->type === Token::TYPE_OPERATOR && $prev->value === '(') {
                    array_push($blocksIndentation, $indent);
                    $shortGroup = true;
                    if (static::getGroupLength($list) > 30) {
                        ++$indent;
                        $lineEnded = true;
                        $shortGroup = false;
                    }
                    array_push($blocksLineEndings, $lineEnded);
                } elseif ($curr->type === Token::TYPE_OPERATOR && $curr->value === ')') {
                    $indent = array_pop($blocksIndentation);
                    $lineEnded |= array_pop($blocksLineEndings);
                    $shortGroup = false;
                }

                // Adding the token.
                $ret .= $this->toString($prev);

                // Finishing the line.
                if ($lineEnded) {
                    if ($indent < 0) {
                        // TODO: Make sure this never occurs and delete it.
                        $indent = 0;
                    }

                    $ret .= $this->options['line_ending']
                        . str_repeat($this->options['indentation'], $indent);

                    $lineEnded = false;
                } else {
                    // If the line ended there is no point in adding whitespaces.
                    // Also, some tokens do not have spaces before or after them.
                    if (
                        // A space after delimiters that are longer than 2 characters.
                        $prev->value === 'DELIMITER'
                        || !(
                            ($prev->type === Token::TYPE_OPERATOR && ($prev->value === '.' || $prev->value === '('))
                            // No space after . (
                            || ($curr->type === Token::TYPE_OPERATOR && ($curr->value === '.' || $curr->value === ',' || $curr->value === '(' || $curr->value === ')'))
                            // No space before . , ( )
                            || $curr->type === Token::TYPE_DELIMITER && mb_strlen($curr->value, 'UTF-8') < 2
                        )
                    ) {
                        $ret .= ' ';
                    }
                }
            }

            // Iteration finished, consider current token as previous.
            $prev = $curr;
        }

        if ($this->options['type'] === 'cli') {
            return $ret . "\x1b[0m";
        }

        return $ret;
    }

    public function escapeConsole($string)
    {
        return str_replace(
            array(
                "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07", "\x08", "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F",
                "\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17", "\x18", "\x19", "\x1A", "\x1B", "\x1C",  "\x1D", "\x1E", "\x1F",
            ),
            array(
                '\x00', '\x01', '\x02', '\x03', '\x04', '\x05', '\x06', '\x07', '\x08', '\x09', '\x0A', '\x0B', '\x0C', '\x0D', '\x0E', '\x0F',
                '\x10', '\x11', '\x12', '\x13', '\x14', '\x15', '\x16', '\x17', '\x18', '\x19', '\x1A', '\x1B', '\x1C',  '\x1D', '\x1E', '\x1F',
            ),
            $string
        );
    }

    /**
     * Tries to print the query and returns the result.
     *
     * @param Token $token the token to be printed
     *
     * @return string
     */
    public function toString($token)
    {
        $text = $token->token;

        foreach ($this->options['formats'] as $format) {
            if ($token->type === $format['type']
                && ($token->flags & $format['flags']) === $format['flags']
            ) {
                // Running transformation function.
                if (!empty($format['function'])) {
                    $func = $format['function'];
                    $text = $func($text);
                }

                // Formatting HTML.
                if ($this->options['type'] === 'html') {
                    return '<span ' . $format['html'] . '>' . htmlspecialchars($text, ENT_NOQUOTES) . '</span>';
                } elseif ($this->options['type'] === 'cli') {
                    return $format['cli'] . $this->escapeConsole($text);
                }

                break;
            }
        }

        if ($this->options['type'] === 'cli') {
            return "\x1b[39m" . $this->escapeConsole($text);
        } elseif ($this->options['type'] === 'html') {
            return htmlspecialchars($text, ENT_NOQUOTES);
        }

        return $text;
    }

    /**
     * Formats a query.
     *
     * @param string $query   The query to be formatted
     * @param array  $options the formatting options
     *
     * @return string the formatted string
     */
    public static function format($query, array $options = array())
    {
        $lexer = new Lexer($query);
        $formatter = new self($options);

        return $formatter->formatList($lexer->list);
    }

    /**
     * Computes the length of a group.
     *
     * A group is delimited by a pair of brackets.
     *
     * @param TokensList $list the list of tokens
     *
     * @return int
     */
    public static function getGroupLength($list)
    {
        /**
         * The number of opening brackets found.
         * This counter starts at one because by the time this function called,
         * the list already advanced one position and the opening bracket was
         * already parsed.
         *
         * @var int
         */
        $count = 1;

        /**
         * The length of this group.
         *
         * @var int
         */
        $length = 0;

        for ($idx = $list->idx; $idx < $list->count; ++$idx) {
            // Counting the brackets.
            if ($list->tokens[$idx]->type === Token::TYPE_OPERATOR) {
                if ($list->tokens[$idx]->value === '(') {
                    ++$count;
                } elseif ($list->tokens[$idx]->value === ')') {
                    --$count;
                    if ($count == 0) {
                        break;
                    }
                }
            }

            // Keeping track of this group's length.
            $length += mb_strlen($list->tokens[$idx]->value, 'UTF-8');
        }

        return $length;
    }

    /**
     * Checks if a token is a statement or a clause inside a statement.
     *
     * @param Token $token the token to be checked
     *
     * @return int|bool
     */
    public static function isClause($token)
    {
        if (
            ($token->type === Token::TYPE_KEYWORD && isset(Parser::$STATEMENT_PARSERS[$token->value]))
            || ($token->type === Token::TYPE_NONE && strtoupper($token->token) === 'DELIMITER')
        ) {
            return 2;
        } elseif (
            $token->type === Token::TYPE_KEYWORD && isset(Parser::$KEYWORD_PARSERS[$token->value])
        ) {
            return 1;
        }

        return false;
    }
}
