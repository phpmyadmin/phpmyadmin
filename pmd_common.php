<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * @author  Ivan A Kirillov (Ivan.A.Kirillov@gmail.com)
 * @version $Id$
 * @package phpMyAdmin-Designer
 */

/**
 *
 */
require_once './libraries/common.inc.php';
// not understand
require_once './libraries/header_http.inc.php';

$GLOBALS['PMD']['STYLE']          = 'default';

require_once './libraries/relation.lib.php';
$cfgRelation = PMA_getRelationsParam();

$GLOBALS['script_display_field'] =
    '<script type="text/javascript">' . "\n" .
    '// <![CDATA[' . "\n" .
    'var display_field = new Array();' . "\n";

/**
 * retrieves table info and stores it in $GLOBALS['PMD']
 *
 * @uses    $GLOBALS['script_display_field']
 * @uses    $GLOBALS['PMD'] to fill it
 * @uses    $GLOBALS['db']
 * @uses    PMA_DBI_get_tables_full()
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_getDisplayField()
 * @uses    urlencode()
 * @uses    htmlspecialchars()
 * @uses    strtoupper()
 * @uses    urlencode()
 */
function get_tabs()
{
    $GLOBALS['PMD']['TABLE_NAME'] = array();// that foreach no error
    $GLOBALS['PMD']['OWNER'] = array();
    $GLOBALS['PMD']['TABLE_NAME_SMALL'] = array();

    $tables = PMA_DBI_get_tables_full($GLOBALS['db']);
    // seems to be needed later
    PMA_DBI_select_db($GLOBALS['db']);
    $i = 0;
    foreach ($tables as $one_table) {
        $GLOBALS['PMD']['TABLE_NAME'][$i] = $GLOBALS['db'] . "." . $one_table['TABLE_NAME'];
        $GLOBALS['PMD']['OWNER'][$i] = $GLOBALS['db'];
        $GLOBALS['PMD']['TABLE_NAME_SMALL'][$i] = $one_table['TABLE_NAME'];

        $GLOBALS['PMD_URL']['TABLE_NAME'][$i] = urlencode($GLOBALS['db'] . "." . $one_table['TABLE_NAME']);
        $GLOBALS['PMD_URL']['OWNER'][$i] = urlencode($GLOBALS['db']);
        $GLOBALS['PMD_URL']['TABLE_NAME_SMALL'][$i] = urlencode($one_table['TABLE_NAME']);

        $GLOBALS['PMD_OUT']['TABLE_NAME'][$i] = htmlspecialchars($GLOBALS['db'] . "." . $one_table['TABLE_NAME'], ENT_QUOTES);
        $GLOBALS['PMD_OUT']['OWNER'][$i] = htmlspecialchars($GLOBALS['db'], ENT_QUOTES);
        $GLOBALS['PMD_OUT']['TABLE_NAME_SMALL'][$i] = htmlspecialchars($one_table['TABLE_NAME'], ENT_QUOTES);

        $GLOBALS['PMD']['TABLE_TYPE'][$i] = strtoupper($one_table['ENGINE']);

        $DF = PMA_getDisplayField($GLOBALS['db'], $one_table['TABLE_NAME']);
        if ($DF != '') {
            $GLOBALS['script_display_field'] .= "  display_field['"
                . $GLOBALS['PMD_URL']["TABLE_NAME_SMALL"][$i] . "'] = '"
                . urlencode($DF) . "';\n";
        }

        $i++;
    }

    $GLOBALS['script_display_field'] .=
        '// ]]>' . "\n" .
        '</script>' . "\n";
    //  return $GLOBALS['PMD'];       // many bases // not use ??????
}

/**
 * retrieves table column info
 *
 * @uses    $GLOBALS['db']
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_DBI_query()
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_backquote()
 * @uses    count()
 * @return  array   table column nfo
 */
function get_tab_info()
{
    PMA_DBI_select_db($GLOBALS['db']);
    $tab_column = array();
    for ($i = 0, $cnt = count($GLOBALS['PMD']["TABLE_NAME"]); $i < $cnt; $i++) {
        $fields_rs   = PMA_DBI_query('SHOW FULL FIELDS FROM '.PMA_backquote($GLOBALS['PMD']["TABLE_NAME_SMALL"][$i]), NULL, PMA_DBI_QUERY_STORE);
        $j = 0;
        while ($row = PMA_DBI_fetch_assoc($fields_rs)) {
            $tab_column[$GLOBALS['PMD']['TABLE_NAME'][$i]]['COLUMN_ID'][$j]   = $j;
            $tab_column[$GLOBALS['PMD']['TABLE_NAME'][$i]]['COLUMN_NAME'][$j] = $row['Field'];
            $tab_column[$GLOBALS['PMD']['TABLE_NAME'][$i]]['TYPE'][$j]        = $row['Type'];
            $tab_column[$GLOBALS['PMD']['TABLE_NAME'][$i]]['NULLABLE'][$j]    = $row['Null'];
            $j++;
        }
    }
    return $tab_column;
}

/**
 * returns JavaScript code for intializing vars
 *
 * @uses    $GLOBALS['db']
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    PMA_DBI_select_db()
 * @uses    PMA_DBI_query()
 * @uses    PMA_backquote()
 * @uses    PMA_DBI_fetch_row()
 * @uses    PMA_getForeigners()
 * @uses    urlencode()
 * @uses    count()
 * @uses    in_array()
 * @return string   JavaScript code
 */
