<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table structure infos like fields/columns, indexes, size, rows
 * and allows manipulation of indexes and columns/fields
 * @package PhpMyAdmin
 */

/**
 *
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.lib.php';

/**
 * Function implementations for this script
 */
require_once 'libraries/structure.lib.php';

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$scripts  = $header->getScripts();
$scripts->addFile('tbl_structure.js');
$scripts->addFile('indexes.js');
$common_functions = PMA_CommonFunctions::getInstance();

/**
 * handle multiple field commands if required
 *
 * submit_mult_*_x comes from IE if <input type="img" ...> is used
 */
if (isset($_REQUEST['submit_mult_change_x'])) {
    $submit_mult = 'change';
} elseif (isset($_REQUEST['submit_mult_drop_x'])) {
    $submit_mult = 'drop';
} elseif (isset($_REQUEST['submit_mult_primary_x'])) {
    $submit_mult = 'primary';
} elseif (isset($_REQUEST['submit_mult_index_x'])) {
    $submit_mult = 'index';
} elseif (isset($_REQUEST['submit_mult_unique_x'])) {
    $submit_mult = 'unique';
} elseif (isset($_REQUEST['submit_mult_spatial_x'])) {
    $submit_mult = 'spatial';
} elseif (isset($_REQUEST['submit_mult_fulltext_x'])) {
    $submit_mult = 'ftext';
} elseif (isset($_REQUEST['submit_mult_browse_x'])) {
    $submit_mult = 'browse';
} elseif (isset($_REQUEST['submit_mult'])) {
    $submit_mult = $_REQUEST['submit_mult'];
} elseif (isset($_REQUEST['mult_btn']) && $_REQUEST['mult_btn'] == __('Yes')) {
    $submit_mult = 'row_delete';
    if (isset($_REQUEST['selected'])) {
        $_REQUEST['selected_fld'] = $_REQUEST['selected'];
    }
}

if (! empty($submit_mult) && isset($_REQUEST['selected_fld'])) {
    $err_url = 'tbl_structure.php?' . PMA_generate_common_url($db, $table);
    if ($submit_mult == 'browse') {
        // browsing the table displaying only selected fields/columns
        $GLOBALS['active_page'] = 'sql.php';
        $sql_query = '';
        foreach ($_REQUEST['selected_fld'] as $idx => $sval) {
            if ($sql_query == '') {
                $sql_query .= 'SELECT ' . $common_functions->backquote($sval);
            } else {
                $sql_query .=  ', ' . $common_functions->backquote($sval);
            }
        }

        // what is this htmlspecialchars() for??
        //$sql_query .= ' FROM ' . backquote(htmlspecialchars($table));
        $sql_query .= ' FROM ' . $common_functions->backquote($db)
            . '.' . $common_functions->backquote($table);
        include 'sql.php';
        exit;
    } else {
        // handle multiple field commands
        // handle confirmation of deleting multiple fields/columns
        $action = 'tbl_structure.php';
        include 'libraries/mult_submits.inc.php';

        if (empty($message)) {
            $message = PMA_Message::success();
        }
    }
}

/**
 * Gets the relation settings
 */
$cfgRelation = PMA_getRelationsParam();

/**
 * Runs common work
 */
require_once 'libraries/tbl_common.inc.php';
$url_query .= '&amp;goto=tbl_structure.php&amp;back=tbl_structure.php';
$url_params['goto'] = 'tbl_structure.php';
$url_params['back'] = 'tbl_structure.php';

/**
 * Prepares the table structure display
 */


/**
 * Gets tables informations
 */
require_once 'libraries/tbl_info.inc.php';

require_once 'libraries/Index.class.php';

// 2. Gets table keys and retains them
// @todo should be: $server->db($db)->table($table)->primary()
$primary = PMA_Index::getPrimary($table, $db);

$columns_with_unique_index = array();
foreach (PMA_Index::getFromTable($table, $db) as $index) {
    if ($index->isUnique() && $index->getChoice() == 'UNIQUE') {
        $columns = $index->getColumns();
        foreach ($columns as $column_name => $dummy) {
            $columns_with_unique_index[$column_name] = 1;
        }
    }
}
unset($index, $columns, $column_name, $dummy);

