<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Message;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function array_column;
use function array_diff;
use function array_unique;
use function array_values;
use function bin2hex;
use function ceil;
use function count;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function mb_strtoupper;
use function sprintf;
use function trim;

class CentralColumns
{
    /**
     * Current user
     */
    private string $user;

    /**
     * Number of rows displayed when browsing a result set
     */
    private int $maxRows;

    /**
     * Which editor should be used for CHAR/VARCHAR fields
     */
    private string $charEditing;

    /**
     * Disable use of INFORMATION_SCHEMA
     */
    private bool $disableIs;

    private Relation $relation;

    public Template $template;

    public function __construct(private DatabaseInterface $dbi)
    {
        $this->user = $GLOBALS['cfg']['Server']['user'];
        $this->maxRows = (int) $GLOBALS['cfg']['MaxRows'];
        $this->charEditing = $GLOBALS['cfg']['CharEditing'];
        $this->disableIs = (bool) $GLOBALS['cfg']['Server']['DisableIS'];

        $this->relation = new Relation($this->dbi);
        $this->template = new Template();
    }

    /**
     * Defines the central_columns parameters for the current user
     *
     * @return mixed[]|bool the central_columns parameters for the current user
     */
    public function getParams(): array|bool
    {
        static $cfgCentralColumns = null;

        if ($cfgCentralColumns !== null) {
            return $cfgCentralColumns;
        }

        $centralColumnsFeature = $this->relation->getRelationParameters()->centralColumnsFeature;
        if ($centralColumnsFeature === null) {
            $cfgCentralColumns = false;

            return $cfgCentralColumns;
        }

        $cfgCentralColumns = [
            'user' => $this->user,
            'db' => $centralColumnsFeature->database->getName(),
            'table' => $centralColumnsFeature->centralColumns->getName(),
        ];

        return $cfgCentralColumns;
    }

    /**
     * get $num columns of given database from central columns list
     * starting at offset $from
     *
     * @param string $db   selected database
     * @param int    $from starting offset of first result
     * @param int    $num  maximum number of results to return
     *
     * @return mixed[] list of $num columns present in central columns list
     * starting at offset $from for the given database
     */
    public function getColumnsList(string $db, int $from = 0, int $num = 25): array
    {
        $cfgCentralColumns = $this->getParams();
        if (! is_array($cfgCentralColumns)) {
            return [];
        }

        $pmadb = $cfgCentralColumns['db'];
        $this->dbi->selectDb($pmadb, Connection::TYPE_CONTROL);
        $centralListTable = $cfgCentralColumns['table'];
        //get current values of $db from central column list
        if ($num == 0) {
            $query = 'SELECT * FROM ' . Util::backquote($centralListTable) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\';';
        } else {
            $query = 'SELECT * FROM ' . Util::backquote($centralListTable) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\' '
                . 'LIMIT ' . $from . ', ' . $num . ';';
        }

        $hasList = $this->dbi->fetchResult($query, null, null, Connection::TYPE_CONTROL);
        $this->handleColumnExtra($hasList);

        return $hasList;
    }

    /**
     * Get the number of columns present in central list for given db
     *
     * @param string $db current database
     *
     * @return int number of columns in central list of columns for $db
     */
    public function getCount(string $db): int
    {
        $cfgCentralColumns = $this->getParams();
        if (! is_array($cfgCentralColumns)) {
            return 0;
        }

        $pmadb = $cfgCentralColumns['db'];
        $this->dbi->selectDb($pmadb, Connection::TYPE_CONTROL);
        $centralListTable = $cfgCentralColumns['table'];
        $query = 'SELECT count(db_name) FROM '
            . Util::backquote($centralListTable) . ' '
            . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\';';
        $res = $this->dbi->fetchResult($query, null, null, Connection::TYPE_CONTROL);
        if (isset($res[0])) {
            return (int) $res[0];
        }

        return 0;
    }

