<?php
/* vim: expandtab sw=4 ts=4 sts=4: */
/**
 * display information about indexes in a table
 *
 * @version $Id$
 */

/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/tbl_indexes.lib.php';

/**
 * Ensures the db & table are valid, then loads headers and gets indexes
 * informations.
 * Skipped if this script is called by "tbl_sql.php"
 */
if (!defined('PMA_IDX_INCLUDED')) {
    // Not a valid db name -> back to the welcome page
    if (strlen($db)) {
        $is_db = PMA_DBI_select_db($db);
    }
    if (!strlen($db) || !$is_db) {
        $uri_params = array('reload' => '1');
        if (isset($message)) {
            $uri_params['message'] = $message;
        }
        PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'main.php'
            . PMA_generate_common_url($uri_params, '&'));
        exit;
    }
    // Not a valid table name -> back to the default db sub-page
    if (strlen($table)) {
        $is_table = PMA_DBI_query('SHOW TABLES LIKE \''
            . PMA_sqlAddslashes($table, TRUE) . '\'', null, PMA_DBI_QUERY_STORE);
    }
    if (! strlen($table)
      || !($is_table && PMA_DBI_num_rows($is_table))) {
        $uri_params = array('reload' => '1', 'db' => $db);
        if (isset($message)) {
            $uri_params['message'] = $message;
        }
        PMA_sendHeaderLocation($cfg['PmaAbsoluteUri']
            . $cfg['DefaultTabDatabase']
            . PMA_generate_common_url($uri_params, '&'));
        exit;
    } elseif (isset($is_table)) {
        PMA_DBI_free_result($is_table);
    }

    // Displays headers (if needed)
    $GLOBALS['js_include'][] = 'functions.js';
    $GLOBALS['js_include'][] = 'indexes.js';
    require_once './libraries/header.inc.php';
} // end if


/**
 * Gets fields and indexes informations
 */
if (!defined('PMA_IDX_INCLUDED')) {
    $err_url_0 = 'db_sql.php?' . PMA_generate_common_url($db);
}

//  Gets table keys and store them in arrays
$indexes      = array();
$indexes_info = array();
$indexes_data = array();
// keys had already been grabbed in "tbl_sql.php"
if (!defined('PMA_IDX_INCLUDED')) {
    $ret_keys = PMA_get_indexes($table, $err_url_0);
}

PMA_extract_indexes($ret_keys, $indexes, $indexes_info, $indexes_data);

// Get fields and stores their name/type
// fields had already been grabbed in "tbl_sql.php"
if (!defined('PMA_IDX_INCLUDED')) {
    $fields_rs   = PMA_DBI_query('SHOW FIELDS FROM '
        . PMA_backquote($table) . ';');
    $save_row   = array();
    while ($row = PMA_DBI_fetch_assoc($fields_rs)) {
        $save_row[] = $row;
    }
}

$fields_names           = array();
$fields_types           = array();
foreach ($save_row AS $saved_row_key => $row) {
    $fields_names[]     = $row['Field'];
    // loic1: set or enum types: slashes single quotes inside options
    if (preg_match('@^(set|enum)\((.+)\)$@i', $row['Type'], $tmp)) {
        $tmp[2]         = substr(preg_replace('@([^,])\'\'@', '\\1\\\'',
            ',' . $tmp[2]), 1);
        $fields_types[] = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';
    } else {
        $fields_types[] = $row['Type'];
    }
} // end while

if ($fields_rs) {
    PMA_DBI_free_result($fields_rs);
}


/**
 * Do run the query to build the new index and moves back to
 * "tbl_sql.php"
 */
