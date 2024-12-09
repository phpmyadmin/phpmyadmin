<?php
/**
 * function for the main export logic
 */

declare(strict_types=1);

namespace PhpMyAdmin\Export;

use PhpMyAdmin\Config;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Encoding;
use PhpMyAdmin\Exceptions\ExportException;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\ZipExtension;

use function __;
use function array_filter;
use function array_merge_recursive;
use function error_get_last;
use function fclose;
use function file_exists;
use function fopen;
use function function_exists;
use function fwrite;
use function gzencode;
use function header;
use function htmlentities;
use function htmlspecialchars;
use function http_build_query;
use function implode;
use function in_array;
use function ini_get;
use function ini_parse_quantity;
use function is_array;
use function is_file;
use function is_numeric;
use function is_string;
use function is_writable;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function ob_list_handlers;
use function preg_match;
use function preg_replace;
use function str_contains;
use function strlen;
use function substr;
use function time;

use const ENT_COMPAT;

/**
 * PhpMyAdmin\Export\Export class
 */
class Export
{
    public string $dumpBuffer = '';

    public int $dumpBufferLength = 0;

    /** @var mixed[] */
    public array $dumpBufferObjects = [];

    public function __construct(private DatabaseInterface $dbi)
    {
    }

    /**
     * Sets a session variable upon a possible fatal error during export
     */
    public function shutdown(): void
    {
        $error = error_get_last();
        if ($error === null || ! str_contains($error['message'], 'execution time')) {
            return;
        }

        //set session variable to check if there was error while exporting
        $_SESSION['pma_export_error'] = $error['message'];
    }

    /**
     * Detect ob_gzhandler
     */
    public function isGzHandlerEnabled(): bool
    {
        /** @var string[] $handlers */
        $handlers = ob_list_handlers();

        return in_array('ob_gzhandler', $handlers, true);
    }

    /**
     * Detect whether gzencode is needed; it might not be needed if
     * the server is already compressing by itself
     */
    public function gzencodeNeeded(): bool
    {
        /**
         * We should gzencode only if the function exists
         * but we don't want to compress twice, therefore
         * gzencode only if transparent compression is not enabled
         * and gz compression was not asked via $cfg['OBGzip']
         * but transparent compression does not apply when saving to server
         */
        return function_exists('gzencode')
            && ((! ini_get('zlib.output_compression')
                    && ! $this->isGzHandlerEnabled())
                || $GLOBALS['save_on_server']
                || Config::getInstance()->get('PMA_USR_BROWSER_AGENT') === 'CHROME');
    }

    /**
     * Output handler for all exports, if needed buffering, it stores data into
     * $this->dumpBuffer, otherwise it prints them out.
     *
     * @param string $line the insert statement
     */
    public function outputHandler(string $line): bool
    {
        $GLOBALS['time_start'] ??= null;
        $GLOBALS['save_filename'] ??= null;

        // Kanji encoding convert feature
        if ($GLOBALS['output_kanji_conversion']) {
            $line = Encoding::kanjiStrConv($line, $GLOBALS['knjenc'], $GLOBALS['xkana'] ?? '');
        }

        // If we have to buffer data, we will perform everything at once at the end
        if ($GLOBALS['buffer_needed']) {
            $this->dumpBuffer .= $line;
            if ($GLOBALS['onfly_compression']) {
                $this->dumpBufferLength += strlen($line);

                if ($this->dumpBufferLength > $GLOBALS['memory_limit']) {
                    if ($GLOBALS['output_charset_conversion']) {
                        $this->dumpBuffer = Encoding::convertString('utf-8', $GLOBALS['charset'], $this->dumpBuffer);
                    }

                    if ($GLOBALS['compression'] === 'gzip' && $this->gzencodeNeeded()) {
                        // as a gzipped file
                        // without the optional parameter level because it bugs
                        $this->dumpBuffer = (string) gzencode($this->dumpBuffer);
                    }

                    if ($GLOBALS['save_on_server']) {
                        $writeResult = @fwrite($GLOBALS['file_handle'], $this->dumpBuffer);
                        // Here, use strlen rather than mb_strlen to get the length
                        // in bytes to compare against the number of bytes written.
                        if ($writeResult != strlen($this->dumpBuffer)) {
                            $GLOBALS['message'] = Message::error(
                                __('Insufficient space to save the file %s.'),
                            );
                            $GLOBALS['message']->addParam($GLOBALS['save_filename']);

                            return false;
                        }
                    } else {
                        echo $this->dumpBuffer;
                    }

                    $this->dumpBuffer = '';
                    $this->dumpBufferLength = 0;
                }
            } else {
                $timeNow = time();
                if ($GLOBALS['time_start'] >= $timeNow + 30) {
                    $GLOBALS['time_start'] = $timeNow;
                    header('X-pmaPing: Pong');
                }
            }
        } elseif ($GLOBALS['asfile']) {
            if ($GLOBALS['output_charset_conversion']) {
                $line = Encoding::convertString('utf-8', $GLOBALS['charset'], $line);
            }

            if ($GLOBALS['save_on_server'] && $line !== '') {
                if ($GLOBALS['file_handle'] !== null) {
                    $writeResult = @fwrite($GLOBALS['file_handle'], $line);
                } else {
                    $writeResult = false;
                }

                // Here, use strlen rather than mb_strlen to get the length
                // in bytes to compare against the number of bytes written.
                if ($writeResult === 0 || $writeResult === false || $writeResult != strlen($line)) {
                    $GLOBALS['message'] = Message::error(
                        __('Insufficient space to save the file %s.'),
                    );
                    $GLOBALS['message']->addParam($GLOBALS['save_filename']);

                    return false;
                }

                $timeNow = time();
                if ($GLOBALS['time_start'] >= $timeNow + 30) {
                    $GLOBALS['time_start'] = $timeNow;
                    header('X-pmaPing: Pong');
                }
            } else {
                // We export as file - output normally
                echo $line;
            }
        } else {
            // We export as html - replace special chars
            echo htmlspecialchars($line, ENT_COMPAT);
        }

        return true;
    }

