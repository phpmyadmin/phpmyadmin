<?php

declare(strict_types=1);

namespace PhpMyAdmin;

enum MessageType
{
    case Success;
    case Notice;
    case Error;

    /**
     * This value is used in generation of MD5 code.
     */
    public function getNumericalValue(): string
    {
        return match ($this) {
            self::Success => '1',
            self::Notice => '2',
            self::Error => '8',
        };
    }
}
