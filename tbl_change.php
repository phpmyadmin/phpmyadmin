<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays form for editing and inserting new table rows
 *
 * register_globals_save (mark this file save for disabling register globals)
 *
 * @package PhpMyAdmin
 */

/**
 * Gets the variables sent or posted to this script and displays the header
 */
require_once './libraries/common.inc.php';
require_once './libraries/common.lib.php';

/**
 * Ensures db and table are valid, else moves to the "parent" script
 */
require_once './libraries/db_table_exists.lib.php';

// load additional configuration variables
if (PMA_DRIZZLE) {
    include_once './libraries/data_drizzle.inc.php';
} else {
    include_once './libraries/data_mysql.inc.php';
}

/**
 * Sets global variables.
 * Here it's better to use a if, instead of the '?' operator
 * to avoid setting a variable to '' when it's not present in $_REQUEST
 */
if (isset($_REQUEST['where_clause'])) {
    $where_clause = $_REQUEST['where_clause'];
}
if (isset($_REQUEST['clause_is_unique'])) {
    $clause_is_unique = $_REQUEST['clause_is_unique'];
}
if (isset($_SESSION['edit_next'])) {
    $where_clause = $_SESSION['edit_next'];
    unset($_SESSION['edit_next']);
    $after_insert = 'edit_next';
}
if (isset($_REQUEST['sql_query'])) {
    $sql_query = $_REQUEST['sql_query'];
}
if (isset($_REQUEST['ShowFunctionFields'])) {
    $cfg['ShowFunctionFields'] = $_REQUEST['ShowFunctionFields'];
}
if (isset($_REQUEST['ShowFieldTypesInDataEditView'])) {
    $cfg['ShowFieldTypesInDataEditView'] = $_REQUEST['ShowFieldTypesInDataEditView'];
}
if (isset($_REQUEST['default_action'])) {
    $default_action = $_REQUEST['default_action'];
}

/**
 * file listing
 */
require_once './libraries/file_listing.php';


/**
 * Defines the url to return to in case of error in a sql statement
 * (at this point, $GLOBALS['goto'] will be set but could be empty)
 */
if (empty($GLOBALS['goto'])) {
    if (strlen($table)) {
        // avoid a problem (see bug #2202709)
        $GLOBALS['goto'] = 'tbl_sql.php';
    } else {
        $GLOBALS['goto'] = 'db_sql.php';
    }
}
/**
 * @todo check if we could replace by "db_|tbl_" - please clarify!?
 */
$_url_params = array(
    'db'        => $db,
    'sql_query' => $sql_query
);

if (preg_match('@^tbl_@', $GLOBALS['goto'])) {
    $_url_params['table'] = $table;
}

$err_url = $GLOBALS['goto'] . PMA_generate_common_url($_url_params);
unset($_url_params);


/**
 * Sets parameters for links
 * where is this variable used?
 * replace by PMA_generate_common_url($url_params);
 */
$url_query = PMA_generate_common_url($url_params, 'html', '');

/**
 * get table information
 * @todo should be done by a Table object
 */
require_once './libraries/tbl_info.inc.php';

/**
 * Get comments for table fileds/columns
 */
$comments_map = array();

if ($GLOBALS['cfg']['ShowPropertyComments']) {
    $comments_map = PMA_getComments($db, $table);
}

/**
 * START REGULAR OUTPUT
 */

/**
 * used in ./libraries/header.inc.php to load JavaScript library file
 */
$GLOBALS['js_include'][] = 'functions.js';
$GLOBALS['js_include'][] = 'tbl_change.js';
$GLOBALS['js_include'][] = 'jquery/jquery-ui-1.8.16.custom.js';
$GLOBALS['js_include'][] = 'jquery/timepicker.js';
$GLOBALS['js_include'][] = 'gis_data_editor.js';

/**
 * HTTP and HTML headers
 */
require_once './libraries/header.inc.php';

/**
 * Displays the query submitted and its result
 *
 * @todo where does $disp_message and $disp_query come from???
 */
if (! empty($disp_message)) {
    if (! isset($disp_query)) {
        $disp_query     = null;
    }
    PMA_showMessage($disp_message, $disp_query);
}

/**
 * Displays top menu links
 */
require_once './libraries/tbl_links.inc.php';


/**
 * Get the analysis of SHOW CREATE TABLE for this table
 * @todo should be handled by class Table
 */
$show_create_table = PMA_DBI_fetch_value(
        'SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table),
        0, 1);
$analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));
unset($show_create_table);

/**
 * Get the list of the fields of the current table
 */
PMA_DBI_select_db($db);
$table_fields = array_values(PMA_DBI_get_columns($db, $table));
$rows               = array();
if (isset($where_clause)) {
    // when in edit mode load all selected rows from table
    $insert_mode = false;
    if (is_array($where_clause)) {
        $where_clause_array = $where_clause;
    } else {
        $where_clause_array = array(0 => $where_clause);
    }

    $result             = array();
    $found_unique_key   = false;
    $where_clauses      = array();

    foreach ($where_clause_array as $key_id => $where_clause) {
        $local_query           = 'SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . ' WHERE ' . $where_clause . ';';
        $result[$key_id]       = PMA_DBI_query($local_query, null, PMA_DBI_QUERY_STORE);
        $rows[$key_id]         = PMA_DBI_fetch_assoc($result[$key_id]);
        $where_clauses[$key_id] = str_replace('\\', '\\\\', $where_clause);

        // No row returned
        if (! $rows[$key_id]) {
            unset($rows[$key_id], $where_clause_array[$key_id]);
            PMA_showMessage(__('MySQL returned an empty result set (i.e. zero rows).'), $local_query);
            echo "\n";
            include './libraries/footer.inc.php';
        } else { // end if (no row returned)
            $meta = PMA_DBI_get_fields_meta($result[$key_id]);
            list($unique_condition, $tmp_clause_is_unique) = PMA_getUniqueCondition($result[$key_id], count($meta), $meta, $rows[$key_id], true);
            if (! empty($unique_condition)) {
                $found_unique_key = true;
            }
            unset($unique_condition, $tmp_clause_is_unique);
        }

    }
} else {
    // no primary key given, just load first row - but what happens if table is empty?
    $insert_mode = true;
    $result = PMA_DBI_query('SELECT * FROM ' . PMA_backquote($db) . '.' . PMA_backquote($table) . ' LIMIT 1;', null, PMA_DBI_QUERY_STORE);
    $rows = array_fill(0, $cfg['InsertRows'], false);
}