    /**
     * return the existing columns in central list among the given list of columns
     *
     * @param string $db        the selected database
     * @param string $cols      comma separated list of given columns
     * @param bool   $allFields set if need all the fields of existing columns,
     *                          otherwise only column_name is returned
     *
     * @return mixed[] list of columns in central columns among given set of columns
     */
    private function findExistingColNames(
        string $db,
        string $cols,
        bool $allFields = false,
    ): array {
        $cfgCentralColumns = $this->getParams();
        if (! is_array($cfgCentralColumns)) {
            return [];
        }

        $pmadb = $cfgCentralColumns['db'];
        $this->dbi->selectDb($pmadb, Connection::TYPE_CONTROL);
        $centralListTable = $cfgCentralColumns['table'];
        if ($allFields) {
            $query = 'SELECT * FROM ' . Util::backquote($centralListTable) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\' AND col_name IN (' . $cols . ');';
            $hasList = $this->dbi->fetchResult($query, null, null, Connection::TYPE_CONTROL);
            $this->handleColumnExtra($hasList);
        } else {
            $query = 'SELECT col_name FROM '
                . Util::backquote($centralListTable) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\' AND col_name IN (' . $cols . ');';
            $hasList = $this->dbi->fetchResult($query, null, null, Connection::TYPE_CONTROL);
        }

        return $hasList;
    }

    /**
     * build the insert query for central columns list given PMA storage
     * db, central_columns table, column name and corresponding definition to be added
     *
     * @param string  $column           column to add into central list
     * @param mixed[] $def              list of attributes of the column being added
     * @param string  $db               PMA configuration storage database name
     * @param string  $centralListTable central columns configuration storage table name
     *
     * @return string query string to insert the given column
     * with definition into central list
     */
    private function getInsertQuery(
        string $column,
        array $def,
        string $db,
        string $centralListTable,
    ): string {
        $type = '';
        $length = 0;
        $attribute = '';
        if (isset($def['Type'])) {
            $extractedColumnSpec = Util::extractColumnSpec($def['Type']);
            $attribute = trim($extractedColumnSpec['attribute']);
            $type = $extractedColumnSpec['type'];
            $length = $extractedColumnSpec['spec_in_brackets'];
        }

        if (isset($def['Attribute'])) {
            $attribute = $def['Attribute'];
        }

        $collation = $def['Collation'] ?? '';
        $isNull = $def['Null'] === 'NO' ? '0' : '1';
        $extra = $def['Extra'] ?? '';
        $default = $def['Default'] ?? '';

        return 'INSERT INTO '
            . Util::backquote($centralListTable) . ' '
            . 'VALUES ( \'' . $this->dbi->escapeString($db) . '\' ,'
            . '\'' . $this->dbi->escapeString($column) . '\',\''
            . $this->dbi->escapeString($type) . '\','
            . '\'' . $this->dbi->escapeString((string) $length) . '\',\''
            . $this->dbi->escapeString($collation) . '\','
            . '\'' . $this->dbi->escapeString($isNull) . '\','
            . '\'' . implode(',', [$extra, $attribute])
            . '\',\'' . $this->dbi->escapeString($default) . '\');';
    }

