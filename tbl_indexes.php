<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/tbl_indexes.lib.php');

/**
 * Defines the index types ("FULLTEXT" is available since MySQL 3.23.23)
 */
$index_types       = PMA_get_indextypes();
$index_types_cnt   = count($index_types);

/**
 * Ensures the db & table are valid, then loads headers and gets indexes
 * informations.
 * Skipped if this script is called by "tbl_properties.php"
 */
if (!defined('PMA_IDX_INCLUDED')) {
    // Not a valid db name -> back to the welcome page
    if (!empty($db)) {
        $is_db = PMA_DBI_select_db($db);
    }
    if (empty($db) || !$is_db) {
        PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'main.php?' . PMA_generate_common_url('', '', '&') . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        exit;
    }
    // Not a valid table name -> back to the default db_details sub-page
    if (!empty($table)) {
        $is_table = PMA_DBI_query('SHOW TABLES LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'', NULL, PMA_DBI_QUERY_STORE);
    }
    if (empty($table)
        || !($is_table && PMA_DBI_num_rows($is_table))) {
        PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . $cfg['DefaultTabDatabase'] . '?' . PMA_generate_common_url($db, '', '&') . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        exit;
    } else if (isset($is_table)) {
        PMA_DBI_free_result($is_table);
    }

    // Displays headers (if needed)
    $js_to_run = ((isset($index) && isset($do_save_data)) ? 'functions.js' : 'indexes.js');
    require_once('./header.inc.php');
} // end if


/**
 * Gets fields and indexes informations
 */
if (!defined('PMA_IDX_INCLUDED')) {
    $err_url_0 = 'db_details.php?' . PMA_generate_common_url($db);
}

//  Gets table keys and store them in arrays
$indexes      = array();
$indexes_info = array();
$indexes_data = array();
// keys had already been grabbed in "tbl_properties.php"
if (!defined('PMA_IDX_INCLUDED')) {
    $ret_keys = PMA_get_indexes($table, $err_url_0);
}

PMA_extract_indexes($ret_keys, $indexes, $indexes_info, $indexes_data);

// Get fields and stores their name/type
// fields had already been grabbed in "tbl_properties.php"
if (!defined('PMA_IDX_INCLUDED')) {
    $fields_rs   = PMA_DBI_query('SHOW FIELDS FROM ' . PMA_backquote($table) . ';');
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
        $tmp[2]         = substr(preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]), 1);
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
 * "tbl_properties.php"
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
        } else if ($index != 'PRIMARY') {
            PMA_mysqlDie($strPrimaryKeyName, '', FALSE, $err_url);
        }
    } else if ($index == 'PRIMARY') {
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
            $sql_query .= ' ADD FULLTEXT ' . (empty($index) ? '' : PMA_backquote($index)) . ' (';
            break;
        case 'UNIQUE':
            $sql_query .= ' ADD UNIQUE ' . (empty($index) ? '' : PMA_backquote($index)) . ' (';
            break;
        case 'INDEX':
            $sql_query .= ' ADD INDEX ' . (empty($index) ? '' : PMA_backquote($index)) . ' (';
            break;
    } // end switch
    $index_fields         = '';
    foreach ($column AS $i => $name) {
        if ($name != '--ignore--') {
            $index_fields .= (empty($index_fields) ? '' : ',')
                          . PMA_backquote($name)
                          . (empty($sub_part[$i]) ? '' : '(' . $sub_part[$i] . ')');
        }
    } // end while
    if (empty($index_fields)){
        PMA_mysqlDie($strNoIndexPartsDefined, '', FALSE, $err_url);
    } else {
        $sql_query .= $index_fields . ')';
    }

    $result    = PMA_DBI_query($sql_query);
    $message   = $strTable . ' ' . htmlspecialchars($table) . ' ' . $strHasBeenAltered;

    $active_page = 'tbl_properties_structure.php';
    require('./tbl_properties_structure.php');
} // end builds the new index


/**
 * Edits an index or defines a new one
 */
