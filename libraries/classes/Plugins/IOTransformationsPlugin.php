<?php
/**
 * Abstract class for the I/O transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

/**
 * Provides a common interface that will have to be implemented
 * by all of the Input/Output transformations plugins.
 */
abstract class IOTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Specifies whether transformation was successful or not.
     */
    protected bool $success = true;

    /**
     * To store the error message in case of failed transformations.
     */
    protected string $error = '';

    /**
     * Returns the html for input field to override default textarea.
     * Note: Return empty string if default textarea is required.
     *
     * @param mixed[] $column             column details
     * @param int     $rowId              row number
     * @param string  $columnNameAppendix the name attribute
     * @param mixed[] $options            transformation options
     * @param string  $value              Current field value
     * @param string  $textDir            text direction
     * @param int     $fieldIndex         field index
     *
     * @return string the html for input field
     */
    public function getInputHtml(
        array $column,
        int $rowId,
        string $columnNameAppendix,
        array $options,
        string $value,
        string $textDir,
        int $fieldIndex,
    ): string {
        return '';
    }

    /**
     * Returns the array of scripts (filename) required for plugin
     * initialization and handling
     *
     * @return string[] javascripts to be included
     */
    public function getScripts(): array
    {
        return [];
    }

    /**
     * Returns the error message
     *
     * @return string error
     */
    public function getError(): string
    {
        return $this->error;
    }

    /**
     * Returns the success status
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Resets the object properties
     */
    public function reset(): void
    {
        $this->success = true;
        $this->error = '';
    }
}