    /**
     * If $isTable is true then unique columns from given tables as $field_select
     * are added to central list otherwise the $field_select is considered as
     * list of columns and these columns are added to central list if not already added
     *
     * @param mixed[] $fieldSelect if $isTable is true selected tables list
     *                            otherwise selected columns list
     * @param bool    $isTable     if passed array is of tables or columns
     * @param string  $table       if $isTable is false, then table name to
     *                             which columns belong
     *
     * @return true|Message
     */
    public function syncUniqueColumns(
        array $fieldSelect,
        bool $isTable = true,
        string|null $table = null,
    ): bool|Message {
        $cfgCentralColumns = $this->getParams();
        if (! is_array($cfgCentralColumns)) {
            return Message::error(
                __('The configuration storage is not ready for the central list of columns feature.'),
            );
        }

        $db = $_POST['db'];
        $pmadb = $cfgCentralColumns['db'];
        $centralListTable = $cfgCentralColumns['table'];
        $this->dbi->selectDb($db);
        $existingCols = [];
        $cols = '';
        $insQuery = [];
        $fields = [];
        $message = true;
        if ($isTable) {
            foreach ($fieldSelect as $table) {
                $fields[$table] = $this->dbi->getColumns($db, $table, true);
                foreach (array_column($fields[$table], 'Field') as $field) {
                    $cols .= "'" . $this->dbi->escapeString($field) . "',";
                }
            }

            $hasList = $this->findExistingColNames($db, trim($cols, ','));
            foreach ($fieldSelect as $table) {
                foreach ($fields[$table] as $def) {
                    $field = (string) $def['Field'];
                    if (! in_array($field, $hasList)) {
                        $hasList[] = $field;
                        $insQuery[] = $this->getInsertQuery($field, $def, $db, $centralListTable);
                    } else {
                        $existingCols[] = "'" . $field . "'";
                    }
                }
            }
        } else {
            if ($table === null) {
                $table = $_POST['table'];
            }

            foreach ($fieldSelect as $column) {
                $cols .= "'" . $this->dbi->escapeString($column) . "',";
            }

            $hasList = $this->findExistingColNames($db, trim($cols, ','));
            foreach ($fieldSelect as $column) {
                if (! in_array($column, $hasList)) {
                    $hasList[] = $column;
                    $field = $this->dbi->getColumn($db, $table, $column, true);
                    $insQuery[] = $this->getInsertQuery($column, $field, $db, $centralListTable);
                } else {
                    $existingCols[] = "'" . $column . "'";
                }
            }
        }

        if ($existingCols !== []) {
            $existingCols = implode(',', array_unique($existingCols));
            $message = Message::notice(
                sprintf(
                    __(
                        'Could not add %1$s as they already exist in central list!',
                    ),
                    htmlspecialchars($existingCols),
                ),
            );
            $message->addMessage(
                Message::notice(
                    'Please remove them first from central list if you want to update above columns',
                ),
            );
        }

        $this->dbi->selectDb($pmadb, Connection::TYPE_CONTROL);
        foreach ($insQuery as $query) {
            if (! $this->dbi->tryQuery($query, Connection::TYPE_CONTROL)) {
                $message = Message::error(__('Could not add columns!'));
                $message->addMessage(
                    Message::rawError($this->dbi->getError(Connection::TYPE_CONTROL)),
                );
                break;
            }
        }

        return $message;
    }

