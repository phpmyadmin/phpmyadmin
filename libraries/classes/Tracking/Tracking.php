<?php
/**
 * Functions used for database and table tracking
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tracking;

use DateTimeImmutable;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use Webmozart\Assert\Assert;

use function __;
use function array_merge;
use function array_multisort;
use function date;
use function explode;
use function htmlspecialchars;
use function in_array;
use function ini_set;
use function json_encode;
use function mb_strpos;
use function mb_strstr;
use function mb_substr;
use function preg_replace;
use function rtrim;
use function sprintf;
use function strtotime;
use function trim;

use const SORT_ASC;

/**
 * PhpMyAdmin\Tracking\Tracking class
 */
class Tracking
{
    public function __construct(
        private SqlQueryForm $sqlQueryForm,
        public Template $template,
        protected Relation $relation,
        private DatabaseInterface $dbi,
        private TrackingChecker $trackingChecker,
    ) {
    }

    /**
     * Removes all tracking data for a table or a version of a table
     *
     * @param string $dbName    name of database
     * @param string $tableName name of table
     * @param string $version   version
     */
    public function deleteTracking(string $dbName, string $tableName, string $version = ''): bool
    {
        $trackingFeature = $this->relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return false;
        }

        $sqlQuery = sprintf(
            '/*NOTRACK*/' . "\n" . 'DELETE FROM %s.%s WHERE `db_name` = %s AND `table_name` = %s',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $this->dbi->quoteString($dbName, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($tableName, Connection::TYPE_CONTROL),
        );
        if ($version) {
            $sqlQuery .= ' AND `version` = ' . $this->dbi->quoteString($version, Connection::TYPE_CONTROL);
        }

        return (bool) $this->dbi->queryAsControlUser($sqlQuery);
    }

    /**
     * Filters tracking entries
     *
     * @param list<array{date: string, username: string, statement: string}> $data        the entries to filter
     * @param mixed[]                                                        $filterUsers users
     *
     * @return mixed[] filtered entries
     */
    public function filter(
        array $data,
        array $filterUsers,
        DateTimeImmutable $dateFrom,
        DateTimeImmutable $dateTo,
    ): array {
        $tmpEntries = [];
        $id = 0;
        foreach ($data as $entry) {
            $filteredUser = in_array($entry['username'], $filterUsers);
            if (
                $this->isDateBetweenInclusive(new DateTimeImmutable($entry['date']), $dateFrom, $dateTo)
                && (in_array('*', $filterUsers) || $filteredUser)
            ) {
                $tmpEntries[] = [
                    'id' => $id,
                    'timestamp' => strtotime($entry['date']),
                    'username' => $entry['username'],
                    'statement' => $entry['statement'],
                ];
            }

            $id++;
        }

        return $tmpEntries;
    }

    /**
     * Function to get the list versions of the table
     */
    public function getListOfVersionsOfTable(string $db, string $table): ResultInterface|false
    {
        $trackingFeature = $this->relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return false;
        }

        $query = sprintf(
            'SELECT * FROM %s.%s WHERE db_name = %s AND table_name = %s ORDER BY version DESC',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $this->dbi->quoteString($db, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($table, Connection::TYPE_CONTROL),
        );

        return $this->dbi->queryAsControlUser($query);
    }

    /**
     * Function to get html for main page parts that do not use $_REQUEST
     *
     * @param mixed[] $urlParams url parameters
     * @param string  $textDir   text direction
     */
    public function getHtmlForMainPage(
        string $db,
        string $table,
        array $urlParams,
        string $textDir,
    ): string {
        $versionSqlResult = $this->getListOfVersionsOfTable($db, $table);
        $lastVersion = null;
        $versions = [];
        if ($versionSqlResult !== false) {
            $lastVersion = $this->getTableLastVersionNumber($versionSqlResult);
            $versions = $versionSqlResult->fetchAllAssoc();
        }

        $type = $this->dbi->getTable($db, $table)->isView() ? 'view' : 'table';

        return $this->template->render('table/tracking/main', [
            'url_params' => $urlParams,
            'db' => $db,
            'table' => $table,
            'selectable_tables_entries' => $this->trackingChecker->getTrackedTables($db),
            'selected_table' => $_POST['table'] ?? null,
            'last_version' => $lastVersion,
            'versions' => $versions,
            'type' => $type,
            'default_statements' => $GLOBALS['cfg']['Server']['tracking_default_statements'],
            'text_dir' => $textDir,
        ]);
    }

