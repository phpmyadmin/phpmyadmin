<?php

namespace SqlParser\Exceptions;

use SqlParser\Token;

/**
 * Exception thrown by the lexer.
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
     * @param string $message
     * @param string $ch
     * @param int $positiion
     * @param int $code
     */
    public function __construct($message = '', $ch = '', $pos = 0, $code = 0)
    {
        parent::__construct($message, $code);
        $this->ch = $ch;
        $this->pos = $pos;
    }
}