    /**
     * if $isTable is true it removes all columns of given tables as $field_select from
     * central columns list otherwise $field_select is columns list and it removes
     * given columns if present in central list
     *
     * @param string  $database    Database name
     * @param mixed[] $fieldSelect if $isTable selected list of tables otherwise
     *                            selected list of columns to remove from central list
     * @param bool    $isTable     if passed array is of tables or columns
     *
     * @return true|Message
     */
    public function deleteColumnsFromList(
        string $database,
        array $fieldSelect,
        bool $isTable = true,
    ): bool|Message {
        $cfgCentralColumns = $this->getParams();
        if (! is_array($cfgCentralColumns)) {
            return Message::error(
                __('The configuration storage is not ready for the central list of columns feature.'),
            );
        }

        $pmadb = $cfgCentralColumns['db'];
        $centralListTable = $cfgCentralColumns['table'];
        $this->dbi->selectDb($database);
        $message = true;
        $colNotExist = [];
        $fields = [];
        if ($isTable) {
            $cols = '';
            foreach ($fieldSelect as $table) {
                $fields[$table] = $this->dbi->getColumnNames($database, $table);
                foreach ($fields[$table] as $colSelect) {
                    $cols .= '\'' . $this->dbi->escapeString($colSelect) . '\',';
                }
            }

            $cols = trim($cols, ',');
            $hasList = $this->findExistingColNames($database, $cols);
            foreach ($fieldSelect as $table) {
                foreach ($fields[$table] as $column) {
                    if (in_array($column, $hasList)) {
                        continue;
                    }

                    $colNotExist[] = "'" . $column . "'";
                }
            }
        } else {
            $cols = '';
            foreach ($fieldSelect as $colSelect) {
                $cols .= '\'' . $this->dbi->escapeString($colSelect) . '\',';
            }

            $cols = trim($cols, ',');
            $hasList = $this->findExistingColNames($database, $cols);
            foreach ($fieldSelect as $column) {
                if (in_array($column, $hasList)) {
                    continue;
                }

                $colNotExist[] = "'" . $column . "'";
            }
        }

        if ($colNotExist !== []) {
            $colNotExist = implode(',', array_unique($colNotExist));
            $message = Message::notice(
                sprintf(
                    __(
                        'Couldn\'t remove Column(s) %1$s as they don\'t exist in central columns list!',
                    ),
                    htmlspecialchars($colNotExist),
                ),
            );
        }

        $this->dbi->selectDb($pmadb, Connection::TYPE_CONTROL);

        $query = 'DELETE FROM ' . Util::backquote($centralListTable) . ' '
            . 'WHERE db_name = \'' . $this->dbi->escapeString($database) . '\' AND col_name IN (' . $cols . ');';

        if (! $this->dbi->tryQuery($query, Connection::TYPE_CONTROL)) {
            $message = Message::error(__('Could not remove columns!'));
            $message->addHtml('<br>' . htmlspecialchars($cols) . '<br>');
            $message->addMessage(
                Message::rawError($this->dbi->getError(Connection::TYPE_CONTROL)),
            );
        }

        return $message;
    }

    /**
     * Make the columns of given tables consistent with central list of columns.
     * Updates only those columns which are not being referenced.
     *
     * @param string   $db             current database
     * @param string[] $selectedTables list of selected tables.
     *
     * @return true|Message
     */
    public function makeConsistentWithList(
        string $db,
        array $selectedTables,
    ): bool|Message {
        $message = true;
        foreach ($selectedTables as $table) {
            $query = 'ALTER TABLE ' . Util::backquote($table);
            $hasList = $this->getFromTable($db, $table, true);
            $this->dbi->selectDb($db);
            foreach ($hasList as $column) {
                $columnStatus = $this->relation->checkChildForeignReferences($db, $table, $column['col_name']);
                //column definition can only be changed if
                //it is not referenced by another column
                if (! $columnStatus['isEditable']) {
                    continue;
                }

                $query .= ' MODIFY ' . Util::backquote($column['col_name']) . ' '
                    . $this->dbi->escapeString($column['col_type']);
                if ($column['col_length']) {
                    $query .= '(' . $column['col_length'] . ')';
                }

                $query .= ' ' . $column['col_attribute'];
                if ($column['col_isNull']) {
                    $query .= ' NULL';
                } else {
                    $query .= ' NOT NULL';
                }

                $query .= ' ' . $column['col_extra'];
                if ($column['col_default']) {
                    if (
                        $column['col_default'] !== 'CURRENT_TIMESTAMP'
                        && $column['col_default'] !== 'current_timestamp()'
                    ) {
                        $query .= ' DEFAULT \'' . $this->dbi->escapeString((string) $column['col_default']) . '\'';
                    } else {
                        $query .= ' DEFAULT ' . $this->dbi->escapeString($column['col_default']);
                    }
                }

                $query .= ',';
            }

            $query = trim($query, ' ,') . ';';
            if ($this->dbi->tryQuery($query)) {
                continue;
            }

            if ($message === true) {
                $message = Message::error($this->dbi->getError());
            } else {
                $message->addText($this->dbi->getError(), '<br>');
            }
        }

        return $message;
    }

