<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function is_array;
use function mb_strtolower;
use function preg_match;
use function preg_replace;
use function str_contains;
use function str_ireplace;
use function str_replace;
use function strncasecmp;

/**
 * Handles find and replace tab.
 *
 * Displays find and replace form, allows previewing and do the replacing.
 */
class FindReplaceController extends AbstractController
{
    /** @var mixed[] */
    private array $columnNames = [];

    /** @var mixed[] */
    private array $columnTypes = [];

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        if (! $this->checkParameters(['db', 'table'])) {
            return;
        }

        $GLOBALS['urlParams'] = ['db' => Current::$database, 'table' => Current::$table];
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption(
            Config::getInstance()->settings['DefaultTabTable'],
            'table',
        );
        $GLOBALS['errorUrl'] .= Url::getCommon($GLOBALS['urlParams'], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return;
        }

        $tableName = TableName::tryFrom($request->getParam('table'));
        if ($tableName === null || ! $this->dbTableExists->hasTable($databaseName, $tableName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No table selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No table selected.')]);

            return;
        }

        $this->loadTableInfo();
        $connectionCharSet = (string) $this->dbi->fetchValue('SELECT @@character_set_connection');

        $useRegex = (bool) $request->getParsedBodyParam('useRegex');
        $replaceWith = (string) $request->getParsedBodyParam('replaceWith');
        $columnIndex = (int) $request->getParsedBodyParam('columnIndex');

        if ($request->hasBodyParam('find')) {
            $find = (string) $request->getParsedBodyParam('find');
            $preview = $this->getReplacePreview($columnIndex, $find, $replaceWith, $useRegex, $connectionCharSet);
            $this->response->addJSON('preview', $preview);

            return;
        }

        $this->addScriptFiles(['table/find_replace.js']);

        if ($request->hasBodyParam('replace')) {
            $findString = (string) $request->getParsedBodyParam('findString');
            $this->replace($columnIndex, $findString, $replaceWith, $useRegex, $connectionCharSet);
            $this->response->addHTML(
                Generator::getMessage(
                    __('Your SQL query has been executed successfully.'),
                    null,
                    'success',
                ),
            );
        }

        // Displays the find and replace form
        $this->displaySelectionFormAction();
    }

    /**
     * Gets all the columns of a table along with their types.
     */
    private function loadTableInfo(): void
    {
        // Gets the list and number of columns
        $columns = $this->dbi->getColumns(Current::$database, Current::$table, true);

        foreach ($columns as $row) {
            // set column name
            $this->columnNames[] = $row->field;

            $type = $row->type;
            // reformat mysql query output
            if (strncasecmp($type, 'set', 3) == 0 || strncasecmp($type, 'enum', 4) == 0) {
                $type = str_replace(',', ', ', $type);
            } else {
                // strip the "BINARY" attribute, except if we find "BINARY(" because
                // this would be a BINARY or VARBINARY column type
                if (! preg_match('@BINARY[\(]@i', $type)) {
                    $type = str_ireplace('BINARY', '', $type);
                }

                $type = str_ireplace('ZEROFILL', '', $type);
                $type = str_ireplace('UNSIGNED', '', $type);
                $type = mb_strtolower($type);
            }

            if ($type === '') {
                $type = '&nbsp;';
            }

            $this->columnTypes[] = $type;
        }
    }

    /**
     * Display selection form action
     */
    private function displaySelectionFormAction(): void
    {
        if (! isset($GLOBALS['goto'])) {
            $GLOBALS['goto'] = Util::getScriptNameForOption(
                Config::getInstance()->settings['DefaultTabTable'],
                'table',
            );
        }

        $types = [];
        foreach ($this->columnNames as $i => $columnName) {
            $types[$columnName] = preg_replace('@\\(.*@s', '', $this->columnTypes[$i]);
        }

        $this->render('table/find_replace/index', [
            'db' => Current::$database,
            'table' => Current::$table,
            'goto' => $GLOBALS['goto'],
            'column_names' => $this->columnNames,
            'types' => $types,
            'sql_types' => $this->dbi->types,
        ]);
    }

