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
     * Gets the next token.
     *
     * @return Token
     */
    public function getNext()
    {
        if ($this->idx < $this->count) {
            return $this->tokens[$this->idx++];
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