    /**
     * return the columns present in central list of columns for a given
     * table of a given database
     *
     * @param string $db        given database
     * @param string $table     given table
     * @param bool   $allFields set if need all the fields of existing columns,
     *                          otherwise only column_name is returned
     *
     * @return mixed[] columns present in central list from given table of given db.
     */
    public function getFromTable(
        string $db,
        string $table,
        bool $allFields = false,
    ): array {
        $cfgCentralColumns = $this->getParams();
        if ($cfgCentralColumns === false || $cfgCentralColumns === []) {
            return [];
        }

        $this->dbi->selectDb($db);
        $fields = $this->dbi->getColumnNames($db, $table);
        $cols = '';
        foreach ($fields as $colSelect) {
            $cols .= '\'' . $this->dbi->escapeString($colSelect) . '\',';
        }

        $cols = trim($cols, ',');

        return $this->findExistingColNames($db, $cols, $allFields);
    }

    /**
     * update a column in central columns list if a edit is requested
     *
     * @param string $db           current database
     * @param string $origColName  original column name before edit
     * @param string $colName      new column name
     * @param string $colType      new column type
     * @param string $colAttribute new column attribute
     * @param string $colLength    new column length
     * @param int    $colIsNull    value 1 if new column isNull is true, 0 otherwise
     * @param string $collation    new column collation
     * @param string $colExtra     new column extra property
     * @param string $colDefault   new column default value
     *
     * @return true|Message
     */
    public function updateOneColumn(
        string $db,
        string $origColName,
        string $colName,
        string $colType,
        string $colAttribute,
        string $colLength,
        int $colIsNull,
        string $collation,
        string $colExtra,
        string $colDefault,
    ): bool|Message {
        $cfgCentralColumns = $this->getParams();
        if (! is_array($cfgCentralColumns)) {
            return Message::error(
                __('The configuration storage is not ready for the central list of columns feature.'),
            );
        }

        $centralTable = $cfgCentralColumns['table'];
        $this->dbi->selectDb($cfgCentralColumns['db'], Connection::TYPE_CONTROL);
        if ($origColName == '') {
            $def = [];
            $def['Type'] = $colType;
            if ($colLength) {
                $def['Type'] .= '(' . $colLength . ')';
            }

            $def['Collation'] = $collation;
            $def['Null'] = $colIsNull ? __('YES') : __('NO');
            $def['Extra'] = $colExtra;
            $def['Attribute'] = $colAttribute;
            $def['Default'] = $colDefault;
            $query = $this->getInsertQuery($colName, $def, $db, $centralTable);
        } else {
            $query = 'UPDATE ' . Util::backquote($centralTable)
                . ' SET col_type = \'' . $this->dbi->escapeString($colType) . '\''
                . ', col_name = \'' . $this->dbi->escapeString($colName) . '\''
                . ', col_length = \'' . $this->dbi->escapeString($colLength) . '\''
                . ', col_isNull = ' . $colIsNull
                . ', col_collation = \'' . $this->dbi->escapeString($collation) . '\''
                . ', col_extra = \''
                . implode(',', [$colExtra, $colAttribute]) . '\''
                . ', col_default = \'' . $this->dbi->escapeString($colDefault) . '\''
                . ' WHERE db_name = \'' . $this->dbi->escapeString($db) . '\' '
                . 'AND col_name = \'' . $this->dbi->escapeString($origColName)
                . '\'';
        }

        if (! $this->dbi->tryQuery($query, Connection::TYPE_CONTROL)) {
            return Message::error($this->dbi->getError(Connection::TYPE_CONTROL));
        }

        return true;
    }