else if (!defined('PMA_IDX_INCLUDED')
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
            $edited_index_data[$i]            = array('Column_name' => '', 'Sub_part' => '');
        } // end for
        if ($old_index == ''
            && !isset($indexes_info['PRIMARY'])
            && ($index_type == '' || $index_type == 'PRIMARY')) {
            $old_index                        = 'PRIMARY';
        }
    } else {
        $edited_index_info                    = $indexes_info[$old_index];
        $edited_index_data                    = $indexes_data[$old_index];


        if ((PMA_MYSQL_INT_VERSION < 40002 && $edited_index_info['Comment'] == 'FULLTEXT')
                || (PMA_MYSQL_INT_VERSION >= 40002 && $edited_index_info['Index_type'] == 'FULLTEXT')) {
            $index_type                       = 'FULLTEXT';
        } else if ($index == 'PRIMARY') {
            $index_type                       = 'PRIMARY';
        } else if ($edited_index_info['Non_unique'] == '0') {
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
            $edited_index_data[$i]            = array('Column_name' => '', 'Sub_part' => '');
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

<!-- Build index form -->
<form action="./tbl_indexes.php" method="post" name="index_frm"
    onsubmit="if (typeof(this.elements['index'].disabled) != 'undefined') {this.elements['index'].disabled = false}">
<table border="0" cellpadding="2" cellspacing="1">
    <tr><td class="tblHeaders" colspan="2">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <?php
    if (isset($create_index)) {
        echo '<input type="hidden" name="create_index" value="1" />';
    }
    echo "\n";
    ?>
    <input type="hidden" name="old_index" value="<?php echo (isset($create_index) ? '' : htmlspecialchars($old_index)); ?>" />
    <?php echo ' ' . (isset($create_index) ? $strCreateIndexTopic : $strModifyIndexTopic) . ' '; ?>
    </th></tr>


    <tr>
        <td align="right"><b><?php echo $strIndexName; ?></b>&nbsp;</th>
        <td>
            <input type="text" name="index" value="<?php echo htmlspecialchars($index); ?>" size="25" onfocus="this.select()" />
        </td>
    </tr>
    <tr><td align="right"><?php
    if ($cfg['ErrorIconic']) {
        echo '<img src="' . $pmaThemeImage . 's_warn.png" width="16" height="16" border="0" alt="Attention" />';
    }
?></td><td><?php echo $strPrimaryKeyWarning . "\n"; ?></td></tr>
    <tr>
        <td align="right"><b><?php echo $strIndexType; ?></b>&nbsp;</td>
        <td>
            <select name="index_type" onchange="return checkIndexName()">
    <?php
    echo "\n";
    for ($i = 0; $i < $index_types_cnt; $i++) {
        if ($index_types[$i] == 'PRIMARY') {
            if ($index == 'PRIMARY' || !isset($indexes_info['PRIMARY'])) {
                echo '                '
                     . '<option value="PRIMARY"' . (($index_type == 'PRIMARY') ? ' selected="selected"' : '') . '>PRIMARY</option>'
                     . "\n";
            }
        } else {
            echo '                '
                 . '<option value="' . $index_types[$i] . '"' . (($index_type == $index_types[$i]) ? ' selected="selected"' : '') . '>'. $index_types[$i] . '</option>'
                 . "\n";

        } // end if... else...
    } // end for
    ?>
            </select>
            <?php echo PMA_showMySQLDocu('Reference', 'ALTER_TABLE') . "\n"; ?>
        </td>
    </tr>

    <tr><td valign="top" align="right"><b><?php echo $strFields; ?> :</b>&nbsp;</td><td><table border="<?php echo $cfg['Border']; ?>" cellpadding="2" cellspacing="1">
    <tr>
        <th><?php echo $strField; ?></th>
        <th><?php echo $strSize; ?></th>
    </tr>
    <?php
    foreach ($edited_index_info['Sequences'] AS $row_no => $seq_index) {
        $add_type     = (is_array($fields_types) && count($fields_types) == count($fields_names));
        $selected     = $edited_index_data[$seq_index]['Column_name'];
        if (!empty($edited_index_data[$seq_index]['Sub_part'])) {
            $sub_part = ' value="' . $edited_index_data[$seq_index]['Sub_part'] . '"';
        } else {
            $sub_part = '';
        }
        $bgcolor      = (($row_no % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']);
        echo "\n";
        ?>
    <tr>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <select name="column[]">
                <option value="--ignore--"<?php if ('--ignore--' == $selected) echo ' selected="selected"'; ?>>
                    -- <?php echo $strIgnore; ?> --</option>
        <?php
        foreach ($fields_names AS $key => $val) {
            if ($index_type != 'FULLTEXT'
                || preg_match('@^(varchar|text|tinytext|mediumtext|longtext)@i', $fields_types[$key])) {
                echo "\n" . '                '
                     . '<option value="' . htmlspecialchars($val) . '"' . (($val == $selected) ? ' selected="selected"' : '') . '>'
                     . htmlspecialchars($val) . (($add_type) ? ' [' . $fields_types[$key] . ']' : '' ) . '</option>' . "\n";
            }
        } // end while
        echo "\n";
        ?>
            </select>
        </td>
        <td bgcolor="<?php echo $bgcolor; ?>">
            <input type="text" size="5" name="sub_part[]"<?php echo $sub_part; ?> onfocus="this.select()" />
        </td>
    </tr>
        <?php
    } // end while

    echo "\n";
    ?>
    <tr><td colspan="2"><?php
    echo "\n";
    if (isset($added_fields)) {
        echo '    <input type="hidden" name="prev_add_fields" value="' . $added_fields . '" />';
    }
    if (isset($idx_num_fields)) {
        echo '    <input type="hidden" name="idx_num_fields" value="' . $idx_num_fields . '" />' . "\n";
    }
    echo '    ' . "\n";
    echo '    ' . sprintf($strAddToIndex,  '<input type="text" name="added_fields" size="2" value="1" onfocus="this.select()" style="vertical-align: middle;" />') . "\n";
    echo '    &nbsp;<input type="submit" name="add_fields" value="' . $strGo . '" onclick="return checkFormElementInRange(this.form, \'added_fields\', 1)" style="vertical-align: middle;" />' . "\n";
?></td>
</tr>
    </table></td></tr>
<tr><td colspan="2" class="tblFooters" align="center">
    <input type="submit" name="do_save_data" value="<?php echo $strSave; ?>" /></td></tr>
</table>
</form>
<?php
} else {
    /**
     * Display indexes
     */
    ?>
    <!-- Indexes form -->
    <form action="./tbl_indexes.php" method="post">
    <table border="0" cellpadding="2" cellspacing="1">
    <tr><td class="tblHeaders" colspan="7">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <?php
    echo "\n";
    echo '        ' . $strIndexes . ':' . "\n";
    echo '        ' . PMA_showMySQLDocu('MySQL_Optimisation', 'Optimising_Database_Structure') . "\n";
?></td></tr><?php
    $edit_link_text = '';
    $drop_link_text = '';

    // We need to copy the value or else the == 'both' check will always return true
    $propicon = (string)$cfg['PropertiesIconic'];

    if ($cfg['PropertiesIconic'] === true || $propicon == 'both') {
        $edit_link_text = '<img src="' . $pmaThemeImage . 'b_edit.png" width="16" height="16" hspace="2" border="0" title="' . $strEdit . '" alt="' . $strEdit . '" />';
        $drop_link_text = '<img src="' . $pmaThemeImage . 'b_drop.png" width="16" height="16" hspace="2" border="0" title="' . $strDrop . '" alt="' . $strDrop . '" />';
    }
    if ($cfg['PropertiesIconic'] === false || $propicon == 'both') {
        $edit_link_text .= $strEdit;
        $drop_link_text .= $strDrop;
    }
    if ($propicon == 'both') {
        $edit_link_text = '<nobr>' . $edit_link_text . '</nobr>';
        $drop_link_text = '<nobr>' . $drop_link_text . '</nobr>';
    }

    if (count($ret_keys) > 0) {
        ?>
        <!--table border="<?php echo $cfg['Border']; ?>" cellpadding="2" cellspacing="1"-->
        <tr>
            <th><?php echo $strKeyname; ?></th>
            <th><?php echo $strType; ?></th>
            <th><?php echo $strCardinality; ?></th>
            <th colspan="2"><?php echo $strAction; ?></th>
            <th colspan="2"><?php echo $strField; ?></th>
        </tr>
        <?php
        $idx_collection = PMA_show_indexes($table, $indexes, $indexes_info, $indexes_data, true);
        echo PMA_check_indexes($idx_collection);
    } // end display indexes
    else {
        // none indexes
        echo "\n" . '        <tr><td colspan=7" align="center">' . "\n";
        if ($cfg['ErrorIconic']) {
            echo '<img src="' . $pmaThemeImage . 's_warn.png" width="16" height="16" border="0" alt="Warning" hspace="2" align="middle" />';
        }
        echo '        <b>' . $strNoIndex . '</b></td></tr>' . "\n\n";
    }

    echo '<tr><td colspan="7" class="tblFooters" nowrap="nowrap" align="center">        '
       . sprintf($strCreateIndex, '<input type="text" size="2" name="idx_num_fields" value="1" style="vertical-align: middle;" />') . "\n";
    echo '        &nbsp;<input type="submit" name="create_index" value="' . $strGo . '" onclick="return checkFormElementInRange(this.form, \'idx_num_fields\', 1)" style="vertical-align: middle;" />' . "\n";
    echo '</td></tr>    ';
?>
</table></form>
<?php
} // end display indexes


/**
 * Displays the footer
 */
echo "\n";

if (!defined('PMA_IDX_INCLUDED')){
    require_once('./footer.inc.php');
}
?>