// Copying a row - fetched data will be inserted as a new row, therefore the where clause is needless.
if (isset($default_action) && $default_action === 'insert') {
    unset($where_clause, $where_clauses);
}

// retrieve keys into foreign fields, if any
$foreigners  = PMA_getForeigners($db, $table);


/**
 * Displays the form
 */
// autocomplete feature of IE kills the "onchange" event handler and it
//        must be replaced by the "onpropertychange" one in this case
$chg_evt_handler = (PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 5 && PMA_USR_BROWSER_VER < 7)
                 ? 'onpropertychange'
                 : 'onchange';
// Had to put the URI because when hosted on an https server,
// some browsers send wrongly this form to the http server.

    ?>
<!-- Set on key handler for moving using by Ctrl+arrows -->
<script src="./js/keyhandler.js" type="text/javascript"></script>
<script type="text/javascript">
//<![CDATA[
var switch_movement = 0;
document.onkeydown = onKeyDownArrowsHandler;
//]]>
</script>
    <?php

$_form_params = array(
    'db'        => $db,
    'table'     => $table,
    'goto'      => $GLOBALS['goto'],
    'err_url'   => $err_url,
    'sql_query' => $sql_query,
);
if (isset($where_clauses)) {
    foreach ($where_clause_array as $key_id => $where_clause) {
        $_form_params['where_clause[' . $key_id . ']'] = trim($where_clause);
    }
}
if (isset($clause_is_unique)) {
    $_form_params['clause_is_unique'] = $clause_is_unique;
}

?>

<!-- Insert/Edit form -->
<form id="insertForm" method="post" action="tbl_replace.php" name="insertForm" <?php if ($is_upload) { echo ' enctype="multipart/form-data"'; } ?>>
<?php
echo PMA_generate_common_hidden_inputs($_form_params);

$titles['Browse'] = PMA_getIcon('b_browse.png', __('Browse foreign values'));

// Set if we passed the first timestamp field
$timestamp_seen = 0;
$fields_cnt     = count($table_fields);

$tabindex = 0;
$tabindex_for_function = +3000;
$tabindex_for_null     = +6000;
$tabindex_for_value    = 0;
$o_rows   = 0;
$biggest_max_file_size = 0;

// user can toggle the display of Function column
// (currently does not work for multi-edits)
$url_params['db'] = $db;
$url_params['table'] = $table;
if (isset($where_clause)) {
    $url_params['where_clause'] = trim($where_clause);
}
if (! empty($sql_query)) {
    $url_params['sql_query'] = $sql_query;
}

if (! $cfg['ShowFunctionFields'] || ! $cfg['ShowFieldTypesInDataEditView']) {
    echo __('Show');
}
if (! $cfg['ShowFunctionFields']) {
    $this_url_params = array_merge($url_params,
        array('ShowFunctionFields' => 1, 'ShowFieldTypesInDataEditView' => $cfg['ShowFieldTypesInDataEditView'], 'goto' => 'sql.php'));
    echo ' : <a href="tbl_change.php' . PMA_generate_common_url($this_url_params) . '">' . __('Function') . '</a>' . "\n";
}
if (! $cfg['ShowFieldTypesInDataEditView']) {
    $this_other_url_params = array_merge($url_params,
        array('ShowFieldTypesInDataEditView' => 1, 'ShowFunctionFields' => $cfg['ShowFunctionFields'], 'goto' => 'sql.php'));
    echo ' : <a href="tbl_change.php' . PMA_generate_common_url($this_other_url_params) . '">' . __('Type') . '</a>' . "\n";
}