    /**
     * Update Multiple column in central columns list if a change is requested
     *
     * @param mixed[] $params Request parameters
     *
     * @return true|Message
     */
    public function updateMultipleColumn(array $params): bool|Message
    {
        $columnDefault = $params['field_default_type'];
        $columnIsNull = [];
        $columnExtra = [];
        $numberCentralFields = count($params['orig_col_name']);
        for ($i = 0; $i < $numberCentralFields; $i++) {
            $columnIsNull[$i] = isset($params['field_null'][$i]) ? 1 : 0;
            $columnExtra[$i] = $params['col_extra'][$i] ?? '';

            if ($columnDefault[$i] === 'NONE') {
                $columnDefault[$i] = '';
            } elseif ($columnDefault[$i] === 'USER_DEFINED') {
                $columnDefault[$i] = $params['field_default_value'][$i];
            }

            $message = $this->updateOneColumn(
                $params['db'],
                $params['orig_col_name'][$i],
                $params['field_name'][$i],
                $params['field_type'][$i],
                $params['field_attribute'][$i],
                $params['field_length'][$i],
                $columnIsNull[$i],
                $params['field_collation'][$i],
                $columnExtra[$i],
                $columnDefault[$i],
            );
            if (! is_bool($message)) {
                return $message;
            }
        }

        return true;
    }

    /**
     * build html for editing a row in central columns table
     *
     * @param mixed[] $row    array contains complete information of a
     *                      particular row of central list table
     * @param int     $rowNum position the row in the table
     *
     * @return string html of a particular row in the central columns table.
     */
    private function getHtmlForEditTableRow(array $row, int $rowNum): string
    {
        $meta = [];
        if (! isset($row['col_default']) || $row['col_default'] == '') {
            $meta['DefaultType'] = 'NONE';
        } elseif ($row['col_default'] === 'CURRENT_TIMESTAMP' || $row['col_default'] === 'current_timestamp()') {
            $meta['DefaultType'] = 'CURRENT_TIMESTAMP';
        } elseif ($row['col_default'] === 'NULL') {
            $meta['DefaultType'] = $row['col_default'];
        } else {
            $meta['DefaultType'] = 'USER_DEFINED';
            $meta['DefaultValue'] = $row['col_default'];
        }

        $defaultValue = '';
        $typeUpper = mb_strtoupper((string) $row['col_type']);

        // For a TIMESTAMP, do not show the string "CURRENT_TIMESTAMP" as a default value
        if (isset($meta['DefaultValue'])) {
            $defaultValue = $meta['DefaultValue'];

            if ($typeUpper === 'BIT') {
                $defaultValue = Util::convertBitDefaultValue($meta['DefaultValue']);
            } elseif ($typeUpper == 'BINARY' || $typeUpper == 'VARBINARY') {
                $defaultValue = bin2hex($meta['DefaultValue']);
            }
        }

        $charsets = Charsets::getCharsets($this->dbi, $this->disableIs);
        $collations = Charsets::getCollations($this->dbi, $this->disableIs);

        return $this->template->render('database/central_columns/edit_table_row', [
            'row_num' => $rowNum,
            'row' => $row,
            'max_rows' => $this->maxRows,
            'meta' => $meta,
            'default_value' => $defaultValue,
            'char_editing' => $this->charEditing,
            'charsets' => $charsets,
            'collations' => $collations,
            'attribute_types' => $this->dbi->types->getAttributes(),
        ]);
    }

    /**
     * get the list of columns in given database excluding
     * the columns present in current table
     *
     * @param string $db    selected database
     * @param string $table current table name
     *
     * @return mixed[] encoded list of columns present in central list for the given
     *               database
     */
    public function getListRaw(string $db, string $table): array
    {
        $cfgCentralColumns = $this->getParams();
        if (! is_array($cfgCentralColumns)) {
            return [];
        }

        $centralTable = $cfgCentralColumns['table'];
        if ($table === '') {
            $query = 'SELECT * FROM ' . Util::backquote($centralTable) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\';';
        } else {
            $this->dbi->selectDb($db);
            $columns = $this->dbi->getColumnNames($db, $table);
            $cols = '';
            foreach ($columns as $colSelect) {
                $cols .= '\'' . $this->dbi->escapeString($colSelect) . '\',';
            }

            $cols = trim($cols, ',');
            $query = 'SELECT * FROM ' . Util::backquote($centralTable) . ' '
                . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\'';
            if ($cols) {
                $query .= ' AND col_name NOT IN (' . $cols . ')';
            }

            $query .= ';';
        }

        $this->dbi->selectDb($cfgCentralColumns['db'], Connection::TYPE_CONTROL);
        $columnsList = $this->dbi->fetchResult($query, null, null, Connection::TYPE_CONTROL);
        $this->handleColumnExtra($columnsList);

        return $columnsList;
    }

