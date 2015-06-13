<?php

namespace SqlParser\Exceptions;

use SqlParser\Token;

/**
 * Exception thrown by the parser.
 */
class ParserException extends \Exception
{

    /**
     * The token that produced this error.
     *
     * @var Token
     */
    public $token;

    /**
     * Constructor.
     *
     * @param string $message
     * @param Token $token
     * @param int $code
     */
    public function __construct($message = '', Token $token = null, $code = 0)
    {
        parent::__construct($message, $code);
        $this->token = $token;
    }
}