    /**
     * Returns HTML containing the footer for a displayed export
     *
     * @param string $exportType the export type
     * @param string $db         the database name
     * @param string $table      the table name
     *
     * @return string the HTML output
     */
    public function getHtmlForDisplayedExportFooter(
        string $exportType,
        string $db,
        string $table,
    ): string {
        /**
         * Close the html tags and add the footers for on-screen export
         */
        return '</textarea>'
            . '    </form>'
            . '<br>'
            // bottom back button
            . $this->getHTMLForBackButton($exportType, $db, $table)
            . $this->getHTMLForRefreshButton($exportType)
            . '<br><br>'
            . $this->getHTMLForCopyButton()
            . '</div>'
            . '<script>' . "\n"
            . '//<![CDATA[' . "\n"
            . 'var $body = $("body");' . "\n"
            . '$("#textSQLDUMP")' . "\n"
            . '.width($body.width() - 50)' . "\n"
            . '.height($body.height() - 100);' . "\n"
            . '//]]>' . "\n"
            . '</script>' . "\n";
    }

    /**
     * Computes the memory limit for export
     *
     * @return int the memory limit
     */
    public function getMemoryLimit(): int
    {
        $memoryLimit = ini_parse_quantity((string) ini_get('memory_limit'));

        // Some of memory is needed for other things and as threshold.
        // During export I had allocated (see memory_get_usage function)
        // approx 1.2MB so this comes from that.
        if ($memoryLimit > 1500000) {
            $memoryLimit -= 1500000;
        }

        // Some memory is needed for compression, assume 1/3
        $memoryLimit /= 8;

        return (int) $memoryLimit;
    }

    public function getFinalFilename(
        ExportPlugin $exportPlugin,
        string $compression,
        string $filename,
    ): string {
        // Grab basic dump extension and mime type
        // Check if the user already added extension;
        // get the substring where the extension would be if it was included
        $requiredExtension = '.' . $exportPlugin->getProperties()->getExtension();
        $extensionLength = mb_strlen($requiredExtension);
        $userExtension = mb_substr($filename, -$extensionLength);
        if (mb_strtolower($userExtension) !== $requiredExtension) {
            $filename .= $requiredExtension;
        }

        // If dump is going to be compressed, add compression to extension
        if ($compression === 'gzip') {
            $filename .= '.gz';
        } elseif ($compression === 'zip') {
            $filename .= '.zip';
        }

        return $filename;
    }

    public function getMimeType(ExportPlugin $exportPlugin, string $compression): string
    {
        return match ($compression) {
            'gzip' => 'application/x-gzip',
            'zip' => 'application/zip',
            default => $exportPlugin->getProperties()->getMimeType(),
        };
    }

    public function rememberFilename(
        Config $config,
        string $exportType,
        string $filenameTemplate,
    ): void {
        if ($exportType === 'server') {
            $config->setUserValue('pma_server_filename_template', 'Export/file_template_server', $filenameTemplate);
        } elseif ($exportType === 'database') {
            $config->setUserValue('pma_db_filename_template', 'Export/file_template_database', $filenameTemplate);
        } elseif ($exportType === 'raw') {
            $config->setUserValue('pma_raw_filename_template', 'Export/file_template_raw', $filenameTemplate);
        } else {
            $config->setUserValue('pma_table_filename_template', 'Export/file_template_table', $filenameTemplate);
        }
    }