    /**
     * Column `col_extra` is used to store both extra and attributes for a column.
     * This method separates them.
     *
     * @param mixed[] $columnsList columns list
     */
    private function handleColumnExtra(array &$columnsList): void
    {
        foreach ($columnsList as &$row) {
            $vals = explode(',', $row['col_extra']);

            if (in_array('BINARY', $vals)) {
                $row['col_attribute'] = 'BINARY';
            } elseif (in_array('UNSIGNED', $vals)) {
                $row['col_attribute'] = 'UNSIGNED';
            } elseif (in_array('UNSIGNED ZEROFILL', $vals)) {
                $row['col_attribute'] = 'UNSIGNED ZEROFILL';
            } elseif (in_array('on update CURRENT_TIMESTAMP', $vals)) {
                $row['col_attribute'] = 'on update CURRENT_TIMESTAMP';
            } else {
                $row['col_attribute'] = '';
            }

            if (in_array('auto_increment', $vals)) {
                $row['col_extra'] = 'auto_increment';
            } else {
                $row['col_extra'] = '';
            }
        }
    }

    /**
     * Get HTML for editing page central columns
     *
     * @param mixed[] $selectedFld Array containing the selected fields
     * @param string  $selectedDb  String containing the name of database
     *
     * @return string HTML for complete editing page for central columns
     */
    public function getHtmlForEditingPage(array $selectedFld, string $selectedDb): string
    {
        $html = '';
        $selectedFldSafe = [];
        foreach ($selectedFld as $key) {
            $selectedFldSafe[] = $this->dbi->escapeString($key);
        }

        $columnsList = implode("','", $selectedFldSafe);
        $columnsList = "'" . $columnsList . "'";
        $listDetailCols = $this->findExistingColNames($selectedDb, $columnsList, true);
        $rowNum = 0;
        foreach ($listDetailCols as $row) {
            $tableHtmlRow = $this->getHtmlForEditTableRow($row, $rowNum);
            $html .= $tableHtmlRow;
            $rowNum++;
        }

        return $html;
    }

    /**
     * get number of columns of given database from central columns list
     * starting at offset $from
     *
     * @param string $db   selected database
     * @param int    $from starting offset of first result
     * @param int    $num  maximum number of results to return
     *
     * @return int count of $num columns present in central columns list
     * starting at offset $from for the given database
     */
    public function getColumnsCount(string $db, int $from = 0, int $num = 25): int
    {
        $cfgCentralColumns = $this->getParams();
        if (! is_array($cfgCentralColumns)) {
            return 0;
        }

        $pmadb = $cfgCentralColumns['db'];
        $this->dbi->selectDb($pmadb, Connection::TYPE_CONTROL);
        $centralListTable = $cfgCentralColumns['table'];
        //get current values of $db from central column list
        $query = 'SELECT COUNT(db_name) FROM ' . Util::backquote($centralListTable) . ' '
            . 'WHERE db_name = \'' . $this->dbi->escapeString($db) . '\''
            . ($num === 0 ? '' : 'LIMIT ' . $from . ', ' . $num) . ';';
        $result = $this->dbi->fetchResult($query, null, null, Connection::TYPE_CONTROL);

        if (isset($result[0])) {
            return (int) $result[0];
        }

        return -1;
    }

    /** @return string[] */
    public function getColumnsNotInCentralList(string $db, string $table): array
    {
        $existingColumns = $this->getFromTable($db, $table);
        $this->dbi->selectDb($db);
        $columnNames = $this->dbi->getColumnNames($db, $table);

        // returns a list of column names less the ones from $existingColumns
        return array_values(array_diff($columnNames, $existingColumns));
    }