if (!defined('PMA_IDX_INCLUDED')
    && (isset($index) && isset($do_save_data))) {

    $err_url     = 'tbl_indexes.php?' . PMA_generate_common_url($db, $table);
    if (empty($old_index)) {
        $err_url .= '&amp;create_index=1&amp;idx_num_fields=' . $idx_num_fields;
    } else {
        $err_url .= '&amp;index=' . urlencode($old_index);
    }

    if ($index_type == 'PRIMARY') {
        if ($index == '') {
            $index = 'PRIMARY';
        } elseif ($index != 'PRIMARY') {
            PMA_mysqlDie($strPrimaryKeyName, '', FALSE, $err_url);
        }
    } elseif ($index == 'PRIMARY') {
         PMA_mysqlDie($strCantRenameIdxToPrimary, '', FALSE, $err_url);
    }


    // $sql_query is the one displayed in the query box
    $sql_query         = 'ALTER TABLE ' . PMA_backquote($table);

    // Drops the old index
    if (!empty($old_index)) {
        if ($old_index == 'PRIMARY') {
            $sql_query .= ' DROP PRIMARY KEY,';
        } else {
            $sql_query .= ' DROP INDEX ' . PMA_backquote($old_index) .',';
        }
    } // end if

    // Builds the new one
    switch ($index_type) {
        case 'PRIMARY':
            $sql_query .= ' ADD PRIMARY KEY (';
            break;
        case 'FULLTEXT':
            $sql_query .= ' ADD FULLTEXT '
                . (empty($index) ? '' : PMA_backquote($index)) . ' (';
            break;
        case 'UNIQUE':
            $sql_query .= ' ADD UNIQUE '
                . (empty($index) ? '' : PMA_backquote($index)) . ' (';
            break;
        case 'INDEX':
            $sql_query .= ' ADD INDEX '
                . (empty($index) ? '' : PMA_backquote($index)) . ' (';
            break;
    } // end switch
    $index_fields         = '';
    foreach ($column AS $i => $name) {
        if ($name != '--ignore--') {
            $index_fields .= (empty($index_fields) ? '' : ',')
                          . PMA_backquote($name)
                          . (empty($sub_part[$i])
                                ? ''
                                : '(' . $sub_part[$i] . ')');
        }
    } // end while
    if (empty($index_fields)){
        PMA_mysqlDie($strNoIndexPartsDefined, '', FALSE, $err_url);
    } else {
        $sql_query .= $index_fields . ')';
    }

    $result  = PMA_DBI_query($sql_query);
    $message = PMA_Message::success('strTableAlteredSuccessfully');
    $message->addParam($table);

    $active_page = 'tbl_structure.php';
    require './tbl_structure.php';
} // end builds the new index


/**
 * Edits an index or defines a new one
 */
elseif (!defined('PMA_IDX_INCLUDED')
         && (isset($index) || isset($create_index))) {

    // Prepares the form values
    if (!isset($index)) {
        $index                                = '';
    }
    if (!isset($old_index)){
        $old_index                            = $index;
    }
    if (!isset($index_type)) {
        $index_type                           = '';
    }
    if ($old_index == '' || !isset($indexes_info[$old_index])) {
        $edited_index_info['Sequences']       = array();
        $edited_index_data                    = array();
        for ($i = 1; $i <= $idx_num_fields; $i++) {
            $edited_index_info['Sequences'][] = $i;
            $edited_index_data[$i]            = array('Column_name' => '',
                'Sub_part' => '');
        } // end for
        if ($old_index == ''
            && !isset($indexes_info['PRIMARY'])
            && ($index_type == '' || $index_type == 'PRIMARY')) {
            $old_index                        = 'PRIMARY';
        }
    } else {
        $edited_index_info                    = $indexes_info[$old_index];
        $edited_index_data                    = $indexes_data[$old_index];


        if ($edited_index_info['Index_type'] == 'FULLTEXT') {
            $index_type                       = 'FULLTEXT';
        } elseif ($index == 'PRIMARY') {
            $index_type                       = 'PRIMARY';
        } elseif ($edited_index_info['Non_unique'] == '0') {
            $index_type                       = 'UNIQUE';
        } else {
            $index_type                       = 'INDEX';
        }
    } // end if... else...

    if (isset($add_fields)) {
        if (isset($prev_add_fields)) {
            $added_fields += $prev_add_fields;
        }
        $field_cnt = count($edited_index_info['Sequences']) + 1;
        for ($i = $field_cnt; $i < ($added_fields + $field_cnt); $i++) {
            $edited_index_info['Sequences'][] = $i;
            $edited_index_data[$i]            = array('Column_name' => '',
                'Sub_part' => '');
        } // end for

        // Restore entered values
        foreach ($column AS $i => $name) {
            if ($name != '--ignore--'){
                $edited_index_data[$i+1]['Column_name'] = $name;
                $edited_index_data[$i+1]['Sub_part']    = $sub_part[$i];
            }
        } // end while
    } // end if
    // end preparing form values
    ?>

<div style="float: left;">
<form action="./tbl_indexes.php" method="post" name="index_frm"
    onsubmit="if (typeof(this.elements['index'].disabled) != 'undefined') {
        this.elements['index'].disabled = false}">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <?php
    if (isset($create_index)) {
        echo '<input type="hidden" name="create_index" value="1" />' . "\n";
    }
    if (isset($added_fields)) {
        echo '    <input type="hidden" name="prev_add_fields" value="'
            . $added_fields . '" />' . "\n";
    }
    if (isset($idx_num_fields)) {
        echo '    <input type="hidden" name="idx_num_fields" value="'
            . $idx_num_fields . '" />' . "\n";
    }
    ?>