    /**
     * Open the export file
     *
     * @param string $filename    the export filename
     * @param bool   $quickExport whether it's a quick export or not
     *
     * @psalm-return array{string, Message|null, resource|null}
     */
    public function openFile(string $filename, bool $quickExport): array
    {
        $fileHandle = null;
        $message = null;
        $doNotSaveItOver = true;

        if (isset($_POST['quick_export_onserver_overwrite'])) {
            $doNotSaveItOver = $_POST['quick_export_onserver_overwrite'] !== 'saveitover';
        }

        $saveFilename = Util::userDir(Config::getInstance()->settings['SaveDir'] ?? '')
            . preg_replace('@[/\\\\]@', '_', $filename);

        if (
            @file_exists($saveFilename)
            && ((! $quickExport && empty($_POST['onserver_overwrite']))
            || ($quickExport
            && $doNotSaveItOver))
        ) {
            $message = Message::error(
                __(
                    'File %s already exists on server, change filename or check overwrite option.',
                ),
            );
            $message->addParam($saveFilename);
        } elseif (@is_file($saveFilename) && ! @is_writable($saveFilename)) {
            $message = Message::error(
                __(
                    'The web server does not have permission to save the file %s.',
                ),
            );
            $message->addParam($saveFilename);
        } else {
            $fileHandle = @fopen($saveFilename, 'w');
            if ($fileHandle === false) {
                $fileHandle = null;
                $message = Message::error(
                    __(
                        'The web server does not have permission to save the file %s.',
                    ),
                );
                $message->addParam($saveFilename);
            }
        }

        return [$saveFilename, $message, $fileHandle];
    }

    /**
     * Close the export file
     *
     * @param resource $fileHandle   the export file handle
     * @param string   $dumpBuffer   the current dump buffer
     * @param string   $saveFilename the export filename
     *
     * @return Message a message object (or empty string)
     */
    public function closeFile(
        $fileHandle,
        string $dumpBuffer,
        string $saveFilename,
    ): Message {
        $writeResult = @fwrite($fileHandle, $dumpBuffer);
        fclose($fileHandle);
        // Here, use strlen rather than mb_strlen to get the length
        // in bytes to compare against the number of bytes written.
        if ($dumpBuffer !== '' && $writeResult !== strlen($dumpBuffer)) {
            return new Message(
                __('Insufficient space to save the file %s.'),
                MessageType::Error,
                [$saveFilename],
            );
        }

        return new Message(
            __('Dump has been saved to file %s.'),
            MessageType::Success,
            [$saveFilename],
        );
    }

    /**
     * Compress the export buffer
     *
     * @param mixed[]|string $dumpBuffer  the current dump buffer
     * @param string         $compression the compression mode
     * @param string         $filename    the filename
     */
    public function compress(array|string $dumpBuffer, string $compression, string $filename): array|string|bool
    {
        if ($compression === 'zip' && function_exists('gzcompress')) {
            $zipExtension = new ZipExtension();
            $filename = substr($filename, 0, -4); // remove extension (.zip)
            $dumpBuffer = $zipExtension->createFile($dumpBuffer, $filename);
        } elseif ($compression === 'gzip' && $this->gzencodeNeeded() && is_string($dumpBuffer)) {
            // without the optional parameter level because it bugs
            $dumpBuffer = gzencode($dumpBuffer);
        }

        return $dumpBuffer;
    }

    /**
     * Saves the dump buffer for a particular table in an array
     * Used in separate files export
     *
     * @param string $objectName the name of current object to be stored
     * @param bool   $append     optional boolean to append to an existing index or not
     */
    public function saveObjectInBuffer(string $objectName, bool $append = false): void
    {
        if ($this->dumpBuffer !== '') {
            if ($append && isset($this->dumpBufferObjects[$objectName])) {
                $this->dumpBufferObjects[$objectName] .= $this->dumpBuffer;
            } else {
                $this->dumpBufferObjects[$objectName] = $this->dumpBuffer;
            }
        }

        // Re - initialize
        $this->dumpBuffer = '';
        $this->dumpBufferLength = 0;
    }

    /**
     * Returns HTML containing the header for a displayed export
     *
     * @param string $exportType the export type
     * @param string $db         the database name
     * @param string $table      the table name
     *
     * @return string the generated HTML and back button
     */
    public function getHtmlForDisplayedExportHeader(
        string $exportType,
        string $db,
        string $table,
    ): string {
        /**
         * Displays a back button with all the $_POST data in the URL
         */
        return '<div>'
            . '<br>'
            . $this->getHTMLForBackButton($exportType, $db, $table)
            . $this->getHTMLForRefreshButton($exportType)
            . '<br><br>'
            . $this->getHTMLForCopyButton()
            . '<br>'
            . '<form name="nofunction">'
            . '<textarea name="sqldump" cols="50" rows="30" '
            . 'id="textSQLDUMP" wrap="OFF">';
    }

