<?php

declare(strict_types=1);

namespace PhpMyAdmin\Exceptions;

use Exception;

/**
 * Used in tests as a replacement for the 'exit' language construct.
 */
final class ExitException extends Exception
{
}