// 3. Get fields
$fields = (array) PMA_DBI_get_columns($db, $table, null, true);

// Get more complete field information
// For now, this is done just for MySQL 4.1.2+ new TIMESTAMP options
// but later, if the analyser returns more information, it
// could be executed for any MySQL version and replace
// the info given by SHOW FULL COLUMNS FROM.
//
// We also need this to correctly learn if a TIMESTAMP is NOT NULL, since
// SHOW FULL COLUMNS or INFORMATION_SCHEMA incorrectly says NULL
// and SHOW CREATE TABLE says NOT NULL (tested
// in MySQL 4.0.25 and 5.0.21, http://bugs.mysql.com/20910).

$show_create_table = PMA_DBI_fetch_value(
    'SHOW CREATE TABLE ' . $common_functions->backquote($db) . '.' . $common_functions->backquote($table),
    0, 1
);
$analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));

/**
 * prepare table infos
 */
// action titles (image or string)
$titles = PMA_getActionTitlesArray();

// hidden action titles (image and string)
$hidden_titles = PMA_getHiddenTitlesArray();

/**
 * Displays the table structure ('show table' works correct since 3.23.03)
 */
/* TABLE INFORMATION */
// table header
$i = 0;
?>
<form method="post" action="tbl_structure.php" name="fieldsForm" id="fieldsForm" <?php echo ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : '');?>>
    <?php echo PMA_generate_common_hidden_inputs($db, $table);
    echo '<input type="hidden" name="table_type" value=';
    if ($db_is_information_schema) {
         echo '"information_schema" />';
    } else if ($tbl_is_view) {
         echo '"view" />';
    } else {
         echo '"table" />';
    } ?>

<table id="tablestructure" class="data<?php
if ($GLOBALS['cfg']['PropertiesIconic'] === true) {
    echo ' PropertiesIconic';
} ?>">
<?php
echo PMA_getHtmlForTableStructureHeader(
    $db_is_information_schema,
    $tbl_is_view
);
?>
<tbody>

<?php
unset($i);

// table body

// prepare comments
$comments_map = array();
$mime_map = array();

if ($GLOBALS['cfg']['ShowPropertyComments']) {
    include_once 'libraries/transformations.lib.php';

    //$cfgRelation = PMA_getRelationsParam();

    $comments_map = PMA_getComments($db, $table);

    if ($cfgRelation['mimework'] && $cfg['BrowseMIME']) {
        $mime_map = PMA_getMIME($db, $table, true);
    }
}