<input type="hidden" name="old_index" value="<?php
    echo (isset($create_index) ? '' : htmlspecialchars($old_index)); ?>" />

<fieldset>
    <legend>
    <?php
    echo (isset($create_index) ? $strCreateIndexTopic : $strModifyIndexTopic);
    ?>
    </legend>

<div class="formelement">
<label for="input_index_name"><?php echo $strIndexName; ?></label>
<input type="text" name="index" id="input_index_name" size="25"
    value="<?php echo htmlspecialchars($index); ?>" onfocus="this.select()" />
</div>

<div class="formelement">
<label for="select_index_type"><?php echo $strIndexType; ?></label>
<select name="index_type" id="select_index_type" onchange="return checkIndexName()">
    <?php
    foreach (PMA_get_indextypes() as $each_index_type) {
        if ($each_index_type === 'PRIMARY'
         && $index !== 'PRIMARY'
         && isset($indexes_info['PRIMARY'])) {
            // skip PRIMARY if there is already one in the table
            continue;
        }
        echo '                '
             . '<option value="' . $each_index_type . '"'
             . (($index_type == $each_index_type) ? ' selected="selected"' : '')
             . '>'. $each_index_type . '</option>' . "\n";
    }
    ?>
</select>
<?php echo PMA_showMySQLDocu('SQL-Syntax', 'ALTER_TABLE'); ?>
</div>


<br class="clearfloat" />
<?php
PMA_Message::warning('strPrimaryKeyWarning')->display();
?>

<table>
<thead>
<tr><th><?php echo $strField; ?></th>
    <th><?php echo $strSize; ?></th>
</tr>
</thead>
<tbody>
    <?php
    $odd_row = true;
    foreach ($edited_index_info['Sequences'] as $seq_index) {
        $add_type     = (is_array($fields_types) && count($fields_types) == count($fields_names));
        $selected     = $edited_index_data[$seq_index]['Column_name'];
        if (!empty($edited_index_data[$seq_index]['Sub_part'])) {
            $sub_part = ' value="' . $edited_index_data[$seq_index]['Sub_part'] . '"';
        } else {
            $sub_part = '';
        }
        ?>

<tr class="<?php echo $odd_row ? 'odd' : 'even'; ?>">
    <td><select name="column[]">
            <option value="--ignore--"
                <?php if ('--ignore--' == $selected) { echo ' selected="selected"'; } ?>>
                -- <?php echo $strIgnore; ?> --</option>
        <?php
        foreach ($fields_names AS $key => $val) {
            if ($index_type != 'FULLTEXT'
                || preg_match('@^(varchar|text|tinytext|mediumtext|longtext)@i', $fields_types[$key])) {
                echo "\n" . '                '
                     . '<option value="' . htmlspecialchars($val) . '"'
                     . (($val == $selected) ? ' selected="selected"' : '') . '>'
                     . htmlspecialchars($val) . (($add_type) ? ' ['
                     . $fields_types[$key] . ']' : '') . '</option>' . "\n";
            }
        } // end foreach $fields_names
        ?>

        </select>
    </td>
    <td><input type="text" size="5" onfocus="this.select()"
            name="sub_part[]"<?php echo $sub_part; ?> />
    </td>
</tr>
        <?php
        $odd_row = !$odd_row;
    } // end foreach $edited_index_info['Sequences']
    ?>
