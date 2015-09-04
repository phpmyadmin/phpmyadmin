<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Interface for the import->upload plugins
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\plugins;

/**
 * Provides a common interface that will have to implemented by all of the
 * import->upload plugins.
 *
 * @package PhpMyAdmin
 */
interface UploadInterface
{
    /**
     * Gets the specific upload ID Key
     *
     * @return string ID Key
     */
    public static function getIdKey();

    /**
     * Returns upload status.
     *
     * @param string $id upload id
     *
     * @return array|null
     */
    public static function getUploadStatus($id);
}
