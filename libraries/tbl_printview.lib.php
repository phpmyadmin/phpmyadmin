<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions related to show table print view
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Template.class.php';

/**
 * return html for tables' info
 *
 * @param array $the_tables selected tables
 *
 * @return string
 */
function PMA_getHtmlForTablesInfo($the_tables)
{
    return PMA\Template::get('printview/tables_info')
        ->render(array(
            'tables' => $the_tables
        ));
}


/**
 * return html for print view footer
 *
 * @return string
 */
function PMA_getHtmlForPrintViewFooter()
{
    return PMA\Template::get('printview/footer')->render();
}

/**
 * return html for Print View Columns
 *
 * @param bool   $tbl_is_view  whether table is a view
 * @param array  $columns      columns list
 * @param array  $analyzed_sql analyzed sql
 * @param bool   $have_rel     have relation?
 * @param array  $res_rel      relations array
 * @param string $db           database name
 * @param string $table        table name
 * @param array  $cfgRelation  config from PMA_getRelationsParam
 *
 * @return string
 */
function PMA_getHtmlForPrintViewColumns(
    $tbl_is_view, $columns, $analyzed_sql, $have_rel,
    $res_rel, $db, $table, $cfgRelation
) {
    return PMA\Template::get('printview/columns')->render(array(
        'tbl_is_view' => $tbl_is_view,
        'columns' => $columns,
        'analyzed_sql' => $analyzed_sql,
        'have_rel' => $have_rel,
        'res_rel' => $res_rel,
        'db' => $db,
        'table' => $table,
        'cfgRelation' => $cfgRelation
    ));
}

/**
 * return html for Row Statistic
 *
 * @param array $showtable       showing table information
 * @param int   $cell_align_left cell align left
 * @param int   $avg_size        avg size
 * @param int   $avg_unit        avg unit
 * @param bool  $mergetable      is merge table?
 *
 * @return string
 */
function PMA_getHtmlForRowStatistics(
    $showtable, $cell_align_left, $avg_size, $avg_unit, $mergetable
) {
    return PMA\Template::get('printview/row_statistics')
        ->render(array(
            'showtable' => $showtable,
            'cell_align_left' => $cell_align_left,
            'avg_size' => $avg_size,
            'avg_unit' => $avg_unit,
            'mergetable' => $mergetable
        ));
}

/**
 * return html for Space Usage
 *
 * @param int  $data_size   data size
 * @param int  $data_unit   data unit
 * @param int  $index_size  index size
 * @param int  $index_unit  index unit
 * @param int  $free_size   free size
 * @param int  $free_unit   free unit
 * @param int  $effect_size effect size
 * @param int  $effect_unit effect unit
 * @param int  $tot_size    total size
 * @param int  $tot_unit    total unit
 * @param bool $mergetable  is merge table?
 *
 * @return string
 */
function PMA_getHtmlForSpaceUsage(
    $data_size, $data_unit, $index_size, $index_unit,
    $free_size, $free_unit, $effect_size, $effect_unit,
    $tot_size, $tot_unit, $mergetable
) {
    return PMA\Template::get('printview/space_usage')
        ->render(array(
            'data_size' => $data_size,
            'data_unit' => $data_unit,
            'index_size' => $index_size,
            'index_unit' => $index_unit,
            'free_size' => $free_size,
            'free_unit' => $free_unit,
            'effect_size' => $effect_size,
            'effect_unit' => $effect_unit,
            'tot_size' => $tot_size,
            'tot_unit' => $tot_unit,
            'mergetable' => $mergetable
        ));
}
/**
 * return html for Space Usage And Row Statistic
 *
 * @param array  $showtable       showing table information
 * @param string $db              database
 * @param string $table           table
 * @param int    $cell_align_left cell align left
 *
 * @return string
 */
function PMA_getHtmlForSpaceUsageAndRowStatistics(
    $showtable, $db, $table, $cell_align_left
) {
    $nonisam = false;
    if (isset($showtable['Type'])
        && ! preg_match('@ISAM|HEAP@i', $showtable['Type'])
    ) {
        $nonisam = true;
    }
    if ($nonisam == false) {
        // Gets some sizes

        $mergetable = PMA_Table::isMerge($db, $table);

        list($data_size, $data_unit) = PMA_Util::formatByteDown(
            $showtable['Data_length']
        );
        if ($mergetable == false) {
            list($index_size, $index_unit) = PMA_Util::formatByteDown(
                $showtable['Index_length']
            );
        }
        if (isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
            list($free_size, $free_unit) = PMA_Util::formatByteDown(
                $showtable['Data_free']
            );
            list($effect_size, $effect_unit) = PMA_Util::formatByteDown(
                $showtable['Data_length'] + $showtable['Index_length']
                - $showtable['Data_free']
            );
        } else {
            unset($free_size);
            unset($free_unit);
            list($effect_size, $effect_unit) = PMA_Util::formatByteDown(
                $showtable['Data_length'] + $showtable['Index_length']
            );
        }
        list($tot_size, $tot_unit) = PMA_Util::formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length']
        );
        $num_rows = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
        if ($num_rows > 0) {
            list($avg_size, $avg_unit) = PMA_Util::formatByteDown(
                ($showtable['Data_length'] + $showtable['Index_length'])
                / $showtable['Rows'],
                6, 1
            );
        }

        return PMA\Template::get(
            'printview/space_usage_and_row_statistics'
        )->render(array(
            'data_size' => $data_size,
            'data_unit' => $data_unit,
            'index_size' => $index_size,
            'index_unit' => $index_unit,
            'free_size' => $free_size,
            'free_unit' => $free_unit,
            'effect_size' => $effect_size,
            'effect_unit' => $effect_unit,
            'tot_size' => $tot_size,
            'tot_unit' => $tot_unit,
            'mergetable' => $mergetable,
            'showtable' => $showtable,
            'cell_align_left' => $cell_align_left,
            'avg_size' => $avg_size,
            'avg_unit' => $avg_unit
        ));
    }
}

