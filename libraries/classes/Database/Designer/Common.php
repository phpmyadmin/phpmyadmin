<?php
/**
 * Holds the PhpMyAdmin\Database\Designer\Common class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Database\Designer;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Database\Designer\DesignerTable;
use PhpMyAdmin\Database\Designer\DesignerForeignKey;
use PhpMyAdmin\Index;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Table;
use PhpMyAdmin\Util;
use function count;
use function explode;
use function in_array;
use function intval;
use function is_array;
use function is_string;
use function json_decode;
use function json_encode;
use function mb_strtoupper;

/**
 * Common functions for Designer
 */
class Common
{
    /** @var Relation */
    private $relation;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param DatabaseInterface $dbi      DatabaseInterface object
     * @param Relation          $relation Relation instance
     */
    public function __construct(DatabaseInterface $dbi, Relation $relation)
    {
        $this->dbi = $dbi;
        $this->relation = $relation;
    }

    /**
     * Retrieves table info and returns it
     *
     * @param string $db    (optional) Filter only a DB ($table is required if you use $db)
     * @param string $table (optional) Filter only a table ($db is now required)
     *
     * @return DesignerTable[] with table info
     */
    public function getTablesInfo(?string $db = null, ?string $table = null): array
    {
        $designerTables = [];
        $db = $db ?? $GLOBALS['db'];
        // seems to be needed later
        $this->dbi->selectDb($db);
        if ($table === null) {
            $tables = $this->dbi->getTablesFull($db);
        } else {
            $tables = $this->dbi->getTablesFull($db, $table);
        }

        foreach ($tables as $one_table) {
            $DF = $this->relation->getDisplayField($db, $one_table['TABLE_NAME']);
            $DF = is_string($DF) ? $DF : '';
            $DF = $DF !== '' ? $DF : null;
            $designerTables[] = new DesignerTable(
                $db,
                $one_table['TABLE_NAME'],
                is_string($one_table['ENGINE']) ? $one_table['ENGINE'] : '',
                $DF
            );
        }

        return $designerTables;
    }

    /**
     * Retrieves table column info
     *
     * @param DesignerTable[]   $designerTables       The designer tables
     * @param array<string,int> $tablesPkOrUniqueKeys The data to indicate a db.table.col is a pk or unique
     * @param array<string,int> $tablesAllKeys        The data to indicate a db.table.col is a key
     *
     * @return DesignerColumn[]
     */
    public function addColumnsInfo(array $designerTables, array $tablesPkOrUniqueKeys, array $tablesAllKeys): array
    {
        /** @var DesignerColumn[] $designerColumns */
        $designerColumns = [];

        foreach ($designerTables as $designerTable) {
            $fieldsRs = $this->dbi->query(
                QueryGenerator::getColumnsSql(
                    $designerTable->getDatabaseName(),
                    $designerTable->getTableName()
                ),
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            );
            while ($row = $GLOBALS['dbi']->fetchAssoc($fieldsRs)) {
                $colKey = $designerTable->getDatabaseName() . '.' . $designerTable->getTableName() . '.' . $row['Field'];
                $isPkOrUnique = false;
                if ($designerTable->supportsForeignkeys()) {
                    $isPkOrUnique = isset($tablesPkOrUniqueKeys[$colKey]);
                } else {
                    // if foreign keys are supported, it's not necessary that the index is a primary key
                    $isPkOrUnique = isset($tablesAllKeys[$colKey]);
                }
                $col = new DesignerColumn(
                    $designerTable->getDatabaseName(),
                    $designerTable->getTableName(),
                    $row['Field'],
                    $row['Type'],
                    $row['Null'] === 'YES',
                    $isPkOrUnique
                );
                $designerTable->addColumn($col);
                $designerColumns[] = $col;
            }
        }

        return $designerColumns;
    }

