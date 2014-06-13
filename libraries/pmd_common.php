<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common functions for Designer
 *
 * @package PhpMyAdmin-Designer
 */
/**
 * Block attempts to directly run this script
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$GLOBALS['PMD']['STYLE']          = 'default';

$cfgRelation = PMA_getRelationsParam();

/**
 * Retrieves table info and stores it in $GLOBALS['PMD']
 *
 * @return array with table info
 */
function PMA_getTablesInfo()
{
    $retval = array();

    $GLOBALS['PMD']['TABLE_NAME'] = array();// that foreach no error
    $GLOBALS['PMD']['OWNER'] = array();
    $GLOBALS['PMD']['TABLE_NAME_SMALL'] = array();

    $tables = $GLOBALS['dbi']->getTablesFull($GLOBALS['db']);
    // seems to be needed later
    $GLOBALS['dbi']->selectDb($GLOBALS['db']);
    $i = 0;
    foreach ($tables as $one_table) {
        $GLOBALS['PMD']['TABLE_NAME'][$i]
            = $GLOBALS['db'] . "." . $one_table['TABLE_NAME'];
        $GLOBALS['PMD']['OWNER'][$i] = $GLOBALS['db'];
        $GLOBALS['PMD']['TABLE_NAME_SMALL'][$i] = $one_table['TABLE_NAME'];

        $GLOBALS['PMD_URL']['TABLE_NAME'][$i]
            = urlencode($GLOBALS['db'] . "." . $one_table['TABLE_NAME']);
        $GLOBALS['PMD_URL']['OWNER'][$i] = urlencode($GLOBALS['db']);
        $GLOBALS['PMD_URL']['TABLE_NAME_SMALL'][$i]
            = urlencode($one_table['TABLE_NAME']);

        $GLOBALS['PMD_OUT']['TABLE_NAME'][$i] = htmlspecialchars(
            $GLOBALS['db'] . "." . $one_table['TABLE_NAME'], ENT_QUOTES
        );
        $GLOBALS['PMD_OUT']['OWNER'][$i] = htmlspecialchars(
            $GLOBALS['db'], ENT_QUOTES
        );
        $GLOBALS['PMD_OUT']['TABLE_NAME_SMALL'][$i] = htmlspecialchars(
            $one_table['TABLE_NAME'], ENT_QUOTES
        );

        $GLOBALS['PMD']['TABLE_TYPE'][$i] = strtoupper($one_table['ENGINE']);

        $DF = PMA_getDisplayField($GLOBALS['db'], $one_table['TABLE_NAME']);
        if ($DF != '') {
            $retval[$GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i]] = urlencode($DF);
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
function PMA_getColumnsInfo()
{
    $GLOBALS['dbi']->selectDb($GLOBALS['db']);
    $tab_column = array();
    for ($i = 0, $cnt = count($GLOBALS['PMD']["TABLE_NAME"]); $i < $cnt; $i++) {
        $fields_rs = $GLOBALS['dbi']->query(
            $GLOBALS['dbi']->getColumnsSql(
                $GLOBALS['db'],
                $GLOBALS['PMD']["TABLE_NAME_SMALL"][$i],
                null,
                true
            ),
            null,
            PMA_DatabaseInterface::QUERY_STORE
        );
        $tbl_name_i = $GLOBALS['PMD']['TABLE_NAME'][$i];
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
 * @param int $pg page number
 *
 * @return string   JavaScript code
 */
function PMA_getScriptContr($pg)
{
    $GLOBALS['dbi']->selectDb($GLOBALS['db']);
    $con = array();
    $con["C_NAME"] = array();
    $i = 0;
    $alltab_rs = $GLOBALS['dbi']->query(
        'SHOW TABLES FROM ' . PMA_Util::backquote($GLOBALS['db']),
        null,
        PMA_DatabaseInterface::QUERY_STORE
    );
    while ($val = @$GLOBALS['dbi']->fetchRow($alltab_rs)) {
        $row = PMA_getForeigners($GLOBALS['db'], $val[0], '', 'internal');
        //echo "<br> internal ".$GLOBALS['db']." - ".$val[0]." - ";
        //print_r($row);
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
        $row = PMA_getForeigners($GLOBALS['db'], $val[0], '', 'foreign');
        //echo "<br> INNO ";
        //print_r($row);
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
    }

    $ti = 0;
    $retval = array();
    for ($i = 0, $cnt = count($con["C_NAME"]); $i < $cnt; $i++) {
        $c_name_i = $con['C_NAME'][$i];
        $dtn_i = $con['DTN'][$i];
        $retval[$ti] = array();
        $retval[$ti][$c_name_i] = array();
        $tb = getTables($pg);
        if ($pg == -1 || in_array($dtn_i, $tb)) {
            if (in_array($dtn_i, $GLOBALS['PMD_URL']["TABLE_NAME"])
                && in_array($con['STN'][$i], $GLOBALS['PMD_URL']["TABLE_NAME"])
            ) {
                $retval[$ti][$c_name_i][$dtn_i] = array();
                $retval[$ti][$c_name_i][$dtn_i][$con['DCN'][$i]] = array(
                    0 => $con['STN'][$i],
                    1 => $con['SCN'][$i]
                );
            }
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
function PMA_getPKOrUniqueKeys()
{
    return PMA_getAllKeys(true);
}

/**
 * Returns all indices
 *
 * @param bool $unique_only whether to include only unique ones
 *
 * @return array indices
 */
function PMA_getAllKeys($unique_only = false)
{
    include_once './libraries/Index.class.php';

    $keys = array();

    foreach ($GLOBALS['PMD']['TABLE_NAME_SMALL'] as $I => $table) {
        $schema = $GLOBALS['PMD']['OWNER'][$I];
        // for now, take into account only the first index segment
        foreach (PMA_Index::getFromTable($table, $schema) as $index) {
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
function PMA_getScriptTabs()
{
    $retval = array(
        'j_tabs' => array(),
        'h_tabs' => array()
    );

    for ($i = 0, $cnt = count($GLOBALS['PMD']['TABLE_NAME']); $i < $cnt; $i++) {
        $j = 0;
        if (PMA_Util::isForeignKeySupported($GLOBALS['PMD']['TABLE_TYPE'][$i])) {
            $j = 1;
        }
        $retval['j_tabs'][$GLOBALS['PMD_URL']['TABLE_NAME'][$i]] = $j;
        $retval['h_tabs'][$GLOBALS['PMD_URL']['TABLE_NAME'][$i]] = 1;
    }
    return $retval;
}

/**
 * Returns table position
 *
 * @return array table positions and sizes
 */
function PMA_getTabPos()
{
    $cfgRelation = PMA_getRelationsParam();
    if (! $cfgRelation['designerwork']) {
        return null;
    }

    $query = "
         SELECT CONCAT_WS('.', `B`.`db_name`, `table_name`) AS `name`,
                `x` AS `X`,
                `y` AS `Y`,
                `v` AS `V`,
                `h` AS `H`
        FROM " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['designer_coords']) . " AS A
        JOIN " . PMA_Util::backquote($cfgRelation['db'])
            . "." . PMA_Util::backquote($cfgRelation['pdf_pages']) . " AS B
        ON `A`.`db_name` = `page_nr`";
    $tab_pos = $GLOBALS['dbi']->fetchResult(
        $query,
        'name',
        null,
        $GLOBALS['controllink'],
        PMA_DatabaseInterface::QUERY_STORE
    );
    return count($tab_pos) ? $tab_pos : null;
}

/**
 * Returns table positions of a given pdf page
 *
 * @param int $pg pdf page id
 *
 * @return array of table positions
 */
function PMA_getTablePositions($pg)
{
    $cfgRelation = PMA_getRelationsParam();
    if (! $cfgRelation['designerwork']) {
        return null;
    }

    $query = "
         SELECT CONCAT_WS('.', `B`.`db_name`, `table_name`) AS `name`,
                `x` AS `X`,
                `y` AS `Y`,
                `v` AS `V`,
                `h` AS `H`
           FROM " . PMA_Util::backquote($cfgRelation['db'])
               . "." . PMA_Util::backquote($cfgRelation['designer_coords']) . " AS A
           JOIN " . PMA_Util::backquote($cfgRelation['db'])
               . "." . PMA_Util::backquote($cfgRelation['pdf_pages']) . " AS B
           ON `A`.`db_name` = `page_nr`
           WHERE `A`.`db_name` = " . PMA_Util::sqlAddSlashes($pg);

    $tab_pos = $GLOBALS['dbi']->fetchResult(
        $query,
        'name',
        null,
        $GLOBALS['controllink'],
        PMA_DatabaseInterface::QUERY_STORE
    );
    return count($tab_pos) ? $tab_pos : null;
}

/**
 * Returns page name of a given pdf page
 *
 * @param int $pg pdf page id
 *
 * @return String table name
 */
function PMA_getPageName($pg)
{
    $cfgRelation = PMA_getRelationsParam();
    if (! $cfgRelation['designerwork']) {
        return null;
    }

    $query = "SELECT `page_descr`"
           . " FROM " . PMA_Util::backquote($cfgRelation['db'])
           . "." . PMA_Util::backquote($cfgRelation['pdf_pages'])
           . " WHERE `page_nr` = " . PMA_Util::sqlAddSlashes($pg);
    $page_name = $GLOBALS['dbi']->fetchResult($query);
    return count($page_name) ? $page_name[0] : __("*Untitled");
}

/**
 * Deletes a given pdf page and its corresponding coordinates
 *
 * @param int $pg page id
 *
 * @return boolean success/failure
 */
function PMA_deletePage($pg)
{
    $cfgRelation = PMA_getRelationsParam();
    if (! $cfgRelation['designerwork']) {
        return null;
    }

    $query = " DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
             . "." . PMA_Util::backquote($cfgRelation['designer_coords'])
             . " WHERE `db_name` = " . PMA_Util::sqlAddSlashes($pg);
    $success = PMA_queryAsControlUser(
        $query, true, PMA_DatabaseInterface::QUERY_STORE
    );

    if ($success) {
        $query = "DELETE FROM " . PMA_Util::backquote($cfgRelation['db'])
                 . "." . PMA_Util::backquote($cfgRelation['pdf_pages'])
                 . " WHERE `page_nr` = " . PMA_Util::sqlAddSlashes($pg);
        $success = PMA_queryAsControlUser(
            $query, true, PMA_DatabaseInterface::QUERY_STORE
        );
    }

    return $success;
}

/**
 * Returns the id of the first pdf page of the database
 *
 * @param string $db database
 *
 * @return int id of the first pdf page, default is -1
 */
function getFirstPage($db)
{
    $cfgRelation = PMA_getRelationsParam();
    if (! $cfgRelation['designerwork']) {
        return null;
    }

    $query = "SELECT MIN(`page_nr`)"
        . " FROM " . PMA_Util::backquote($cfgRelation['db'])
        . "." . PMA_Util::backquote($cfgRelation['pdf_pages']) . " AS A"
        . " JOIN " . PMA_Util::backquote($cfgRelation['db'])
        . "." . PMA_Util::backquote($cfgRelation['designer_coords']) . " AS B"
        . " ON `A`.`page_nr` = `B`.`db_name`"
        . " WHERE `A`.`db_name` = '" . PMA_Util::sqlAddSlashes($db) . "'";

    $min_page_no = $GLOBALS['dbi']->fetchResult($query);
    return count($min_page_no[0]) ? $min_page_no[0] : -1;
}

/**
 * Creates a new page and returns its auto-incrementing id
 *
 * @param string $pageName name of the page
 *
 * @return int|null
 */
function createNewPage($pageName)
{
    $cfgRelation = PMA_getRelationsParam();
    if ($cfgRelation['designerwork']) {
        $_POST['newpage'] = $pageName;
        // temporarlily using schema code for creating a page
        include_once 'libraries/schema/User_Schema.class.php';
        $user_schema = new PMA_User_Schema();
        $user_schema->setAction("createpage");
        $user_schema->processUserChoice();
        return $user_schema->pageNumber;
    }
    return null;
}

/**
 * Returns all tables of a given pdf page
 *
 * @param int $pg pdf page id
 *
 * @return array of tables
 */
function getTables($pg)
{
    $cfgRelation = PMA_getRelationsParam();
    if (! $cfgRelation['designerwork']) {
        return null;
    }

    $query = "SELECT `table_name`"
         . " FROM " . PMA_Util::backquote($cfgRelation['db'])
         . "." . PMA_Util::backquote($cfgRelation['designer_coords'])
         . " WHERE `db_name` = " . PMA_Util::sqlAddSlashes($pg);

    $tables = $GLOBALS['dbi']->fetchResult($query);
    $return_array = array();
    foreach ($tables as $temp ) {
        array_push($return_array, $GLOBALS['db'] . "." . $temp);
    }
    return count($return_array) ? $return_array : null;
}

/**
 * Saves positions of table(s) of a given pdf page
 *
 * @param int $pg pdf page id
 *
 * @return boolean success/failure
 */
function saveTablePositions($pg)
{
    $cfgRelation = PMA_getRelationsParam();
    if (! $cfgRelation['designerwork']) {
        return null;
    }

    foreach ($_REQUEST['t_x'] as $key => $value) {
        // table name decode (post PDF exp/imp)
        $KEY = empty($_REQUEST['IS_AJAX']) ? urldecode($key) : $key;
        list($DB, $TAB) = explode(".", $KEY);
        $res = PMA_queryAsControlUser(
            'DELETE FROM ' . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
            . '.' . PMA_Util::backquote($GLOBALS['cfgRelation']['designer_coords'])
            . ' WHERE `db_name` = \'' . PMA_Util::sqlAddSlashes($pg) . '\''
            . ' AND `table_name` = \'' . PMA_Util::sqlAddSlashes($TAB) . '\'',
            true, PMA_DatabaseInterface::QUERY_STORE
        );
        if (! $res) {
            return $res;
        }

        PMA_queryAsControlUser(
            'INSERT INTO ' . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
            . '.' . PMA_Util::backquote($GLOBALS['cfgRelation']['designer_coords'])
            . ' (db_name, table_name, x, y, v, h)'
            . ' VALUES ('
            . '\'' . PMA_Util::sqlAddSlashes($pg) . '\', '
            . '\'' . PMA_Util::sqlAddSlashes($TAB) . '\', '
            . '\'' . PMA_Util::sqlAddSlashes($_REQUEST['t_x'][$key]) . '\', '
            . '\'' . PMA_Util::sqlAddSlashes($_REQUEST['t_y'][$key]) . '\', '
            . '\'' . PMA_Util::sqlAddSlashes($_REQUEST['t_v'][$key]) . '\', '
            . '\'' . PMA_Util::sqlAddSlashes($_REQUEST['t_h'][$key]) . '\')',
            true, PMA_DatabaseInterface::QUERY_STORE
        );
        if (! $res) {
            return $res;
        }
    }

    return true;
}


/**
 * Prepares XML output for js/pmd/ajax.js to display a message
 *
 * @param string $b   b attribute value
 * @param string $ret Return attribute value
 *
 * @return void
 */
function PMA_returnUpd($b, $ret)
{
    // not sure where this was defined...
    global $K;

    header("Content-Type: text/xml; charset=utf-8");
    header("Cache-Control: no-cache");
    die(
        '<root act="relation_upd" return="' . $ret . '" b="'
        . $b . '" K="' . $K . '"></root>'
    );
}
?>
