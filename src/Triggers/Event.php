<?php

declare(strict_types=1);

namespace PhpMyAdmin\Triggers;

/**
 * Indicates the kind of statement that activates the trigger.
 */
enum Event: string
{
    case Insert = 'INSERT';
    case Update = 'UPDATE';
    case Delete = 'DELETE';
}
