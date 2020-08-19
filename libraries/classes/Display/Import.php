<?php

declare(strict_types=1);

namespace PhpMyAdmin\Display;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Charsets\Charset;
use PhpMyAdmin\Core;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\Util;
use function function_exists;
use function intval;

/**
 * Functions for displaying import for: server, database and table
 */
class Import
{
    /**
     * @param string $importType    Import type: server|database|table
     * @param string $db            Selected DB
     * @param string $table         Selected Table
     * @param int    $maxUploadSize Max upload size
     *
     * @return array
     */
    public static function get(
        $importType,
        $db,
        $table,
        $maxUploadSize,
        string $sessionKey,
        string $uploadId,
        array $importList
    ): array {
        global $cfg;

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

        return [
            'upload_id' => $uploadId,
            'handler' => $_SESSION[$sessionKey]['handler'],
            'id_key' => $_SESSION[$sessionKey]['handler']::getIdKey(),
            'pma_theme_image' => $GLOBALS['pmaThemeImage'],
            'import_type' => $importType,
            'db' => $db,
            'table' => $table,
            'max_upload_size' => $maxUploadSize,
            'import_list' => $importList,
            'local_import_file' => $localImportFile,
            'is_upload' => $GLOBALS['is_upload'],
            'upload_dir' => $cfg['UploadDir'] ?? null,
            'timeout_passed_global' => $GLOBALS['timeout_passed'] ?? null,
            'compressions' => $compressions,
            'is_encoding_supported' => Encoding::isSupported(),
            'encodings' => Encoding::listEncodings(),
            'import_charset' => $cfg['Import']['charset'] ?? null,
            'timeout_passed' => $timeoutPassed ?? null,
            'offset' => $offset ?? null,
            'can_convert_kanji' => Encoding::canConvertKanji(),
            'charsets' => $charsets,
            'is_foreign_key_check' => Util::isForeignKeyCheck(),
            'user_upload_dir' => Util::userDir($cfg['UploadDir'] ?? ''),
            'local_files' => self::getLocalFiles($importList),
        ];
    }

    /**
     * @param array $importList List of plugin instances.
     *
     * @return false|string
     */
    private static function getLocalFiles(array $importList)
    {
        $fileListing = new FileListing();

        $extensions = '';
        foreach ($importList as $importPlugin) {
            if (! empty($extensions)) {
                $extensions .= '|';
            }
            $extensions .= $importPlugin->getProperties()->getExtension();
        }

        $matcher = '@\.(' . $extensions . ')(\.(' . $fileListing->supportedDecompressions() . '))?$@';

        $active = isset($GLOBALS['timeout_passed'], $GLOBALS['local_import_file']) && $GLOBALS['timeout_passed']
            ? $GLOBALS['local_import_file']
            : '';

        return $fileListing->getFileSelectOptions(
            Util::userDir($GLOBALS['cfg']['UploadDir'] ?? ''),
            $matcher,
            $active
        );
    }
}