foreach ($rows as $row_id => $vrow) {
    if ($vrow === false) {
        unset($vrow);
    }

    $jsvkey = $row_id;
    $rownumber_param = '&amp;rownumber=' . $row_id;
    $vkey = '[multi_edit][' . $jsvkey . ']';

    $vresult = (isset($result) && is_array($result) && isset($result[$row_id]) ? $result[$row_id] : $result);
    if ($insert_mode && $row_id > 0) {
        echo '<input type="checkbox" checked="checked" name="insert_ignore_' . $row_id . '" id="insert_ignore_' . $row_id . '" />';
        echo '<label for="insert_ignore_' . $row_id . '">' . __('Ignore') . '</label><br />' . "\n";
    }
?>
    <table class="insertRowTable">
    <thead>
        <tr>
            <th><?php echo __('Column'); ?></th>

 <?php
     if ($cfg['ShowFieldTypesInDataEditView']) {
        $this_url_params = array_merge($url_params,
            array('ShowFieldTypesInDataEditView' => 0, 'ShowFunctionFields' => $cfg['ShowFunctionFields'], 'goto' => 'sql.php'));
        echo '          <th><a href="tbl_change.php' . PMA_generate_common_url($this_url_params) . '" title="' . __('Hide') . '">' . __('Type') . '</a></th>' . "\n";
    }

    if ($cfg['ShowFunctionFields']) {
        $this_url_params = array_merge($url_params,
            array('ShowFunctionFields' => 0, 'ShowFieldTypesInDataEditView' => $cfg['ShowFieldTypesInDataEditView'], 'goto' => 'sql.php'));
        echo '          <th><a href="tbl_change.php' . PMA_generate_common_url($this_url_params) . '" title="' . __('Hide') . '">' . __('Function') . '</a></th>' . "\n";
    }
?>
            <th><?php echo __('Null'); ?></th>
            <th><?php echo __('Value'); ?></th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <th colspan="5" align="right" class="tblFooters">
                <input type="submit" value="<?php echo __('Go'); ?>" />
            </th>
        </tr>
    </tfoot>
    <tbody>
<?php
    // Sets a multiplier used for input-field counts (as zero cannot be used, advance the counter plus one)
    $m_rows = $o_rows + 1;

    $odd_row = true;
    for ($i = 0; $i < $fields_cnt; $i++) {
        if (! isset($table_fields[$i]['processed'])) {
            $table_fields[$i]['Field_html'] = htmlspecialchars($table_fields[$i]['Field']);
            $table_fields[$i]['Field_md5']  = md5($table_fields[$i]['Field']);
            // True_Type contains only the type (stops at first bracket)
            $table_fields[$i]['True_Type']  = preg_replace('@\(.*@s', '', $table_fields[$i]['Type']);

            // d a t e t i m e
            //
            // Current date should not be set as default if the field is NULL
            // for the current row, but do not put here the current datetime
            // if there is a default value (the real default value will be set
            // in the Default value logic below)

            // Note: (tested in MySQL 4.0.16): when lang is some UTF-8,
            // $field['Default'] is not set if it contains NULL:
            // Array ([Field] => d [Type] => datetime [Null] => YES [Key] => [Extra] => [True_Type] => datetime)
            // but, look what we get if we switch to iso: (Default is NULL)
            // Array ([Field] => d [Type] => datetime [Null] => YES [Key] => [Default] => [Extra] => [True_Type] => datetime)
            // so I force a NULL into it (I don't think it's possible
            // to have an empty default value for DATETIME)
            // then, the "if" after this one will work
            if ($table_fields[$i]['Type'] == 'datetime'
             && ! isset($table_fields[$i]['Default'])
             && isset($table_fields[$i]['Null'])
             && $table_fields[$i]['Null'] == 'YES') {
                $table_fields[$i]['Default'] = null;
            }

            $table_fields[$i]['len']
                = preg_match('@float|double@', $table_fields[$i]['Type']) ? 100 : -1;


            if (isset($comments_map[$table_fields[$i]['Field']])) {
                $table_fields[$i]['Field_title'] = '<span style="border-bottom: 1px dashed black;" title="'
                    . htmlspecialchars($comments_map[$table_fields[$i]['Field']]) . '">'
                    . $table_fields[$i]['Field_html'] . '</span>';
            } else {
                $table_fields[$i]['Field_title'] = $table_fields[$i]['Field_html'];
            }

            // The type column.
            // Fix for bug #3152931 'ENUM and SET cannot have "Binary" option'
            // If check to ensure types such as "enum('one','two','binary',..)" or
            // "enum('one','two','varbinary',..)" are not categorized as binary.
            if (stripos($table_fields[$i]['Type'], 'binary') === 0
            || stripos($table_fields[$i]['Type'], 'varbinary') === 0) {
                $table_fields[$i]['is_binary'] = stristr($table_fields[$i]['Type'], 'binary');
            } else {
                $table_fields[$i]['is_binary'] = false;
            }

            // If check to ensure types such as "enum('one','two','blob',..)" or
            // "enum('one','two','tinyblob',..)" etc. are not categorized as blob.
            if (stripos($table_fields[$i]['Type'], 'blob') === 0
            || stripos($table_fields[$i]['Type'], 'tinyblob') === 0
            || stripos($table_fields[$i]['Type'], 'mediumblob') === 0
            || stripos($table_fields[$i]['Type'], 'longblob') === 0) {
                $table_fields[$i]['is_blob']   = stristr($table_fields[$i]['Type'], 'blob');
            } else {
                $table_fields[$i]['is_blob'] = false;
            }

            // If check to ensure types such as "enum('one','two','char',..)" or
            // "enum('one','two','varchar',..)" are not categorized as char.
            if (stripos($table_fields[$i]['Type'], 'char') === 0
            || stripos($table_fields[$i]['Type'], 'varchar') === 0) {
                $table_fields[$i]['is_char']   = stristr($table_fields[$i]['Type'], 'char');
            } else {
                $table_fields[$i]['is_char'] = false;
            }

            $table_fields[$i]['first_timestamp'] = false;
            switch ($table_fields[$i]['True_Type']) {
                case 'set':
                    $table_fields[$i]['pma_type'] = 'set';
                    $table_fields[$i]['wrap']  = '';
                    break;
                case 'enum':
                    $table_fields[$i]['pma_type'] = 'enum';
                    $table_fields[$i]['wrap']  = '';
                    break;
                case 'timestamp':
                    if (!$timestamp_seen) {   // can only occur once per table
                        $timestamp_seen  = 1;
                        $table_fields[$i]['first_timestamp'] = true;
                    }
                    $table_fields[$i]['pma_type'] = $table_fields[$i]['Type'];
                    $table_fields[$i]['wrap']  = ' nowrap="nowrap"';
                    break;

                default:
                    $table_fields[$i]['pma_type'] = $table_fields[$i]['Type'];
                    $table_fields[$i]['wrap']  = ' nowrap="nowrap"';
                    break;
            }
        }
        $field = $table_fields[$i];
        $extracted_fieldspec = PMA_extractFieldSpec($field['Type']);

        if (-1 === $field['len']) {
            $field['len'] = PMA_DBI_field_len($vresult, $i);
            // length is unknown for geometry fields, make enough space to edit very simple WKTs
            if (-1 === $field['len']) {
                $field['len'] = 30;
            }
        }
        //Call validation when the form submited...
        $unnullify_trigger = $chg_evt_handler . "=\"return verificationsAfterFieldChange('". PMA_escapeJsString($field['Field_md5']) . "', '"
            . PMA_escapeJsString($jsvkey) . "','".$field['pma_type']."')\"";

        // Use an MD5 as an array index to avoid having special characters in the name atttibute (see bug #1746964 )
        $field_name_appendix =  $vkey . '[' . $field['Field_md5'] . ']';

        if ($field['Type'] == 'datetime'
         && ! isset($field['Default'])
         && ! is_null($field['Default'])
         && ($insert_mode || ! isset($vrow[$field['Field']]))) {
            // INSERT case or
            // UPDATE case with an NULL value
            $vrow[$field['Field']] = date('Y-m-d H:i:s', time());
        }
        ?>
        <tr class="noclick <?php echo $odd_row ? 'odd' : 'even'; ?>">
            <td <?php echo ($cfg['LongtextDoubleTextarea'] && strstr($field['True_Type'], 'longtext') ? 'rowspan="2"' : ''); ?> align="center">
                <?php echo $field['Field_title']; ?>
                <input type="hidden" name="fields_name<?php echo $field_name_appendix; ?>" value="<?php echo $field['Field_html']; ?>"/>
            </td>
<?php if ($cfg['ShowFieldTypesInDataEditView']) { ?>
             <td align="center"<?php echo $field['wrap']; ?>><span class="column_type"><?php echo $field['pma_type']; ?></span>
             </td>

         <?php } //End if

        // Get a list of GIS data types.
        $gis_data_types = PMA_getGISDatatypes();

        // Prepares the field value
        $real_null_value = false;
        $special_chars_encoded = '';
        if (isset($vrow)) {
            // (we are editing)
            if (is_null($vrow[$field['Field']])) {
                $real_null_value = true;
                $vrow[$field['Field']]    = '';
                $special_chars   = '';
                $data            = $vrow[$field['Field']];
            } elseif ($field['True_Type'] == 'bit') {
                $special_chars = PMA_printable_bit_value($vrow[$field['Field']], $extracted_fieldspec['spec_in_brackets']);
            } elseif (in_array($field['True_Type'], $gis_data_types)) {
                // Convert gis data to Well Know Text format
                $vrow[$field['Field']] = PMA_asWKT($vrow[$field['Field']], true);
                $special_chars = htmlspecialchars($vrow[$field['Field']]);
            } else {
                // special binary "characters"
                if ($field['is_binary'] || ($field['is_blob'] && ! $cfg['ProtectBinary'])) {
                    if ($_SESSION['tmp_user_values']['display_binary_as_hex'] && $cfg['ShowFunctionFields']) {
                        $vrow[$field['Field']] = bin2hex($vrow[$field['Field']]);
                        $field['display_binary_as_hex'] = true;
                    } else {
                        $vrow[$field['Field']] = PMA_replace_binary_contents($vrow[$field['Field']]);
                    }
                } // end if
                $special_chars   = htmlspecialchars($vrow[$field['Field']]);

            //We need to duplicate the first \n or otherwise we will lose the first newline entered in a VARCHAR or TEXT column
                $special_chars_encoded = PMA_duplicateFirstNewline($special_chars);

                $data            = $vrow[$field['Field']];
            } // end if... else...

            //when copying row, it is useful to empty auto-increment column to prevent duplicate key error
            if (isset($default_action) && $default_action === 'insert') {
                if ($field['Key'] === 'PRI' && strpos($field['Extra'], 'auto_increment') !== false) {
                    $data = $special_chars_encoded = $special_chars = null;
                }
            }
            // If a timestamp field value is not included in an update
            // statement MySQL auto-update it to the current timestamp;
            // however, things have changed since MySQL 4.1, so
            // it's better to set a fields_prev in this situation
            $backup_field  = '<input type="hidden" name="fields_prev'
                . $field_name_appendix . '" value="'
                . htmlspecialchars($vrow[$field['Field']]) . '" />';
        } else {
            // (we are inserting)
            // display default values
            if (! isset($field['Default'])) {
                $field['Default'] = '';
                $real_null_value          = true;
                $data                     = '';
            } else {
                $data                     = $field['Default'];
            }
            if ($field['True_Type'] == 'bit') {
                $special_chars = PMA_convert_bit_default_value($field['Default']);
            } else {
                $special_chars = htmlspecialchars($field['Default']);
            }
            $backup_field  = '';
            $special_chars_encoded = PMA_duplicateFirstNewline($special_chars);
            // this will select the UNHEX function while inserting
            if (($field['is_binary'] || ($field['is_blob'] && ! $cfg['ProtectBinary'])) && $_SESSION['tmp_user_values']['display_binary_as_hex'] && $cfg['ShowFunctionFields']) {
                $field['display_binary_as_hex'] = true;
            }
        }

        $idindex  = ($o_rows * $fields_cnt) + $i + 1;
        $tabindex = $idindex;

        // Get a list of data types that are not yet supported.
        $no_support_types = PMA_unsupportedDatatypes();

        // The function column
        // -------------------
        // We don't want binary data to be destroyed
        // Note: from the MySQL manual: "BINARY doesn't affect how the column is
        //       stored or retrieved" so it does not mean that the contents is
        //       binary
        if ($cfg['ShowFunctionFields']) {
            if (($cfg['ProtectBinary'] && $field['is_blob'] && !$is_upload)
             || ($cfg['ProtectBinary'] == 'all' && $field['is_binary'])) {
                echo '        <td align="center">' . __('Binary') . '</td>' . "\n";
            } elseif (strstr($field['True_Type'], 'enum') || strstr($field['True_Type'], 'set') || in_array($field['pma_type'], $no_support_types)) {
                echo '        <td align="center">--</td>' . "\n";
            } else {
                ?>
            <td>
                <select name="funcs<?php echo $field_name_appendix; ?>" <?php echo $unnullify_trigger; ?> tabindex="<?php echo ($tabindex + $tabindex_for_function); ?>" id="field_<?php echo $idindex; ?>_1">
<?php
    echo PMA_getFunctionsForField($field, $insert_mode);
?>
                </select>
            </td>
                <?php
            }
        } // end if ($cfg['ShowFunctionFields'])


        // The null column
        // ---------------
        $foreignData = PMA_getForeignData($foreigners, $field['Field'], false, '', '');
        echo '        <td>' . "\n";
        if ($field['Null'] == 'YES') {
            echo '            <input type="hidden" name="fields_null_prev' . $field_name_appendix . '"';
            if ($real_null_value && !$field['first_timestamp']) {
                echo ' value="on"';
            }
            echo ' />' . "\n";

            echo '            <input type="checkbox" class="checkbox_null" tabindex="' . ($tabindex + $tabindex_for_null) . '"'
                 . ' name="fields_null' . $field_name_appendix . '"';
            if ($real_null_value && !$field['first_timestamp']) {
                echo ' checked="checked"';
            }
            echo ' id="field_' . ($idindex) . '_2" />';

            // nullify_code is needed by the js nullify() function
            if (strstr($field['True_Type'], 'enum')) {
                if (strlen($field['Type']) > 20) {
                    $nullify_code = '1';
                } else {
                    $nullify_code = '2';
                }
            } elseif (strstr($field['True_Type'], 'set')) {
                $nullify_code = '3';
            } elseif ($foreigners && isset($foreigners[$field['Field']]) && $foreignData['foreign_link'] == false) {
                // foreign key in a drop-down
                $nullify_code = '4';
            } elseif ($foreigners && isset($foreigners[$field['Field']]) && $foreignData['foreign_link'] == true) {
                // foreign key with a browsing icon
                $nullify_code = '6';
            } else {
                $nullify_code = '5';
            }
            // to be able to generate calls to nullify() in jQuery
            echo '<input type="hidden" class="nullify_code" name="nullify_code' . $field_name_appendix . '" value="' . $nullify_code . '" />';
            echo '<input type="hidden" class="hashed_field" name="hashed_field' . $field_name_appendix . '" value="' .  $field['Field_md5'] . '" />';
            echo '<input type="hidden" class="multi_edit" name="multi_edit' . $field_name_appendix . '" value="' . PMA_escapeJsString($vkey) . '" />';
        }
        echo '        </td>' . "\n";

        // The value column (depends on type)
        // ----------------
        // See bug #1667887 for the reason why we don't use the maxlength
        // HTML attribute

        echo '        <td>' . "\n";
        // Will be used by js/tbl_change.js to set the default value
        // for the "Continue insertion" feature
        echo '<span class="default_value hide">' . $special_chars . '</span>';
        if ($foreignData['foreign_link'] == true) {
            echo $backup_field . "\n";
            ?>
            <input type="hidden" name="fields_type<?php echo $field_name_appendix; ?>"
                value="foreign" />
            <input type="text" name="fields<?php echo $field_name_appendix; ?>"
                class="textfield" <?php echo $unnullify_trigger; ?>
                tabindex="<?php echo ($tabindex + $tabindex_for_value); ?>"
                id="field_<?php echo ($idindex); ?>_3"
                value="<?php echo htmlspecialchars($data); ?>" />
                <a class="hide foreign_values_anchor" target="_blank" onclick="window.open(this.href, 'foreigners', 'width=640,height=240,scrollbars=yes,resizable=yes'); return false;" href="browse_foreigners.php?<?php echo PMA_generate_common_url($db, $table); ?>&amp;field=<?php echo PMA_escapeJsString(urlencode($field['Field']) . $rownumber_param); ?>"><?php echo str_replace("'", "\'", $titles['Browse']); ?></a>
            <?php
        } elseif (is_array($foreignData['disp_row'])) {
            echo $backup_field . "\n";
            ?>
            <input type="hidden" name="fields_type<?php echo $field_name_appendix; ?>"
                value="foreign" />
            <select name="fields<?php echo $field_name_appendix; ?>"
                <?php echo $unnullify_trigger; ?>
                class="textfield"
                tabindex="<?php echo ($tabindex + $tabindex_for_value); ?>"
                id="field_<?php echo ($idindex); ?>_3">
                <?php echo PMA_foreignDropdown($foreignData['disp_row'], $foreignData['foreign_field'], $foreignData['foreign_display'], $data, $cfg['ForeignKeyMaxLimit']); ?>
            </select>
            <?php
                // still needed? :
            unset($foreignData['disp_row']);
        } elseif ($cfg['LongtextDoubleTextarea'] && strstr($field['pma_type'], 'longtext')) {
            ?>
            &nbsp;</td>
        </tr>
        <tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
            <td colspan="5" align="right">
                <?php echo $backup_field . "\n"; ?>
                <textarea name="fields<?php echo $field_name_appendix; ?>"
                    rows="<?php echo ($cfg['TextareaRows']*2); ?>"
                    cols="<?php echo ($cfg['TextareaCols']*2); ?>"
                    dir="<?php echo $text_dir; ?>"
                    id="field_<?php echo ($idindex); ?>_3"
                    <?php echo $unnullify_trigger; ?>
                    tabindex="<?php echo ($tabindex + $tabindex_for_value); ?>"
                    ><?php echo $special_chars_encoded; ?></textarea>
          <?php
        } elseif (strstr($field['pma_type'], 'text')) {
            echo $backup_field . "\n";
            ?>
                <textarea name="fields<?php echo $field_name_appendix; ?>"
                    rows="<?php echo $cfg['TextareaRows']; ?>"
                    cols="<?php echo $cfg['TextareaCols']; ?>"
                    dir="<?php echo $text_dir; ?>"
                    id="field_<?php echo ($idindex); ?>_3"
                    <?php echo $unnullify_trigger; ?>
                    tabindex="<?php echo ($tabindex + $tabindex_for_value); ?>"
                    ><?php echo $special_chars_encoded; ?></textarea>
            <?php
            echo "\n";
            if (strlen($special_chars) > 32000) {
                echo "        </td>\n";
                echo '        <td>' . __('Because of its length,<br /> this column might not be editable');
            }
        } elseif ($field['pma_type'] == 'enum') {
            if (! isset($table_fields[$i]['values'])) {
                $table_fields[$i]['values'] = array();
                foreach ($extracted_fieldspec['enum_set_values'] as $val) {
                    $table_fields[$i]['values'][] = array(
                        'plain' => $val,
                        'html'  => htmlspecialchars($val),
                    );
                }
            }
            $field_enum_values = $table_fields[$i]['values'];
            ?>
                <input type="hidden" name="fields_type<?php echo $field_name_appendix; ?>" value="enum" />
                <input type="hidden" name="fields<?php echo $field_name_appendix; ?>" value="" />
            <?php
            echo "\n" . '            ' . $backup_field . "\n";

            // show dropdown or radio depend on length
            if (strlen($field['Type']) > 20) {
                ?>
                <select name="fields<?php echo $field_name_appendix; ?>"
                    <?php echo $unnullify_trigger; ?>
                    class="textfield"
                    tabindex="<?php echo ($tabindex + $tabindex_for_value); ?>"
                    id="field_<?php echo ($idindex); ?>_3">
                    <option value="">&nbsp;</option>
                <?php
                echo "\n";

                foreach ($field_enum_values as $enum_value) {
                    echo '                ';
                    echo '<option value="' . $enum_value['html'] . '"';
                    if ($data == $enum_value['plain']
                     || ($data == ''
                      && (! isset($where_clause) || $field['Null'] != 'YES')
                      && isset($field['Default'])
                      && $enum_value['plain'] == $field['Default'])) {
                        echo ' selected="selected"';
                    }
                    echo '>' . $enum_value['html'] . '</option>' . "\n";
                } // end for

                ?>
                </select>
                <?php
            } else {
                $j = 0;
                foreach ($field_enum_values as $enum_value) {
                    echo '            ';
                    echo '<input type="radio" name="fields' . $field_name_appendix . '"';
                    echo ' class="textfield"';
                    echo ' value="' . $enum_value['html'] . '"';
                    echo ' id="field_' . ($idindex) . '_3_'  . $j . '"';
                    echo $unnullify_trigger;
                    if ($data == $enum_value['plain']
                     || ($data == ''
                      && (! isset($where_clause) || $field['Null'] != 'YES')
                      && isset($field['Default'])
                      && $enum_value['plain'] == $field['Default'])) {
                        echo ' checked="checked"';
                    }
                    echo ' tabindex="' . ($tabindex + $tabindex_for_value) . '" />';
                    echo '<label for="field_' . $idindex . '_3_' . $j . '">'
                        . $enum_value['html'] . '</label>' . "\n";
                    $j++;
                } // end for
            } // end else
        } elseif ($field['pma_type'] == 'set') {
            if (! isset($table_fields[$i]['values'])) {
                $table_fields[$i]['values'] = array();
                foreach ($extracted_fieldspec['enum_set_values'] as $val) {
                    $table_fields[$i]['values'][] = array(
                        'plain' => $val,
                        'html'  => htmlspecialchars($val),
                    );
                }
                $table_fields[$i]['select_size'] = min(4, count($table_fields[$i]['values']));
            }
            $field_set_values = $table_fields[$i]['values'];
            $select_size = $table_fields[$i]['select_size'];

            $vset = array_flip(explode(',', $data));
            echo $backup_field . "\n";
            ?>
                <input type="hidden" name="fields_type<?php echo $field_name_appendix; ?>" value="set" />
                <select name="fields<?php echo $field_name_appendix . '[]'; ?>"
                    class="textfield"
                    size="<?php echo $select_size; ?>"
                    multiple="multiple" <?php echo $unnullify_trigger; ?>
                    tabindex="<?php echo ($tabindex + $tabindex_for_value); ?>"
                    id="field_<?php echo ($idindex); ?>_3">
            <?php
            foreach ($field_set_values as $field_set_value) {
                echo '                ';
                echo '<option value="' . $field_set_value['html'] . '"';
                if (isset($vset[$field_set_value['plain']])) {
                    echo ' selected="selected"';
                }
                echo '>' . $field_set_value['html'] . '</option>' . "\n";
            } // end for
            ?>
                </select>
            <?php
        // We don't want binary data destroyed
        } elseif ($field['is_binary'] || $field['is_blob']) {
            if (($cfg['ProtectBinary'] && $field['is_blob'])
                || ($cfg['ProtectBinary'] == 'all' && $field['is_binary'])
            ) {
                echo "\n";
                    // for blobstreaming
                if (PMA_BS_IsTablePBMSEnabled($db, $table, $tbl_type)
                    && PMA_BS_IsPBMSReference($data, $db)
                ) {
                    echo '<input type="hidden" name="remove_blob_ref_' . $field['Field_md5'] . $vkey . '" value="' . $data . '" />';
                    echo '<input type="checkbox" name="remove_blob_repo_' . $field['Field_md5'] . $vkey . '" /> ' . __('Remove BLOB Repository Reference') . "<br />";
                    echo PMA_BS_CreateReferenceLink($data, $db);
                    echo "<br />";
                } else {
                    echo __('Binary - do not edit');
                    if (isset($data)) {
                        $data_size = PMA_formatByteDown(strlen(stripslashes($data)), 3, 1);
                        echo ' ('. $data_size [0] . ' ' . $data_size[1] . ')';
                        unset($data_size);
                    }
                    echo "\n";
                }   // end if (PMA_BS_IsTablePBMSEnabled($db, $table, $tbl_type) && PMA_BS_IsPBMSReference($data, $db))
                ?>
                <input type="hidden" name="fields_type<?php echo $field_name_appendix; ?>" value="protected" />
                <input type="hidden" name="fields<?php echo $field_name_appendix; ?>" value="" />
                <?php
            } elseif ($field['is_blob']) {
                echo "\n";
                echo $backup_field . "\n";
                ?>
                <textarea name="fields<?php echo $field_name_appendix; ?>"
                    rows="<?php echo $cfg['TextareaRows']; ?>"
                    cols="<?php echo $cfg['TextareaCols']; ?>"
                    dir="<?php echo $text_dir; ?>"
                    id="field_<?php echo ($idindex); ?>_3"
                    <?php echo $unnullify_trigger; ?>
                    tabindex="<?php echo ($tabindex + $tabindex_for_value); ?>"
                    ><?php echo $special_chars_encoded; ?></textarea>
                <?php

            } else {
                // field size should be at least 4 and max $cfg['LimitChars']
                $fieldsize = min(max($field['len'], 4), $cfg['LimitChars']);
                echo "\n";
                echo $backup_field . "\n";
                ?>
                <input type="text" name="fields<?php echo $field_name_appendix; ?>"
                    value="<?php echo $special_chars; ?>" size="<?php echo $fieldsize; ?>"
                    class="textfield" <?php echo $unnullify_trigger; ?>
                    tabindex="<?php echo ($tabindex + $tabindex_for_value); ?>"
                    id="field_<?php echo ($idindex); ?>_3" />
                <?php
            } // end if...elseif...else

            // Upload choice (only for BLOBs because the binary
            // attribute does not imply binary contents)
            // (displayed whatever value the ProtectBinary has)

            if ($is_upload && $field['is_blob']) {
                // check if field type is of longblob and  if the table is PBMS enabled.
                if (($field['pma_type'] == "longblob")
                    && PMA_BS_IsTablePBMSEnabled($db, $table, $tbl_type)
                ) {
                    echo '<br />';
                    echo '<input type="checkbox" name="upload_blob_repo' . $vkey . '[' . $field['Field_md5'] . ']" /> ' .  __('Upload to BLOB repository');
                }

                echo '<br />';
                echo '<input type="file" name="fields_upload' . $vkey . '[' . $field['Field_md5'] . ']" class="textfield" id="field_' . $idindex . '_3" size="10" ' . $unnullify_trigger . '/>&nbsp;';

                // find maximum upload size, based on field type
                /**
                 * @todo with functions this is not so easy, as you can basically
                 * process any data with function like MD5
                 */
                $max_field_sizes = array(
                    'tinyblob'   =>        '256',
                    'blob'       =>      '65536',
                    'mediumblob' =>   '16777216',
                    'longblob'   => '4294967296'); // yeah, really

                $this_field_max_size = $max_upload_size; // from PHP max
                if ($this_field_max_size > $max_field_sizes[$field['pma_type']]) {
                   $this_field_max_size = $max_field_sizes[$field['pma_type']];
                }
                echo PMA_displayMaximumUploadSize($this_field_max_size) . "\n";
                // do not generate here the MAX_FILE_SIZE, because we should
                // put only one in the form to accommodate the biggest field
                if ($this_field_max_size > $biggest_max_file_size) {
                    $biggest_max_file_size = $this_field_max_size;
                }
            }

            if (!empty($cfg['UploadDir'])) {
                $files = PMA_getFileSelectOptions(PMA_userDir($cfg['UploadDir']));
                if ($files === false) {
                    echo '        <font color="red">' . __('Error') . '</font><br />' . "\n";
                    echo '        ' . __('The directory you set for upload work cannot be reached') . "\n";
                } elseif (!empty($files)) {
                    echo "<br />\n";
                    echo '    <i>' . __('Or') . '</i>' . ' ' . __('web server upload directory') . ':<br />' . "\n";
                    echo '        <select size="1" name="fields_uploadlocal' . $vkey . '[' . $field['Field_md5'] . ']">' . "\n";
                    echo '            <option value="" selected="selected"></option>' . "\n";
                    echo $files;
                    echo '        </select>' . "\n";
                }
            } // end if (web-server upload directory)
        // end elseif (binary or blob)
        } elseif (! in_array($field['pma_type'], $no_support_types)) {
            // ignore this column to avoid changing it
            if ($field['is_char']) {
                $fieldsize = $extracted_fieldspec['spec_in_brackets'];
            } else {
            /**
             * This case happens for example for INT or DATE columns;
             * in these situations, the value returned in $field['len']
             * seems appropriate.
             */
                $fieldsize = $field['len'];
            }
            $fieldsize = min(max($fieldsize, $cfg['MinSizeForInputField']), $cfg['MaxSizeForInputField']);
            echo $backup_field . "\n";
            if ($field['is_char']
                && ($cfg['CharEditing'] == 'textarea'
                || strpos($data, "\n") !== false)
            ) {
                echo "\n";
                ?>
                <textarea class="char" name="fields<?php echo $field_name_appendix; ?>"
                    rows="<?php echo $cfg['CharTextareaRows']; ?>"
                    cols="<?php echo $cfg['CharTextareaCols']; ?>"
                    dir="<?php echo $text_dir; ?>"
                    id="field_<?php echo ($idindex); ?>_3"
                    <?php echo $unnullify_trigger; ?>
                    tabindex="<?php echo ($tabindex + $tabindex_for_value); ?>"
                    ><?php echo $special_chars_encoded; ?></textarea>
                <?php
            } else {
                $the_class = 'textfield';
                if ($field['pma_type'] == 'date') {
                    $the_class .= ' datefield';
                } elseif ($field['pma_type'] == 'datetime'
                    || substr($field['pma_type'], 0, 9) == 'timestamp'
                ) {
                    $the_class .= ' datetimefield';
                }
                ?>
                <input type="text" name="fields<?php echo $field_name_appendix; ?>"
                    value="<?php echo $special_chars; ?>" size="<?php echo $fieldsize; ?>"
                    class="<?php echo $the_class; ?>" <?php echo $unnullify_trigger; ?>
                    tabindex="<?php echo ($tabindex + $tabindex_for_value); ?>"
                    id="field_<?php echo ($idindex); ?>_3" />
                <?php
                if ($field['Extra'] == 'auto_increment') {
                    ?>
                    <input type="hidden" name="auto_increment<?php echo $field_name_appendix; ?>" value="1" />
                    <?php
                } // end if
                if (substr($field['pma_type'], 0, 9) == 'timestamp') {
                    ?>
                    <input type="hidden" name="fields_type<?php echo $field_name_appendix; ?>" value="timestamp" />
                    <?php
                }
                if (substr($field['pma_type'], 0, 8) == 'datetime') {
                    ?>
                    <input type="hidden" name="fields_type<?php echo $field_name_appendix; ?>" value="datetime" />
                    <?php
                }
                if ($field['True_Type'] == 'bit') {
                    ?>
                    <input type="hidden" name="fields_type<?php echo $field_name_appendix; ?>" value="bit" />
                    <?php
                }
                if ($field['pma_type'] == 'date'
                    || $field['pma_type'] == 'datetime'
                    || substr($field['pma_type'], 0, 9) == 'timestamp'
                ) {
                    // the _3 suffix points to the date field
                    // the _2 suffix points to the corresponding NULL checkbox
                    // in dateFormat, 'yy' means the year with 4 digits
                }
            }
        }
        if (in_array($field['pma_type'], $gis_data_types)) {
            $data_val = isset($vrow[$field['Field']]) ? $vrow[$field['Field']] : '';
            $_url_params = array(
                'field' => $field['Field_title'],
                'value' => $data_val,
             );
            if ($field['pma_type'] != 'geometry') {
                $_url_params = $_url_params + array('gis_data[gis_type]' => strtoupper($field['pma_type']));
            }
            $edit_url = 'gis_data_editor.php' . PMA_generate_common_url($_url_params);
            $edit_str = PMA_getIcon('b_edit.png', __('Edit/Insert'));
            echo('<span class="open_gis_editor">');
            echo(PMA_linkOrButton($edit_url, $edit_str, array(), false, false, '_blank'));
            echo('</span>');
        }
        ?>
            </td>
        </tr>
        <?php
        $odd_row = !$odd_row;
    } // end for
    $o_rows++;
    echo '  </tbody></table><br />';
} // end foreach on multi-edit
?>
    <div id="gis_editor"></div><div id="popup_background"></div>
    <br />
    <fieldset id="actions_panel">
    <table border="0" cellpadding="5" cellspacing="0">
    <tr>
        <td valign="middle" nowrap="nowrap">
            <select name="submit_type" class="control_at_footer" tabindex="<?php echo ($tabindex + $tabindex_for_value + 1); ?>">
