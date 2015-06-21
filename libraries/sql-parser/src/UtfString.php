<?php

namespace SqlParser;

/**
 * Implements array-like access for UTF-8 strings.
 *
 * In this library, this class should be used to parse UTF-8 queries.
 */
class UtfString implements \ArrayAccess
{

    /**
     * The raw, multibyte string.
     *
     * @var string
     */
    private $str = '';

    /**
     * The index of current byte.
     *
     * For ASCII strings, the byte index is equal to the character index.
     *
     * @var int
     */
    private $byteIdx = 0;

    /**
     * The index of current character.
     *
     * For non-ASCII strings, some characters occupy more than one byte and
     * the character index will have a lower value than the byte index.
     *
     * @var int
     */
    private $charIdx = 0;

    /**
     * The length of the string (in bytes).
     *
     * @var int
     */
    private $buffLen = 0;

    /**
     * The length of the string (in characters).
     *
     * @var int
     */
    private $charLen = 0;

    /**
     * Constructor.
     *
     * @param string $str
     */
    public function __construct($str)
    {
        $this->str = $str;
        $this->byteIdx = 0;
        $this->charIdx = 0;
        // TODO: `strlen($str)` might return a wrong length when function
        // overloading is enabled.
        // https://php.net/manual/ro/mbstring.overload.php
        $this->byteLen = strlen($str);
        $this->charLen = mb_strlen($str);
    }

    /**
     * Checks if the given offset exists.
     *
     * @param int $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $offset < $charLen;
    }

    /**
     * Gets the character at given offset.
     *
     * @param int $offset
     *
     * @return string
     */
    public function offsetGet($offset)
    {
        if (($offset < 0) || ($offset >= $this->charLen)) {
            return null;
        }

        $delta = $offset - $this->charIdx;

        if ($delta > 0) {
            // Fast forwarding.
            while ($delta-- > 0) {
                $this->byteIdx += static::getCharLength($this->str[$this->byteIdx]);
                ++$this->charIdx;
            }
        } elseif ($delta < 0) {
            // Rewinding.
            while ($delta++ < 0) {
                do {
                    $byte = ord($this->str[--$this->byteIdx]);
                } while ((128 <= $byte) && ($byte < 192));
                --$this->charIdx;
            }
        }

        $bytesCount = static::getCharLength($this->str[$this->byteIdx]);

        $ret = '';
        for ($i = 0; $bytesCount-- > 0; ++$i) {
            $ret .= $this->str[$this->byteIdx + $i];
        }

        return $ret;
    }

    /**
     * Sets the value of a character.
     *
     * @param int $offset
     * @param string $value
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception('Not implemented.');
    }

    /**
     * Unsets an index.
     *
     * @param int $offset
     */
    public function offsetUnset($offset)
    {
        throw new \Exception('Not implemented.');
    }

    /**
     * Gets the length of an UTF-8 character.
     *
     * According to RFC 3629, a UTF-8 character can have at most 4 bytes.
     * However, this implemenation supports UTF-8 characters containing up to 6
     * bytes.
     *
     * @see http://tools.ietf.org/html/rfc3629
     *
     * @param string $byte
     *
     * @return int
     */
    public static function getCharLength($byte)
    {
        $byte = ord($byte);
        if ($byte < 128) {
            return 1;
        } elseif ($byte < 224) {
            return 2;
        } elseif ($byte < 240) {
            return 3;
        } elseif ($byte < 248) {
            return 4;
        } elseif ($byte === 252) {
            return 5; // unofficial
        }
        return 6; // unofficial
    }

    /**
     * Returns the number of remaining characters.
     *
     * @return int
     */
    public function remaining()
    {
        if ($this->charIdx < $this->charLen) {
            return $this->charLen - $this->charIdx;
        }
        return 0;
    }

    /**
     * Returns the length in characters of the string.
     *
     * @return int
     */
    public function length()
    {
        return $this->charLen;
    }

    /**
     * Gets the values of the indexes.
     *
     * @param int &$byte
     * @param int &$char
     */
    public function getIndexes(&$byte, &$char)
    {
        $byte = $this->byteIdx;
        $char = $this->charIdx;
    }

    /**
     * Sets the values of the indexes.
     *
     * @param int $byte
     * @param int $char
     */
    public function setIndexes($byte = 0, $char = 0)
    {
        $this->byteIdx = $byte;
        $this->charIdx = $char;
    }
}