    /**
     * Function to get the last version number of a table
     */
    public function getTableLastVersionNumber(ResultInterface $result): int
    {
        return (int) $result->fetchValue('version');
    }

    /**
     * Function to get html for tracking report and tracking report export
     *
     * @param TrackedData $trackedData data
     * @param mixed[]     $urlParams   url params
     * @param mixed[]     $filterUsers filter users
     * @psalm-param 'schema'|'data'|'schema_and_data' $logType
     */
    public function getHtmlForTrackingReport(
        TrackedData $trackedData,
        array $urlParams,
        string $logType,
        array $filterUsers,
        string $version,
        DateTimeImmutable $dateFrom,
        DateTimeImmutable $dateTo,
        string $users,
    ): string {
        $html = '<h3>' . __('Tracking report')
            . '  [<a href="' . Url::getFromRoute('/table/tracking', $urlParams) . '">' . __('Close')
            . '</a>]</h3>';

        $html .= '<small>' . __('Tracking statements') . ' '
            . htmlspecialchars($trackedData->tracking) . '</small><br>';
        $html .= '<br>';

        [$str1, $str2, $str3, $str4, $str5] = $this->getHtmlForElementsOfTrackingReport(
            $logType,
            $dateFrom,
            $dateTo,
            $users,
        );

        // Prepare delete link content here
        $dropImageOrText = '';
        if (Util::showIcons('ActionLinksMode')) {
            $dropImageOrText .= Generator::getImage(
                'b_drop',
                __('Delete tracking data row from report'),
            );
        }

        if (Util::showText('ActionLinksMode')) {
            $dropImageOrText .= __('Delete');
        }

        // First, list tracked data definition statements
        if ($trackedData->ddlog === [] && $trackedData->dmlog === []) {
            $msg = Message::notice(__('No data'));
            echo $msg->getDisplay();
        }

        $html .= $this->getHtmlForTrackingReportExportForm1(
            $trackedData,
            $urlParams,
            $logType,
            $filterUsers,
            $str1,
            $str2,
            $str3,
            $str4,
            $str5,
            $dropImageOrText,
            $version,
            $dateFrom,
            $dateTo,
        );

        $html .= $this->getHtmlForTrackingReportExportForm2(
            $urlParams,
            $str1,
            $str2,
            $str3,
            $str4,
            $str5,
            $logType,
            $version,
            $dateFrom,
            $dateTo,
            $users,
        );

        $html .= "<br><br><hr><br>\n";

        return $html;
    }

    /**
     * Generate HTML element for report form
     *
     * @psalm-param 'schema'|'data'|'schema_and_data' $logType
     *
     * @return string[]
     */
    public function getHtmlForElementsOfTrackingReport(
        string $logType,
        DateTimeImmutable $dateFrom,
        DateTimeImmutable $dateTo,
        string $users,
    ): array {
        $str1 = '<select name="log_type">'
            . '<option value="schema"'
            . ($logType === 'schema' ? ' selected="selected"' : '') . '>'
            . __('Structure only') . '</option>'
            . '<option value="data"'
            . ($logType === 'data' ? ' selected="selected"' : '') . '>'
            . __('Data only') . '</option>'
            . '<option value="schema_and_data"'
            . ($logType === 'schema_and_data' ? ' selected="selected"' : '') . '>'
            . __('Structure and data') . '</option>'
            . '</select>';
        $str2 = '<input type="text" name="date_from" value="'
            . htmlspecialchars($dateFrom->format('Y-m-d H:i:s')) . '" size="19">';
        $str3 = '<input type="text" name="date_to" value="'
            . htmlspecialchars($dateTo->format('Y-m-d H:i:s')) . '" size="19">';
        $str4 = '<input type="text" name="users" value="'
            . htmlspecialchars($users) . '">';
        $str5 = '<input type="hidden" name="list_report" value="1">'
            . '<input class="btn btn-primary" type="submit" value="' . __('Go') . '">';

        return [$str1, $str2, $str3, $str4, $str5];
    }