    /**
     * Export at the server level
     *
     * @param string|mixed[] $dbSelect        the selected databases to export
     * @param string         $whatStrucOrData structure or data or both
     * @param ExportPlugin   $exportPlugin    the selected export plugin
     * @param string         $errorUrl        the URL in case of error
     * @param string         $exportType      the export type
     * @param bool           $doRelation      whether to export relation info
     * @param bool           $doComments      whether to add comments
     * @param bool           $doMime          whether to add MIME info
     * @param bool           $doDates         whether to add dates
     * @param mixed[]        $aliases         alias information for db/table/column
     * @param string         $separateFiles   whether it is a separate-files export
     */
    public function exportServer(
        string|array $dbSelect,
        string $whatStrucOrData,
        ExportPlugin $exportPlugin,
        string $errorUrl,
        string $exportType,
        bool $doRelation,
        bool $doComments,
        bool $doMime,
        bool $doDates,
        array $aliases,
        string $separateFiles,
    ): void {
        if (is_array($dbSelect) && $dbSelect !== []) {
            $tmpSelect = implode('|', $dbSelect);
            $tmpSelect = '|' . $tmpSelect . '|';
        }

        // Walk over databases
        foreach ($this->dbi->getDatabaseList() as $currentDb) {
            if (! isset($tmpSelect) || ! str_contains(' ' . $tmpSelect, '|' . $currentDb . '|')) {
                continue;
            }

            $tables = $this->dbi->getTables($currentDb);
            $this->exportDatabase(
                DatabaseName::from($currentDb),
                $tables,
                $whatStrucOrData,
                $tables,
                $tables,
                $exportPlugin,
                $errorUrl,
                $exportType,
                $doRelation,
                $doComments,
                $doMime,
                $doDates,
                $aliases,
                $separateFiles === 'database' ? $separateFiles : '',
            );
            if ($separateFiles !== 'server') {
                continue;
            }

            $this->saveObjectInBuffer($currentDb);
        }
    }

