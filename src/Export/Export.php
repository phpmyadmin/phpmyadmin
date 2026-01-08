<?php
/**
 * function for the main export logic
 */

declare(strict_types=1);

namespace PhpMyAdmin\Export;

use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Exceptions\ExportException;
use PhpMyAdmin\FlashMessenger;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportPlugin;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Plugins\SchemaPlugin;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function array_filter;
use function array_merge_recursive;
use function error_get_last;
use function htmlentities;
use function http_build_query;
use function implode;
use function in_array;
use function ini_get;
use function ini_parse_quantity;
use function is_array;
use function is_numeric;
use function mb_strlen;
use function mb_strtolower;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function str_contains;

/**
 * PhpMyAdmin\Export\Export class
 */
class Export
{
    public static bool $singleTable = false;

    public static string $tableMaxSizeInMb = '';
    /** @var array<string> */
    public static array $tableData = [];

    public function __construct(private DatabaseInterface $dbi, public OutputHandler $outputHandler)
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
     * Returns HTML containing the footer for a displayed export
     *
     * @param ExportType $exportType the export type
     * @param string     $db         the database name
     * @param string     $table      the table name
     *
     * @return string the HTML output
     */
    public function getHtmlForDisplayedExportFooter(
        ExportType $exportType,
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
        if ($this->outputHandler->compression === 'gzip') {
            $filename .= '.gz';
        } elseif ($this->outputHandler->compression === 'zip') {
            $filename .= '.zip';
        }

        return $filename;
    }

    public function getMimeType(ExportPlugin $exportPlugin): string
    {
        return match ($this->outputHandler->compression) {
            'gzip' => 'application/x-gzip',
            'zip' => 'application/zip',
            default => $exportPlugin->getProperties()->getMimeType(),
        };
    }

    public function rememberFilename(
        UserPreferencesHandler $userPreferencesHandler,
        ExportType $exportType,
        string $filenameTemplate,
    ): void {
        if ($exportType === ExportType::Server) {
            $userPreferencesHandler->setUserValue(
                'pma_server_filename_template',
                'Export/file_template_server',
                $filenameTemplate,
            );
        } elseif ($exportType === ExportType::Database) {
            $userPreferencesHandler->setUserValue(
                'pma_db_filename_template',
                'Export/file_template_database',
                $filenameTemplate,
            );
        } elseif ($exportType === ExportType::Raw) {
            $userPreferencesHandler->setUserValue(
                'pma_raw_filename_template',
                'Export/file_template_raw',
                $filenameTemplate,
            );
        } else {
            $userPreferencesHandler->setUserValue(
                'pma_table_filename_template',
                'Export/file_template_table',
                $filenameTemplate,
            );
        }
    }