    /**
     * Generate HTML for export form
     *
     * @param TrackedData $trackedData     data
     * @param mixed[]     $urlParams       url params
     * @param mixed[]     $filterUsers     filter users
     * @param string      $str1            HTML for log_type select
     * @param string      $str2            HTML for "from date"
     * @param string      $str3            HTML for "to date"
     * @param string      $str4            HTML for user
     * @param string      $str5            HTML for "list report"
     * @param string      $dropImageOrText HTML for image or text
     * @psalm-param 'schema'|'data'|'schema_and_data' $logType
     *
     * @return string HTML for form
     */
    public function getHtmlForTrackingReportExportForm1(
        TrackedData $trackedData,
        array $urlParams,
        string $logType,
        array $filterUsers,
        string $str1,
        string $str2,
        string $str3,
        string $str4,
        string $str5,
        string $dropImageOrText,
        string $version,
        DateTimeImmutable $dateFrom,
        DateTimeImmutable $dateTo,
    ): string {
        $ddlogCount = 0;

        $html = '<form method="post" action="' . Url::getFromRoute('/table/tracking') . '">';
        $html .= Url::getHiddenInputs($urlParams + ['report' => 'true', 'version' => $version]);

        $html .= sprintf(
            __('Show %1$s with dates from %2$s to %3$s by user %4$s %5$s'),
            $str1,
            $str2,
            $str3,
            $str4,
            $str5,
        );

        if ($logType === 'schema' || $logType === 'schema_and_data' && $trackedData->ddlog !== []) {
            [$temp, $ddlogCount] = $this->getHtmlForDataDefinitionStatements(
                $trackedData,
                $filterUsers,
                $urlParams,
                $dropImageOrText,
                $version,
                $dateFrom,
                $dateTo,
            );
            $html .= $temp;
            unset($temp);
        }

        // Secondly, list tracked data manipulation statements
        if (($logType === 'data' || $logType === 'schema_and_data') && $trackedData->dmlog !== []) {
            $html .= $this->getHtmlForDataManipulationStatements(
                $trackedData,
                $filterUsers,
                $urlParams,
                $ddlogCount,
                $dropImageOrText,
                $version,
                $dateFrom,
                $dateTo,
            );
        }

        $html .= '</form>';

        return $html;
    }