    /**
     * Export at the database level
     *
     * @param DatabaseName $db              the database to export
     * @param string[]     $tables          the tables to export
     * @param string       $whatStrucOrData structure or data or both
     * @param string[]     $tableStructure  whether to export structure for each table
     * @param string[]     $tableData       whether to export data for each table
     * @param ExportPlugin $exportPlugin    the selected export plugin
     * @param string       $errorUrl        the URL in case of error
     * @param string       $exportType      the export type
     * @param bool         $doRelation      whether to export relation info
     * @param bool         $doComments      whether to add comments
     * @param bool         $doMime          whether to add MIME info
     * @param bool         $doDates         whether to add dates
     * @param mixed[]      $aliases         Alias information for db/table/column
     * @param string       $separateFiles   whether it is a separate-files export
     */
    public function exportDatabase(
        DatabaseName $db,
        array $tables,
        string $whatStrucOrData,
        array $tableStructure,
        array $tableData,
        ExportPlugin $exportPlugin,
        string $errorUrl,
        string $exportType,
        bool $doRelation,
        bool $doComments,
        bool $doMime,
        bool $doDates,
        array $aliases,
        string $separateFiles,
    ): void {
        $dbAlias = ! empty($aliases[$db->getName()]['alias'])
            ? $aliases[$db->getName()]['alias'] : '';

        if (! $exportPlugin->exportDBHeader($db->getName(), $dbAlias)) {
            return;
        }

        if (! $exportPlugin->exportDBCreate($db->getName(), $exportType, $dbAlias)) {
            return;
        }

        if ($separateFiles === 'database') {
            $this->saveObjectInBuffer('database', true);
        }

        if (
            ($GLOBALS['sql_structure_or_data'] === 'structure'
            || $GLOBALS['sql_structure_or_data'] === 'structure_and_data')
            && isset($GLOBALS['sql_procedure_function'])
        ) {
            $exportPlugin->exportRoutines($db->getName(), $aliases);

            if ($separateFiles === 'database') {
                $this->saveObjectInBuffer('routines');
            }
        }

        $views = [];

        if ($tables !== []) {
            // Prefetch table information to improve performance.
            // Table status will get saved in Query Cache,
            // and all instantiations of Table below should be much faster.
            $this->dbi->getTablesFull($db->getName(), $tables);
        }

        foreach ($tables as $table) {
            $tableObject = new Table($table, $db->getName(), $this->dbi);
            // if this is a view, collect it for later;
            // views must be exported after the tables
            $isView = $tableObject->isView();
            if ($isView) {
                $views[] = $table;
            }

            if (
                ($whatStrucOrData === 'structure'
                || $whatStrucOrData === 'structure_and_data')
                && in_array($table, $tableStructure, true)
            ) {
                // for a view, export a stand-in definition of the table
                // to resolve view dependencies (only when it's a single-file export)
                if ($isView) {
                    if (
                        $separateFiles === ''
                        && isset($GLOBALS['sql_create_view'])
                        && ! $exportPlugin->exportStructure(
                            $db->getName(),
                            $table,
                            'stand_in',
                            $exportType,
                            $doRelation,
                            $doComments,
                            $doMime,
                            $doDates,
                            $aliases,
                        )
                    ) {
                        break;
                    }
                } elseif (isset($GLOBALS['sql_create_table'])) {
                    $tableSize = $GLOBALS['maxsize'];
                    // Checking if the maximum table size constrain has been set
                    // And if that constrain is a valid number or not
                    if ($tableSize !== '' && is_numeric($tableSize)) {
                        // This obtains the current table's size
                        $query = 'SELECT data_length + index_length
                              from information_schema.TABLES
                              WHERE table_schema = ' . $this->dbi->quoteString($db->getName()) . '
                              AND table_name = ' . $this->dbi->quoteString($table);

                        $size = (int) $this->dbi->fetchValue($query);
                        //Converting the size to MB
                        $size /= 1024 * 1024;
                        if ($size > $tableSize) {
                            continue;
                        }
                    }

                    if (
                        ! $exportPlugin->exportStructure(
                            $db->getName(),
                            $table,
                            'create_table',
                            $exportType,
                            $doRelation,
                            $doComments,
                            $doMime,
                            $doDates,
                            $aliases,
                        )
                    ) {
                        break;
                    }
                }
            }

            // if this is a view or a merge table, don't export data
            if (
                ($whatStrucOrData === 'data' || $whatStrucOrData === 'structure_and_data')
                && in_array($table, $tableData, true)
                && ! $isView
            ) {
                $tableObj = new Table($table, $db->getName(), $this->dbi);
                $nonGeneratedCols = $tableObj->getNonGeneratedColumns();

                $localQuery = 'SELECT ' . implode(', ', $nonGeneratedCols)
                    . ' FROM ' . Util::backquote($db->getName())
                    . '.' . Util::backquote($table);

                if (! $exportPlugin->exportData($db->getName(), $table, $errorUrl, $localQuery, $aliases)) {
                    break;
                }
            }

            // this buffer was filled, we save it and go to the next one
            if ($separateFiles === 'database') {
                $this->saveObjectInBuffer('table_' . $table);
            }

            // now export the triggers (needs to be done after the data because
            // triggers can modify already imported tables)
            if (
                ! isset($GLOBALS['sql_create_trigger']) || ($whatStrucOrData !== 'structure'
                && $whatStrucOrData !== 'structure_and_data')
                || ! in_array($table, $tableStructure, true)
            ) {
                continue;
            }

            if (
                ! $exportPlugin->exportStructure(
                    $db->getName(),
                    $table,
                    'triggers',
                    $exportType,
                    $doRelation,
                    $doComments,
                    $doMime,
                    $doDates,
                    $aliases,
                )
            ) {
                break;
            }

            if ($separateFiles !== 'database') {
                continue;
            }

            $this->saveObjectInBuffer('table_' . $table, true);
        }

        if (isset($GLOBALS['sql_create_view'])) {
            foreach ($views as $view) {
                // no data export for a view
                if ($whatStrucOrData !== 'structure' && $whatStrucOrData !== 'structure_and_data') {
                    continue;
                }

                if (
                    ! $exportPlugin->exportStructure(
                        $db->getName(),
                        $view,
                        'create_view',
                        $exportType,
                        $doRelation,
                        $doComments,
                        $doMime,
                        $doDates,
                        $aliases,
                    )
                ) {
                    break;
                }

                if ($separateFiles !== 'database') {
                    continue;
                }

                $this->saveObjectInBuffer('view_' . $view);
            }
        }

        if (! $exportPlugin->exportDBFooter($db->getName())) {
            return;
        }

        // export metadata related to this db
        if (isset($GLOBALS['sql_metadata'])) {
            // Types of metadata to export.
            // In the future these can be allowed to be selected by the user
            $metadataTypes = $this->getMetadataTypes();
            $exportPlugin->exportMetadata($db->getName(), $tables, $metadataTypes);

            if ($separateFiles === 'database') {
                $this->saveObjectInBuffer('metadata');
            }
        }

        if ($separateFiles === 'database') {
            $this->saveObjectInBuffer('extra');
        }

        if (
            ($GLOBALS['sql_structure_or_data'] !== 'structure'
            && $GLOBALS['sql_structure_or_data'] !== 'structure_and_data')
            || ! isset($GLOBALS['sql_procedure_function'])
        ) {
            return;
        }

        $exportPlugin->exportEvents($db->getName());

        if ($separateFiles !== 'database') {
            return;
        }

        $this->saveObjectInBuffer('events');
    }