<?php
if (isset($where_clause)) {
    ?>
                <option value="save"><?php echo __('Save'); ?></option>
    <?php
}
    ?>
                <option value="insert"><?php echo __('Insert as new row'); ?></option>
                <option value="insertignore"><?php echo __('Insert as new row and ignore errors'); ?></option>
                <option value="showinsert"><?php echo __('Show insert query'); ?></option>
            </select>
    <?php
echo "\n";

if (! isset($after_insert)) {
    $after_insert = 'back';
}
?>
        </td>
        <td valign="middle">
            &nbsp;&nbsp;&nbsp;<strong><?php echo __('and then'); ?></strong>&nbsp;&nbsp;&nbsp;
        </td>
        <td valign="middle" nowrap="nowrap">
            <select name="after_insert">
                <option value="back" <?php echo ($after_insert == 'back' ? 'selected="selected"' : ''); ?>><?php echo __('Go back to previous page'); ?></option>
                <option value="new_insert" <?php echo ($after_insert == 'new_insert' ? 'selected="selected"' : ''); ?>><?php echo __('Insert another new row'); ?></option>
<?php
if (isset($where_clause)) {
    ?>
                <option value="same_insert" <?php echo ($after_insert == 'same_insert' ? 'selected="selected"' : ''); ?>><?php echo __('Go back to this page'); ?></option>
    <?php
    // If we have just numeric primary key, we can also edit next
    // in 2.8.2, we were looking for `field_name` = numeric_value
    //if (preg_match('@^[\s]*`[^`]*` = [0-9]+@', $where_clause)) {
    // in 2.9.0, we are looking for `table_name`.`field_name` = numeric_value
    if ($found_unique_key && preg_match('@^[\s]*`[^`]*`[\.]`[^`]*` = [0-9]+@', $where_clause)) {
        ?>
    <option value="edit_next" <?php echo ($after_insert == 'edit_next' ? 'selected="selected"' : ''); ?>><?php echo __('Edit next row'); ?></option>
        <?php
    }
}
?>
            </select>
        </td>
    </tr>

    <tr>
        <td>
