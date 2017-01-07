<?php

/**
 * Exception thrown by the parser.
 */

namespace SqlParser\Exceptions;

use SqlParser\Token;

/**
 * Exception thrown by the parser.
 *
 * @category   Exceptions
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
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
     * @param string $msg   the message of this exception
     * @param Token  $token the token that produced this exception
     * @param int    $code  the code of this error
     */
    public function __construct($msg = '', Token $token = null, $code = 0)
    {
        parent::__construct($msg, $code);
        $this->token = $token;
    }
}