$rownum    = 0;
$columns_list = array();
$checked   = (!empty($checkall) ? ' checked="checked"' : '');
$save_row  = array();
$odd_row   = true;
foreach ($fields as $row) {
    $save_row[] = $row;
    $rownum++;
    $columns_list[]   = $row['Field'];

    $type             = $row['Type'];
    $extracted_columnspec = $common_functions->extractColumnSpec($row['Type']);

    if ('set' == $extracted_columnspec['type']
        || 'enum' == $extracted_columnspec['type']
    ) {
        $type_nowrap  = '';
    } else {
        $type_nowrap  = ' class="nowrap"';
    }
    $type         = $extracted_columnspec['print_type'];
    if (empty($type)) {
        $type     = ' ';
    }

    $field_charset = '';
    if ($extracted_columnspec['can_contain_collation']
        && ! empty($row['Collation'])
    ) {
        $field_charset = $row['Collation'];
    }

    // Display basic mimetype [MIME]
    if ($cfgRelation['commwork']
        && $cfgRelation['mimework']
        && $cfg['BrowseMIME']
        && isset($mime_map[$row['Field']]['mimetype'])
    ) {
        $type_mime = '<br />MIME: '
            . str_replace('_', '/', $mime_map[$row['Field']]['mimetype']);
    } else {
        $type_mime = '';
    }

    $attribute     = $extracted_columnspec['attribute'];

    // MySQL 4.1.2+ TIMESTAMP options
    // (if on_update_current_timestamp is set, then it's TRUE)
    if (isset($analyzed_sql[0]['create_table_fields'][$row['Field']]['on_update_current_timestamp'])) {
        $attribute = 'on update CURRENT_TIMESTAMP';
    }

    // here, we have a TIMESTAMP that SHOW FULL COLUMNS reports as having the
    // NULL attribute, but SHOW CREATE TABLE says the contrary. Believe
    // the latter.
    if (! empty($analyzed_sql[0]['create_table_fields'][$row['Field']]['type'])
        && $analyzed_sql[0]['create_table_fields'][$row['Field']]['type'] == 'TIMESTAMP'
        && $analyzed_sql[0]['create_table_fields'][$row['Field']]['timestamp_not_null']
    ) {
        $row['Null'] = '';
    }


    if (! isset($row['Default'])) {
        if ($row['Null'] == 'YES') {
            $row['Default'] = '<i>NULL</i>';
        }
    } else {
        $row['Default'] = htmlspecialchars($row['Default']);
    }

    $field_encoded = urlencode($row['Field']);
    $field_name    = htmlspecialchars($row['Field']);
    $displayed_field_name = $field_name;

    // underline commented fields and display a hover-title (CSS only)

    if (isset($comments_map[$row['Field']])) {
        $displayed_field_name = '<span class="commented_column" title="'
            . htmlspecialchars($comments_map[$row['Field']]) . '">'
            . $field_name . '</span>';
    }

    if ($primary && $primary->hasColumn($field_name)) {
        $displayed_field_name = '<u>' . $field_name . '</u>';
    }
    echo "\n";
    
    echo '<tr class="' . ($odd_row ? 'odd': 'even') . '">';
    $odd_row = !$odd_row;
    
    echo PMA_getHtmlTableStructureRow($row, $rownum, $checked,
        $displayed_field_name, $type_nowrap, $extracted_columnspec, $type_mime,
        $field_charset, $attribute, $tbl_is_view, $db_is_information_schema,
        $url_query, $field_encoded, $titles, $table
    );
    
    if (! $tbl_is_view && ! $db_is_information_schema) {
        echo PMA_getHtmlForActionsIntableStructure($type, $tbl_storage_engine,
            $primary, $field_name, $url_query, $titles, $row, $rownum,
            $hidden_titles, $columns_with_unique_index
        );
    } // end if (! $tbl_is_view && ! $db_is_information_schema)
    
    echo '</tr>';

    unset($field_charset);
} // end foreach

echo '</tbody>' . "\n"
    .'</table>' . "\n";

echo PMA_getHtmlForCheckAlltableColumn($pmaThemeImage, $text_dir,
    $tbl_is_view, $db_is_information_schema, $tbl_storage_engine
);

echo '</form>'
    . '<hr />';
echo PMA_getHtmlDivForMoveColumnsDialog();

/**
 * Work on the table
 */

if ($tbl_is_view) {
    echo PMA_getHtmlForEditView($url_params);
}
echo PMA_getHtmlForSomeLinks($url_query, $tbl_is_view,
    $db_is_information_schema, $tbl_storage_engine, $cfgRelation);

if (! $tbl_is_view && ! $db_is_information_schema) {
    echo '<br />';
    echo PMA_getHtmlForAddColumn($columns_list);
    
    echo '<iframe class="IE_hack"></iframe>'
        . '<hr />';
    echo '<div id="index_div" ' 
        . ($GLOBALS['cfg']['AjaxEnable'] ? ' class="ajax"' : '') . ' >';
}

/**
 * Displays indexes
 */

if (! $tbl_is_view
    && ! $db_is_information_schema
    && 'ARCHIVE' !=  $tbl_storage_engine
) {
    echo PMA_getHtmlForDisplayIndexes();
}

/**
 * Displays Space usage and row statistics
 */
