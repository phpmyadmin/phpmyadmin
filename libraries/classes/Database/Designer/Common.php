<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Database\Designer\Common class
 *
 * @package PhpMyAdmin-Designer
 */
namespace PhpMyAdmin\Database\Designer;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Index;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Table;
use PhpMyAdmin\Util;

/**
 * Common functions for Designer
 *
 * @package PhpMyAdmin-Designer
 */
class Common
{
    /**
     * @var Relation $relation
     */
    private $relation;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->relation = new Relation();
    }

    /**
     * Retrieves table info and stores it in $GLOBALS['designer']
     *
     * @return array with table info
     */
    public function getTablesInfo()
    {
        $retval = array();

        $GLOBALS['designer']['TABLE_NAME'] = array();// that foreach no error
        $GLOBALS['designer']['OWNER'] = array();
        $GLOBALS['designer']['TABLE_NAME_SMALL'] = array();

        $tables = $GLOBALS['dbi']->getTablesFull($GLOBALS['db']);
        // seems to be needed later
        $GLOBALS['dbi']->selectDb($GLOBALS['db']);
        $i = 0;
        foreach ($tables as $one_table) {
            $GLOBALS['designer']['TABLE_NAME'][$i]
                = $GLOBALS['db'] . "." . $one_table['TABLE_NAME'];
            $GLOBALS['designer']['OWNER'][$i] = $GLOBALS['db'];
            $GLOBALS['designer']['TABLE_NAME_SMALL'][$i] = htmlspecialchars(
                $one_table['TABLE_NAME'], ENT_QUOTES
            );

            $GLOBALS['designer_url']['TABLE_NAME'][$i]
                = $GLOBALS['db'] . "." . $one_table['TABLE_NAME'];
            $GLOBALS['designer_url']['OWNER'][$i] = $GLOBALS['db'];
            $GLOBALS['designer_url']['TABLE_NAME_SMALL'][$i]
                = $one_table['TABLE_NAME'];

            $GLOBALS['designer_out']['TABLE_NAME'][$i] = htmlspecialchars(
                $GLOBALS['db'] . "." . $one_table['TABLE_NAME'], ENT_QUOTES
            );
            $GLOBALS['designer_out']['OWNER'][$i] = htmlspecialchars(
                $GLOBALS['db'], ENT_QUOTES
            );
            $GLOBALS['designer_out']['TABLE_NAME_SMALL'][$i] = htmlspecialchars(
                $one_table['TABLE_NAME'], ENT_QUOTES
            );

            $GLOBALS['designer']['TABLE_TYPE'][$i] = mb_strtoupper(
                $one_table['ENGINE']
            );

            $DF = $this->relation->getDisplayField($GLOBALS['db'], $one_table['TABLE_NAME']);
            if ($DF != '') {
                $retval[$GLOBALS['designer_url']["TABLE_NAME_SMALL"][$i]] = $DF;
            }

            $i++;
        }

        return $retval;
    }

    /**
     * Retrieves table column info
     *
     * @return array   table column nfo
     */
    public function getColumnsInfo()
    {
        $GLOBALS['dbi']->selectDb($GLOBALS['db']);
        $tab_column = array();
        for ($i = 0, $cnt = count($GLOBALS['designer']["TABLE_NAME"]); $i < $cnt; $i++) {
            $fields_rs = $GLOBALS['dbi']->query(
                $GLOBALS['dbi']->getColumnsSql(
                    $GLOBALS['db'],
                    $GLOBALS['designer_url']["TABLE_NAME_SMALL"][$i],
                    null,
                    true
                ),
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            );
            $tbl_name_i = $GLOBALS['designer']['TABLE_NAME'][$i];
            $j = 0;
            while ($row = $GLOBALS['dbi']->fetchAssoc($fields_rs)) {
                $tab_column[$tbl_name_i]['COLUMN_ID'][$j]   = $j;
                $tab_column[$tbl_name_i]['COLUMN_NAME'][$j] = $row['Field'];
                $tab_column[$tbl_name_i]['TYPE'][$j]        = $row['Type'];
                $tab_column[$tbl_name_i]['NULLABLE'][$j]    = $row['Null'];
                $j++;
            }
        }
        return $tab_column;
    }

    /**
     * Returns JavaScript code for initializing vars
     *
     * @return string   JavaScript code
     */
    public function getScriptContr()
    {
        $GLOBALS['dbi']->selectDb($GLOBALS['db']);
        $con = array();
        $con["C_NAME"] = array();
        $i = 0;
        $alltab_rs = $GLOBALS['dbi']->query(
            'SHOW TABLES FROM ' . Util::backquote($GLOBALS['db']),
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        while ($val = @$GLOBALS['dbi']->fetchRow($alltab_rs)) {
            $row = $this->relation->getForeigners($GLOBALS['db'], $val[0], '', 'internal');

            if ($row !== false) {
                foreach ($row as $field => $value) {
                    $con['C_NAME'][$i] = '';
                    $con['DTN'][$i]    = urlencode($GLOBALS['db'] . "." . $val[0]);
                    $con['DCN'][$i]    = urlencode($field);
                    $con['STN'][$i]    = urlencode(
                        $value['foreign_db'] . "." . $value['foreign_table']
                    );
                    $con['SCN'][$i]    = urlencode($value['foreign_field']);
                    $i++;
                }
            }
            $row = $this->relation->getForeigners($GLOBALS['db'], $val[0], '', 'foreign');

            if ($row !== false) {
                foreach ($row['foreign_keys_data'] as $one_key) {
                    foreach ($one_key['index_list'] as $index => $one_field) {
                        $con['C_NAME'][$i] = $one_key['constraint'];
                        $con['DTN'][$i]    = urlencode($GLOBALS['db'] . "." . $val[0]);
                        $con['DCN'][$i]    = urlencode($one_field);
                        $con['STN'][$i]    = urlencode(
                            (isset($one_key['ref_db_name']) ?
                                $one_key['ref_db_name'] : $GLOBALS['db'])
                            . "." . $one_key['ref_table_name']
                        );
                        $con['SCN'][$i] = urlencode($one_key['ref_index_list'][$index]);
                        $i++;
                    }
                }
            }
        }

        $ti = 0;
        $retval = array();
        for ($i = 0, $cnt = count($con["C_NAME"]); $i < $cnt; $i++) {
            $c_name_i = $con['C_NAME'][$i];
            $dtn_i = $con['DTN'][$i];
            $retval[$ti] = array();
            $retval[$ti][$c_name_i] = array();
            if (in_array($dtn_i, $GLOBALS['designer_url']["TABLE_NAME"])
                && in_array($con['STN'][$i], $GLOBALS['designer_url']["TABLE_NAME"])
            ) {
                $retval[$ti][$c_name_i][$dtn_i] = array();
                $retval[$ti][$c_name_i][$dtn_i][$con['DCN'][$i]] = array(
                    0 => $con['STN'][$i],
                    1 => $con['SCN'][$i]
                );
            }
            $ti++;
        }
        return $retval;
    }

    /**
     * Returns UNIQUE and PRIMARY indices
     *
     * @return array unique or primary indices
     */
    public function getPkOrUniqueKeys()
    {
        return $this->getAllKeys(true);
    }

    /**
     * Returns all indices
     *
     * @param bool $unique_only whether to include only unique ones
     *
     * @return array indices
     */
    public function getAllKeys($unique_only = false)
    {
        $keys = array();

        foreach ($GLOBALS['designer']['TABLE_NAME_SMALL'] as $I => $table) {
            $schema = $GLOBALS['designer']['OWNER'][$I];
            // for now, take into account only the first index segment
            foreach (Index::getFromTable($table, $schema) as $index) {
                if ($unique_only && ! $index->isUnique()) {
                    continue;
                }
                $columns = $index->getColumns();
                foreach ($columns as $column_name => $dummy) {
                    $keys[$schema . '.' . $table . '.' . $column_name] = 1;
                }
            }
        }
        return $keys;
    }

    /**
     * Return script to create j_tab and h_tab arrays
     *
     * @return string
     */
    public function getScriptTabs()
    {
        $retval = array(
            'j_tabs' => array(),
            'h_tabs' => array()
        );

        for ($i = 0, $cnt = count($GLOBALS['designer']['TABLE_NAME']); $i < $cnt; $i++) {
            $j = 0;
            if (Util::isForeignKeySupported($GLOBALS['designer']['TABLE_TYPE'][$i])) {
                $j = 1;
            }
            $retval['j_tabs'][$GLOBALS['designer_url']['TABLE_NAME'][$i]] = $j;
            $retval['h_tabs'][$GLOBALS['designer_url']['TABLE_NAME'][$i]] = 1;
        }
        return $retval;
    }

    /**
     * Returns table positions of a given pdf page
     *
     * @param int $pg pdf page id
     *
     * @return array of table positions
     */
    public function getTablePositions($pg)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return null;
        }

        $query = "
            SELECT CONCAT_WS('.', `db_name`, `table_name`) AS `name`,
                `x` AS `X`,
                `y` AS `Y`,
                1 AS `V`,
                1 AS `H`
            FROM " . Util::backquote($cfgRelation['db'])
                . "." . Util::backquote($cfgRelation['table_coords']) . "
            WHERE pdf_page_number = " . intval($pg);

        $tab_pos = $GLOBALS['dbi']->fetchResult(
            $query,
            'name',
            null,
            DatabaseInterface::CONNECT_CONTROL,
            DatabaseInterface::QUERY_STORE
        );
        return $tab_pos;
    }

    /**
     * Returns page name of a given pdf page
     *
     * @param int $pg pdf page id
     *
     * @return string table name
     */
    public function getPageName($pg)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return null;
        }

        $query = "SELECT `page_descr`"
            . " FROM " . Util::backquote($cfgRelation['db'])
            . "." . Util::backquote($cfgRelation['pdf_pages'])
            . " WHERE " . Util::backquote('page_nr') . " = " . intval($pg);
        $page_name = $GLOBALS['dbi']->fetchResult(
            $query,
            null,
            null,
            DatabaseInterface::CONNECT_CONTROL,
            DatabaseInterface::QUERY_STORE
        );
        return count($page_name) ? $page_name[0] : null;
    }

    /**
     * Deletes a given pdf page and its corresponding coordinates
     *
     * @param int $pg page id
     *
     * @return boolean success/failure
     */
    public function deletePage($pg)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return false;
        }

        $query = "DELETE FROM " . Util::backquote($cfgRelation['db'])
            . "." . Util::backquote($cfgRelation['table_coords'])
            . " WHERE " . Util::backquote('pdf_page_number') . " = " . intval($pg);
        $success = $this->relation->queryAsControlUser(
            $query, true, DatabaseInterface::QUERY_STORE
        );

        if ($success) {
            $query = "DELETE FROM " . Util::backquote($cfgRelation['db'])
                . "." . Util::backquote($cfgRelation['pdf_pages'])
                . " WHERE " . Util::backquote('page_nr') . " = " . intval($pg);
            $success = $this->relation->queryAsControlUser(
                $query, true, DatabaseInterface::QUERY_STORE
            );
        }

        return (boolean) $success;
    }

    /**
     * Returns the id of the default pdf page of the database.
     * Default page is the one which has the same name as the database.
     *
     * @param string $db database
     *
     * @return int id of the default pdf page for the database
     */
    public function getDefaultPage($db)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return null;
        }

        $query = "SELECT `page_nr`"
            . " FROM " . Util::backquote($cfgRelation['db'])
            . "." . Util::backquote($cfgRelation['pdf_pages'])
            . " WHERE `db_name` = '" . $GLOBALS['dbi']->escapeString($db) . "'"
            . " AND `page_descr` = '" .  $GLOBALS['dbi']->escapeString($db) . "'";

        $default_page_no = $GLOBALS['dbi']->fetchResult(
            $query,
            null,
            null,
            DatabaseInterface::CONNECT_CONTROL,
            DatabaseInterface::QUERY_STORE
        );

        if (isset($default_page_no) && count($default_page_no)) {
            return intval($default_page_no[0]);
        }
        return -1;
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
            return null;
        }

        $page_no = -1;

        $default_page_no = $this->getDefaultPage($db);
        if ($default_page_no != -1) {
            $page_no = $default_page_no;
        } else {
            $query = "SELECT MIN(`page_nr`)"
                . " FROM " . Util::backquote($cfgRelation['db'])
                . "." . Util::backquote($cfgRelation['pdf_pages'])
                . " WHERE `db_name` = '" . $GLOBALS['dbi']->escapeString($db) . "'";

            $min_page_no = $GLOBALS['dbi']->fetchResult(
                $query,
                null,
                null,
                DatabaseInterface::CONNECT_CONTROL,
                DatabaseInterface::QUERY_STORE
            );
            if (isset($min_page_no[0]) && count($min_page_no[0])) {
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
            $pageNumber = $this->relation->createPage(
                $pageName,
                $cfgRelation,
                $db
            );
            return $pageNumber;
        }
        return null;
    }

    /**
     * Saves positions of table(s) of a given pdf page
     *
     * @param int $pg pdf page id
     *
     * @return boolean success/failure
     */
    public function saveTablePositions($pg)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['pdfwork']) {
            return false;
        }

        $query =  "DELETE FROM "
            . Util::backquote($GLOBALS['cfgRelation']['db'])
            . "." . Util::backquote(
                $GLOBALS['cfgRelation']['table_coords']
            )
            . " WHERE `db_name` = '" . $GLOBALS['dbi']->escapeString($_REQUEST['db'])
            . "'"
            . " AND `pdf_page_number` = '" . $GLOBALS['dbi']->escapeString($pg)
            . "'";

        $res = $this->relation->queryAsControlUser(
            $query,
            true,
            DatabaseInterface::QUERY_STORE
        );

        if (!$res) {
            return (boolean)$res;
        }

        foreach ($_REQUEST['t_h'] as $key => $value) {
            list($DB, $TAB) = explode(".", $key);
            if (!$value) {
                continue;
            }

            $query = "INSERT INTO "
                . Util::backquote($GLOBALS['cfgRelation']['db']) . "."
                . Util::backquote($GLOBALS['cfgRelation']['table_coords'])
                . " (`db_name`, `table_name`, `pdf_page_number`, `x`, `y`)"
                . " VALUES ("
                . "'" . $GLOBALS['dbi']->escapeString($DB) . "', "
                . "'" . $GLOBALS['dbi']->escapeString($TAB) . "', "
                . "'" . $GLOBALS['dbi']->escapeString($pg) . "', "
                . "'" . $GLOBALS['dbi']->escapeString($_REQUEST['t_x'][$key]) . "', "
                . "'" . $GLOBALS['dbi']->escapeString($_REQUEST['t_y'][$key]) . "')";

            $res = $this->relation->queryAsControlUser(
                $query,  true, DatabaseInterface::QUERY_STORE
            );
        }

        return (boolean) $res;
    }

    /**
     * Saves the display field for a table.
     *
     * @param string $db    database name
     * @param string $table table name
     * @param string $field display field name
     *
     * @return boolean
     */
    public function saveDisplayField($db, $table, $field)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (!$cfgRelation['displaywork']) {
            return false;
        }

        $upd_query = new Table($table, $db, $GLOBALS['dbi']);
        $upd_query->updateDisplayField($field, $cfgRelation);

        return true;
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
     * @return array array of success/failure and message
     */
    public function addNewRelation($db, $T1, $F1, $T2, $F2, $on_delete, $on_update, $DB1, $DB2)
    {
        $tables = $GLOBALS['dbi']->getTablesFull($DB1, $T1);
        $type_T1 = mb_strtoupper($tables[$T1]['ENGINE']);
        $tables = $GLOBALS['dbi']->getTablesFull($DB2, $T2);
        $type_T2 = mb_strtoupper($tables[$T2]['ENGINE']);

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
                return array(false, __('Error: relationship already exists.'));
            }
            // note: in InnoDB, the index does not requires to be on a PRIMARY
            // or UNIQUE key
            // improve: check all other requirements for InnoDB relations
            $result = $GLOBALS['dbi']->query(
                'SHOW INDEX FROM ' . Util::backquote($DB1)
                . '.' . Util::backquote($T1) . ';'
            );

            // will be use to emphasis prim. keys in the table view
            $index_array1 = array();
            while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
                $index_array1[$row['Column_name']] = 1;
            }
            $GLOBALS['dbi']->freeResult($result);

            $result = $GLOBALS['dbi']->query(
                'SHOW INDEX FROM ' . Util::backquote($DB2)
                . '.' . Util::backquote($T2) . ';'
            );
            // will be used to emphasis prim. keys in the table view
            $index_array2 = array();
            while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
                $index_array2[$row['Column_name']] = 1;
            }
            $GLOBALS['dbi']->freeResult($result);

            if (! empty($index_array1[$F1]) && ! empty($index_array2[$F2])) {
                $upd_query  = 'ALTER TABLE ' . Util::backquote($DB2)
                    . '.' . Util::backquote($T2)
                    . ' ADD FOREIGN KEY ('
                    . Util::backquote($F2) . ')'
                    . ' REFERENCES '
                    . Util::backquote($DB1) . '.'
                    . Util::backquote($T1) . '('
                    . Util::backquote($F1) . ')';

                if ($on_delete != 'nix') {
                    $upd_query   .= ' ON DELETE ' . $on_delete;
                }
                if ($on_update != 'nix') {
                    $upd_query   .= ' ON UPDATE ' . $on_update;
                }
                $upd_query .= ';';
                if ($GLOBALS['dbi']->tryQuery($upd_query)) {
                    return array(true, __('FOREIGN KEY relationship has been added.'));
                }

                $error = $GLOBALS['dbi']->getError();
                return array(
                    false,
                    __('Error: FOREIGN KEY relationship could not be added!')
                    . "<br/>" . $error
                );
            }

            return array(false, __('Error: Missing index on column(s).'));
        }

        // internal (pmadb) relation
        if ($GLOBALS['cfgRelation']['relwork'] == false) {
            return array(false, __('Error: Relational features are disabled!'));
        }

        // no need to recheck if the keys are primary or unique at this point,
        // this was checked on the interface part

        $q  = "INSERT INTO "
            . Util::backquote($GLOBALS['cfgRelation']['db'])
            . "."
            . Util::backquote($GLOBALS['cfgRelation']['relation'])
            . "(master_db, master_table, master_field, "
            . "foreign_db, foreign_table, foreign_field)"
            . " values("
            . "'" . $GLOBALS['dbi']->escapeString($DB2) . "', "
            . "'" . $GLOBALS['dbi']->escapeString($T2) . "', "
            . "'" . $GLOBALS['dbi']->escapeString($F2) . "', "
            . "'" . $GLOBALS['dbi']->escapeString($DB1) . "', "
            . "'" . $GLOBALS['dbi']->escapeString($T1) . "', "
            . "'" . $GLOBALS['dbi']->escapeString($F1) . "')";

        if ($this->relation->queryAsControlUser($q, false, DatabaseInterface::QUERY_STORE)
        ) {
            return array(true, __('Internal relationship has been added.'));
        }

        $error = $GLOBALS['dbi']->getError(DatabaseInterface::CONNECT_CONTROL);
        return array(
            false,
            __('Error: Internal relationship could not be added!')
            . "<br/>" . $error
        );
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
        list($DB1, $T1) = explode(".", $T1);
        list($DB2, $T2) = explode(".", $T2);

        $tables = $GLOBALS['dbi']->getTablesFull($DB1, $T1);
        $type_T1 = mb_strtoupper($tables[$T1]['ENGINE']);
        $tables = $GLOBALS['dbi']->getTablesFull($DB2, $T2);
        $type_T2 = mb_strtoupper($tables[$T2]['ENGINE']);

        if (Util::isForeignKeySupported($type_T1)
            && Util::isForeignKeySupported($type_T2)
            && $type_T1 == $type_T2
        ) {
            // InnoDB
            $existrel_foreign = $this->relation->getForeigners($DB2, $T2, '', 'foreign');
            $foreigner = $this->relation->searchColumnInForeigners($existrel_foreign, $F2);

            if (isset($foreigner['constraint'])) {
                $upd_query = 'ALTER TABLE ' . Util::backquote($DB2)
                    . '.' . Util::backquote($T2) . ' DROP FOREIGN KEY '
                    . Util::backquote($foreigner['constraint']) . ';';
                if ($GLOBALS['dbi']->query($upd_query)) {
                    return array(true, __('FOREIGN KEY relationship has been removed.'));
                }

                $error = $GLOBALS['dbi']->getError();
                return array(
                    false,
                    __('Error: FOREIGN KEY relationship could not be removed!')
                    . "<br/>" . $error
                );
            }
        }

        // internal relations
        $delete_query = "DELETE FROM "
            . Util::backquote($GLOBALS['cfgRelation']['db']) . "."
            . $GLOBALS['cfgRelation']['relation'] . " WHERE "
            . "master_db = '" . $GLOBALS['dbi']->escapeString($DB2) . "'"
            . " AND master_table = '" . $GLOBALS['dbi']->escapeString($T2) . "'"
            . " AND master_field = '" . $GLOBALS['dbi']->escapeString($F2) . "'"
            . " AND foreign_db = '" . $GLOBALS['dbi']->escapeString($DB1) . "'"
            . " AND foreign_table = '" . $GLOBALS['dbi']->escapeString($T1) . "'"
            . " AND foreign_field = '" . $GLOBALS['dbi']->escapeString($F1) . "'";

        $result = $this->relation->queryAsControlUser(
            $delete_query,
            false,
            DatabaseInterface::QUERY_STORE
        );

        if (!$result) {
            $error = $GLOBALS['dbi']->getError(DatabaseInterface::CONNECT_CONTROL);
            return array(
                false,
                __('Error: Internal relationship could not be removed!') . "<br/>" . $error
            );
        }

        return array(true, __('Internal relationship has been removed.'));
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
        $cfgDesigner = array(
            'user'  => $GLOBALS['cfg']['Server']['user'],
            'db'    => $cfgRelation['db'],
            'table' => $cfgRelation['designer_settings']
        );

        $success = true;
        if ($GLOBALS['cfgRelation']['designersettingswork']) {

            $orig_data_query = "SELECT settings_data"
                . " FROM " . Util::backquote($cfgDesigner['db'])
                . "." . Util::backquote($cfgDesigner['table'])
                . " WHERE username = '"
                . $GLOBALS['dbi']->escapeString($cfgDesigner['user']) . "';";

            $orig_data = $GLOBALS['dbi']->fetchSingleRow(
                $orig_data_query, 'ASSOC', DatabaseInterface::CONNECT_CONTROL
            );

            if (! empty($orig_data)) {
                $orig_data = json_decode($orig_data['settings_data'], true);
                $orig_data[$index] = $value;
                $orig_data = json_encode($orig_data);

                $save_query = "UPDATE "
                    . Util::backquote($cfgDesigner['db'])
                    . "." . Util::backquote($cfgDesigner['table'])
                    . " SET settings_data = '" . $orig_data . "'"
                    . " WHERE username = '"
                    . $GLOBALS['dbi']->escapeString($cfgDesigner['user']) . "';";

                $success = $this->relation->queryAsControlUser($save_query);
            } else {
                $save_data = array($index => $value);

                $query = "INSERT INTO "
                    . Util::backquote($cfgDesigner['db'])
                    . "." . Util::backquote($cfgDesigner['table'])
                    . " (username, settings_data)"
                    . " VALUES('" . $cfgDesigner['user'] . "',"
                    . " '" . json_encode($save_data) . "');";

                $success = $this->relation->queryAsControlUser($query);
            }
        }

        return (bool) $success;
    }
}