    /**
     * Returns HTML for previewing strings found and their replacements
     *
     * @param int    $columnIndex index of the column
     * @param string $find        string to find in the column
     * @param string $replaceWith string to replace with
     * @param bool   $useRegex    to use Regex replace or not
     * @param string $charSet     character set of the connection
     *
     * @return string HTML for previewing strings found and their replacements
     */
    private function getReplacePreview(
        int $columnIndex,
        string $find,
        string $replaceWith,
        bool $useRegex,
        string $charSet,
    ): string {
        $column = $this->columnNames[$columnIndex];
        if ($useRegex) {
            $result = $this->getRegexReplaceRows($columnIndex, $find, $replaceWith, $charSet);
        } else {
            $sqlQuery = 'SELECT '
                . Util::backquote($column) . ','
                . ' REPLACE('
                . Util::backquote($column) . ", '" . $find . "', '"
                . $replaceWith
                . "'),"
                . ' COUNT(*)'
                . ' FROM ' . Util::backquote(Current::$database)
                . '.' . Util::backquote(Current::$table)
                . ' WHERE ' . Util::backquote($column)
                . " LIKE '%" . $find . "%' COLLATE " . $charSet . '_bin'; // here we
            // change the collation of the 2nd operand to a case sensitive
            // binary collation to make sure that the comparison
            // is case sensitive
            $sqlQuery .= ' GROUP BY ' . Util::backquote($column)
                . ' ORDER BY ' . Util::backquote($column) . ' ASC';

            $result = $this->dbi->fetchResult($sqlQuery, 0);
        }

        return $this->template->render('table/find_replace/replace_preview', [
            'db' => Current::$database,
            'table' => Current::$table,
            'column_index' => $columnIndex,
            'find' => $find,
            'replace_with' => $replaceWith,
            'use_regex' => $useRegex,
            'result' => $result,
        ]);
    }

    /**
     * Finds and returns Regex pattern and their replacements
     *
     * @param int    $columnIndex index of the column
     * @param string $find        string to find in the column
     * @param string $replaceWith string to replace with
     * @param string $charSet     character set of the connection
     *
     * @return mixed[]|bool Array containing original values, replaced values and count
     */
    private function getRegexReplaceRows(
        int $columnIndex,
        string $find,
        string $replaceWith,
        string $charSet,
    ): array|bool {
        $column = $this->columnNames[$columnIndex];
        $sqlQuery = 'SELECT '
            . Util::backquote($column) . ','
            . ' 1,' // to add an extra column that will have replaced value
            . ' COUNT(*)'
            . ' FROM ' . Util::backquote(Current::$database)
            . '.' . Util::backquote(Current::$table)
            . ' WHERE ' . Util::backquote($column)
            . ' RLIKE ' . $this->dbi->quoteString($find) . ' COLLATE '
            . $charSet . '_bin'; // here we
        // change the collation of the 2nd operand to a case sensitive
        // binary collation to make sure that the comparison is case sensitive
        $sqlQuery .= ' GROUP BY ' . Util::backquote($column)
            . ' ORDER BY ' . Util::backquote($column) . ' ASC';

        $result = $this->dbi->fetchResult($sqlQuery, 0);

        /* Iterate over possible delimiters to get one */
        $delimiters = ['/', '@', '#', '~', '!', '$', '%', '^', '&', '_'];

        foreach ($delimiters as $delimiter) {
            if (! str_contains($find, $delimiter)) {
                foreach ($result as $index => $row) {
                    $result[$index][1] = preg_replace($delimiter . $find . $delimiter, $replaceWith, $row[0]);
                }

                return $result;
            }
        }

        return false;
    }

    /**
     * Replaces a given string in a column with a give replacement
     *
     * @param int    $columnIndex index of the column
     * @param string $find        string to find in the column
     * @param string $replaceWith string to replace with
     * @param bool   $useRegex    to use Regex replace or not
     * @param string $charSet     character set of the connection
     */
    private function replace(
        int $columnIndex,
        string $find,
        string $replaceWith,
        bool $useRegex,
        string $charSet,
    ): void {
        $column = $this->columnNames[$columnIndex];
        if ($useRegex) {
            $toReplace = $this->getRegexReplaceRows($columnIndex, $find, $replaceWith, $charSet);
            $sqlQuery = 'UPDATE ' . Util::backquote(Current::$table)
                . ' SET ' . Util::backquote($column);

            if (is_array($toReplace)) {
                if ($toReplace !== []) {
                    $sqlQuery .= ' = CASE';
                    foreach ($toReplace as $row) {
                        $sqlQuery .= "\n WHEN " . Util::backquote($column)
                            . ' = ' . $this->dbi->quoteString($row[0])
                            . ' THEN ' . $this->dbi->quoteString($row[1]);
                    }

                    $sqlQuery .= ' END';
                } else {
                    $sqlQuery .= ' = ' . Util::backquote($column);
                }
            }

            $sqlQuery .= ' WHERE ' . Util::backquote($column)
                . ' RLIKE ' . $this->dbi->quoteString($find) . ' COLLATE '
                . $charSet . '_bin'; // here we
            // change the collation of the 2nd operand to a case sensitive
            // binary collation to make sure that the comparison
            // is case sensitive
        } else {
            $sqlQuery = 'UPDATE ' . Util::backquote(Current::$table)
                . ' SET ' . Util::backquote($column) . ' ='
                . ' REPLACE('
                . Util::backquote($column) . ", '" . $find . "', '"
                . $replaceWith
                . "')"
                . ' WHERE ' . Util::backquote($column)
                . " LIKE '%" . $find . "%' COLLATE " . $charSet . '_bin'; // here we
            // change the collation of the 2nd operand to a case sensitive
            // binary collation to make sure that the comparison
            // is case sensitive
        }

        $this->dbi->query($sqlQuery);
        $GLOBALS['sql_query'] = $sqlQuery;
    }
}