    /**
     * Export a raw query
     *
     * @param string       $whatStrucOrData whether to export structure for each table or raw
     * @param ExportPlugin $exportPlugin    the selected export plugin
     * @param string       $errorUrl        the URL in case of error
     * @param string|null  $db              the database where the query is executed
     * @param string       $sqlQuery        the query to be executed
     */
    public static function exportRaw(
        string $whatStrucOrData,
        ExportPlugin $exportPlugin,
        string $errorUrl,
        string|null $db,
        string $sqlQuery,
    ): void {
        // In case the we need to dump just the raw query
        if ($whatStrucOrData !== 'raw') {
            return;
        }

        if ($exportPlugin->exportRawQuery($errorUrl, $db, $sqlQuery)) {
            return;
        }

        $GLOBALS['message'] = Message::error(
            // phpcs:disable Generic.Files.LineLength.TooLong
            /* l10n: A query written by the user is a "raw query" that could be using no tables or databases in particular */
            __('Exporting a raw query is not supported for this export method.'),
        );
    }

    /**
     * Export at the table level
     *
     * @param string       $db              the database to export
     * @param string       $table           the table to export
     * @param string       $whatStrucOrData structure or data or both
     * @param ExportPlugin $exportPlugin    the selected export plugin
     * @param string       $errorUrl        the URL in case of error
     * @param string       $exportType      the export type
     * @param bool         $doRelation      whether to export relation info
     * @param bool         $doComments      whether to add comments
     * @param bool         $doMime          whether to add MIME info
     * @param bool         $doDates         whether to add dates
     * @param string|null  $allrows         whether "dump all rows" was ticked
     * @param string       $limitTo         upper limit
     * @param string       $limitFrom       starting limit
     * @param string       $sqlQuery        query for which exporting is requested
     * @param mixed[]      $aliases         Alias information for db/table/column
     */
    public function exportTable(
        string $db,
        string $table,
        string $whatStrucOrData,
        ExportPlugin $exportPlugin,
        string $errorUrl,
        string $exportType,
        bool $doRelation,
        bool $doComments,
        bool $doMime,
        bool $doDates,
        string|null $allrows,
        string $limitTo,
        string $limitFrom,
        string $sqlQuery,
        array $aliases,
    ): void {
        $dbAlias = ! empty($aliases[$db]['alias'])
            ? $aliases[$db]['alias'] : '';
        if (! $exportPlugin->exportDBHeader($db, $dbAlias)) {
            return;
        }

        if ($allrows === '0' && $limitTo > 0 && $limitFrom >= 0) {
            $addQuery = ' LIMIT '
                        . ($limitFrom > 0 ? $limitFrom . ', ' : '')
                        . $limitTo;
        } else {
            $addQuery = '';
        }

        $tableObject = new Table($table, $db, $this->dbi);
        $isView = $tableObject->isView();
        if ($whatStrucOrData === 'structure' || $whatStrucOrData === 'structure_and_data') {
            if ($isView) {
                if (isset($GLOBALS['sql_create_view'])) {
                    if (
                        ! $exportPlugin->exportStructure(
                            $db,
                            $table,
                            'create_view',
                            $exportType,
                            $doRelation,
                            $doComments,
                            $doMime,
                            $doDates,
                            $aliases,
                        )
                    ) {
                        return;
                    }
                }
            } elseif (isset($GLOBALS['sql_create_table'])) {
                if (
                    ! $exportPlugin->exportStructure(
                        $db,
                        $table,
                        'create_table',
                        $exportType,
                        $doRelation,
                        $doComments,
                        $doMime,
                        $doDates,
                        $aliases,
                    )
                ) {
                    return;
                }
            }
        }

        // If this is an export of a single view, we have to export data;
        // for example, a PDF report
        // if it is a merge table, no data is exported
        if ($whatStrucOrData === 'data' || $whatStrucOrData === 'structure_and_data') {
            if ($sqlQuery !== '') {
                // only preg_replace if needed
                if ($addQuery !== '') {
                    // remove trailing semicolon before adding a LIMIT
                    $sqlQuery = preg_replace('%;\s*$%', '', $sqlQuery);
                }

                $localQuery = $sqlQuery . $addQuery;
                $this->dbi->selectDb($db);
            } else {
                // Data is exported only for Non-generated columns
                $tableObj = new Table($table, $db, $this->dbi);
                $nonGeneratedCols = $tableObj->getNonGeneratedColumns();

                $localQuery = 'SELECT ' . implode(', ', $nonGeneratedCols)
                    . ' FROM ' . Util::backquote($db)
                    . '.' . Util::backquote($table) . $addQuery;
            }

            if (! $exportPlugin->exportData($db, $table, $errorUrl, $localQuery, $aliases)) {
                return;
            }
        }

        // now export the triggers (needs to be done after the data because
        // triggers can modify already imported tables)
        if (
            isset($GLOBALS['sql_create_trigger']) && ($whatStrucOrData === 'structure'
            || $whatStrucOrData === 'structure_and_data')
        ) {
            if (
                ! $exportPlugin->exportStructure(
                    $db,
                    $table,
                    'triggers',
                    $exportType,
                    $doRelation,
                    $doComments,
                    $doMime,
                    $doDates,
                    $aliases,
                )
            ) {
                return;
            }
        }

        if (! $exportPlugin->exportDBFooter($db)) {
            return;
        }

        if (! isset($GLOBALS['sql_metadata'])) {
            return;
        }

        // Types of metadata to export.
        // In the future these can be allowed to be selected by the user
        $metadataTypes = $this->getMetadataTypes();
        $exportPlugin->exportMetadata($db, $table, $metadataTypes);
    }

