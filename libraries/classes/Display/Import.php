<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying import for: server, database and table
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Display;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Core;
use PhpMyAdmin\Display\ImportAjax;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\Display\Import class
 *
 * @package PhpMyAdmin
 */
class Import
{
    /**
     * Prints Html For Display Import
     *
     * @param int            $upload_id         The selected upload id
     * @param String         $import_type       Import type: server, database, table
     * @param String         $db                Selected DB
     * @param String         $table             Selected Table
     * @param int            $max_upload_size   Max upload size
     * @param ImportPlugin[] $import_list       Import list
     * @param String         $timeout_passed    Timeout passed
     * @param String         $offset            Timeout offset
     * @param String         $local_import_file from upload directory
     *
     * @return string
     */
    public static function getHtmlForImport(
        $upload_id, $import_type, $db, $table,
        $max_upload_size, $import_list, $timeout_passed, $offset, $local_import_file
    ) {
        global $SESSION_KEY;
        global $cfg;

        // zip, gzip and bzip2 encode features
        $compressions = array();
        if ($cfg['GZipDump'] && @function_exists('gzopen')) {
            $compressions[] = 'gzip';
        }
        if ($cfg['BZipDump'] && @function_exists('bzopen')) {
            $compressions[] = 'bzip2';
        }
        if ($cfg['ZipDump'] && @function_exists('zip_open')) {
            $compressions[] = 'zip';
        }

        return Template::get('display/import/import')->render([
            'upload_id' => $upload_id,
            'handler' => $_SESSION[$SESSION_KEY]["handler"],
            'id_key' => $_SESSION[$SESSION_KEY]['handler']::getIdKey(),
            'pma_theme_image' => $GLOBALS['pmaThemeImage'],
            'import_type' => $import_type,
            'db' => $db,
            'table' => $table,
            'max_upload_size' => $max_upload_size,
            'import_list' => $import_list,
            'local_import_file' => $local_import_file,
            'is_upload' => $GLOBALS['is_upload'],
            'upload_dir' => isset($cfg['UploadDir']) ? $cfg['UploadDir'] : null,
            'timeout_passed_global' => isset($GLOBALS['timeout_passed']) ? $GLOBALS['timeout_passed'] : null,
            'compressions' => $compressions,
            'is_encoding_supported' => Encoding::isSupported(),
            'encodings' => Encoding::listEncodings(),
            'import_charset' => isset($cfg['Import']['charset']) ? $cfg['Import']['charset'] : null,
            'dbi' => $GLOBALS['dbi'],
            'disable_is' => $cfg['Server']['DisableIS'],
            'timeout_passed' => isset($timeout_passed) ? $timeout_passed : null,
            'offset' => $offset,
            'import_list' => $import_list,
            'can_convert_kanji' => Encoding::canConvertKanji(),
        ]);
    }

    /**
     * Gets HTML to display import dialogs
     *
     * @param string $import_type     Import type: server|database|table
     * @param string $db              Selected DB
     * @param string $table           Selected Table
     * @param int    $max_upload_size Max upload size
     *
     * @return string $html
     */
    public static function getImportDisplay($import_type, $db, $table, $max_upload_size)
    {
        global $SESSION_KEY;

        list(
            $SESSION_KEY,
            $upload_id,
        ) = ImportAjax::uploadProgressSetup();

        /* Scan for plugins */
        /* @var $import_list ImportPlugin[] */
        $import_list = Plugins::getPlugins(
            "import",
            'libraries/classes/Plugins/Import/',
            $import_type
        );

        /* Fail if we didn't find any plugin */
        if (empty($import_list)) {
            Message::error(
                __(
                    'Could not load import plugins, please check your installation!'
                )
            )->display();
            exit;
        }

        if (Core::isValid($_REQUEST['offset'], 'numeric')) {
            $offset = intval($_REQUEST['offset']);
        }
        if (isset($_REQUEST['timeout_passed'])) {
            $timeout_passed = $_REQUEST['timeout_passed'];
        }

        $local_import_file = '';
        if (isset($_REQUEST['local_import_file'])) {
            $local_import_file = $_REQUEST['local_import_file'];
        }

        $timeout_passed_str = isset($timeout_passed)? $timeout_passed : null;
        $offset_str = isset($offset)? $offset : null;
        return self::getHtmlForImport(
            $upload_id,
            $import_type,
            $db,
            $table,
            $max_upload_size,
            $import_list,
            $timeout_passed_str,
            $offset_str,
            $local_import_file
        );
    }
}