    /**
     * Returns a list of foreign keys
     *
     * @param DesignerTable[] $designerTables The designer tables
     * @return array
     */
    public function getScriptContr(array $designerTables): array
    {
        $this->dbi->selectDb($GLOBALS['db']);
        /** @var array<array[]> $databaseForeignTables */
        $databaseForeignTables = [];
        $alltab_rs = $GLOBALS['dbi']->query(
            'SHOW TABLES FROM ' . Util::backquote($GLOBALS['db']),
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        while ($val = @$GLOBALS['dbi']->fetchRow($alltab_rs)) {
            $designerTable = new DesignerTable(
                $GLOBALS['db'],
                $val[0],
                '', // No engine
                null // No display field
            );
            /** @var DesignerForeignKey[] $foreignTables */
            $foreignTables = [];

            $row = $this->relation->getForeigners(
                $designerTable->getDatabaseName(),
                $designerTable->getTableName(),
                '',
                'internal'
            );

            if ($row !== false) {
                foreach ($row as $field => $value) {
                    $foreignTables[] = new DesignerForeignKey(
                        $value['foreign_db'],
                        $value['foreign_table'],
                        $value['foreign_field'],
                        $field,
                        $designerTable->getDatabaseName(),
                        $designerTable->getTableName(),
                        $value['master_field']
                    );
                }
            }

            $row = $this->relation->getForeigners(
                $designerTable->getDatabaseName(),
                $designerTable->getTableName(),
                '',
                'foreign'
            );

            // We do not have access to the foreign keys if he user has partial access to the columns
            if ($row !== false && isset($row['foreign_keys_data'])) {
                foreach ($row['foreign_keys_data'] as $one_key) {
                    foreach ($one_key['index_list'] as $index => $one_field) {
                        $dbName = isset($one_key['ref_db_name']) ? $one_key['ref_db_name'] : $GLOBALS['db'];
                        $foreignTables[] = new DesignerForeignKey(
                            $dbName,
                            $one_key['ref_table_name'],
                            $one_key['ref_index_list'][$index],
                            $one_key['constraint'],
                            $designerTable->getDatabaseName(),
                            $designerTable->getTableName(),
                            $one_field
                        );
                    }
                }
            }
            $databaseForeignTables[] = [
                'table' => $designerTable,
                'foreignTables' => $foreignTables,
            ];
        }

        /** @var string[] $tableDbNames */
        $tableDbNames = [];
        foreach ($designerTables as $designerTable) {
            $tableDbNames[] = rawurlencode($designerTable->getDbTableString());
        }

        /** @var array<string,DesignerForeignKey>[] $scriptContr */
        $scriptContr = [];
        /** @var array $foreignKey */
        foreach ($databaseForeignTables as $foreignKey) {
            /** @var DesignerTable $originalTable */
            $originalTable = $foreignKey['table'];
            /** @var DesignerForeignKey[] $foreignTablesToAdd */
            $foreignTablesToAdd = [];
            /** @var DesignerForeignKey $foreignTable */
            foreach ($foreignKey['foreignTables'] as $foreignTable) {
                if (in_array($originalTable->getDbTableString(), $tableDbNames) &&
                    in_array($foreignTable->getDbTableString(), $tableDbNames)
                ) {
                    $foreignTablesToAdd[] = $foreignTable;
                }
            }
            if (count($foreignTablesToAdd) > 0) {
                $scriptContr[] = [
                    'table' => $originalTable,
                    'foreignKeys' => $foreignTablesToAdd,
                ];
            }
        }

        return $scriptContr;
    }

    /**
     * Returns UNIQUE and PRIMARY indices
     *
     * @param DesignerTable[] $designerTables The designer tables
     *
     * @return array<string,int> unique or primary indices
     */
    public function getPkOrUniqueKeys(array $designerTables): array
    {
        return $this->getAllKeys($designerTables, true);
    }

    /**
     * Returns all indices
     *
     * @param DesignerTable[] $designerTables The designer tables
     * @param bool            $unique_only    whether to include only unique ones
     *
     * @return array<string,int> indices
     */
    public function getAllKeys(array $designerTables, bool $unique_only = false): array
    {
        $keys = [];

        foreach ($designerTables as $designerTable) {
            $schema = $designerTable->getDatabaseName();
            // for now, take into account only the first index segment
            foreach (Index::getFromTable($designerTable->getTableName(), $schema) as $index) {
                if ($unique_only && ! $index->isUnique()) {
                    continue;
                }
                $columns = $index->getColumns();
                foreach ($columns as $column_name => $dummy) {
                    $keys[$schema . '.' . $designerTable->getTableName() . '.' . $column_name] = 1;
                }
            }
        }

        return $keys;
    }

    /**
     * Return j_tab and h_tab arrays
     *
     * @param DesignerTable[] $designerTables The designer tables
     *
     * @return array
     */
    public function getScriptTabs(array $designerTables): array
    {
        $retval = [
            'j_tabs' => [],
            'h_tabs' => [],
        ];

        foreach ($designerTables as $designerTable) {
            $key = $designerTable->getUniqueIdentifier();
            $retval['j_tabs'][$key] = $designerTable->supportsForeignkeys() ? 1 : 0;
            $retval['h_tabs'][$key] = 1;
        }

        return $retval;
    }

    /**
     * Returns table positions of a given pdf page
     *
     * @param int $pg pdf page id
     *
     * @return array<string,array> of table positions
     */
    public function getTablePositions($pg): array
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return [];
        }

