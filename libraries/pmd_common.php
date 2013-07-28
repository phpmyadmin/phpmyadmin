<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @package PhpMyAdmin-Designer
 */
/**
 * block attempts to directly run this script 
 */
if (getcwd() == dirname(__FILE__)) {
    die('Attack stopped');
}

/**
 *
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$GLOBALS['PMD']['STYLE']          = 'default';

$cfgRelation = PMA_getRelationsParam();

/**
 * retrieves table info and stores it in $GLOBALS['PMD']
 *
 * @return array with table info
 */
function get_tables_info()
{
    $retval = array();

    $GLOBALS['PMD']['TABLE_NAME'] = array();// that foreach no error
    $GLOBALS['PMD']['OWNER'] = array();
    $GLOBALS['PMD']['TABLE_NAME_SMALL'] = array();

    $tables = PMA_DBI_get_tables_full($GLOBALS['db']);
    // seems to be needed later
    PMA_DBI_select_db($GLOBALS['db']);
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
 * retrieves table column info
 *
 * @return array   table column nfo
 */
function get_columns_info()
{
    PMA_DBI_select_db($GLOBALS['db']);
    $tab_column = array();
    for ($i = 0, $cnt = count($GLOBALS['PMD']["TABLE_NAME"]); $i < $cnt; $i++) {
        $fields_rs = PMA_DBI_query(
            PMA_DBI_get_columns_sql(
                $GLOBALS['db'],
                $GLOBALS['PMD']["TABLE_NAME_SMALL"][$i],
                null,
                true
            ),
            null,
            PMA_DBI_QUERY_STORE
        );
        $tbl_name_i = $GLOBALS['PMD']['TABLE_NAME'][$i];
        $j = 0;
        while ($row = PMA_DBI_fetch_assoc($fields_rs)) {
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
 * returns JavaScript code for intializing vars
 *
 * @return string   JavaScript code
 */
function get_script_contr()
{
    PMA_DBI_select_db($GLOBALS['db']);
    $con["C_NAME"] = array();
    $i = 0;
    $alltab_rs = PMA_DBI_query(
        'SHOW TABLES FROM ' . PMA_Util::backquote($GLOBALS['db']),
        null,
        PMA_DBI_QUERY_STORE
    );
    while ($val = @PMA_DBI_fetch_row($alltab_rs)) {
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
                $con['DTN'][$i]    = urlencode($GLOBALS['db'].".".$val[0]);
                $con['DCN'][$i]    = urlencode($field);
                $con['STN'][$i]    = urlencode(
                    $value['foreign_db'].".".$value['foreign_table']
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
        if (in_array($dtn_i, $GLOBALS['PMD_URL']["TABLE_NAME"])
            && in_array($con['STN'][$i], $GLOBALS['PMD_URL']["TABLE_NAME"])
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
function get_pk_or_unique_keys()
{
    return get_all_keys(true);
}

/**
 * returns all indices
 *
 * @param bool $unique_only whether to include only unique ones
 *
 * @return array indices
 */
function get_all_keys($unique_only = false)
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
                $keys[$schema . '.' .$table . '.' . $column_name] = 1;
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
function get_script_tabs()
{
    $script_tabs = 'var j_tabs = new Array();' . "\n"
        . 'var h_tabs = new Array();' . "\n" ;

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
function get_tab_pos()
{
    $cfgRelation = PMA_getRelationsParam();

    if (! $cfgRelation['designerwork']) {
        return null;
    }

    $query = "
         SELECT CONCAT_WS('.', `db_name`, `table_name`) AS `name`,
                `x` AS `X`,
                `y` AS `Y`,
                `v` AS `V`,
                `h` AS `H`
           FROM " . PMA_Util::backquote($cfgRelation['db'])
        . "." . PMA_Util::backquote($cfgRelation['designer_coords']);
    $tab_pos = PMA_DBI_fetch_result(
        $query, 'name', null, $GLOBALS['controllink'], PMA_DBI_QUERY_STORE
    );
    return count($tab_pos) ? $tab_pos : null;
}
?>
