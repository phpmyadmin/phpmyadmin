<?php
/**
 * Interface for the transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\FieldMetadata;

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

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string             $buffer  text to be transformed
     * @param mixed[]            $options transformation options
     * @param FieldMetadata|null $meta    meta information
     *
     * @return string the transformed text
     */
    public function applyTransformation(
        string $buffer,
        array $options = [],
        FieldMetadata|null $meta = null,
    ): string;

    /**
     * Returns true if the element requires no wrapping
     *
     * @param mixed[] $options transformation options
     */
    public function applyTransformationNoWrap(array $options = []): bool;
}
