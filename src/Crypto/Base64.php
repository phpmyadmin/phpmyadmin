<?php

declare(strict_types=1);

namespace PhpMyAdmin\Crypto;

use SensitiveParameter;
use SodiumException;

use function sodium_base642bin;
use function sodium_bin2base64;

use const SODIUM_BASE64_VARIANT_ORIGINAL;
use const SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING;

final class Base64
{
    /**
     * @return ($string is non-empty-string ? non-empty-string : string)
     *
     * @throws SodiumException
     */
    public static function encode(
        #[SensitiveParameter]
        string $string,
    ): string {
        return sodium_bin2base64($string, SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /**
     * @return ($string is non-empty-string ? non-empty-string : string)
     *
     * @throws SodiumException
     */
    public static function encodeUrlSafeNoPadding(
        #[SensitiveParameter]
        string $string,
    ): string {
        return sodium_bin2base64($string, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING);
    }

    /** @throws SodiumException */
    public static function decode(
        #[SensitiveParameter]
        string $string,
        string $ignore = '',
    ): string {
        return sodium_base642bin($string, SODIUM_BASE64_VARIANT_ORIGINAL, $ignore);
    }

    /** @throws SodiumException */
    public static function decodeUrlSafeNoPadding(
        #[SensitiveParameter]
        string $string,
        string $ignore = '',
    ): string {
        return sodium_base642bin($string, SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING, $ignore);
    }
}