/**
 * return html for Table Structure
 *
 * @param bool   $have_rel        whether have relation
 * @param bool   $tbl_is_view     Is a table view?
 * @param array  $columns         columns list
 * @param array  $analyzed_sql    analyzed sql
 * @param array  $res_rel         relations array
 * @param string $db              database
 * @param string $table           table
 * @param array  $cfgRelation     config from PMA_getRelationsParam
 * @param array  $cfg             global config
 * @param array  $showtable       showing table information
 * @param int    $cell_align_left cell align left
 *
 * @return string
 */
function PMA_getHtmlForTableStructure(
    $have_rel, $tbl_is_view, $columns, $analyzed_sql,
    $res_rel, $db, $table, $cfgRelation,
    $cfg, $showtable, $cell_align_left
) {
    return PMA\Template::get('printview/tables_structure')
        ->render(array(
            'have_rel' => $have_rel,
            'tbl_is_view' => $tbl_is_view,
            'columns' => $columns,
            'analyzed_sql' => $analyzed_sql,
            'res_rel' => $res_rel,
            'db' => $db,
            'table' => $table,
            'cfgRelation' => $cfgRelation,
            'cfg' => $cfg,
            'showtable' => $showtable,
            'cell_align_left' => $cell_align_left
        ));
}

/**
 * return html for tables' detail
 *
 * @param array  $the_tables      tables list
 * @param string $db              database name
 * @param array  $cfg             global config
 * @param array  $cfgRelation     config from PMA_getRelationsParam
 * @param int    $cell_align_left cell align left
 *
 * @return string
 */
function PMA_getHtmlForTablesDetail(
    $the_tables, $db, $cfg, $cfgRelation, $cell_align_left
) {
    $tables_cnt = count($the_tables);
    $multi_tables = (count($the_tables) > 1);
    $counter = 0;
    $html = '';
    $template = PMA\Template::get('printview/tables_detail');

    foreach ($the_tables as $table) {

        /**
         * Gets table informations
         */
        $showtable    = PMA_Table::sGetStatusInfo($db, $table);
        $num_rows     = (isset($showtable['Rows']) ? $showtable['Rows'] : 0);
        $show_comment = (isset($showtable['Comment']) ? $showtable['Comment'] : '');

        $tbl_is_view = PMA_Table::isView($db, $table);

        /**
         * Gets fields properties
         */
        $columns = $GLOBALS['dbi']->getColumns($db, $table);

        // We need this to correctly learn if a TIMESTAMP is NOT NULL, since
        // SHOW FULL FIELDS or INFORMATION_SCHEMA incorrectly says NULL
        // and SHOW CREATE TABLE says NOT NULL (tested
        // in MySQL 4.0.25 and 5.0.21, http://bugs.mysql.com/20910).

        $show_create_table = $GLOBALS['dbi']->fetchValue(
            'SHOW CREATE TABLE ' . PMA_Util::backquote($db) . '.'
            . PMA_Util::backquote($table),
            0, 1
        );
        $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

        // Check if we can use Relations
        // Find which tables are related with the current one and write it in
        // an array
        $res_rel  = PMA_getForeigners($db, $table);
        $have_rel = (bool) count($res_rel);

        $html .= $template->render(array(
            'counter' => $counter,
            'tables_cnt' => $tables_cnt,
            'table' => $table,
            'show_comment' => $show_comment,
            'have_rel' => $have_rel,
            'tbl_is_view' => $tbl_is_view,
            'columns' => $columns,
            'analyzed_sql' => $analyzed_sql,
            'res_rel' => $res_rel,
            'db' => $db,
            'cfgRelation' => $cfgRelation,
            'cfg' => $cfg,
            'showtable' => $showtable,
            'cell_align_left' => $cell_align_left,
            'multi_tables' => $multi_tables
        ));

        if ($multi_tables) {
            unset($num_rows, $show_comment);
        }
    } // end while
    return $html;
}
?>
