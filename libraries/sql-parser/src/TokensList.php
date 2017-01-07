<?php

/**
 * Defines an array of tokens and utility functions to iterate through it.
 */

namespace SqlParser;

/**
 * A structure representing a list of tokens.
 *
 * @category Tokens
 *
 * @license  https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
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
     * @param array $tokens the initial array of tokens
     * @param int   $count  the count of tokens in the initial array
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
     * @param string|Token[]|TokensList $list the tokens to be built
     *
     * @return string
     */
    public static function build($list)
    {
        if (is_string($list)) {
            return $list;
        }

        if ($list instanceof self) {
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
     * @param Token $token token to be added in list
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
     * @param int $type the type
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
     * @param int    $type  the type of the token
     * @param string $value the value of the token
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
     * @param int   $offset the offset to be set
     * @param Token $value  the token to be saved
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
     * @param int $offset the offset to be returned
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
     * @param int $offset the offset to be checked
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
     * @param int $offset the offset to be unset
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
