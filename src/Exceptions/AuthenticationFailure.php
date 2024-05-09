<?php

declare(strict_types=1);

namespace PhpMyAdmin\Exceptions;

use RuntimeException;
use Throwable;

use function __;

final class AuthenticationFailure extends RuntimeException
{
    public const SERVER_DENIED = 'server-denied';
    public const ALLOW_DENIED = 'allow-denied';
    public const ROOT_DENIED = 'root-denied';
    public const EMPTY_DENIED = 'empty-denied';
    public const NO_ACTIVITY = 'no-activity';

    /** @psalm-param self::* $failureType */
    public function __construct(
        public readonly string $failureType,
        string $message = '',
        int $code = 0,
        Throwable|null $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Database server denied user login
     */
    public static function deniedByDatabaseServer(): self
    {
        return new self(self::SERVER_DENIED, __('Cannot log in to the database server.'));
    }

    /**
     * User denied by allow/deny rules
     */
    public static function deniedByAllowDenyRules(): self
    {
        return new self(self::ALLOW_DENIED, __('Access denied!'));
    }

    /**
     * User 'root' is denied in configuration
     */
    public static function rootDeniedByConfiguration(): self
    {
        return new self(self::ROOT_DENIED, __('Access denied!'));
    }

    /**
     * Empty password is denied
     */
    public static function emptyPasswordDeniedByConfiguration(): self
    {
        return new self(
            self::EMPTY_DENIED,
            __('Login without a password is forbidden by configuration (see AllowNoPassword).'),
        );
    }

    /**
     * Automatically logged out due to inactivity
     */
    public static function loggedOutDueToInactivity(): self
    {
        return new self(
            self::NO_ACTIVITY,
            __(
                'You have been automatically logged out due to inactivity of %s seconds.'
                . ' Once you log in again, you should be able to resume the work where you left off.',
            ),
        );
    }
}
