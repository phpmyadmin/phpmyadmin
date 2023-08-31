<?php
/**
 * Interface for the import->upload plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

/**
 * Provides a common interface that will have to implemented by all of the
 * import->upload plugins.
 */
interface UploadInterface
{
    /**
     * Gets the specific upload ID Key
     *
     * @return string ID Key
     */
    public static function getIdKey(): string;

    /**
     * Returns upload status.
     *
     * @param string $id upload id
     */
    public static function getUploadStatus(string $id): array|null;
}