    /**
     * Loads correct page after doing export
     *
     * @psalm-return non-empty-string
     */
    public function getPageLocationAndSaveMessage(string $exportType, Message $message): string
    {
        (new FlashMessenger())->addMessage($message->isError() ? 'danger' : 'success', $message->getMessage());

        if ($exportType === 'server') {
            return 'index.php?route=/server/export' . Url::getCommonRaw([], '&');
        }

        if ($exportType === 'database') {
            $params = ['db' => Current::$database];

            return 'index.php?route=/database/export' . Url::getCommonRaw($params, '&');
        }

        $params = ['db' => Current::$database, 'table' => Current::$table, 'single_table' => 'true'];

        return 'index.php?route=/table/export' . Url::getCommonRaw($params, '&');
    }

    /**
     * Merge two alias arrays, if array1 and array2 have
     * conflicting alias then array2 value is used if it
     * is non empty otherwise array1 value.
     *
     * @param mixed[] $aliases1 first array of aliases
     * @param mixed[] $aliases2 second array of aliases
     *
     * @return mixed[] resultant merged aliases info
     */
    public function mergeAliases(array $aliases1, array $aliases2): array
    {
        // First do a recursive array merge
        // on aliases arrays.
        $aliases = array_merge_recursive($aliases1, $aliases2);
        // Now, resolve conflicts in aliases, if any
        foreach ($aliases as $dbName => $db) {
            // If alias key is an array then
            // it is a merge conflict.
            if (isset($db['alias']) && is_array($db['alias'])) {
                $val1 = $db['alias'][0];
                $val2 = $db['alias'][1];
                // Use aliases2 alias if non empty
                $aliases[$dbName]['alias'] = $val2 !== '' && $val2 !== null ? $val2 : $val1;
            }

            if (! isset($db['tables'])) {
                continue;
            }

            foreach ($db['tables'] as $tableName => $tbl) {
                if (isset($tbl['alias']) && is_array($tbl['alias'])) {
                    $val1 = $tbl['alias'][0];
                    $val2 = $tbl['alias'][1];
                    // Use aliases2 alias if non empty
                    $aliases[$dbName]['tables'][$tableName]['alias'] = $val2 !== '' && $val2 !== null ? $val2 : $val1;
                }

                if (! isset($tbl['columns'])) {
                    continue;
                }

                foreach ($tbl['columns'] as $col => $colAs) {
                    if (! isset($colAs) || ! is_array($colAs)) {
                        continue;
                    }

                    $val1 = $colAs[0];
                    $val2 = $colAs[1];
                    // Use aliases2 alias if non empty
                    $aliases[$dbName]['tables'][$tableName]['columns'][$col] = $val2 !== '' && $val2 !== null ? $val2 : $val1;
                }
            }
        }

        return $aliases;
    }

    /**
     * Locks tables
     *
     * @param DatabaseName $db       database name
     * @param mixed[]      $tables   list of table names
     * @param string       $lockType lock type; "[LOW_PRIORITY] WRITE" or "READ [LOCAL]"
     */
    public function lockTables(DatabaseName $db, array $tables, string $lockType = 'WRITE'): void
    {
        $locks = [];
        foreach ($tables as $table) {
            $locks[] = Util::backquote($db->getName()) . '.'
                . Util::backquote($table) . ' ' . $lockType;
        }

        $sql = 'LOCK TABLES ' . implode(', ', $locks);

        $this->dbi->tryQuery($sql);
    }

