<?php

namespace SqlParser;

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
     * @param Token $token
     */
    public function add(Token $token)
    {
        $this->tokens[$this->count++] = $token;
    }

    /**
     * Gets next token in list.
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
     * Gets next token of a specified type.
     *
     * @param int $type
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
     * Gets next token of a specified type.
     *
     * @param int $type
     * @param string $value
     *
     * @return Token
     */
    public function getNextOfTypeAndValue($type, $value)
    {
        for (; $this->idx < $this->count; ++$this->idx) {
            if (($this->tokens[$this->idx]->type === $type) &&
                ($this->tokens[$this->idx]->value === $value)) {
                return $this->tokens[$this->idx++];
            }
        }
        return null;
    }

    // ArrayAccess methods.

    public function offsetSet($offset, $value)
    {
        if ($offset === null) {
            $this->tokens[$this->count++] = $value;
        } else {
            $this->tokens[$offset] = $value;
        }
    }

    public function offsetGet($offset)
    {
        return $offset < $this->count ? $this->tokens[$offset] : null;
    }

    public function offsetExists($offset)
    {
        return $offset < $this->count;
    }

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