// BEGIN - Calc Table Space
// Get valid statistics whatever is the table type
if ($cfg['ShowStats']) {
    echo '<div id="tablestatistics">';
    if (empty($showtable)) {
        $showtable = PMA_Table::sGetStatusInfo(
            $GLOBALS['db'], $GLOBALS['table'], null, true
        );
    }

    $nonisam     = false;
    $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');
    if (isset($showtable['Type'])
        && ! preg_match('@ISAM|HEAP@i', $showtable['Type'])
    ) {
        $nonisam = true;
    }

    // Gets some sizes

    $mergetable = PMA_Table::isMerge($GLOBALS['db'], $GLOBALS['table']);

    // this is to display for example 261.2 MiB instead of 268k KiB
    $max_digits = 3;
    $decimals = 1;
    list($data_size, $data_unit) = $common_functions->formatByteDown(
        $showtable['Data_length'], $max_digits, $decimals
    );
    if ($mergetable == false) {
        list($index_size, $index_unit) = $common_functions->formatByteDown(
            $showtable['Index_length'], $max_digits, $decimals
        );
    }
    // InnoDB returns a huge value in Data_free, do not use it
    if (! $is_innodb
        && isset($showtable['Data_free'])
        && $showtable['Data_free'] > 0
    ) {
        list($free_size, $free_unit) = $common_functions->formatByteDown(
            $showtable['Data_free'], $max_digits, $decimals
        );
        list($effect_size, $effect_unit) = $common_functions->formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free'],
            $max_digits, $decimals
        );
    } else {
        list($effect_size, $effect_unit) = $common_functions->formatByteDown(
            $showtable['Data_length'] + $showtable['Index_length'],
            $max_digits, $decimals
        );
    }
    list($tot_size, $tot_unit) = $common_functions->formatByteDown(
        $showtable['Data_length'] + $showtable['Index_length'],
        $max_digits, $decimals
    );
    if ($table_info_num_rows > 0) {
        list($avg_size, $avg_unit) = $common_functions->formatByteDown(
            ($showtable['Data_length'] + $showtable['Index_length']) / $showtable['Rows'], 6, 1
        );
    }

    // Displays them
    $odd_row = false;

    echo '<fieldset>'
        . '<legend>' . __('Information') . '</legend>'
        . '<a id="showusage"></a>';
    if (! $tbl_is_view && ! $db_is_information_schema) {
        echo '<table id="tablespaceusage" class="data">'
            . '<caption class="tblHeaders">' . __('Space usage') . '</caption>'
            . '<tbody>';

        echo PMA_getHtmlForSpaceUsageTableRow(
            $odd_row, __('Data'), $data_size, $data_unit
        );
        $odd_row = !$odd_row;
        
        if (isset($index_size)) {
            echo PMA_getHtmlForSpaceUsageTableRow(
                $odd_row, __('Index'), $index_size, $index_unit
            );
            $odd_row = !$odd_row;
        }
         
        if (isset($free_size)) {
            echo PMA_getHtmlForSpaceUsageTableRow(
                $odd_row, __('Overhead'), $free_size, $free_unit
            );
            echo PMA_getHtmlForSpaceUsageTableRow(
                $odd_row, __('Effective'), $effect_size, $effect_unit
            );
            $odd_row = !$odd_row;
        }
        if (isset($tot_size) && $mergetable == false) {
            echo PMA_getHtmlForSpaceUsageTableRow(
                $odd_row, __('Total'), $tot_size, $tot_unit
            );
            $odd_row = !$odd_row;
        }
        // Optimize link if overhead
        if (isset($free_size) && !PMA_DRIZZLE 
            && ($tbl_storage_engine == 'MYISAM' 
                || $tbl_storage_engine == 'ARIA' 
                || $tbl_storage_engine == 'MARIA' 
                || $tbl_storage_engine == 'BDB'
            )
        ) {
            echo PMA_getHtmlForOptimizeLink($url_query);
        }
        echo '</tbody>'
            . '</table>';
    }
    
    echo getHtmlForRowStatsTable($showtable, $tbl_collation,
        $is_innodb, $mergetable, 
        (isset ($avg_size) ? $avg_size : ''), 
        (isset ($avg_unit) ? $avg_unit : '')
    );
}
// END - Calc Table Space

echo '<div class="clearfloat"></div>' . "\n";
 
?>