function get_script_contr()
{
    PMA_DBI_select_db($GLOBALS['db']);
    $con["C_NAME"] = array();
    $i = 0;
    $alltab_rs  = PMA_DBI_query('SHOW TABLES FROM ' . PMA_backquote($GLOBALS['db']), NULL, PMA_DBI_QUERY_STORE);
    while ($val = @PMA_DBI_fetch_row($alltab_rs)) {
        $row = PMA_getForeigners($GLOBALS['db'], $val[0], '', 'internal');
        //echo "<br> internal ".$GLOBALS['db']." - ".$val[0]." - ";
        //print_r($row);
        if ($row !== false) {
            foreach ($row as $field => $value) {
                $con['C_NAME'][$i] = '';
                $con['DTN'][$i]    = urlencode($GLOBALS['db'] . "." . $val[0]);
                $con['DCN'][$i]    = urlencode($field);
                $con['STN'][$i]    = urlencode($value['foreign_db'] . "." . $value['foreign_table']);
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
                $con['STN'][$i]    = urlencode($value['foreign_db'].".".$value['foreign_table']);
                $con['SCN'][$i]    = urlencode($value['foreign_field']);
                $i++;
            }
        }
    }

    $ti = 0;
    $script_contr =
        '<script type="text/javascript">' . "\n" .
        '// <![CDATA[' . "\n" .
        'var contr = new Array();' . "\n";
    for ($i = 0, $cnt = count($con["C_NAME"]); $i < $cnt; $i++) {
        $js_var = ' contr[' . $ti . ']';
        $script_contr .= $js_var . " = new Array();\n";
        $js_var .= "['" . $con['C_NAME'][$i] . "']";
        $script_contr .= $js_var . " = new Array();\n";
        if (in_array($con['DTN'][$i], $GLOBALS['PMD_URL']["TABLE_NAME"])
         && in_array($con['STN'][$i], $GLOBALS['PMD_URL']["TABLE_NAME"])) {
            $js_var .= "['" . $con['DTN'][$i] . "']";
            $script_contr .= $js_var . " = new Array();\n";
            $m_col = array();//}
            $js_var .= "['" . $con['DCN'][$i] . "']";
            $script_contr .= $js_var . " = new Array();\n";//}
            $script_contr .= $js_var . "[0] = '" . $con['STN'][$i] . "';\n"; //
            $script_contr .= $js_var . "[1] = '" . $con['SCN'][$i] . "';\n"; //
        }
        $ti++;
    }
    $script_contr .=
        '// ]]>' . "\n" .
        '</script>' . "\n";
    return $script_contr;
}

/**
 * @uses    get_all_keys()
 * @return  array unique or primary indizes
 */
function get_pk_or_unique_keys()
{
    return get_all_keys(true);
}

/**
 * returns all indizes
 *
 * @uses    $GLOBALS['PMD']
 * @uses    PMA_Index::getFromTable()
 * @uses    PMA_Index->isUnique()
 * @uses    PMA_Index->getColumns()
 * @param   boolean whether to include ony unique ones
 * @return  array indizes
 */
function get_all_keys($unique_only = false)
{
    require_once './libraries/Index.class.php';

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
 *
 *
 * @uses    $GLOBALS['PMD']
 * @uses    count()
 * @uses    in_array()
 * @return  array   ???
 */
function get_script_tabs()
{
    $script_tabs =
        '<script type="text/javascript">' . "\n" .
        '// <![CDATA[' . "\n" .
        'var j_tabs = new Array();' . "\n";
    for ($i = 0, $cnt = count($GLOBALS['PMD']['TABLE_NAME']); $i < $cnt; $i++) {
        $script_tabs .= "j_tabs['" . $GLOBALS['PMD_URL']['TABLE_NAME'][$i] . "'] = '"
            . (PMA_foreignkey_supported($GLOBALS['PMD']['TABLE_TYPE'][$i]) ? '1' : '0') . "';\n";
    }
    $script_tabs .=
        '// ]]>' . "\n" .
        '</script>' . "\n";
    return $script_tabs;
}

/**
 * @uses    $GLOBALS['controllink']
 * @uses    $cfgRelation['designerwork']
 * @uses    $cfgRelation['db']
 * @uses    $cfgRelation['designer_coords']
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_backquote()
 * @uses    PMA_DBI_fetch_result()
 * @uses    count()
 * @return  array   table positions and sizes
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
           FROM " . PMA_backquote($cfgRelation['db']) . "." . PMA_backquote($cfgRelation['designer_coords']);
    $tab_pos = PMA_DBI_fetch_result($query, 'name', null, $GLOBALS['controllink'], PMA_DBI_QUERY_STORE);
    return count($tab_pos) ? $tab_pos : null;
}

/**
 * returns  distinct values from $GLOBALS['PMD']['OWNER']
 *
 * @uses    array_values()
 * @uses    array_unique()
 * @uses    $GLOBALS['PMD']['OWNER']
 * @return  array   owner
 */
function get_owners()
{
    return array_values(array_unique($GLOBALS['PMD']['OWNER']));
}

get_tabs();
?>
