<?php

/**
 * Utilities that are used for formatting queries.
 *
 * @package    SqlParser
 * @subpackage Utils
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
 * @package    SqlParser
 * @subpackage Utils
 * @author     Dan Ungureanu <udan1107@gmail.com>
 * @license    http://opensource.org/licenses/GPL-2.0 GNU Public License
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
        'CREATE'                        => true,
        'LIMIT'                         => true,
        'PARTITION BY'                  => true,
        'PARTITION'                     => true,
        'PROCEDURE'                     => true,
        'SUBPARTITION BY'               => true,
        'VALUES'                        => true,
    );

    /**
     * Constructor.
     *
     * @param array $options The formatting options.
     */
    public function __construct(array $options = array())
    {
        // The specified formatting options are merged with the default values.
        $this->options = array_merge(
            array(

                /**
                 * The format of the result.
                 *
                 * @var string The type ('text', 'cli' or 'html')
                 */
                'type' => php_sapi_name() == 'cli' ? 'cli' : 'text',

                /**
                 * The line ending used.
                 * By default, for text this is "\n" and for HTML this is "<br/>".
                 *
                 * @var string
                 */
                'line_ending' => $this->options['type'] == 'html' ? '<br/>' : "\n",

                /**
                 * The string used for indentation.
                 *
                 * @var string
                 */
                'indentation' => "  ",

                /**
                 * Whether comments should be removed or not.
                 *
                 * @var bool
                 */
                'remove_comments' => false,

                /**
                 * Whether each clause should be on a new line.
                 *
                 * @var bool
                 */
                'clause_newline' => true,

                /**
                 * Whether each part should be on a new line.
                 * Parts are delimited by brackets and commas.
                 *
                 * @var bool
                 */
                'parts_newline' => true,

                /**
                 * Whether each part of each clause should be indented.
                 *
                 * @var bool
                 */
                'indent_parts' => true,

                /**
                 * The styles used for HTML formatting.
                 * array($type, $flags, $span, $callback)
                 *
                 * @var array[]
                 */
                'formats' => array(
                    array(
                        'type'      => Token::TYPE_KEYWORD,
                        'flags'     => Token::FLAG_KEYWORD_RESERVED,
                        'html'      => 'class="sql-reserved"',
                        'cli'      => "\e[35m",
                        'function'  => 'strtoupper',
                    ),
                    array(
                        'type'      => Token::TYPE_KEYWORD,
                        'flags'     => 0,
                        'html'      => 'class="sql-keyword"',
                        'cli'      => "\e[95m",
                        'function'  => 'strtoupper',
                    ),
                    array(
                        'type'      => Token::TYPE_COMMENT,
                        'flags'     => 0,
                        'html'      => 'class="sql-comment"',
                        'cli'      => "\e[37m",
                        'function'  => '',
                    ),
                    array(
                        'type'      => Token::TYPE_BOOL,
                        'flags'     => 0,
                        'html'      => 'class="sql-atom"',
                        'cli'      => "\e[36m",
                        'function'  => 'strtoupper',
                    ),
                    array(
                        'type'      => Token::TYPE_NUMBER,
                        'flags'     => 0,
                        'html'      => 'class="sql-number"',
                        'cli'      => "\e[92m",
                        'function'  => 'strtolower',
                    ),
                    array(
                        'type'      => Token::TYPE_STRING,
                        'flags'     => 0,
                        'html'      => 'class="sql-string"',
                        'cli'      => "\e[91m",
                        'function'  => '',
                    ),
                    array(
                        'type'      => Token::TYPE_SYMBOL,
                        'flags'     => 0,
                        'html'      => 'class="sql-variable"',
                        'cli'      => "\e[36m",
                        'function'  => '',
                    ),
                )
            ),
            $options
        );

        // `parts_newline` requires `clause_newline`
        $this->options['parts_newline'] &= $this->options['clause_newline'];
    }

    /**
     * Formats the given list of tokens.
     *
     * @param TokensList $list The list of tokens.
     *
     * @return string
     */
    public function formatList($list)
    {

        /**
         * The query to be returned.
         *
         * @var string $ret
         */
        $ret = '';

        /**
         * The indentation level.
         *
         * @var int $indent
         */
        $indent = 0;

        /**
         * Whether the line ended.
         *
         * @var bool $lineEnded
         */
        $lineEnded = false;

        /**
         * The name of the last clause.
         *
         * @var string $lastClause
         */
        $lastClause = '';

        /**
         * A stack that keeps track of the indentation level every time a new
         * block is found.
         *
         * @var array $blocksIndentation
         */
        $blocksIndentation = array();

        /**
         * A stack that keeps track of the line endings every time a new block
         * is found.
         *
         * @var array $blocksLineEndings
         */
        $blocksLineEndings = array();

        /**
         * Whether clause's options were formatted.
         *
         * @var bool $formattedOptions
         */
        $formattedOptions = false;

        /**
         * Previously parsed token.
         *
         * @var Token $prev
         */
        $prev = null;

        /**
         * Comments are being formatted separately to maintain the whitespaces
         * before and after them.
         *
         * @var string $comment
         */
        $comment = '';

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
            } elseif ($curr->type === Token::TYPE_COMMENT) {
                // Whether the comments should be parsed.
                if (!empty($this->options['remove_comments'])) {
                    continue;
                }

                if ($list->tokens[$list->idx - 1]->type === Token::TYPE_WHITESPACE) {
                    // The whitespaces before and after are preserved for
                    // formatting reasons.
                    $comment .= $list->tokens[$list->idx - 1]->token;
                }
                $comment .= $this->toString($curr);
                if (($list->tokens[$list->idx + 1]->type === Token::TYPE_WHITESPACE)
                    && ($list->tokens[$list->idx + 2]->type !== Token::TYPE_COMMENT)
                ) {
                    // Adding the next whitespace only there is no comment that
                    // follows it immediately which may cause adding a
                    // whitespace twice.
                    $comment .= $list->tokens[$list->idx + 1]->token;
                }

                // Everything was handled here, no need to continue.
                continue;
            }

            // Checking if pointers were initialized.
            if ($prev !== null) {
                // Checking if a new clause started.
                if (static::isClause($prev) !== false) {
                    $lastClause = $prev->value;
                    $formattedOptions = false;
                }

                // The options of a clause should stay on the same line and everything that follows.
                if (($this->options['parts_newline'])
                    && (!$formattedOptions)
                    && (empty(self::$INLINE_CLAUSES[$lastClause]))
                    && (($curr->type !== Token::TYPE_KEYWORD)
                        || (($curr->type === Token::TYPE_KEYWORD)
                            && ($curr->flags & Token::FLAG_KEYWORD_FUNCTION)))
                ) {
                    $formattedOptions = true;
                    $lineEnded = true;
                    ++$indent;
                }

                // Checking if this clause ended.
                if ($tmp = static::isClause($curr)) {
                    if (($tmp == 2) || ($this->options['clause_newline'])) {
                        $lineEnded = true;
                        if ($this->options['parts_newline']) {
                            --$indent;
                        }
                    }
                }

                // Indenting BEGIN ... END blocks.
                if (($prev->type === Token::TYPE_KEYWORD) && ($prev->value === 'BEGIN')) {
                    $lineEnded = true;
                    array_push($blocksIndentation, $indent);
                    ++$indent;
                } elseif (($curr->type === Token::TYPE_KEYWORD) && ($curr->value === 'END')) {
                    $lineEnded = true;
                    $indent = array_pop($blocksIndentation);
                }

                // Formatting fragments delimited by comma.
                if (($prev->type === Token::TYPE_OPERATOR) && ($prev->value === ',')) {
                    // Fragments delimited by a comma are broken into multiple
                    // pieces only if the clause is not inlined or this fragment
                    // is between brackets that are on new line.
                    if (((empty(self::$INLINE_CLAUSES[$lastClause]))
                        && ($this->options['parts_newline']))
                        || (end($blocksLineEndings) === true)
                    ) {
                        $lineEnded = true;
                    }
                }

                // Handling brackets.
                // Brackets are indented only if the length of the fragment between
                // them is longer than 30 characters.
                if (($prev->type === Token::TYPE_OPERATOR) && ($prev->value === '(')) {
                    array_push($blocksIndentation, $indent);
                    if (static::getGroupLength($list) > 30) {
                        ++$indent;
                        $lineEnded = true;
                    }
                    array_push($blocksLineEndings, $lineEnded);
                } elseif (($curr->type === Token::TYPE_OPERATOR) && ($curr->value === ')')) {
                    $indent = array_pop($blocksIndentation);
                    $lineEnded |= array_pop($blocksLineEndings);
                }

                // Delimiter must be placed on the same line with the last
                // clause.
                if ($curr->type === Token::TYPE_DELIMITER) {
                    $lineEnded = false;
                }

                // Adding the token.
                $ret .= $this->toString($prev);

                // Finishing the line.
                if ($lineEnded) {
                    if ($indent < 0) {
                        // TODO: Make sure this never occurs and delete it.
                        $indent = 0;
                    }

                    if ($curr->type !== Token::TYPE_COMMENT) {
                        $ret .= $this->options['line_ending']
                            . str_repeat($this->options['indentation'], $indent);
                    }
                    $lineEnded = false;
                } else {
                    // If the line ended there is no point in adding whitespaces.
                    // Also, some tokens do not have spaces before or after them.
                    if (!((($prev->type === Token::TYPE_OPERATOR) && (($prev->value === '.') || ($prev->value === '(')))
                        // No space after . (
                        || (($curr->type === Token::TYPE_OPERATOR) && (($curr->value === '.') || ($curr->value === ',') || ($curr->value === '(') || ($curr->value === ')')))
                        // No space before . , ( )
                        || (($curr->type === Token::TYPE_DELIMITER)) && (mb_strlen($curr->value, 'UTF-8') < 2))
                        // A space after delimiters that are longer than 2 characters.
                        || ($prev->value === 'DELIMITER')
                    ) {
                        $ret .= ' ';
                    }
                }
            }

            if (!empty($comment)) {
                $ret .= $comment;
                $comment = '';
            }

            // Iteration finished, consider current token as previous.
            $prev = $curr;
        }

        return $ret;
    }

    /**
     * Tries to print the query and returns the result.
     *
     * @param Token $token The token to be printed.
     *
     * @return string
     */
    public function toString($token)
    {
        $text = $token->token;

        foreach ($this->options['formats'] as $format) {
            if (($token->type === $format['type'])
                && (($token->flags & $format['flags']) === $format['flags'])
            ) {
                // Running transformation function.
                if (!empty($format['function'])) {
                    $func = $format['function'];
                    $text = $func($text);
                }

                // Formatting HTML.
                if ($this->options['type'] === 'html') {
                    return '<span ' . $format['html'] . '>' . $text . '</span>';
                } elseif ($this->options['type'] === 'cli') {
                    return $format['cli'] . $text;
                }

                break;
            }
        }

        if ($this->options['type'] === 'cli') {
            return "\e[39m" . $text;
        }
        return $text;
    }

    /**
     * Formats a query.
     *
     * @param string $query   The query to be formatted
     * @param array  $options The formatting options.
     *
     * @return string          The formatted string.
     */
    public static function format($query, array $options = array())
    {
        $lexer = new Lexer($query);
        $formatter = new Formatter($options);
        return $formatter->formatList($lexer->list);
    }

    /**
     * Computes the length of a group.
     *
     * A group is delimited by a pair of brackets.
     *
     * @param TokensList $list The list of tokens.
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
         * @var int $count
         */
        $count = 1;

        /**
         * The length of this group.
         *
         * @var int $length
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
     * @param Token $token The token to be checked.
     *
     * @return int|bool
     */
    public static function isClause($token)
    {
        if ((($token->type === Token::TYPE_NONE) && (strtoupper($token->token) === 'DELIMITER'))
            || (($token->type === Token::TYPE_KEYWORD) && (isset(Parser::$STATEMENT_PARSERS[$token->value])))
        ) {
            return 2;
        } elseif (($token->type === Token::TYPE_KEYWORD) && (isset(Parser::$KEYWORD_PARSERS[$token->value]))) {
            return 1;
        }
        return false;
    }
}