    /**
     * Adding a new user defined column to central list
     *
     * @param string $db        current database
     * @param int    $totalRows number of rows in central columns
     * @param int    $pos       offset of first result with complete result set
     * @param string $textDir   table footer arrow direction
     *
     * @return mixed[]
     */
    public function getTemplateVariablesForMain(
        string $db,
        int $totalRows,
        int $pos,
        string $textDir,
    ): array {
        $maxRows = $this->maxRows;
        $attributeTypes = $this->dbi->types->getAttributes();

        $tnPageNow = ($pos / $this->maxRows) + 1;
        $tnNbTotalPage = (int) ceil($totalRows / $this->maxRows);
        $tnPageSelector = $tnNbTotalPage > 1 ? Util::pageselector(
            'pos',
            $this->maxRows,
            $tnPageNow,
            $tnNbTotalPage,
        ) : '';
        $this->dbi->selectDb($db);
        $tables = $this->dbi->getTables($db);
        $rowsList = $this->getColumnsList($db, $pos, $maxRows);

        $defaultValues = [];
        $rowsMeta = [];
        $typesUpper = [];
        $rowNum = 0;
        foreach ($rowsList as $row) {
            $rowsMeta[$rowNum] = [];
            if (! isset($row['col_default']) || $row['col_default'] == '') {
                $rowsMeta[$rowNum]['DefaultType'] = 'NONE';
            } elseif ($row['col_default'] === 'CURRENT_TIMESTAMP' || $row['col_default'] === 'current_timestamp()') {
                $rowsMeta[$rowNum]['DefaultType'] = 'CURRENT_TIMESTAMP';
            } elseif ($row['col_default'] == 'NULL') {
                $rowsMeta[$rowNum]['DefaultType'] = $row['col_default'];
            } else {
                $rowsMeta[$rowNum]['DefaultType'] = 'USER_DEFINED';
                $rowsMeta[$rowNum]['DefaultValue'] = $row['col_default'];
            }

            $typesUpper[$rowNum] = mb_strtoupper((string) $row['col_type']);

            // For a TIMESTAMP, do not show the string "CURRENT_TIMESTAMP" as a default value
            $defaultValues[$rowNum] = '';
            if (isset($rowsMeta[$rowNum]['DefaultValue'])) {
                $defaultValues[$rowNum] = $rowsMeta[$rowNum]['DefaultValue'];

                if ($typesUpper[$rowNum] === 'BIT') {
                    $defaultValues[$rowNum] = Util::convertBitDefaultValue($rowsMeta[$rowNum]['DefaultValue']);
                } elseif ($typesUpper[$rowNum] === 'BINARY' || $typesUpper[$rowNum] === 'VARBINARY') {
                    $defaultValues[$rowNum] = bin2hex($rowsMeta[$rowNum]['DefaultValue']);
                }
            }

            $rowNum++;
        }

        $charsets = Charsets::getCharsets($this->dbi, $this->disableIs);
        $collations = Charsets::getCollations($this->dbi, $this->disableIs);
        $charsetsList = [];
        foreach ($charsets as $charset) {
            $collationsList = [];
            foreach ($collations[$charset->getName()] as $collation) {
                $collationsList[] = ['name' => $collation->getName(), 'description' => $collation->getDescription()];
            }

            $charsetsList[] = [
                'name' => $charset->getName(),
                'description' => $charset->getDescription(),
                'collations' => $collationsList,
            ];
        }

        return [
            'db' => $db,
            'total_rows' => $totalRows,
            'max_rows' => $maxRows,
            'pos' => $pos,
            'char_editing' => $this->charEditing,
            'attribute_types' => $attributeTypes,
            'tn_nbTotalPage' => $tnNbTotalPage,
            'tn_page_selector' => $tnPageSelector,
            'tables' => $tables,
            'rows_list' => $rowsList,
            'rows_meta' => $rowsMeta,
            'default_values' => $defaultValues,
            'types_upper' => $typesUpper,
            'text_dir' => $textDir,
            'charsets' => $charsetsList,
        ];
    }
}
