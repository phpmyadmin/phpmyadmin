<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function defined;

/**
 * Class to handle the phpMyAdmin version
 */
final class Version
{
    /*
     * Packaging people can add a version suffix using the VERSION_SUFFIX constant at vendor_config.php
     */

    public const VERSION_MAJOR = 5;
    public const VERSION_MINOR = 1;
    public const VERSION_PATCH = 0;

    // The version will be {major}.{minor}.{patch}-dev
    public const IS_DEV = true;

    // The version will be {major}.{minor}.{patch}-{PRE_RELEASE_NAME} if not empty
    public const PRE_RELEASE_NAME = '';

    /**
     * Get the current phpMyAdmin series version
     *
     * @example 5.1
     */
    public static function phpMyAdminSeriesVersion(): string
    {
        return self::VERSION_MAJOR . '.' . self::VERSION_MINOR;
    }

    /**
     * Get the current phpMyAdmin version
     */
    public static function phpMyAdminVersion(): string
    {
        $versionRaw = self::VERSION_MAJOR . '.' . self::VERSION_MINOR . '.' . self::VERSION_PATCH;

        if (self::IS_DEV) {
            return $versionRaw . '-dev';
        }

        if (self::PRE_RELEASE_NAME !== '') {
            return $versionRaw . '-' . self::PRE_RELEASE_NAME;
        }

        if (defined('VERSION_SUFFIX')) {
            return $versionRaw . VERSION_SUFFIX;
        }

        return $versionRaw;
    }

    /**
     * If the current version is a dev version
     */
    public static function isDev(): bool
    {
        return self::IS_DEV;
    }
}
