<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Gets some core libraries
 */
require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');


/**
 * Defines the index types ("FULLTEXT" is available since MySQL 3.23.23)
 */
$index_types_cnt   = 4;
$index_types       = array(
    'PRIMARY',
    'INDEX',
    'UNIQUE',
    'FULLTEXT'
);

/**
 * Ensures the db & table are valid, then loads headers and gets indexes
 * informations.
 * Skipped if this script is called by "tbl_properties.php"
 */
if (!defined('PMA_IDX_INCLUDED')) {
    // Not a valid db name -> back to the welcome page
    if (!empty($db)) {
        $is_db = @PMA_mysql_select_db($db);
    }
    if (empty($db) || !$is_db) {
        header('Location: ' . $cfg['PmaAbsoluteUri'] . 'main.php?' . PMA_generate_common_url('', '', '&') . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        exit;
    }
    // Not a valid table name -> back to the default db_details sub-page
    if (!empty($table)) {
        $is_table = @PMA_mysql_query('SHOW TABLES LIKE \'' . PMA_sqlAddslashes($table, TRUE) . '\'');
    }
    if (empty($table)
        || !($is_table && @mysql_numrows($is_table))) {
        header('Location: ' . $cfg['PmaAbsoluteUri'] . $cfg['DefaultTabDatabase'] . '?' . PMA_generate_common_url($db, '', '&') . (isset($message) ? '&message=' . urlencode($message) : '') . '&reload=1');
        exit;
    } else if (isset($is_table)) {
        mysql_free_result($is_table);
    }

    // Displays headers (if needed)
    $js_to_run = ((isset($index) && isset($do_save_data)) ? 'functions.js' : 'indexes.js');
    require_once('./header.inc.php');
} // end if


/**
 * Gets fields and indexes informations
 */
if (defined('PMA_IDX_INCLUDED')) {
    $err_url_0 = 'db_details.php?' . PMA_generate_common_url($db);
}

//  Gets table keys and store them in arrays
$indexes      = array();
$prev_index   = '';
$indexes_info = array();
$indexes_data = array();
// keys had already been grabbed in "tbl_properties.php"
if (defined('PMA_IDX_INCLUDED')) {
    $idx_cnt     = count($ret_keys);
} else {
    $local_query = 'SHOW KEYS FROM ' . PMA_backquote($table);
    $result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
    $idx_cnt     = mysql_num_rows($result);
}

for ($i = 0; $i < $idx_cnt; $i++) {
    $row = (defined('PMA_IDX_INCLUDED') ? $ret_keys[$i] : PMA_mysql_fetch_array($result));

    if ($row['Key_name'] != $prev_index ){
        $indexes[]  = $row['Key_name'];
        $prev_index = $row['Key_name'];
    }
    $indexes_info[$row['Key_name']]['Sequences'][]     = $row['Seq_in_index'];
    $indexes_info[$row['Key_name']]['Non_unique']      = $row['Non_unique'];
    if (isset($row['Cardinality'])) {
        $indexes_info[$row['Key_name']]['Cardinality'] = $row['Cardinality'];
    }
//    I don't know what does following column mean....
//    $indexes_info[$row['Key_name']]['Packed']          = $row['Packed'];
    $indexes_info[$row['Key_name']]['Comment']         = (isset($row['Comment']))
                                                       ? $row['Comment']
                                                       : '';
    $indexes_info[$row['Key_name']]['Index_type']      = (isset($row['Index_type']))
                                                       ? $row['Index_type']
                                                       : '';

    $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Column_name']  = $row['Column_name'];
    if (isset($row['Sub_part'])) {
        $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Sub_part'] = $row['Sub_part'];
    }
} // end while

if (defined('PMA_IDX_INCLUDED')) {
    unset($ret_keys);
} else if ($result) {
    mysql_free_result($result);
}

// Get fields and stores their name/type
// fields had already been grabbed in "tbl_properties.php"
if (defined('PMA_IDX_INCLUDED')) {
    mysql_data_seek($fields_rs, 0);
} else {
    $local_query = 'SHOW FIELDS FROM ' . PMA_backquote($table);
    $fields_rs   = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
    $fields_cnt  = mysql_num_rows($fields_rs);
}

$fields_names           = array();
$fields_types           = array();
while ($row = PMA_mysql_fetch_array($fields_rs)) {
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
    mysql_free_result($fields_rs);
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
    foreach($column AS $i => $name) {
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

    $result    = PMA_mysql_query($sql_query) or PMA_mysqlDie('', $sql_query, FALSE, $err_url);
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
        foreach($column AS $i => $name) {
            if ($name != '--ignore--'){
                $edited_index_data[$i+1]['Column_name'] = $name;
                $edited_index_data[$i+1]['Sub_part']    = $sub_part[$i];
            }
        } // end while
    } // end if
    // end preparing form values
    ?>

<!-- Build index form -->
<form action="tbl_indexes.php" method="post" name="index_frm"
    onsubmit="if (typeof(this.elements['index'].disabled) != 'undefined') {this.elements['index'].disabled = false}">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <?php
    if (isset($create_index)) {
        echo '<input type="hidden" name="create_index" value="1" />';
    }
    echo "\n";
    ?>
    <input type="hidden" name="old_index" value="<?php echo (isset($create_index) ? '' : htmlspecialchars($old_index)); ?>" />
    <b><?php echo '------ ' . (isset($create_index) ? $strCreateIndexTopic : $strModifyIndexTopic) . ' ------'; ?></b>
    <br /><br />

    <table border="0">
    <tr>
        <td><?php echo $strIndexName; ?>&nbsp;</td>
        <td>
            <input type="text" name="index" value="<?php echo htmlspecialchars($index); ?>" class="textfield" onfocus="this.select()" />
            &nbsp;<?php echo $strPrimaryKeyWarning . "\n"; ?>
        </td>
    </tr>
    <tr>
        <td><?php echo $strIndexType; ?>&nbsp;</td>
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
            </select>&nbsp;
            <?php echo PMA_showMySQLDocu('Reference', 'ALTER_TABLE') . "\n"; ?>
        </td>
    </tr>
    </table><br />

    <table border="<?php echo $cfg['Border']; ?>" cellpadding="5">
    <tr>
        <th><?php echo $strField; ?></th>
        <th><?php echo $strSize; ?></th>
    </tr>
    <?php
    foreach($edited_index_info['Sequences'] AS $row_no => $seq_index) {
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
        foreach($fields_names AS $key => $val) {
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
    </table><br />

    <input type="submit" name="do_save_data" value="<?php echo $strSave; ?>" /><br />

    <?php
    echo "\n";
    if (isset($added_fields)) {
        echo '    <input type="hidden" name="prev_add_fields" value="' . $added_fields . '" />';
    }
    if (isset($idx_num_fields)) {
        echo '    <input type="hidden" name="idx_num_fields" value="' . $idx_num_fields . '" />' . "\n";
    }
    echo '    <hr /><br />' . "\n";
    echo '    ' . sprintf($strAddToIndex,  '<input type="text" name="added_fields" size="4" value="1" class="textfield" onfocus="this.select()" />') . "\n";
    echo '    &nbsp;<input type="submit" name="add_fields" value="' . $strGo . '" onclick="return checkFormElementInRange(this.form, \'added_fields\', 1)" />' . "\n";

} else {
    /**
     * Display indexes
     */
    ?>
    <!-- Indexes form -->
    <form action="tbl_indexes.php" method="post">
        <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
    <?php
    echo "\n";
    echo '        ' . $strIndexes . '&nbsp;:' . "\n";
    echo '        ' . PMA_showMySQLDocu('MySQL_Optimisation', 'Optimising_Database_Structure') . '<br />' ."\n";

    if ($idx_cnt > 0) {
        ?>
        <table border="<?php echo $cfg['Border']; ?>">
        <tr>
            <th><?php echo $strKeyname; ?></th>
            <th><?php echo $strType; ?></th>
            <th><?php echo $strCardinality; ?></th>
            <th colspan="2"><?php echo $strAction; ?></th>
            <th colspan="2"><?php echo $strField; ?></th>
        </tr>
        <?php
        echo "\n";
        foreach($indexes AS $index_no => $index_name) {
            $cell_bgd = (($index_no % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']);
            $index_td = '            <td bgcolor="' . $cell_bgd . '" rowspan="' . count($indexes_info[$index_name]['Sequences']) . '">' . "\n";
            echo '        <tr>' . "\n";
            echo $index_td
                 . '                ' . htmlspecialchars($index_name) . "\n"
                 . '            </td>' . "\n";

            if ((PMA_MYSQL_INT_VERSION < 40002 && $indexes_info[$index_name]['Comment'] == 'FULLTEXT')
                || (PMA_MYSQL_INT_VERSION >= 40002 && $indexes_info[$index_name]['Index_type'] == 'FULLTEXT')) {
                $index_type = 'FULLTEXT';
            } else if ($index_name == 'PRIMARY') {
                $index_type = 'PRIMARY';
            } else if ($indexes_info[$index_name]['Non_unique'] == '0') {
                $index_type = 'UNIQUE';
            } else {
                $index_type = 'INDEX';
            }
            echo $index_td
                 . '                ' . $index_type . "\n"
                 . '            </td>' . "\n";

            echo str_replace('">' . "\n", '" align="right">' . "\n", $index_td)
                 . '                ' . (isset($indexes_info[$index_name]['Cardinality']) ? $indexes_info[$index_name]['Cardinality'] : $strNone) . '&nbsp;' . "\n"
                 . '            </td>' . "\n";

            echo $index_td
                 . '                <a href="tbl_indexes.php?' . $url_query . '&amp;index=' . urlencode($index_name) . '">' . ($cfg['PropertiesIconic'] ? '<img src="./images/button_edit.png" width="12" height="13" hspace="7" border="0" title="' . $strEdit . '" alt="' . $strEdit . '">' : $strEdit) . '</a>' . "\n"
                 . '            </td>' . "\n";

            if ($index_name == 'PRIMARY') {
                $local_query = urlencode('ALTER TABLE ' . PMA_backquote($table) . ' DROP PRIMARY KEY');
                $js_msg    = 'ALTER TABLE ' . PMA_jsFormat($table) . ' DROP PRIMARY KEY';
                $zero_rows = urlencode($strPrimaryKeyHasBeenDropped);
            } else {
                $local_query = urlencode('ALTER TABLE ' . PMA_backquote($table) . ' DROP INDEX ' . PMA_backquote($index_name));
                $js_msg    = 'ALTER TABLE ' . PMA_jsFormat($table) . ' DROP INDEX ' . PMA_jsFormat($index_name);
                $zero_rows = urlencode(sprintf($strIndexHasBeenDropped, htmlspecialchars($index_name)));
            }
            echo $index_td
                 . '                <a href="sql.php?' . $url_query . '&amp;sql_query=' . $local_query . '&amp;zero_rows=' . $zero_rows . '" onclick="return confirmLink(this, \'' . $js_msg . '\')">' . ($cfg['PropertiesIconic'] ? '<img src="./images/button_drop.png" width="11" height="12" hspace="7" border="0" title="' . $strDrop . '" alt="' . $strDrop . '">' : $strDrop) . '</a>' . "\n"
                 . '            </td>' . "\n";

            foreach($indexes_info[$index_name]['Sequences'] AS $row_no => $seq_index) {
                if ($row_no > 0) {
                    echo '        <tr>' . "\n";
                }
                if (!empty($indexes_data[$index_name][$seq_index]['Sub_part'])) {
                    echo '            <td bgcolor="' . $cell_bgd . '">' . "\n"
                         . '                ' . $indexes_data[$index_name][$seq_index]['Column_name'] . "\n"
                         . '            </td>' . "\n";
                    echo '            <td align="right" bgcolor="' . $cell_bgd . '">' . "\n"
                         . '                ' . $indexes_data[$index_name][$seq_index]['Sub_part'] . "\n"
                         . '            </td>' . "\n";
                    echo '        </tr>' . "\n";
                } else {
                    echo '            <td bgcolor="' . $cell_bgd . '" colspan="2">' . "\n"
                         . '                ' . htmlspecialchars($indexes_data[$index_name][$seq_index]['Column_name']) . "\n"
                         . '            </td>' . "\n";
                    echo '        </tr>' . "\n";
                }
            } // end while
        } // end while
        ?>
        </table><br />
        <?php
        echo "\n\n";
    } // end display indexes
    else {
        // none indexes
        echo "\n" . '        <br />' . "\n";
        echo '        <i>' . $strNoIndex . '</i><br /><br />' . "\n\n";
    }

    echo '        ' . sprintf($strCreateIndex, '<input type="text" size="4" name="idx_num_fields" value="1" class="textfield" />') . "\n";
    echo '        &nbsp;<input type="submit" name="create_index" value="' . $strGo . '" onclick="return checkFormElementInRange(this.form, \'idx_num_fields\', 1)" />' . "\n";
    echo '    ';
} // end display indexes

?>
</form>


<?php
/**
 * Displays the footer
 */
echo "\n";

if (!defined('PMA_IDX_INCLUDED')){
    require_once('./footer.inc.php');
}
?>
