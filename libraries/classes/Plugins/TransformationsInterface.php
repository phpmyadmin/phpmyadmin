<?php
/**
 * Interface for the transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

/**
 * Provides a common interface that will have to be implemented by all of the
 * transformations plugins.
 */
interface TransformationsInterface
{
    /**
     * Gets the transformation description
     */
    public static function getInfo(): string;

    /**
     * Gets the specific MIME type
     */
    public static function getMIMEType(): string;

    /**
     * Gets the specific MIME subtype
     */
    public static function getMIMESubtype(): string;

    /**
     * Gets the transformation name of the specific plugin
     */
    public static function getName(): string;
}