        $query = '
            SELECT `db_name` as `dbName`, `table_name` as `tableName`,
                `x` AS `X`,
                `y` AS `Y`,
                1 AS `V`,
                1 AS `H`
            FROM ' . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['table_coords']) . '
            WHERE pdf_page_number = ' . intval($pg);

        $tabPositions = (array) $GLOBALS['dbi']->fetchResult(
            $query,
            null,
            null,
            DatabaseInterface::CONNECT_CONTROL,
            DatabaseInterface::QUERY_STORE
        );
        $tabPositionsOut = [];
        foreach ($tabPositions as $tab) {
            $dt = new DesignerTable(
                $tab['dbName'],
                $tab['tableName'],
                '',
                null
            );
            $tabPositionsOut[$dt->getUniqueIdentifier()] = $tab;
        }
        return $tabPositionsOut;
    }

    /**
     * Returns page name of a given pdf page
     *
     * @param int $pg pdf page id
     *
     * @return string|null table name
     */
    public function getPageName($pg)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return null;
        }

        $query = 'SELECT `page_descr`'
            . ' FROM ' . Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['pdf_pages'])
            . ' WHERE ' . Util::backquote('page_nr') . ' = ' . intval($pg);
        $page_name = $this->dbi->fetchResult(
            $query,
            null,
            null,
            DatabaseInterface::CONNECT_CONTROL,
            DatabaseInterface::QUERY_STORE
        );

        return is_array($page_name) && isset($page_name[0]) ? $page_name[0] : null;
    }

    /**
     * Deletes a given pdf page and its corresponding coordinates
     *
     * @param int $pg page id
     *
     * @return bool success/failure
     */
    public function deletePage($pg)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return false;
        }

        $query = 'DELETE FROM ' . Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['table_coords'])
            . ' WHERE ' . Util::backquote('pdf_page_number') . ' = ' . intval($pg);
        $success = $this->relation->queryAsControlUser(
            $query,
            true,
            DatabaseInterface::QUERY_STORE
        );

        if ($success) {
            $query = 'DELETE FROM ' . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['pdf_pages'])
                . ' WHERE ' . Util::backquote('page_nr') . ' = ' . intval($pg);
            $success = $this->relation->queryAsControlUser(
                $query,
                true,
                DatabaseInterface::QUERY_STORE
            );
        }

        return (bool) $success;
    }

    /**
     * Returns the id of the default pdf page of the database.
     * Default page is the one which has the same name as the database.
     *
     * @param string $db database
     *
     * @return int|null id of the default pdf page for the database
     */
    public function getDefaultPage($db): ?int
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return -1;
        }

        $query = 'SELECT `page_nr`'
            . ' FROM ' . Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['pdf_pages'])
            . " WHERE `db_name` = '" . $this->dbi->escapeString($db) . "'"
            . " AND `page_descr` = '" . $this->dbi->escapeString($db) . "'";

        $default_page_no = $this->dbi->fetchResult(
            $query,
            null,
            null,
            DatabaseInterface::CONNECT_CONTROL,
            DatabaseInterface::QUERY_STORE
        );

        if (is_array($default_page_no) && isset($default_page_no[0])) {
            return intval($default_page_no[0]);
        }

        return -1;
    }

    /**
     * Get the status if the page already exists
     * If no such exists, returns negative index.
     *
     * @param string $pg name
     *
     * @return bool if the page already exists
     */
    public function getPageExists(string $pg): bool
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return false;
        }

        $query = 'SELECT `page_nr`'
            . ' FROM ' . Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote($cfgRelation['pdf_pages'])
            . " WHERE `page_descr` = '" . $this->dbi->escapeString($pg) . "'";
        $pageNos = $this->dbi->fetchResult(
            $query,
            null,
            null,
            DatabaseInterface::CONNECT_CONTROL,
            DatabaseInterface::QUERY_STORE
        );

        return is_array($pageNos) && count($pageNos) > 0;
    }

    /**
     * Get the id of the page to load. If a default page exists it will be returned.
     * If no such exists, returns the id of the first page of the database.
     *
     * @param string $db database
     *
     * @return int id of the page to load
     */
    public function getLoadingPage($db)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return -1;
        }

        $page_no = -1;

        $default_page_no = $this->getDefaultPage($db);
        if ($default_page_no != -1) {
            $page_no = $default_page_no;
        } else {
            $query = 'SELECT MIN(`page_nr`)'
                . ' FROM ' . Util::backquote($cfgRelation['db'])
                . '.' . Util::backquote($cfgRelation['pdf_pages'])
                . " WHERE `db_name` = '" . $this->dbi->escapeString($db) . "'";

            $min_page_no = $this->dbi->fetchResult(
                $query,
                null,
                null,
                DatabaseInterface::CONNECT_CONTROL,
                DatabaseInterface::QUERY_STORE
            );
            if (is_array($min_page_no) && isset($min_page_no[0])) {
                $page_no = $min_page_no[0];
            }
        }

        return intval($page_no);
    }

    /**
     * Creates a new page and returns its auto-incrementing id
     *
     * @param string $pageName name of the page
     * @param string $db       name of the database
     *
     * @return int|null
     */
    public function createNewPage($pageName, $db)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if ($cfgRelation['pdfwork']) {
            return $this->relation->createPage(
                $pageName,
                $cfgRelation,
                $db
            );
        }

        return null;
    }

    /**
     * Saves positions of table(s) of a given pdf page
     *
     * @param int $pg pdf page id
     *
     * @return bool success/failure
     */
    public function saveTablePositions($pg)
    {
        $pageId = $this->dbi->escapeString($pg);

        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return false;
        }

        $query =  'DELETE FROM '
            . Util::backquote($cfgRelation['db'])
            . '.' . Util::backquote(
                $cfgRelation['table_coords']
            )
            . " WHERE `pdf_page_number` = '" . $pageId . "'";

        $res = $this->relation->queryAsControlUser(
            $query,
            true,
            DatabaseInterface::QUERY_STORE
        );

        if (! $res) {
            return (bool) $res;
        }

        foreach ($_POST['t_h'] as $key => $value) {
            $DB = $_POST['t_db'][$key];
            $TAB = $_POST['t_tbl'][$key];
            if (! $value) {
                continue;
            }

            $query = 'INSERT INTO '
                . Util::backquote($cfgRelation['db']) . '.'
                . Util::backquote($cfgRelation['table_coords'])
                . ' (`db_name`, `table_name`, `pdf_page_number`, `x`, `y`)'
                . ' VALUES ('
                . "'" . $this->dbi->escapeString($DB) . "', "
                . "'" . $this->dbi->escapeString($TAB) . "', "
                . "'" . $pageId . "', "
                . "'" . $this->dbi->escapeString($_POST['t_x'][$key]) . "', "
                . "'" . $this->dbi->escapeString($_POST['t_y'][$key]) . "')";

            $res = $this->relation->queryAsControlUser(
                $query,
                true,
                DatabaseInterface::QUERY_STORE
            );
        }

        return (bool) $res;
    }

    /**
     * Saves the display field for a table.
     *
     * @param string $db    database name
     * @param string $table table name
     * @param string $field display field name
     *
     * @return array<int, (string|bool|null)>
     */
    public function saveDisplayField($db, $table, $field): array
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['displaywork']) {
            return [
                false,
                _pgettext(
                    'phpMyAdmin configuration storage is not configured for'
                        . ' "Display Features" on designer when user tries to set a display field.',
                    'phpMyAdmin configuration storage is not configured for "Display Features".'
                ),
            ];
        }

        $upd_query = new Table($table, $db, $this->dbi);
        $upd_query->updateDisplayField($field, $cfgRelation);

        return [
            true,
            null,
        ];
    }

    /**
     * Adds a new foreign relation
     *
     * @param string $db        database name
     * @param string $T1        foreign table
     * @param string $F1        foreign field
     * @param string $T2        master table
     * @param string $F2        master field
     * @param string $on_delete on delete action
     * @param string $on_update on update action
     * @param string $DB1       database
     * @param string $DB2       database
     *
     * @return array<int,string|bool> array of success/failure and message
     */
    public function addNewRelation($db, $T1, $F1, $T2, $F2, $on_delete, $on_update, $DB1, $DB2): array
    {
        $tables = $this->dbi->getTablesFull($DB1, $T1);
        $type_T1 = mb_strtoupper($tables[$T1]['ENGINE'] ?? '');
        $tables = $this->dbi->getTablesFull($DB2, $T2);
        $type_T2 = mb_strtoupper($tables[$T2]['ENGINE'] ?? '');

        // native foreign key
        if (Util::isForeignKeySupported($type_T1)
            && Util::isForeignKeySupported($type_T2)
            && $type_T1 == $type_T2
        ) {
            // relation exists?
            $existrel_foreign = $this->relation->getForeigners($DB2, $T2, '', 'foreign');
            $foreigner = $this->relation->searchColumnInForeigners($existrel_foreign, $F2);
            if ($foreigner
                && isset($foreigner['constraint'])
            ) {
                return [
                    false,
                    __('Error: relationship already exists.'),
                ];
            }
            // note: in InnoDB, the index does not requires to be on a PRIMARY
            // or UNIQUE key
            // improve: check all other requirements for InnoDB relations
            $result = $this->dbi->query(
                'SHOW INDEX FROM ' . Util::backquote($DB1)
                . '.' . Util::backquote($T1) . ';'
            );

            // will be use to emphasis prim. keys in the table view
            $index_array1 = [];
            while ($row = $this->dbi->fetchAssoc($result)) {
                $index_array1[$row['Column_name']] = 1;
            }
            $this->dbi->freeResult($result);

            $result = $this->dbi->query(
                'SHOW INDEX FROM ' . Util::backquote($DB2)
                . '.' . Util::backquote($T2) . ';'
            );
            // will be used to emphasis prim. keys in the table view
            $index_array2 = [];
            while ($row = $this->dbi->fetchAssoc($result)) {
                $index_array2[$row['Column_name']] = 1;
            }
            $this->dbi->freeResult($result);

            if (! empty($index_array1[$F1]) && ! empty($index_array2[$F2])) {
                $upd_query  = 'ALTER TABLE ' . Util::backquote($DB2)
                    . '.' . Util::backquote($T2)
                    . ' ADD FOREIGN KEY ('
                    . Util::backquote($F2) . ')'
                    . ' REFERENCES '
                    . Util::backquote($DB1) . '.'
                    . Util::backquote($T1) . '('
                    . Util::backquote($F1) . ')';

                if ($on_delete !== 'nix') {
                    $upd_query   .= ' ON DELETE ' . $on_delete;
                }
                if ($on_update !== 'nix') {
                    $upd_query   .= ' ON UPDATE ' . $on_update;
                }
                $upd_query .= ';';
                if ($this->dbi->tryQuery($upd_query)) {
                    return [
                        true,
                        __('FOREIGN KEY relationship has been added.'),
                    ];
                }

                $error = $this->dbi->getError();

                return [
                    false,
                    __('Error: FOREIGN KEY relationship could not be added!')
                    . '<br>' . $error,
                ];
            }

            return [
                false,
                __('Error: Missing index on column(s).'),
            ];
        }

        // internal (pmadb) relation
        if ($GLOBALS['cfgRelation']['relwork'] == false) {
            return [
                false,
                __('Error: Relational features are disabled!'),
            ];
        }

        // no need to recheck if the keys are primary or unique at this point,
        // this was checked on the interface part

        $q  = 'INSERT INTO '
            . Util::backquote($GLOBALS['cfgRelation']['db'])
            . '.'
            . Util::backquote($GLOBALS['cfgRelation']['relation'])
            . '(master_db, master_table, master_field, '
            . 'foreign_db, foreign_table, foreign_field)'
            . ' values('
            . "'" . $this->dbi->escapeString($DB2) . "', "
            . "'" . $this->dbi->escapeString($T2) . "', "
            . "'" . $this->dbi->escapeString($F2) . "', "
            . "'" . $this->dbi->escapeString($DB1) . "', "
            . "'" . $this->dbi->escapeString($T1) . "', "
            . "'" . $this->dbi->escapeString($F1) . "')";

        if ($this->relation->queryAsControlUser($q, false, DatabaseInterface::QUERY_STORE)
        ) {
            return [
                true,
                __('Internal relationship has been added.'),
            ];
        }

        $error = $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);

        return [
            false,
            __('Error: Internal relationship could not be added!')
            . '<br>' . $error,
        ];
    }

    /**
     * Removes a foreign relation
     *
     * @param string $T1 foreign db.table
     * @param string $F1 foreign field
     * @param string $T2 master db.table
     * @param string $F2 master field
     *
     * @return array array of success/failure and message
     */
    public function removeRelation($T1, $F1, $T2, $F2)
    {
        [$DB1, $T1] = explode('.', $T1);
        [$DB2, $T2] = explode('.', $T2);

        $tables = $this->dbi->getTablesFull($DB1, $T1);
        $type_T1 = mb_strtoupper($tables[$T1]['ENGINE']);
        $tables = $this->dbi->getTablesFull($DB2, $T2);
        $type_T2 = mb_strtoupper($tables[$T2]['ENGINE']);

        if (Util::isForeignKeySupported($type_T1)
            && Util::isForeignKeySupported($type_T2)
            && $type_T1 == $type_T2
        ) {
            // InnoDB
            $existrel_foreign = $this->relation->getForeigners($DB2, $T2, '', 'foreign');
            $foreigner = $this->relation->searchColumnInForeigners($existrel_foreign, $F2);

            if (is_array($foreigner) && isset($foreigner['constraint'])) {
                $upd_query = 'ALTER TABLE ' . Util::backquote($DB2)
                    . '.' . Util::backquote($T2) . ' DROP FOREIGN KEY '
                    . Util::backquote($foreigner['constraint']) . ';';
                if ($this->dbi->query($upd_query)) {
                    return [
                        true,
                        __('FOREIGN KEY relationship has been removed.'),
                    ];
                }

                $error = $this->dbi->getError();

                return [
                    false,
                    __('Error: FOREIGN KEY relationship could not be removed!')
                    . '<br>' . $error,
                ];
            }
        }

        // internal relations
        $delete_query = 'DELETE FROM '
            . Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
            . $GLOBALS['cfgRelation']['relation'] . ' WHERE '
            . "master_db = '" . $this->dbi->escapeString($DB2) . "'"
            . " AND master_table = '" . $this->dbi->escapeString($T2) . "'"
            . " AND master_field = '" . $this->dbi->escapeString($F2) . "'"
            . " AND foreign_db = '" . $this->dbi->escapeString($DB1) . "'"
            . " AND foreign_table = '" . $this->dbi->escapeString($T1) . "'"
            . " AND foreign_field = '" . $this->dbi->escapeString($F1) . "'";

        $result = $this->relation->queryAsControlUser(
            $delete_query,
            false,
            DatabaseInterface::QUERY_STORE
        );

        if (! $result) {
            $error = $this->dbi->getError(DatabaseInterface::CONNECT_CONTROL);

            return [
                false,
                __('Error: Internal relationship could not be removed!') . '<br>' . $error,
            ];
        }

        return [
            true,
            __('Internal relationship has been removed.'),
        ];
    }

    /**
     * Save value for a designer setting
     *
     * @param string $index setting
     * @param string $value value
     *
     * @return bool whether the operation succeeded
     */
    public function saveSetting($index, $value)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        $success = true;
        if ($cfgRelation['designersettingswork']) {
            $cfgDesigner = [
                'user'  => $GLOBALS['cfg']['Server']['user'],
                'db'    => $cfgRelation['db'],
                'table' => $cfgRelation['designer_settings'],
            ];

            $orig_data_query = 'SELECT settings_data'
                . ' FROM ' . Util::backquote($cfgDesigner['db'])
                . '.' . Util::backquote($cfgDesigner['table'])
                . " WHERE username = '"
                . $this->dbi->escapeString($cfgDesigner['user']) . "';";

            $orig_data = $this->dbi->fetchSingleRow(
                $orig_data_query,
                'ASSOC',
                DatabaseInterface::CONNECT_CONTROL
            );

            if (! empty($orig_data)) {
                $orig_data = json_decode($orig_data['settings_data'], true);
                $orig_data[$index] = $value;
                $orig_data = json_encode($orig_data);

                $save_query = 'UPDATE '
                    . Util::backquote($cfgDesigner['db'])
                    . '.' . Util::backquote($cfgDesigner['table'])
                    . " SET settings_data = '" . $orig_data . "'"
                    . " WHERE username = '"
                    . $this->dbi->escapeString($cfgDesigner['user']) . "';";

                $success = $this->relation->queryAsControlUser($save_query);
            } else {
                $save_data = [$index => $value];

                $query = 'INSERT INTO '
                    . Util::backquote($cfgDesigner['db'])
                    . '.' . Util::backquote($cfgDesigner['table'])
                    . ' (username, settings_data)'
                    . " VALUES('" . $this->dbi->escapeString($cfgDesigner['user'])
                    . "', '" . json_encode($save_data) . "');";

                $success = $this->relation->queryAsControlUser($query);
            }
        }

        return (bool) $success;
    }
}
