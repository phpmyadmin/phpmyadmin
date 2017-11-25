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
     * Prints Html For Display Import charset
     *
     * @return string
     */
    public static function getHtmlForImportCharset()
    {
        global $cfg;

        return Template::get('display/import/charset')->render([
            'is_encoding_supported' => Encoding::isSupported(),
            'encodings' => Encoding::listEncodings(),
            'import_charset' => isset($cfg['Import']['charset']) ? $cfg['Import']['charset'] : null,
            'dbi' => $GLOBALS['dbi'],
            'disable_is' => $GLOBALS['cfg']['Server']['DisableIS'],
        ]);
    }

    /**
     * Prints Html For Display Import options : file property
     *
     * @param int            $max_upload_size   Max upload size
     * @param ImportPlugin[] $import_list       import list
     * @param String         $local_import_file from upload directory
     *
     * @return string
     */
    public static function getHtmlForImportOptionsFile(
        $max_upload_size, $import_list, $local_import_file
    ) {
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

        $html  = '    <div class="importoptions">';
        $html .= '         <h3>'  . __('File to import:') . '</h3>';
        $html .= Template::get('display/import/compressions')->render([
            'compressions' => $compressions,
        ]);
        $html .= '        <div class="formelementrow" id="upload_form">';

        if ($GLOBALS['is_upload'] && !empty($cfg['UploadDir'])) {
            $html .= '            <ul>';
            $html .= '            <li>';
            $html .= '                <input type="radio" name="file_location" '
                . 'id="radio_import_file" required="required" />';
            $html .= Util::getBrowseUploadFileBlock($max_upload_size);
            $html .= '<br />' . __('You may also drag and drop a file on any page.');
            $html .= '            </li>';
            $html .= '            <li>';
            $html .= '               <input type="radio" name="file_location" '
                . 'id="radio_local_import_file"';
            if (! empty($GLOBALS['timeout_passed'])
                && ! empty($local_import_file)
            ) {
                $html .= ' checked="checked"';
            }
            $html .= ' />';
            $html .= Util::getSelectUploadFileBlock(
                $import_list,
                $cfg['UploadDir']
            );
            $html .= '            </li>';
            $html .= '            </ul>';

        } elseif ($GLOBALS['is_upload']) {
            $html .= Util::getBrowseUploadFileBlock($max_upload_size);
            $html .= '<br />' . __('You may also drag and drop a file on any page.');
        } elseif (!$GLOBALS['is_upload']) {
            $html .= Message::notice(
                __('File uploads are not allowed on this server.')
            )->getDisplay();
        } elseif (!empty($cfg['UploadDir'])) {
            $html .= Util::getSelectUploadFileBlock(
                $import_list,
                $cfg['UploadDir']
            );
        } // end if (web-server upload directory)

        $html .= '        </div>';
        $html .= self::getHtmlForImportCharset();
        $html .= '   </div>';

        return $html;
    }

    /**
     * Prints Html For Display Import options : Partial Import
     *
     * @param String $timeout_passed timeout passed
     * @param String $offset         timeout offset
     *
     * @return string
     */
    public static function getHtmlForImportOptionsPartialImport($timeout_passed, $offset)
    {
        return Template::get('display/import/partial_import_option')->render([
            'timeout_passed' => isset($timeout_passed) ? $timeout_passed : null,
            'offset' => $offset,
        ]);
    }

    /**
     * Prints Html For Display Import options : Other
     *
     * @return string
     */
    public static function getHtmlForImportOptionsOther()
    {
        return Template::get('display/import/other_option')->render();
    }

    /**
     * Prints Html For Display Import options : Format
     *
     * @param ImportPlugin[] $importList import list
     *
     * @return string
     */
    public static function getHtmlForImportOptionsFormat($importList)
    {
        return Template::get('display/import/format_option')->render([
            'import_list' => $importList,
            'can_convert_kanji' => Encoding::canConvertKanji(),
        ]);
    }

    /**
     * Prints Html For Display Import options : submit
     *
     * @return string
     */
    public static function getHtmlForImportOptionsSubmit()
    {
        return Template::get('display/import/submit_option')->render();
    }

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
        $html  = '';
        $html .= '<iframe id="import_upload_iframe" name="import_upload_iframe" '
            . 'width="1" height="1" class="hide"></iframe>';
        $html .= '<div id="import_form_status" class="hide"></div>';
        $html .= '<div id="importmain">';
        $html .= '    <img src="' . $GLOBALS['pmaThemeImage'] . 'ajax_clock_small.gif" '
            . 'width="16" height="16" alt="ajax clock" class="hide" />';

        $html .= Template::get('display/import/javascript')->render([
            'upload_id' => $upload_id,
            'handler' => $_SESSION[$SESSION_KEY]["handler"],
            'pma_theme_image' => $GLOBALS['pmaThemeImage'],
        ]);

        $html .= '    <form id="import_file_form" action="import.php" method="post" '
            . 'enctype="multipart/form-data"';
        $html .= '        name="import"';
        if ($_SESSION[$SESSION_KEY]["handler"] != 'PhpMyAdmin\Plugins\Import\Upload\UploadNoplugin') {
            $html .= ' target="import_upload_iframe"';
        }
        $html .= ' class="ajax"';
        $html .= '>';
        $html .= '    <input type="hidden" name="';
        $html .= $_SESSION[$SESSION_KEY]['handler']::getIdKey();
        $html .= '" value="' . $upload_id . '" />';

        $html .= Template::get('display/import/hidden_inputs')->render([
            'import_type' => $import_type,
            'db' => $db,
            'table' => $table,
        ]);

        $html .= Template::get('display/import/options')->render([
            'import_type' => $import_type,
            'db' => $db,
            'table' => $table,
        ]);

        $html .= self::getHtmlForImportOptionsFile(
            $max_upload_size, $import_list, $local_import_file
        );

        $html .= self::getHtmlForImportOptionsPartialImport($timeout_passed, $offset);

        $html .= self::getHtmlForImportOptionsOther();

        $html .= self::getHtmlForImportOptionsFormat($import_list);

        $html .= self::getHtmlForImportOptionsSubmit();

        $html .= '</form>';
        $html .= '</div>';

        return $html;
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