</tbody>
</table>
</fieldset>

<fieldset class="tblFooters">
    <input type="submit" name="do_save_data" value="<?php echo $strSave; ?>" />
    <?php
    echo $strOr;
    echo '    ' . sprintf($strAddToIndex,
            '<input type="text" name="added_fields" size="2" value="1"'
        .' onfocus="this.select()" />') . "\n";
    echo '    <input type="submit" name="add_fields" value="' . $strGo . '"'
        .' onclick="return checkFormElementInRange(this.form,'
        .' \'added_fields\', \''
        . str_replace('\'', '\\\'', $GLOBALS['strInvalidColumnCount'])
        . '\', 1)" />' . "\n";
    ?>
</fieldset>
</form>
</div>
<?php
} else {
    /**
     * Display indexes
     */
    ?>
    <form action="./tbl_indexes.php" method="post"
        onsubmit="return checkFormElementInRange(this, 'idx_num_fields',
            '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidColumnCount']); ?>',
            1)">
    <?php
    echo PMA_generate_common_hidden_inputs($db, $table);
    ?>
    <table id="table_indexes" class="data">
        <caption class="tblHeaders">
        <?php
        echo $strIndexes . ':' . "\n";
        echo '        ' . PMA_showMySQLDocu('optimization',
            'optimizing-database-structure');
        ?>

        </caption>
    <?php

    if (count($ret_keys) > 0) {
        $edit_link_text = '';
        $drop_link_text = '';

        if ($cfg['PropertiesIconic'] === true || $cfg['PropertiesIconic'] === 'both') {
            $edit_link_text = '<img class="icon" src="' . $pmaThemeImage
                . 'b_edit.png" width="16" height="16" title="' . $strEdit
                . '" alt="' . $strEdit . '" />';
            $drop_link_text = '<img class="icon" src="' . $pmaThemeImage
                . 'b_drop.png" width="16" height="16" title="' . $strDrop
                . '" alt="' . $strDrop . '" />';
        }
        if ($cfg['PropertiesIconic'] === false || $cfg['PropertiesIconic'] === 'both') {
            $edit_link_text .= $strEdit;
            $drop_link_text .= $strDrop;
        }
        if ($cfg['PropertiesIconic'] === 'both') {
            $edit_link_text = '<nobr>' . $edit_link_text . '</nobr>';
            $drop_link_text = '<nobr>' . $drop_link_text . '</nobr>';
        }
        ?>

        <thead>
        <tr><th><?php echo $strKeyname; ?></th>
            <th><?php echo $strType; ?></th>
            <th><?php echo $strCardinality; ?></th>
            <th colspan="2"><?php echo $strAction; ?></th>
            <th colspan="2"><?php echo $strField; ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $idx_collection = PMA_show_indexes($table, $indexes, $indexes_info,
            $indexes_data, true);
        echo PMA_check_indexes($ret_keys);
    } // end display indexes
    else {
        // none indexes
        echo '<tbody>'
            .'<tr><td colspan="7">';
        PMA_Message::warning('strNoIndex')->display();
        echo '</td></tr>' . "\n";
    }
    ?>

    <tr class="tblFooters"><td colspan="7">
    <?php echo sprintf($strCreateIndex,
        '<input type="text" size="2" name="idx_num_fields" value="1" />'); ?>
    <input type="submit" name="create_index" value="<?php echo $strGo; ?>"
        onclick="return checkFormElementInRange(this.form,
            'idx_num_fields',
            '<?php echo str_replace('\'', '\\\'', $GLOBALS['strInvalidColumnCount']); ?>',
            1)" />
    </td></tr>
    </tbody>
    </table>
    </form>
    <?php
} // end display indexes


/**
 * Displays the footer
 */
echo "\n";

if (!defined('PMA_IDX_INCLUDED')){
    require_once './libraries/footer.inc.php';
}
?>
