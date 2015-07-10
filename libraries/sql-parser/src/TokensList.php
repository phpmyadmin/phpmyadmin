<?php

/**
 * Defines an array of tokens and utility functions to iterate through it.
 *
 * @package SqlParser
 */
namespace SqlParser;

/**
 * A structure representing a list of tokens.
 *
 * @category Tokens
 * @package  SqlParser
 * @author   Dan Ungureanu <udan1107@gmail.com>
 * @license  http://opensource.org/licenses/GPL-2.0 GNU Public License
 */
class TokensList implements \ArrayAccess
{

    /**
     * The array of tokens.
     *
     * @var array
     */
    public $tokens = array();

    /**
     * The count of tokens.
     *
     * @var int
     */
    public $count = 0;

    /**
     * The index of the next token to be returned.
     *
     * @var int
     */
    public $idx = 0;

    /**
     * Constructor.
     *
     * @param array $tokens The initial array of tokens.
     * @param int   $count  The count of tokens in the initial array.
     */
    public function __construct(array $tokens = array(), $count = -1)
    {
        if (!empty($tokens)) {
            $this->tokens = $tokens;
            if ($count === -1) {
                $this->count = count($tokens);
            }
        }
    }

    /**
     * Builds an array of tokens by merging their raw value.
     *
     * @param string|Token[]|TokensList $list The tokens to be built.
     *
     * @return string
     */
    public static function build($list)
    {
        if (is_string($list)) {
            return $list;
        }

        if ($list instanceof TokensList) {
            $list = $list->tokens;
        }

        $ret = '';
        if (is_array($list)) {
            foreach ($list as $tok) {
                $ret .= $tok->token;
            }
        }
        return $ret;
    }

    /**
     * Adds a new token.
     *
     * @param Token $token Token to be added in list.
     *
     * @return void
     */
    public function add(Token $token)
    {
        $this->tokens[$this->count++] = $token;
    }

    /**
     * Gets the next token. Skips any irrelevant token (whitespaces and
     * comments).
     *
     * @return Token
     */
    public function getNext()
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if (($this->tokens[$this->idx]->type !== Token::TYPE_WHITESPACE)
                && ($this->tokens[$this->idx]->type !== Token::TYPE_COMMENT)
            ) {
                return $this->tokens[$this->idx++];
            }
        }
        return null;
    }

    /**
     * Gets the next token.
     *
     * @param int $type The type.
     *
     * @return Token
     */
    public function getNextOfType($type)
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if ($this->tokens[$this->idx]->type === $type) {
                return $this->tokens[$this->idx++];
            }
        }
        return null;
    }

    /**
     * Gets the next token.
     *
     * @param int    $type  The type of the token.
     * @param string $value The value of the token.
     *
     * @return Token
     */
    public function getNextOfTypeAndValue($type, $value)
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if (($this->tokens[$this->idx]->type === $type)
                && ($this->tokens[$this->idx]->value === $value)
            ) {
                return $this->tokens[$this->idx++];
            }
        }
        return null;
    }

    /**
     * Sets an value inside the container.
     *
     * @param int   $offset The offset to be set.
     * @param Token $value  The token to be saved.
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->tokens[$this->count++] = $value;
        } else {
            $this->tokens[$offset] = $value;
        }
    }

    /**
     * Gets a value from the container.
     *
     * @param int $offset The offset to be returned.
     *
     * @return Token
     */
    public function offsetGet($offset)
    {
        return $offset < $this->count ? $this->tokens[$offset] : null;
    }

    /**
     * Checks if an offset was previously set.
     *
     * @param int $offset The offset to be checked.
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $offset < $this->count;
    }

    /**
     * Unsets the value of an offset.
     *
     * @param int $offset The offset to be unset.
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->tokens[$offset]);
        --$this->count;
        for ($i = $offset; $i < $this->count; ++$i) {
            $this->tokens[$i] = $this->tokens[$i + 1];
        }
        unset($this->tokens[$this->count]);
    }
}