    /**
     * Generate HTML for export form
     *
     * @param mixed[] $urlParams Parameters
     * @param string  $str1      HTML for log_type select
     * @param string  $str2      HTML for "from date"
     * @param string  $str3      HTML for "to date"
     * @param string  $str4      HTML for user
     * @param string  $str5      HTML for "list report"
     * @psalm-param 'schema'|'data'|'schema_and_data' $logType
     *
     * @return string HTML for form
     */
    public function getHtmlForTrackingReportExportForm2(
        array $urlParams,
        string $str1,
        string $str2,
        string $str3,
        string $str4,
        string $str5,
        string $logType,
        string $version,
        DateTimeImmutable $dateFrom,
        DateTimeImmutable $dateTo,
        string $users,
    ): string {
        $html = '<form method="post" action="' . Url::getFromRoute('/table/tracking') . '">';
        $html .= Url::getHiddenInputs($urlParams + ['report' => 'true', 'version' => $version]);

        $html .= sprintf(
            __('Show %1$s with dates from %2$s to %3$s by user %4$s %5$s'),
            $str1,
            $str2,
            $str3,
            $str4,
            $str5,
        );
        $html .= '</form>';

        $html .= '<form class="disableAjax" method="post" action="' . Url::getFromRoute('/table/tracking') . '">';
        $html .= Url::getHiddenInputs($urlParams + [
            'report' => 'true',
            'version' => $version,
            'log_type' => $logType,
            'date_from' => $dateFrom->format('Y-m-d H:i:s'),
            'date_to' => $dateTo->format('Y-m-d H:i:s'),
            'users' => $users,
        ]);

        $strExport1 = '<select name="export_type">'
            . '<option value="sqldumpfile">' . __('SQL dump (file download)')
            . '</option>'
            . '<option value="sqldump">' . __('SQL dump') . '</option>'
            . '<option value="execution" onclick="alert('
            . htmlspecialchars((string) json_encode(
                __('This option will replace your table and contained data.'),
            ))
            . ')">' . __('SQL execution') . '</option></select>';

        $strExport2 = '<input class="btn btn-primary" type="submit" value="' . __('Go') . '">';

        $html .= '<br>' . sprintf(__('Export as %s'), $strExport1)
            . $strExport2 . '<br>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Function to get html for data manipulation statements
     *
     * @param TrackedData $trackedData     data
     * @param mixed[]     $filterUsers     filter users
     * @param mixed[]     $urlParams       url parameters
     * @param int         $ddlogCount      data definition log count
     * @param string      $dropImageOrText drop image or text
     */
    public function getHtmlForDataManipulationStatements(
        TrackedData $trackedData,
        array $filterUsers,
        array $urlParams,
        int $ddlogCount,
        string $dropImageOrText,
        string $version,
        DateTimeImmutable $dateFrom,
        DateTimeImmutable $dateTo,
    ): string {
        // no need for the second returned parameter
        [$html] = $this->getHtmlForDataStatements(
            $trackedData->dmlog,
            $filterUsers,
            $urlParams,
            $dropImageOrText,
            LogTypeEnum::DML,
            $ddlogCount,
            $version,
            $dateFrom,
            $dateTo,
        );

        return $html;
    }

    /**
     * Function to get html for data definition statements in schema snapshot
     *
     * @param TrackedData $trackedData     data
     * @param mixed[]     $filterUsers     filter users
     * @param mixed[]     $urlParams       url parameters
     * @param string      $dropImageOrText drop image or text
     *
     * @return mixed[]
     */
    public function getHtmlForDataDefinitionStatements(
        TrackedData $trackedData,
        array $filterUsers,
        array $urlParams,
        string $dropImageOrText,
        string $version,
        DateTimeImmutable $dateFrom,
        DateTimeImmutable $dateTo,
    ): array {
        [$html, $lineNumber] = $this->getHtmlForDataStatements(
            $trackedData->ddlog,
            $filterUsers,
            $urlParams,
            $dropImageOrText,
            LogTypeEnum::DDL,
            1,
            $version,
            $dateFrom,
            $dateTo,
        );

        return [$html, $lineNumber];
    }

    /**
     * Function to get html for data statements in schema snapshot
     *
     * @param list<array{date: string, username: string, statement: string}> $logData         tracked data
     * @param mixed[]                                                        $filterUsers     filter users
     * @param mixed[]                                                        $urlParams       url parameters
     * @param string                                                         $dropImageOrText drop image or text
     * @param LogTypeEnum                                                    $logType         DDL|DML
     * @param int                                                            $lineNumber      line number
     *
     * @return mixed[] [$html, $lineNumber]
     */
    private function getHtmlForDataStatements(
        array $logData,
        array $filterUsers,
        array $urlParams,
        string $dropImageOrText,
        LogTypeEnum $logType,
        int $lineNumber,
        string $version,
        DateTimeImmutable $dateFrom,
        DateTimeImmutable $dateTo,
    ): array {
        $offset = $lineNumber;
        $entries = [];
        foreach ($logData as $entry) {
            if (
                $this->isDateBetweenInclusive(new DateTimeImmutable($entry['date']), $dateFrom, $dateTo)
                && (in_array('*', $filterUsers)
                || in_array($entry['username'], $filterUsers))
            ) {
                $entry['formated_statement'] = Generator::formatSql($entry['statement'], true);
                $deleteParam = 'delete_' . $logType->getLogName();
                $entry['url_params'] = Url::getCommon($urlParams + [
                    'report' => 'true',
                    'version' => $version,
                    $deleteParam => $lineNumber - $offset,
                ], '');
                $entry['line_number'] = $lineNumber;
                $entries[] = $entry;
            }

            $lineNumber++;
        }

        $html = $this->template->render('table/tracking/report_table', [
            'table_id' => $logType->getTableId(),
            'header_message' => $logType->getHeaderMessage(),
            'entries' => $entries,
            'drop_image_or_text' => $dropImageOrText,
        ]);

        return [$html, $lineNumber];
    }

    /**
     * Function to get html for schema snapshot
     *
     * @param mixed[] $params url parameters
     */
    public function getHtmlForSchemaSnapshot(string $db, string $table, string $version, array $params): string
    {
        $html = '<h3>' . __('Structure snapshot')
            . '  [<a href="' . Url::getFromRoute('/table/tracking', $params) . '">' . __('Close')
            . '</a>]</h3>';
        $trackedData = $this->getTrackedData($db, $table, $version);

        // Get first DROP TABLE/VIEW and CREATE TABLE/VIEW statements
        $dropCreateStatements = $trackedData->ddlog[0]['statement'];

        if (
            mb_strstr($trackedData->ddlog[0]['statement'], 'DROP TABLE')
            || mb_strstr($trackedData->ddlog[0]['statement'], 'DROP VIEW')
        ) {
            $dropCreateStatements .= $trackedData->ddlog[1]['statement'];
        }

        // Print SQL code
        $html .= Generator::getMessage(
            sprintf(
                __('Version %s snapshot (SQL code)'),
                htmlspecialchars($version),
            ),
            $dropCreateStatements,
        );

        // Unserialize snapshot
        $temp = Core::safeUnserialize($trackedData->schemaSnapshot);
        if ($temp === null) {
            $temp = ['COLUMNS' => [], 'INDEXES' => []];
        }

        $columns = $temp['COLUMNS'];
        $indexes = $temp['INDEXES'];
        $html .= $this->getHtmlForColumns($columns);

        if ($indexes !== []) {
            $html .= $this->getHtmlForIndexes($indexes);
        }

        $html .= '<br><hr><br>';

        return $html;
    }

    /**
     * Gets the record of a tracking job.
     *
     * @param string $dbname    name of database
     * @param string $tablename name of table
     * @param string $version   version number
     */
    public function getTrackedData(string $dbname, string $tablename, string $version): TrackedData
    {
        $trackingFeature = $this->relation->getRelationParameters()->trackingFeature;
        Assert::notNull($trackingFeature);

        $sqlQuery = sprintf(
            'SELECT * FROM %s.%s WHERE `db_name` = %s',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $this->dbi->quoteString($dbname, Connection::TYPE_CONTROL),
        );
        if ($tablename !== '') {
            $sqlQuery .= ' AND `table_name` = ' . $this->dbi->quoteString($tablename, Connection::TYPE_CONTROL) . ' ';
        }

        $sqlQuery .= ' AND `version` = ' . $this->dbi->quoteString($version, Connection::TYPE_CONTROL)
            . ' ORDER BY `version` DESC LIMIT 1';

        $mixed = $this->dbi->queryAsControlUser($sqlQuery)->fetchAssoc();

        // PHP 7.4 fix for accessing array offset on null
        if ($mixed === []) {
            $mixed = ['schema_sql' => null, 'data_sql' => null, 'tracking' => null, 'schema_snapshot' => null];
        }

        // Parse log
        $logSchemaEntries = explode('# log ', (string) $mixed['schema_sql']);
        $logDataEntries = explode('# log ', (string) $mixed['data_sql']);

        $ddlDateFrom = $date = Util::date('Y-m-d H:i:s');

        $ddlog = [];
        $firstIteration = true;

        // Iterate tracked data definition statements
        // For each log entry we want to get date, username and statement
        foreach ($logSchemaEntries as $logEntry) {
            if (trim($logEntry) == '') {
                continue;
            }

            $date = mb_substr($logEntry, 0, 19);
            $username = mb_substr(
                $logEntry,
                20,
                mb_strpos($logEntry, "\n") - 20,
            );
            if ($firstIteration) {
                $ddlDateFrom = $date;
                $firstIteration = false;
            }

            $statement = rtrim((string) mb_strstr($logEntry, "\n"));

            $ddlog[] = ['date' => $date, 'username' => $username, 'statement' => $statement];
        }

        $dateFrom = $ddlDateFrom;
        $ddlDateTo = $date;

        $dmlDateFrom = $dateFrom;

        $dmlog = [];
        $firstIteration = true;

        // Iterate tracked data manipulation statements
        // For each log entry we want to get date, username and statement
        foreach ($logDataEntries as $logEntry) {
            if (trim($logEntry) == '') {
                continue;
            }

            $date = mb_substr($logEntry, 0, 19);
            $username = mb_substr(
                $logEntry,
                20,
                mb_strpos($logEntry, "\n") - 20,
            );
            if ($firstIteration) {
                $dmlDateFrom = $date;
                $firstIteration = false;
            }

            $statement = rtrim((string) mb_strstr($logEntry, "\n"));

            $dmlog[] = ['date' => $date, 'username' => $username, 'statement' => $statement];
        }

        $dmlDateTo = $date;

        return new TrackedData(
            strtotime($ddlDateFrom) <= strtotime($dmlDateFrom) ? $ddlDateFrom : $dmlDateFrom,
            strtotime($ddlDateTo) >= strtotime($dmlDateTo) ? $ddlDateTo : $dmlDateTo,
            $ddlog,
            $dmlog,
            $mixed['tracking'] ?? '',
            $mixed['schema_snapshot'] ?? '',
        );
    }

    /**
     * Function to get html for displaying columns in the schema snapshot
     *
     * @param mixed[] $columns columns
     */
    public function getHtmlForColumns(array $columns): string
    {
        return $this->template->render('table/tracking/structure_snapshot_columns', ['columns' => $columns]);
    }

    /**
     * Function to get html for the indexes in schema snapshot
     *
     * @param mixed[] $indexes indexes
     */
    public function getHtmlForIndexes(array $indexes): string
    {
        return $this->template->render('table/tracking/structure_snapshot_indexes', ['indexes' => $indexes]);
    }

    /**
     * Function to delete from a tracking report log
     *
     * @param list<array{date: string, username: string, statement: string}> $logData tracked data
     * @param LogTypeEnum                                                    $logType DDL|DML
     *
     * @return string HTML for the message
     */
    public function deleteFromTrackingReportLog(
        string $db,
        string $table,
        string $version,
        array $logData,
        LogTypeEnum $logType,
        int $deleteId,
    ): string {
        unset($logData[$deleteId]);

        $successfullyDeleted = $this->changeTrackingData($db, $table, $version, $logType, $logData);
        if ($successfullyDeleted) {
            $msg = Message::success($logType->getSuccessMessage());
        } else {
            $msg = Message::rawError(__('Query error'));
        }

        return $msg->getDisplay();
    }

    /**
     * Changes tracking data of a table.
     *
     * @param string                                                          $dbName    name of database
     * @param string                                                          $tableName name of table
     * @param string                                                          $version   version
     * @param LogTypeEnum                                                     $logType   type of data(DDL || DML)
     * @param array<array{date: string, username: string, statement: string}> $newData   the new tracking data
     */
    public function changeTrackingData(
        string $dbName,
        string $tableName,
        string $version,
        LogTypeEnum $logType,
        array $newData,
    ): bool {
        $newDataProcessed = '';
        foreach ($newData as $data) {
            $newDataProcessed .= '# log ' . $data['date'] . ' ' . $data['username'] . $data['statement'] . "\n";
        }

        $trackingFeature = $this->relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return false;
        }

        $sqlQuery = sprintf(
            'UPDATE %s.%s SET `%s` = %s WHERE `db_name` = %s AND `table_name` = %s AND `version` = %s',
            Util::backquote($trackingFeature->database),
            Util::backquote($trackingFeature->tracking),
            $logType->getColumnName(),
            $this->dbi->quoteString($newDataProcessed, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($dbName, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($tableName, Connection::TYPE_CONTROL),
            $this->dbi->quoteString($version, Connection::TYPE_CONTROL),
        );

        $result = $this->dbi->queryAsControlUser($sqlQuery);

        return (bool) $result;
    }

    /**
     * Function to export as sql dump
     *
     * @param mixed[] $entries entries
     *
     * @return string HTML SQL query form
     */
    public function exportAsSqlDump(array $entries): string
    {
        $html = '';
        $newQuery = '# '
            . __(
                'You can execute the dump by creating and using a temporary database. '
                . 'Please ensure that you have the privileges to do so.',
            )
            . "\n"
            . '# ' . __('Comment out these two lines if you do not need them.') . "\n"
            . "\n"
            . "CREATE database IF NOT EXISTS pma_temp_db; \n"
            . "USE pma_temp_db; \n"
            . "\n";

        foreach ($entries as $entry) {
            $newQuery .= $entry['statement'];
        }

        $msg = Message::success(
            __('SQL statements exported. Please copy the dump or execute it.'),
        );
        $html .= $msg->getDisplay();

        $html .= $this->sqlQueryForm->getHtml('', '', $newQuery, 'sql');

        return $html;
    }

    /**
     * Function to export as sql execution
     *
     * @param mixed[] $entries entries
     */
    public function exportAsSqlExecution(array $entries): void
    {
        foreach ($entries as $entry) {
            $this->dbi->query("/*NOTRACK*/\n" . $entry['statement']);
        }
    }

    /**
     * @param array<int, array<string, int|string>> $entries
     *
     * @return array<string, string>
     * @psalm-return array{filename: non-empty-string, dump: non-empty-string}
     */
    public function getDownloadInfoForExport(string $table, array $entries): array
    {
        ini_set('url_rewriter.tags', '');

        // Replace all multiple whitespaces by a single space
        $table = htmlspecialchars((string) preg_replace('/\s+/', ' ', $table));
        $dump = '# ' . sprintf(__('Tracking report for table `%s`'), $table) . "\n"
            . '# ' . date('Y-m-d H:i:sP') . "\n";
        foreach ($entries as $entry) {
            $dump .= $entry['statement'];
        }

        $filename = 'log_' . $table . '.sql';

        return ['filename' => $filename, 'dump' => $dump];
    }

    /**
     * Function to activate or deactivate tracking
     *
     * @param string $action activate|deactivate
     *
     * @return string HTML for the success message
     */
    public function changeTracking(string $db, string $table, string $version, string $action): string
    {
        $html = '';
        if ($action === 'activate') {
            $status = Tracker::activateTracking($db, $table, $version);
            $message = __('Tracking for %1$s was activated at version %2$s.');
        } else {
            $status = Tracker::deactivateTracking($db, $table, $version);
            $message = __('Tracking for %1$s was deactivated at version %2$s.');
        }

        if ($status) {
            $msg = Message::success(
                sprintf(
                    $message,
                    htmlspecialchars($db . '.' . $table),
                    htmlspecialchars($version),
                ),
            );
            $html .= $msg->getDisplay();
        }

        return $html;
    }

    /**
     * Function to get tracking set
     */
    public function getTrackingSet(): string
    {
        $trackingSet = '';

        // a key is absent from the request if it has been removed from
        // tracking_default_statements in the config
        if (isset($_POST['alter_table']) && $_POST['alter_table'] == true) {
            $trackingSet .= 'ALTER TABLE,';
        }

        if (isset($_POST['rename_table']) && $_POST['rename_table'] == true) {
            $trackingSet .= 'RENAME TABLE,';
        }

        if (isset($_POST['create_table']) && $_POST['create_table'] == true) {
            $trackingSet .= 'CREATE TABLE,';
        }

        if (isset($_POST['drop_table']) && $_POST['drop_table'] == true) {
            $trackingSet .= 'DROP TABLE,';
        }

        if (isset($_POST['alter_view']) && $_POST['alter_view'] == true) {
            $trackingSet .= 'ALTER VIEW,';
        }

        if (isset($_POST['create_view']) && $_POST['create_view'] == true) {
            $trackingSet .= 'CREATE VIEW,';
        }

        if (isset($_POST['drop_view']) && $_POST['drop_view'] == true) {
            $trackingSet .= 'DROP VIEW,';
        }

        if (isset($_POST['create_index']) && $_POST['create_index'] == true) {
            $trackingSet .= 'CREATE INDEX,';
        }

        if (isset($_POST['drop_index']) && $_POST['drop_index'] == true) {
            $trackingSet .= 'DROP INDEX,';
        }

        if (isset($_POST['insert']) && $_POST['insert'] == true) {
            $trackingSet .= 'INSERT,';
        }

        if (isset($_POST['update']) && $_POST['update'] == true) {
            $trackingSet .= 'UPDATE,';
        }

        if (isset($_POST['delete']) && $_POST['delete'] == true) {
            $trackingSet .= 'DELETE,';
        }

        if (isset($_POST['truncate']) && $_POST['truncate'] == true) {
            $trackingSet .= 'TRUNCATE,';
        }

        return rtrim($trackingSet, ',');
    }

    /**
     * Deletes a tracking version
     *
     * @param string $version tracking version
     *
     * @return string HTML of the success message
     */
    public function deleteTrackingVersion(string $db, string $table, string $version): string
    {
        $html = '';
        $versionDeleted = $this->deleteTracking($db, $table, $version);
        if ($versionDeleted) {
            $msg = Message::success(
                sprintf(
                    __('Version %1$s of %2$s was deleted.'),
                    htmlspecialchars($version),
                    htmlspecialchars($db . '.' . $table),
                ),
            );
            $html .= $msg->getDisplay();
        }

        return $html;
    }

    /**
     * Function to create the tracking version
     *
     * @return string HTML of the success message
     */
    public function createTrackingVersion(string $db, string $table, string $version): string
    {
        $html = '';
        $trackingSet = $this->getTrackingSet();

        $versionCreated = Tracker::createVersion(
            $db,
            $table,
            $version,
            $trackingSet,
            $this->dbi->getTable($db, $table)->isView(),
        );
        if ($versionCreated) {
            $msg = Message::success(
                sprintf(
                    __('Version %1$s was created, tracking for %2$s is active.'),
                    htmlspecialchars($version),
                    htmlspecialchars($db . '.' . $table),
                ),
            );
            $html .= $msg->getDisplay();
        }

        return $html;
    }

    /**
     * Create tracking version for multiple tables
     *
     * @param mixed[] $selected list of selected tables
     */
    public function createTrackingForMultipleTables(string $db, array $selected, string $version): void
    {
        $trackingSet = $this->getTrackingSet();

        foreach ($selected as $selectedTable) {
            Tracker::createVersion(
                $db,
                $selectedTable,
                $version,
                $trackingSet,
                $this->dbi->getTable($db, $selectedTable)->isView(),
            );
        }
    }

    /**
     * Function to get the entries
     *
     * @param mixed[] $filterUsers filter users
     * @phpstan-param 'schema'|'data'|'schema_and_data' $logType
     *
     * @return mixed[]
     */
    public function getEntries(
        TrackedData $trackedData,
        array $filterUsers,
        string $logType,
        DateTimeImmutable $dateFrom,
        DateTimeImmutable $dateTo,
    ): array {
        $entries = [];
        // Filtering data definition statements
        if ($logType === 'schema' || $logType === 'schema_and_data') {
            $entries = array_merge(
                $entries,
                $this->filter($trackedData->ddlog, $filterUsers, $dateFrom, $dateTo),
            );
        }

        // Filtering data manipulation statements
        if ($logType === 'data' || $logType === 'schema_and_data') {
            $entries = array_merge(
                $entries,
                $this->filter($trackedData->dmlog, $filterUsers, $dateFrom, $dateTo),
            );
        }

        // Sort it
        $ids = $timestamps = $usernames = $statements = [];
        foreach ($entries as $key => $row) {
            $ids[$key] = $row['id'];
            $timestamps[$key] = $row['timestamp'];
            $usernames[$key] = $row['username'];
            $statements[$key] = $row['statement'];
        }

        array_multisort($timestamps, SORT_ASC, $ids, SORT_ASC, $usernames, SORT_ASC, $statements, SORT_ASC, $entries);

        return $entries;
    }

    /**
     * Get HTML for tracked and untracked tables
     *
     * @param string  $db        current database
     * @param mixed[] $urlParams url parameters
     * @param string  $textDir   text direction
     *
     * @return string HTML
     */
    public function getHtmlForDbTrackingTables(
        string $db,
        array $urlParams,
        string $textDir,
    ): string {
        $trackingFeature = $this->relation->getRelationParameters()->trackingFeature;
        if ($trackingFeature === null) {
            return '';
        }

        // Prepare statement to get HEAD version
        $allTablesQuery = ' SELECT table_name, MAX(version) as version FROM '
            . Util::backquote($trackingFeature->database) . '.' . Util::backquote($trackingFeature->tracking)
            . ' WHERE db_name = ' . $this->dbi->quoteString($db, Connection::TYPE_CONTROL)
            . '  GROUP BY table_name ORDER BY table_name ASC';

        $allTablesResult = $this->dbi->queryAsControlUser($allTablesQuery);
        $untrackedTables = $this->trackingChecker->getUntrackedTableNames($db);

        // If a HEAD version exists
        $versions = [];
        while ($oneResult = $allTablesResult->fetchRow()) {
            [$tableName, $versionNumber] = $oneResult;
            $tableQuery = ' SELECT * FROM '
                . Util::backquote($trackingFeature->database) . '.' . Util::backquote($trackingFeature->tracking)
                . ' WHERE `db_name` = ' . $this->dbi->quoteString($db, Connection::TYPE_CONTROL)
                . ' AND `table_name`  = ' . $this->dbi->quoteString($tableName, Connection::TYPE_CONTROL)
                . ' AND `version` = ' . $this->dbi->quoteString($versionNumber, Connection::TYPE_CONTROL);

            $versions[] = $this->dbi->queryAsControlUser($tableQuery)->fetchAssoc();
        }

        return $this->template->render('database/tracking/tables', [
            'db' => $db,
            'head_version_exists' => $versions !== [],
            'untracked_tables_exists' => $untrackedTables !== [],
            'versions' => $versions,
            'url_params' => $urlParams,
            'text_dir' => $textDir,
            'untracked_tables' => $untrackedTables,
        ]);
    }

    private function isDateBetweenInclusive(
        DateTimeImmutable $date,
        DateTimeImmutable $start,
        DateTimeImmutable $end,
    ): bool {
        return $date >= $start && $date <= $end;
    }
}