    /**
     * Releases table locks
     */
    public function unlockTables(): void
    {
        $this->dbi->tryQuery('UNLOCK TABLES');
    }

    /**
     * Returns all the metadata types that can be exported with a database or a table
     *
     * @return string[] metadata types.
     */
    public function getMetadataTypes(): array
    {
        return [
            'column_info',
            'table_uiprefs',
            'tracking',
            'bookmark',
            'relation',
            'table_coords',
            'pdf_pages',
            'savedsearches',
            'central_columns',
            'export_templates',
        ];
    }

    /**
     * Returns the checked clause, depending on the presence of key in array
     *
     * @param string  $key   the key to look for
     * @param mixed[] $array array to verify
     *
     * @return string the checked clause
     */
    public function getCheckedClause(string $key, array $array): string
    {
        if (in_array($key, $array)) {
            return ' checked="checked"';
        }

        return '';
    }

    /**
     * get all the export options and verify
     * call and include the appropriate Schema Class depending on $export_type
     *
     * @param non-empty-string $exportType
     *
     * @return array{fileName: non-empty-string, mediaType: non-empty-string, fileData: string}
     *
     * @throws ExportException
     */
    public function getExportSchemaInfo(DatabaseName $db, string $exportType): array
    {
        /**
         * default is PDF, otherwise validate it's only letters a-z
         */
        if (preg_match('/^[a-zA-Z]+$/', $exportType) !== 1) {
            $exportType = 'pdf';
        }

        // get the specific plugin
        /** @var SchemaPlugin|null $exportPlugin */
        $exportPlugin = Plugins::getPlugin('schema', $exportType);

        // Check schema export type
        if ($exportPlugin === null) {
            throw new ExportException(__('Bad type!'));
        }

        $this->dbi->selectDb($db);

        return $exportPlugin->getExportInfo($db);
    }

    /** @return string[] */
    public function getTableNames(string $database): array
    {
        return $this->dbi->getTables($database);
    }

    private function getHTMLForRefreshButton(string $exportType): string
    {
        $postParams = $this->getPostParams($exportType);

        $refreshButton = '<form id="export_refresh_form" method="POST" action="'
            . Url::getFromRoute('/export') . '" class="disableAjax">';
        $refreshButton .= '[ <a class="disableAjax export_refresh_btn">' . __('Refresh') . '</a> ]';
        foreach ($postParams as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $val) {
                    $refreshButton .= '<input type="hidden" name="' . htmlentities((string) $name)
                        . '[]" value="' . htmlentities((string) $val) . '">';
                }
            } else {
                $refreshButton .= '<input type="hidden" name="' . htmlentities((string) $name)
                    . '" value="' . htmlentities((string) $value) . '">';
            }
        }

        return $refreshButton . '</form>';
    }

    private function getHTMLForCopyButton(): string
    {
        return '<p>[ <a class="export_copy_to_clipboard_btn disableAjax" href="#">'
                . __('Copy to clipboard') . '</a> ]</p>';
    }

    private function getHTMLForBackButton(string $exportType, string $db, string $table): string
    {
        $backButton = '<p>[ <a href="';
        $backButton .= match ($exportType) {
            'server' => Url::getFromRoute('/server/export') . '" data-post="' . Url::getCommon([], '', false),
            'database' => Url::getFromRoute('/database/export') . '" data-post="' . Url::getCommon(
                ['db' => $db],
                '',
                    false,
            ),
            default => Url::getFromRoute('/table/export') . '" data-post="' . Url::getCommon(
                ['db' => $db, 'table' => $table],
                '',
                false,
            ),
        };

        $postParams = array_filter($this->getPostParams($exportType), static fn ($value): bool => ! is_array($value));
        $backButton .= '&amp;' . http_build_query($postParams);

        $backButton .= '&amp;repopulate=1">' . __('Back') . '</a> ]</p>';

        return $backButton;
    }

    /** @return mixed[] */
    private function getPostParams(string $exportType): array
    {
        $postParams = $_POST;

        // Convert the multiple select elements from an array to a string
        if ($exportType === 'database') {
            $structOrDataForced = empty($postParams['structure_or_data_forced']);
            if ($structOrDataForced && ! isset($postParams['table_structure'])) {
                $postParams['table_structure'] = [];
            }

            if ($structOrDataForced && ! isset($postParams['table_data'])) {
                $postParams['table_data'] = [];
            }
        }

        return $postParams;
    }
}
