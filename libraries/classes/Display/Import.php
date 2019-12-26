<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying import for: server, database and table
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Display;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Core;
use PhpMyAdmin\Display\ImportAjax;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ImportPlugin;
use PhpMyAdmin\Template;

/**
 * PhpMyAdmin\Display\Import class
 *
 * @package PhpMyAdmin
 */
class Import
{
    /**
     * Gets HTML to display import dialogs
     *
     * @param string $importType    Import type: server|database|table
     * @param string $db            Selected DB
     * @param string $table         Selected Table
     * @param int    $maxUploadSize Max upload size
     *
     * @return string HTML
     */
    public static function get($importType, $db, $table, $maxUploadSize)
    {
        global $cfg;
        global $SESSION_KEY;

        $template = new Template();

        list(
            $SESSION_KEY,
            $uploadId,
        ) = ImportAjax::uploadProgressSetup();

        /* Scan for plugins */
        /** @var ImportPlugin[] $importList */
        $importList = Plugins::getPlugins(
            "import",
            'libraries/classes/Plugins/Import/',
            $importType
        );

        /* Fail if we didn't find any plugin */
        if (empty($importList)) {
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
            $timeoutPassed = $_REQUEST['timeout_passed'];
        }

        $localImportFile = '';
        if (isset($_REQUEST['local_import_file'])) {
            $localImportFile = $_REQUEST['local_import_file'];
        }

        // zip, gzip and bzip2 encode features
        $compressions = [];
        if ($cfg['GZipDump'] && function_exists('gzopen')) {
            $compressions[] = 'gzip';
        }
        if ($cfg['BZipDump'] && function_exists('bzopen')) {
            $compressions[] = 'bzip2';
        }
        if ($cfg['ZipDump'] && function_exists('zip_open')) {
            $compressions[] = 'zip';
        }

        $allCharsets = Charsets::getCharsets($GLOBALS['dbi'], $cfg['Server']['DisableIS']);
        $charsets = [];
        /** @var Charset $charset */
        foreach ($allCharsets as $charset) {
            $charsets[] = [
                'name' => $charset->getName(),
                'description' => $charset->getDescription(),
            ];
        }

        return $template->render('display/import/import', [
            'upload_id' => $uploadId,
            'handler' => $_SESSION[$SESSION_KEY]["handler"],
            'id_key' => $_SESSION[$SESSION_KEY]['handler']::getIdKey(),
            'pma_theme_image' => $GLOBALS['pmaThemeImage'],
            'import_type' => $importType,
            'db' => $db,
            'table' => $table,
            'max_upload_size' => $maxUploadSize,
            'import_list' => $importList,
            'local_import_file' => $localImportFile,
            'is_upload' => $GLOBALS['is_upload'],
            'upload_dir' => isset($cfg['UploadDir']) ? $cfg['UploadDir'] : null,
            'timeout_passed_global' => isset($GLOBALS['timeout_passed']) ? $GLOBALS['timeout_passed'] : null,
            'compressions' => $compressions,
            'is_encoding_supported' => Encoding::isSupported(),
            'encodings' => Encoding::listEncodings(),
            'import_charset' => isset($cfg['Import']['charset']) ? $cfg['Import']['charset'] : null,
            'timeout_passed' => isset($timeoutPassed) ? $timeoutPassed : null,
            'offset' => isset($offset) ? $offset : null,
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'charsets' => $charsets,
        ]);
    }
}