    /**
     * Returns HTML containing the header for a displayed export
     *
     * @param ExportType $exportType the export type
     * @param string     $db         the database name
     * @param string     $table      the table name
     *
     * @return string the generated HTML and back button
     */
    public function getHtmlForDisplayedExportHeader(
        ExportType $exportType,
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
     * @param string|mixed[] $dbSelect      the selected databases to export
     * @param ExportPlugin   $exportPlugin  the selected export plugin
     * @param mixed[]        $aliases       alias information for db/table/column
     * @param SeparateFiles  $separateFiles whether it is a separate-files export
     */
    public function exportServer(
        string|array $dbSelect,
        ExportPlugin $exportPlugin,
        array $aliases,
        SeparateFiles $separateFiles,
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
                $tables,
                $tables,
                $exportPlugin,
                $aliases,
                $separateFiles === SeparateFiles::Database ? SeparateFiles::Database : SeparateFiles::None,
            );
            if ($separateFiles !== SeparateFiles::Server) {
                continue;
            }

            $this->outputHandler->saveObjectInBuffer($currentDb);
        }
    }

    /**
     * Export at the database level
     *
     * @param DatabaseName  $db             the database to export
     * @param string[]      $tables         the tables to export
     * @param string[]      $tableStructure whether to export structure for each table
     * @param string[]      $tableData      whether to export data for each table
     * @param ExportPlugin  $exportPlugin   the selected export plugin
     * @param mixed[]       $aliases        Alias information for db/table/column
     * @param SeparateFiles $separateFiles  whether it is a separate-files export
     */
    public function exportDatabase(
        DatabaseName $db,
        array $tables,
        array $tableStructure,
        array $tableData,
        ExportPlugin $exportPlugin,
        array $aliases,
        SeparateFiles $separateFiles,
    ): void {
        $dbAlias = ! empty($aliases[$db->getName()]['alias'])
            ? $aliases[$db->getName()]['alias'] : '';

        if (! $exportPlugin->exportDBHeader($db->getName(), $dbAlias)) {
            return;
        }

        if (! $exportPlugin->exportDBCreate($db->getName(), $dbAlias)) {
            return;
        }

        if ($separateFiles === SeparateFiles::Database) {
            $this->outputHandler->saveObjectInBuffer('database', true);
        }

        if ($exportPlugin->includeStructure()) {
            $exportPlugin->exportRoutines($db->getName(), $aliases);

            if ($separateFiles === SeparateFiles::Database) {
                $this->outputHandler->saveObjectInBuffer('routines');
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

            if ($exportPlugin->includeStructure() && in_array($table, $tableStructure, true)) {
                // for a view, export a stand-in definition of the table
                // to resolve view dependencies (only when it's a single-file export)
                if ($isView) {
                    if (
                        $separateFiles === SeparateFiles::None
                        && ! $exportPlugin->exportStructure($db->getName(), $table, 'stand_in', $aliases)
                    ) {
                        break;
                    }
                } else {
                    if ($this->exceedsMaxTableSize($db->getName(), $table)) {
                        continue;
                    }

                    if (! $exportPlugin->exportStructure($db->getName(), $table, 'create_table', $aliases)) {
                        break;
                    }
                }
            }

            // if this is a view or a merge table, don't export data
            if ($exportPlugin->includeData() && in_array($table, $tableData, true) && ! $isView) {
                $tableObj = new Table($table, $db->getName(), $this->dbi);
                $nonGeneratedCols = $tableObj->getNonGeneratedColumns();

                $localQuery = 'SELECT ' . implode(', ', $nonGeneratedCols)
                    . ' FROM ' . Util::backquote($db->getName())
                    . '.' . Util::backquote($table);

                if (! $exportPlugin->exportData($db->getName(), $table, $localQuery, $aliases)) {
                    break;
                }
            }

            // this buffer was filled, we save it and go to the next one
            if ($separateFiles === SeparateFiles::Database) {
                $this->outputHandler->saveObjectInBuffer('table_' . $table);
            }

            // now export the triggers (needs to be done after the data because
            // triggers can modify already imported tables)
            if (! $exportPlugin->includeStructure()) {
                continue;
            }

            if (! $exportPlugin->exportStructure($db->getName(), $table, 'triggers', $aliases)) {
                break;
            }

            if ($separateFiles !== SeparateFiles::Database) {
                continue;
            }

            $this->outputHandler->saveObjectInBuffer('table_' . $table, true);
        }

        foreach ($views as $view) {
            // no data export for a view
            if (! $exportPlugin->includeStructure()) {
                continue;
            }

            if (! $exportPlugin->exportStructure($db->getName(), $view, 'create_view', $aliases)) {
                break;
            }

            if ($separateFiles !== SeparateFiles::Database) {
                continue;
            }

            $this->outputHandler->saveObjectInBuffer('view_' . $view);
        }

        if (! $exportPlugin->exportDBFooter($db->getName())) {
            return;
        }

        if ($separateFiles === SeparateFiles::Database) {
            $this->outputHandler->saveObjectInBuffer('extra');
        }

        // Types of metadata to export.
        // In the future these can be allowed to be selected by the user
        $metadataTypes = $this->getMetadataTypes();
        $exportPlugin->exportMetadata($db->getName(), $tables, $metadataTypes);

        if ($separateFiles === SeparateFiles::Database) {
            $this->outputHandler->saveObjectInBuffer('metadata');
        }

        if (! $exportPlugin->includeStructure()) {
            return;
        }

        $exportPlugin->exportEvents($db->getName());

        if ($separateFiles !== SeparateFiles::Database) {
            return;
        }

        $this->outputHandler->saveObjectInBuffer('events');
    }

    /**
     * Export a raw query
     *
     * @param ExportPlugin $exportPlugin the selected export plugin
     * @param  string|null  $db           the database where the query is executed
     * @param string       $sqlQuery     the query to be executed
     */
    public static function exportRaw(
        ExportPlugin $exportPlugin,
        string|null $db,
        string $sqlQuery,
    ): void {
        if ($exportPlugin->exportRawQuery($db, $sqlQuery)) {
            return;
        }

        Current::$message = Message::error(
            // phpcs:disable Generic.Files.LineLength.TooLong
            /* l10n: A query written by the user is a "raw query" that could be using no tables or databases in particular */
            __('Exporting a raw query is not supported for this export method.'),
        );
    }

    /**
     * Export at the table level
     *
     * @param string       $db           the database to export
     * @param string       $table        the table to export
     * @param ExportPlugin $exportPlugin the selected export plugin
     * @param string|null  $allrows      whether "dump all rows" was ticked
     * @param string       $limitTo      upper limit
     * @param string       $limitFrom    starting limit
     * @param string       $sqlQuery     query for which exporting is requested
     * @param mixed[]      $aliases      Alias information for db/table/column
     */
    public function exportTable(
        string $db,
        string $table,
        ExportPlugin $exportPlugin,
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

        if ($exportPlugin->includeStructure()) {
            $tableObject = new Table($table, $db, $this->dbi);
            if ($tableObject->isView()) {
                if (! $exportPlugin->exportStructure($db, $table, 'create_view', $aliases)) {
                    return;
                }
            } elseif (! $exportPlugin->exportStructure($db, $table, 'create_table', $aliases)) {
                return;
            }
        }

        // If this is an export of a single view, we have to export data;
        // for example, a PDF report
        // if it is a merge table, no data is exported
        if ($exportPlugin->includeData()) {
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

            if (! $exportPlugin->exportData($db, $table, $localQuery, $aliases)) {
                return;
            }
        }

        // now export the triggers (needs to be done after the data because
        // triggers can modify already imported tables)
        if ($exportPlugin->includeStructure()) {
            if (! $exportPlugin->exportStructure($db, $table, 'triggers', $aliases)) {
                return;
            }
        }

        if (! $exportPlugin->exportDBFooter($db)) {
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
    public function getPageLocationAndSaveMessage(ExportType $exportType, Message $message): string
    {
        (new FlashMessenger())->addMessage($message->isError() ? 'danger' : 'success', $message->getMessage());

        if ($exportType === ExportType::Server) {
            return 'index.php?route=/server/export' . Url::getCommonRaw([], '&');
        }

        if ($exportType === ExportType::Database) {
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
                    if (! is_array($colAs)) {
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
            RelationParameters::COLUMN_INFO,
            RelationParameters::TABLE_UI_PREFS,
            RelationParameters::TRACKING,
            RelationParameters::BOOKMARK,
            RelationParameters::RELATION,
            RelationParameters::TABLE_COORDS,
            RelationParameters::PDF_PAGES,
            RelationParameters::SAVED_SEARCHES,
            RelationParameters::CENTRAL_COLUMNS,
            RelationParameters::EXPORT_TEMPLATES,
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
            return ' checked';
        }

        return '';
    }

    /**
     * get all the export options and verify
     * call and include the appropriate Schema Class depending on $export_type
     *
     * @return array{fileName: non-empty-string, mediaType: non-empty-string, fileData: string}
     *
     * @throws ExportException
     */
    public function getExportSchemaInfo(DatabaseName $db, string $format): array
    {
        /**
         * default is PDF, otherwise validate it's only letters a-z
         */
        if (preg_match('/^[a-zA-Z]+$/', $format) !== 1) {
            $format = 'pdf';
        }

        // get the specific plugin
        /** @var SchemaPlugin|null $exportPlugin */
        $exportPlugin = Plugins::getPlugin('schema', $format);

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

    private function getHTMLForRefreshButton(ExportType $exportType): string
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

    private function getHTMLForBackButton(ExportType $exportType, string $db, string $table): string
    {
        $backButton = '<p>[ <a href="';
        $backButton .= match ($exportType) {
            ExportType::Server => Url::getFromRoute('/server/export') . '" data-post="' . Url::getCommon([], '', false),
            ExportType::Database => Url::getFromRoute('/database/export') . '" data-post="' . Url::getCommon(
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
    private function getPostParams(ExportType $exportType): array
    {
        $postParams = $_POST;

        // Convert the multiple select elements from an array to a string
        if ($exportType === ExportType::Database) {
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

    private function exceedsMaxTableSize(string $db, string $table): bool
    {
        // Checking if the maximum table size constrain has been set
        // And if that constrain is a valid number or not
        if (is_numeric(self::$tableMaxSizeInMb)) {
            // This obtains the current table's size
            $query = 'SELECT data_length + index_length
                    from information_schema.TABLES
                    WHERE table_schema = ' . $this->dbi->quoteString($db) . '
                    AND table_name = ' . $this->dbi->quoteString($table);

            $size = (int) $this->dbi->fetchValue($query);
            //Converting the size to MB
            $size /= 1024 * 1024;

            return $size > self::$tableMaxSizeInMb;
        }

        return false;
    }
}