<?php echo PMA_showHint(__('Use TAB key to move from value to value, or CTRL+arrows to move anywhere')); ?>
        </td>
        <td colspan="3" align="right" valign="middle">
            <input type="submit" class="control_at_footer" value="<?php echo __('Go'); ?>" tabindex="<?php echo ($tabindex + $tabindex_for_value + 6); ?>" id="buttonYes" />
            <input type="reset" class="control_at_footer" value="<?php echo __('Reset'); ?>" tabindex="<?php echo ($tabindex + $tabindex_for_value + 7); ?>" />
        </td>
    </tr>
    </table>
    </fieldset>
    <?php if ($biggest_max_file_size > 0) {
            echo '        ' . PMA_generateHiddenMaxFileSize($biggest_max_file_size) . "\n";
          } ?>
</form>
<?php
if ($insert_mode) {
?>
<!-- Continue insertion form -->
<form id="continueForm" method="post" action="tbl_replace.php" name="continueForm" >
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <input type="hidden" name="goto" value="<?php echo htmlspecialchars($GLOBALS['goto']); ?>" />
    <input type="hidden" name="err_url" value="<?php echo htmlspecialchars($err_url); ?>" />
    <input type="hidden" name="sql_query" value="<?php echo htmlspecialchars($sql_query); ?>" />
<?php
    if (isset($where_clauses)) {
        foreach ($where_clause_array as $key_id => $where_clause) {
            echo '<input type="hidden" name="where_clause[' . $key_id . ']" value="' . htmlspecialchars(trim($where_clause)) . '" />'. "\n";
        }
    }
    $tmp = '<select name="insert_rows" id="insert_rows">' . "\n";
    $option_values = array(1,2,5,10,15,20,30,40);
    foreach ($option_values as $value) {
        $tmp .= '<option value="' . $value . '"';
        if ($value == $cfg['InsertRows']) {
            $tmp .= ' selected="selected"';
        }
        $tmp .= '>' . $value . '</option>' . "\n";
    }
    $tmp .= '</select>' . "\n";
    echo "\n" . sprintf(__('Continue insertion with %s rows'), $tmp);
    unset($tmp);
    echo '<noscript><input type="submit" value="' . __('Go') . '" /></noscript>' . "\n";
    echo '</form>' . "\n";
}

/**
 * Displays the footer
 */
require './libraries/footer.inc.php';
?>
