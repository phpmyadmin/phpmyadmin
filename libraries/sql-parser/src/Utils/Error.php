<?php

/**
 * Error related utilities.
 */

namespace SqlParser\Utils;

use SqlParser\Lexer;
use SqlParser\Parser;

/**
 * Error related utilities.
 *
 * @category   Exceptions
 *
 * @license    https://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 */
class Error
{
    /**
     * Gets the errors of a lexer and a parser.
     *
     * @param array $objs objects from where the errors will be extracted
     *
     * @return array Each element of the array represents an error.
     *               `$err[0]` holds the error message.
     *               `$err[1]` holds the error code.
     *               `$err[2]` holds the string that caused the issue.
     *               `$err[3]` holds the position of the string.
     *               (i.e. `array($msg, $code, $str, $pos)`)
     */
    public static function get($objs)
    {
        $ret = array();

        foreach ($objs as $obj) {
            if ($obj instanceof Lexer) {
                foreach ($obj->errors as $err) {
                    $ret[] = array(
                        $err->getMessage(),
                        $err->getCode(),
                        $err->ch,
                        $err->pos,
                    );
                }
            } elseif ($obj instanceof Parser) {
                foreach ($obj->errors as $err) {
                    $ret[] = array(
                        $err->getMessage(),
                        $err->getCode(),
                        $err->token->token,
                        $err->token->position,
                    );
                }
            }
        }

        return $ret;
    }

    /**
     * Formats the specified errors.
     *
     * @param array  $errors the errors to be formatted
     * @param string $format The format of an error.
     *                       '$1$d' is replaced by the position of this error.
     *                       '$2$s' is replaced by the error message.
     *                       '$3$d' is replaced by the error code.
     *                       '$4$s' is replaced by the string that caused the
     *                       issue.
     *                       '$5$d' is replaced by the position of the string.
     *
     * @return array
     */
    public static function format(
        $errors,
        $format = '#%1$d: %2$s (near "%4$s" at position %5$d)'
    ) {
        $ret = array();

        $i = 0;
        foreach ($errors as $key => $err) {
            $ret[$key] = sprintf(
                $format,
                ++$i,
                $err[0],
                $err[1],
                htmlspecialchars($err[2]),
                $err[3]
            );
        }

        return $ret;
    }
}
