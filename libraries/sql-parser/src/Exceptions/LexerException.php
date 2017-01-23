<?php

/**
 * Exception thrown by the lexer.
 */

namespace SqlParser\Exceptions;

/**
 * Exception thrown by the lexer.
 *
 * @category   Exceptions
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class LexerException extends \Exception
{
    /**
     * The character that produced this error.
     *
     * @var string
     */
    public $ch;

    /**
     * The index of the character that produced this error.
     *
     * @var int
     */
    public $pos;

    /**
     * Constructor.
     *
     * @param string $msg  the message of this exception
     * @param string $ch   the character that produced this exception
     * @param int    $pos  the position of the character
     * @param int    $code the code of this error
     */
    public function __construct($msg = '', $ch = '', $pos = 0, $code = 0)
    {
        parent::__construct($msg, $code);
        $this->ch = $ch;
        $this->pos = $pos;
    }
}
