<?php

declare(strict_types=1);

namespace PhpMyAdmin\Triggers;

/**
 * Whether the trigger activates before or after the triggering event.
 */
enum Timing: string
{
    case Before = 'BEFORE';
    case After = 'AFTER';
}
