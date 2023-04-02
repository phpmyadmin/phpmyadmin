<?php

declare(strict_types=1);

namespace PhpMyAdmin\Import;

use PhpMyAdmin\Core;

use function defined;
use function function_exists;
use function header;
use function ini_get;
use function json_encode;
use function sprintf;
use function ucwords;
use function uniqid;

/**
 * Handles plugins that show the upload progress.
 */
final class Ajax
{
    /**
     * Sets up some variables for upload progress
     *
     * @return mixed[]
     */
    public static function uploadProgressSetup(): array
    {
        /**
         * constant for differentiating array in $_SESSION variable
         */
        $sessionKey = '__upload_status';

        /**
         * sets default plugin for handling the import process
         */
        $_SESSION[$sessionKey]['handler'] = '';

        /**
         * unique ID for each upload
         */
        $uploadId = ! defined('TESTSUITE') ? uniqid('') : 'abc1234567890';

        /**
         * list of available plugins
         */
        $plugins = [
            // in PHP 5.4 session-based upload progress was problematic, see closed bug 3964
            //"session",
            'progress',
            'noplugin',
        ];

        // select available plugin
        foreach ($plugins as $plugin) {
            $check = $plugin . 'Check';

            if (self::$check()) {
                $uploadClass = 'PhpMyAdmin\Plugins\Import\Upload\Upload' . ucwords($plugin);
                $_SESSION[$sessionKey]['handler'] = $uploadClass;
                break;
            }
        }

        return [$sessionKey, $uploadId, $plugins];
    }

    /**
     * Checks if PhpMyAdmin\Plugins\Import\Upload\UploadProgress bar extension is
     * available.
     */
    public static function progressCheck(): bool
    {
        return ! defined('TESTSUITE') && function_exists('uploadprogress_get_info');
    }

    /**
     * Checks if PHP 5.4 session upload-progress feature is available.
     */
    public static function sessionCheck(): bool
    {
        return ! defined('TESTSUITE') && ini_get('session.upload_progress.enabled') === '1';
    }

    /**
     * Default plugin for handling import.
     * If no other plugin is available, noplugin is used.
     *
     * @return true
     */
    public static function nopluginCheck(): bool
    {
        return true;
    }

    /**
     * The function outputs json encoded status of uploaded.
     * It uses PMA_getUploadStatus, which is defined in plugin's file.
     *
     * @param string $id ID of transfer, usually $upload_id
     */
    public static function status(string $id): void
    {
        foreach (Core::headerJSON() as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }

        echo json_encode(
            $_SESSION[$GLOBALS['SESSION_KEY']]['handler']::getUploadStatus($id),
        );
    }
}
