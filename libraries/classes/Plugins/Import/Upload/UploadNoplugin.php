<?php
/**
 * Provides upload functionalities for the import plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import\Upload;

use PhpMyAdmin\Plugins\UploadInterface;

use function array_key_exists;
use function trim;

/**
 * Implementation for no plugin
 */
class UploadNoplugin implements UploadInterface
{
    /**
     * Gets the specific upload ID Key
     *
     * @return string ID Key
     */
    public static function getIdKey()
    {
        return 'noplugin';
    }

    /**
     * Returns upload status.
     *
     * This is implementation when no webserver support exists,
     * so it returns just zeroes.
     *
     * @param string $id upload id
     *
     * @return array|null
     */
    public static function getUploadStatus($id)
    {
        $GLOBALS['SESSION_KEY'] = $GLOBALS['SESSION_KEY'] ?? null;

        if (trim($id) == '') {
            return null;
        }

        if (! array_key_exists($id, $_SESSION[$GLOBALS['SESSION_KEY']])) {
            $_SESSION[$GLOBALS['SESSION_KEY']][$id] = [
                'id' => $id,
                'finished' => false,
                'percent' => 0,
                'total' => 0,
                'complete' => 0,
                'plugin' => self::getIdKey(),
            ];
        }

        return $_SESSION[$GLOBALS['SESSION_KEY']][$id];
    }
}
