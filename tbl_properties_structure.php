<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/grab_globals.lib.php');
require_once('./libraries/common.lib.php');
require_once('./libraries/mysql_charsets.lib.php');

/**
 * Drop multiple fields if required
 */

// workaround for IE problem:
if (isset($submit_mult_change_x)) {
    $submit_mult = $strChange;
}
if (isset($submit_mult_drop_x)) {
    $submit_mult = $strDrop;
}

if ((!empty($submit_mult) && isset($selected_fld))
    || isset($mult_btn)) {
    $action = 'tbl_properties_structure.php';
    $err_url = 'tbl_properties_structure.php?' . PMA_generate_common_url($db, $table);
    require('./mult_submits.inc.php');
}

/**
 * Runs common work
 */
require('./tbl_properties_common.php');
$url_query .= '&amp;goto=tbl_properties_structure.php&amp;back=tbl_properties_structure.php';

/**
 * Prepares the table structure display
 */
// 1. Get table information/display tabs
require('./tbl_properties_table_info.php');

/**
 * Show result of multi submit operation
 */
if ((!empty($submit_mult) && isset($selected_fld))
    || isset($mult_btn)) {
    PMA_showMessage($strSuccess);
}

// 2. Gets table keys and retains them
$local_query = 'SHOW KEYS FROM ' . PMA_backquote($table);
$result      = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
$primary     = '';
$ret_keys    = array();
$pk_array    = array(); // will be use to emphasis prim. keys in the table view
while ($row = PMA_mysql_fetch_array($result)) {
    $ret_keys[]  = $row;
    // Backups the list of primary keys
    if ($row['Key_name'] == 'PRIMARY') {
        $primary .= $row['Column_name'] . ', ';
        $pk_array[$row['Column_name']] = 1;
    }
} // end while
mysql_free_result($result);

// 3. Get fields
$local_query = 'SHOW FIELDS FROM ' . PMA_backquote($table);
$fields_rs   = PMA_mysql_query($local_query) or PMA_mysqlDie('', $local_query, '', $err_url_0);
$fields_cnt  = mysql_num_rows($fields_rs);



/**
 * Displays the table structure ('show table' works correct since 3.23.03)
 */
?>

<!-- TABLE INFORMATIONS -->

<form method="post" action="tbl_properties_structure.php" name="fieldsForm">
    <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>

<table border="<?php echo $cfg['Border']; ?>">
<tr>
    <td></td>
    <th>&nbsp;<?php echo $strField; ?>&nbsp;</th>
    <th><?php echo $strType; ?></th>
<?php echo PMA_MYSQL_INT_VERSION >= 40100 ? '    <th>' . $strCharset . '</th>' . "\n" : ''; ?>
    <th><?php echo $strAttr; ?></th>
    <th><?php echo $strNull; ?></th>
    <th><?php echo $strDefault; ?></th>
    <th><?php echo $strExtra; ?></th>
    <th colspan="6"><?php echo $strAction; ?></th>
</tr>

<?php
$comments_map = array();
$mime_map = array();

if ($GLOBALS['cfg']['ShowPropertyComments']) {
    require_once('./libraries/relation.lib.php');
    require_once('./libraries/transformations.lib.php');

    $cfgRelation = PMA_getRelationsParam();


    if ($cfgRelation['commwork']) {
        $comments_map = PMA_getComments($db, $table);

        if ($cfgRelation['mimework'] && $cfg['BrowseMIME']) {
            $mime_map = PMA_getMIME($db, $table, true);
        }
    }
}

$i         = 0;
$aryFields = array();
$checked   = (!empty($checkall) ? ' checked="checked"' : '');

while ($row = PMA_mysql_fetch_array($fields_rs)) {
    $i++;
    $bgcolor          = ($i % 2) ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];
    $aryFields[]      = $row['Field'];

    $type             = $row['Type'];
    // reformat mysql query output - staybyte - 9. June 2001
    // loic1: set or enum types: slashes single quotes inside options
    if (preg_match('@^(set|enum)\((.+)\)$@i', $type, $tmp)) {
        $tmp[2]       = substr(preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]), 1);
        $type         = $tmp[1] . '(' . str_replace(',', ', ', $tmp[2]) . ')';
        $type_nowrap  = '';

        $binary       = 0;
        $unsigned     = 0;
        $zerofill     = 0;
    } else {
        $type_nowrap  = ' nowrap="nowrap"';
        $type         = preg_replace('@BINARY@i', '', $type);
        $type         = preg_replace('@ZEROFILL@i', '', $type);
        $type         = preg_replace('@UNSIGNED@i', '', $type);
        if (empty($type)) {
            $type     = '&nbsp;';
        }

        $binary       = stristr($row['Type'], 'blob') || stristr($row['Type'], 'binary');
        $unsigned     = stristr($row['Type'], 'unsigned');
        $zerofill     = stristr($row['Type'], 'zerofill');
    }

    // rabus: Devide charset from the rest of the type definition (MySQL >= 4.1)
    unset($field_charset);
    if (PMA_MYSQL_INT_VERSION >= 40100) {
        if ((substr($type, 0, 4) == 'char'
            || substr($type, 0, 7) == 'varchar'
            || substr($type, 0, 4) == 'text'
            || substr($type, 0, 8) == 'tinytext'
            || substr($type, 0, 10) == 'mediumtext'
            || substr($type, 0, 8) == 'longtext'
            || substr($type, 0, 3) == 'set'
            || substr($type, 0, 4) == 'enum'
            ) && !$binary) {
            if (strpos($type, ' character set ')) {
                $type = substr($type, 0, strpos($type, ' character set '));
            }
            if (!empty($row['Collation'])) {
                $field_charset = $row['Collation'];
            } else {
                $field_charset = '';
            }
        } else {
            $field_charset = '';
        }
    }

    // garvin: Display basic mimetype [MIME]
    if ($cfgRelation['commwork'] && $cfgRelation['mimework'] && $cfg['BrowseMIME'] && isset($mime_map[$row['Field']]['mimetype'])) {
        $type_mime = '<br />MIME: ' . str_replace('_', '/', $mime_map[$row['Field']]['mimetype']);
    } else {
        $type_mime = '';
    }

    $strAttribute     = '&nbsp;';
    if ($binary) {
        $strAttribute = 'BINARY';
    }
    if ($unsigned) {
        $strAttribute = 'UNSIGNED';
    }
    if ($zerofill) {
        $strAttribute = 'UNSIGNED ZEROFILL';
    }
    if (!isset($row['Default'])) {
        if ($row['Null'] != '') {
            $row['Default'] = '<i>NULL</i>';
        }
    } else {
        $row['Default'] = htmlspecialchars($row['Default']);
    }

    $field_encoded = urlencode($row['Field']);
    $field_name    = htmlspecialchars($row['Field']);

    // garvin: underline commented fields and display a hover-title (CSS only)

    $comment_style = '';
    if (isset($comments_map[$row['Field']])) {
        $field_name = '<span style="border-bottom: 1px dashed black;" title="' . htmlspecialchars($comments_map[$row['Field']]) . '">' . $field_name . '</span>';
    }

    if (isset($pk_array[$row['Field']])) {
        $field_name = '<u>' . $field_name . '</u>';
    }
    echo "\n";

    $titles = array();
    if ($cfg['PropertiesIconic'] == true) {
        // We need to copy the value or else the == 'both' check will always return true
        $propicon = (string)$cfg['PropertiesIconic'];

        if ($propicon == 'both') {
            $iconic_spacer = '<nobr>';
        } else {
            $iconic_spacer = '';
        }

        $titles['Change']        = $iconic_spacer . '<img hspace="7" width="12" height="13" src="images/button_edit.png" alt="' . $strChange . '" title="' . $strChange . '" border="0" />';
        $titles['Drop']          = $iconic_spacer . '<img hspace="7" width="11" height="12" src="images/button_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" border="0" />';
        $titles['NoDrop']        = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_drop.png" alt="' . $strDrop . '" title="' . $strDrop . '" border="0" />';
        $titles['Primary']       = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_primary.png" alt="' . $strPrimary . '" title="' . $strPrimary . '" border="0" />';
        $titles['Index']         = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_index.png" alt="' . $strIndex . '" title="' . $strIndex . '" border="0" />';
        $titles['Unique']        = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_unique.png" alt="' . $strUnique . '" title="' . $strUnique . '" border="0" />';
        $titles['IdxFulltext']   = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_fulltext.png" alt="' . $strIdxFulltext . '" title="' . $strIdxFulltext . '" border="0" />';
        $titles['NoPrimary']     = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_noprimary.png" alt="' . $strPrimary . '" title="' . $strPrimary . '" border="0" />';
        $titles['NoIndex']       = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_noindex.png" alt="' . $strIndex . '" title="' . $strIndex . '" border="0" />';
        $titles['NoUnique']      = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_nounique.png" alt="' . $strUnique . '" title="' . $strUnique . '" border="0" />';
        $titles['NoIdxFulltext'] = $iconic_spacer . '<img hspace="7" width="11" height="13" src="images/button_nofulltext.png" alt="' . $strIdxFulltext . '" title="' . $strIdxFulltext . '" border="0" />';

        if ($propicon == 'both') {
            $titles['Change']        .= '&nbsp;' . $strChange . '</nobr>';
            $titles['Drop']          .= '&nbsp;' . $strDrop . '</nobr>';
            $titles['NoDrop']        .= '&nbsp;' . $strDrop . '</nobr>';
            $titles['Primary']       .= '&nbsp;' . $strPrimary . '</nobr>';
            $titles['Index']         .= '&nbsp;' . $strIndex . '</nobr>';
            $titles['Unique']        .= '&nbsp;' . $strUnique . '</nobr>';
            $titles['IdxFulltext'  ] .= '&nbsp;' . $strIdxFulltext . '</nobr>';
            $titles['NoPrimary']     .= '&nbsp;' . $strPrimary . '</nobr>';
            $titles['NoIndex']       .= '&nbsp;' . $strIndex . '</nobr>';
            $titles['NoUnique']      .= '&nbsp;' . $strUnique . '</nobr>';
            $titles['NoIdxFulltext'] .= '&nbsp;' . $strIdxFulltext . '</nobr>';
        }
    } else {
        $titles['Change']        = $strChange;
        $titles['Drop']          = $strDrop;
        $titles['NoDrop']        = $strDrop;
        $titles['Primary']       = $strPrimary;
        $titles['Index']         = $strIndex;
        $titles['Unique']        = $strUnique;
        $titles['IdxFulltext']   = $strIdxFulltext;
        $titles['NoPrimary']     = $strPrimary;
        $titles['NoIndex']       = $strIndex;
        $titles['NoUnique']      = $strUnique;
        $titles['NoIdxFulltext'] = $strIdxFulltext;
    }

    ?>
<tr>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <input type="checkbox" name="selected_fld[]" value="<?php echo $field_encoded; ?>" id="checkbox_row_<?php echo $i; ?>" <?php echo $checked; ?> />
    </td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">&nbsp;<label for="checkbox_row_<?php echo $i; ?>"><?php echo $field_name; ?></label>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>"<?php echo $type_nowrap; ?>><?php echo $type; echo $type_mime; ?><bdo dir="ltr"></bdo></td>
<?php echo PMA_MYSQL_INT_VERSION >= 40100 ? '    <td bgcolor="' . $bgcolor . '">' . (empty($field_charset) ? '&nbsp;' : '<dfn title="' . PMA_getCollationDescr($field_charset) . '">' . $field_charset . '</dfn>') . '</td>' . "\n" : '' ?>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php echo $strAttribute; ?></td>
    <td bgcolor="<?php echo $bgcolor; ?>"><?php echo (($row['Null'] == '') ? $strNo : $strYes); ?>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php if (isset($row['Default'])) echo $row['Default']; ?>&nbsp;</td>
    <td bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap"><?php echo $row['Extra']; ?>&nbsp;</td>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <a href="tbl_alter.php?<?php echo $url_query; ?>&amp;field=<?php echo $field_encoded; ?>">
            <?php echo $titles['Change']; ?></a>
    </td>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <?php
        // loic1: Drop field only if there is more than one field in the table
        if ($fields_cnt > 1) {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' DROP ' . PMA_backquote($row['Field'])); ?>&amp;cpurge=1&amp;purgekey=<?php echo urlencode($row['Field']); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strFieldHasBeenDropped, htmlspecialchars($row['Field']))); ?>"
            onclick="return confirmLink(this, 'ALTER TABLE <?php echo PMA_jsFormat($table); ?> DROP <?php echo PMA_jsFormat($row['Field']); ?>')">
            <?php echo $titles['Drop']; ?></a>
            <?php
        } else {
            echo "\n" . '        ' . $titles['NoDrop'];
        }
        echo "\n";
        ?>
    </td>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if ($type == 'text' || $type == 'blob') {
            echo $titles['NoPrimary'] . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' DROP PRIMARY KEY, ADD PRIMARY KEY(' . $primary . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAPrimaryKey, htmlspecialchars($row['Field']))); ?>"
            onclick="return confirmLink(this, 'ALTER TABLE <?php echo PMA_jsFormat($table); ?> DROP PRIMARY KEY, ADD PRIMARY KEY(<?php echo PMA_jsFormat($row['Field']); ?>)')">
            <?php echo $titles['Primary']; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if ($type == 'text' || $type == 'blob') {
            echo $titles['NoIndex'] . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD INDEX(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex ,htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['Index']; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>">
        <?php
        if ($type == 'text' || $type == 'blob') {
            echo $titles['NoUnique'] . "\n";
        } else {
            echo "\n";
            ?>
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD UNIQUE(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex , htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['Unique']; ?></a>
            <?php
        }
        echo "\n";
        ?>
    </td>
    <?php
        if ((!empty($tbl_type) && $tbl_type == 'MYISAM')
            && (strpos(' ' . $type, 'text') || strpos(' ' . $type, 'varchar'))) {
            echo "\n";
            ?>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        <a href="sql.php?<?php echo $url_query; ?>&amp;sql_query=<?php echo urlencode('ALTER TABLE ' . PMA_backquote($table) . ' ADD FULLTEXT(' . PMA_backquote($row['Field']) . ')'); ?>&amp;zero_rows=<?php echo urlencode(sprintf($strAnIndex , htmlspecialchars($row['Field']))); ?>">
            <?php echo $titles['IdxFulltext']; ?></a>
    </td>
            <?php
        } else {
            echo "\n";
        ?>
    <td align="center" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
        <?php echo $titles['NoIdxFulltext'] . "\n"; ?>
    </td>
        <?php
        } // end if... else...
    echo "\n"
    ?>
</tr>
    <?php
    unset($field_charset);
} // end while

echo "\n";

$checkall_url = 'tbl_properties_structure.php?' . PMA_generate_common_url($db,$table);
?>

<tr>
    <td colspan="<?php echo PMA_MYSQL_INT_VERSION >= 40100 ? '14' : '13'; ?>">
        <table>
            <tr>
                <td>
                    <img src="./images/arrow_<?php echo $text_dir; ?>.gif" border="0" width="38" height="22" alt="<?php echo $strWithChecked; ?>" />
        <a href="<?php echo $checkall_url; ?>&amp;checkall=1" onclick="setCheckboxes('fieldsForm', true); return false;">
            <?php echo $strCheckAll; ?></a>
        &nbsp;/&nbsp;
        <a href="<?php echo $checkall_url; ?>" onclick="setCheckboxes('fieldsForm', false); return false;">
            <?php echo $strUncheckAll; ?></a>
        &nbsp;&nbsp;&nbsp;
        <i><?php echo $strWithChecked; ?></i>&nbsp;&nbsp;
                </td>
                <td>
                    <?php

if ($cfg['PropertiesIconic']) {
    /* Opera has trouble with <input type="image"> */
    /* IE has trouble with <button> */
    if (PMA_USR_BROWSER_AGENT != 'IE') {
        echo '<button class="mult_submit" type="submit" name="submit_mult" value="' . $strChange . '" title="' . $strChange . '">' . "\n"
           . '<img src="./images/button_edit.png" title="' . $strChange . '" alt="' . $strChange . '" width="12" height="13" />' . (($propicon == 'both') ? '&nbsp;' . $strChange : '') . "\n"
           . '</button>' . "\n";
    } else {
        echo '                    <input type="image" name="submit_mult_change" value="' .$strChange . '" title="' . $strChange . '" src="./images/button_edit.png" />'  . (($propicon == 'both') ? '&nbsp;' . $strChange : '') . "\n";
    }
    // Drop button if there is at least two fields
    if ($fields_cnt > 1) {
        if (PMA_USR_BROWSER_AGENT != 'IE') {
            echo '                    <button class="mult_submit" type="submit" name="submit_mult" value="' . $strDrop . '" title="' . $strDrop . '">' . "\n"
               . '<img src="./images/button_drop.png" title="' . $strDrop . '" alt="' . $strDrop . '" width="11" height="13" />' . (($propicon == 'both') ? '&nbsp;' . $strDrop : '') . "\n"
               . '</button>' . "\n";
        } else {
            echo '                    <input type="image" name="submit_mult_drop" value="' .$strDrop . '" title="' . $strDrop . '" src="./images/button_drop.png" />' . (($propicon == 'both') ? '&nbsp;' . $strDrop : '') . "\n";
        }
    }
} else {
    echo '                    <input type="submit" name="submit_mult" value="' . $strChange . '" title="' . $strChange . '" />' . "\n";
    // Drop button if there is at least two fields
    if ($fields_cnt > 1) {
        echo '                    &nbsp;<i>' . $strOr . '</i>&nbsp;' . "\n"
           . '                    <input type="submit" name="submit_mult" value="' . $strDrop . '" title="' . $strDrop . '" />' . "\n";
    }
}

?>
                </td>
            </tr>
        </table>
    </td>
</tr>
</table>

</form>


<?php
/**
 * If there are more than 20 rows, displays browse/select/insert/empty/drop
 * links again
 */
if ($fields_cnt > 20) {
    ?>
<!-- Browse links -->
    <?php
    echo "\n";
    require('./tbl_properties_links.php');
} // end if ($fields_cnt > 20)
echo "\n\n";


/**
 * Displays indexes
 */
?>
<!-- Indexes, space usage and row statistics -->
<br />
<table border="0" cellspacing="0" cellpadding="0">
<tr>
    <td valign="top">
<?php
define('PMA_IDX_INCLUDED', 1);
require ('./tbl_indexes.php');
?>
    </td>

<?php
/**
 * Displays Space usage and row statistics
 */
// BEGIN - Calc Table Space - staybyte - 9 June 2001
// loic1, 22 feb. 2002: updated with patch from
//                      Joshua Nye <josh at boxcarmedia.com> to get valid
//                      statistics whatever is the table type
if ($cfg['ShowStats']) {
    $nonisam     = FALSE;
    $is_innodb = (isset($showtable['Type']) && $showtable['Type'] == 'InnoDB');
    if (isset($showtable['Type']) && !preg_match('@ISAM|HEAP@i', $showtable['Type'])) {
        $nonisam = TRUE;
    }
    if ($nonisam == FALSE || $is_innodb) {
        // Gets some sizes
        $mergetable     = FALSE;
        if (isset($showtable['Type']) && $showtable['Type'] == 'MRG_MyISAM') {
            $mergetable = TRUE;
        }
        list($data_size, $data_unit)         = PMA_formatByteDown($showtable['Data_length']);
        if ($mergetable == FALSE) {
            list($index_size, $index_unit)   = PMA_formatByteDown($showtable['Index_length']);
        }
        if (isset($showtable['Data_free']) && $showtable['Data_free'] > 0) {
            list($free_size, $free_unit)     = PMA_formatByteDown($showtable['Data_free']);
            list($effect_size, $effect_unit) = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length'] - $showtable['Data_free']);
        } else {
            list($effect_size, $effect_unit) = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length']);
        }
        list($tot_size, $tot_unit)           = PMA_formatByteDown($showtable['Data_length'] + $showtable['Index_length']);
        if ($table_info_num_rows > 0) {
            list($avg_size, $avg_unit)       = PMA_formatByteDown(($showtable['Data_length'] + $showtable['Index_length']) / $showtable['Rows'], 6, 1);
        }

        // Displays them
        ?>

    <!-- Space usage -->
    <td width="20">&nbsp;</td>
    <td valign="top">
        <?php echo $strSpaceUsage . '&nbsp;:' . "\n"; ?>
        <a name="showusage"></a>
        <table border="<?php echo $cfg['Border']; ?>">
        <tr>
            <th><?php echo $strType; ?></th>
            <th colspan="2" align="center"><?php echo $strUsage; ?></th>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" style="padding-right: 10px"><?php echo $strData; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right" nowrap="nowrap"><?php echo $data_size; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>"><?php echo $data_unit; ?></td>
        </tr>
        <?php
        if (isset($index_size)) {
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" style="padding-right: 10px"><?php echo $strIndex; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right" nowrap="nowrap"><?php echo $index_size; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>"><?php echo $index_unit; ?></td>
        </tr>
            <?php
        }
        if (isset($free_size)) {
            echo "\n";
            ?>
        <tr style="color: #bb0000">
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" style="padding-right: 10px"><?php echo $strOverhead; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>" align="right" nowrap="nowrap"><?php echo $free_size; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorTwo']; ?>"><?php echo $free_unit; ?></td>
        </tr>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" style="padding-right: 10px"><?php echo $strEffective; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right" nowrap="nowrap"><?php echo $effect_size; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><?php echo $effect_unit; ?></td>
        </tr>
            <?php
        }
        if (isset($tot_size) && $mergetable == FALSE) {
            echo "\n";
        ?>
        <tr>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" style="padding-right: 10px"><?php echo $strTotalUC; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>" align="right" nowrap="nowrap"><?php echo $tot_size; ?></td>
            <td bgcolor="<?php echo $cfg['BgcolorOne']; ?>"><?php echo $tot_unit; ?></td>
        </tr>
            <?php
        }
        // Optimize link if overhead
        if (isset($free_size) && ($tbl_type == 'MYISAM' || $tbl_type == 'BDB')) {
            echo "\n";
            ?>
        <tr>
            <td colspan="3" align="center">
                [<a href="sql.php?<?php echo $url_query; ?>&amp;pos=0&amp;sql_query=<?php echo urlencode('OPTIMIZE TABLE ' . PMA_backquote($table)); ?>"><?php echo $strOptimizeTable; ?></a>]
            </td>
        </tr>
            <?php
        }
        echo "\n";
        ?>
        </table>
    </td>

    <!-- Rows Statistic -->
    <td width="20">&nbsp;</td>
    <td valign="top">
        <?php echo $strRowsStatistic . '&nbsp;:' . "\n"; ?>
        <table border="<?php echo $cfg['Border']; ?>">
        <tr>
            <th><?php echo $strStatement; ?></th>
            <th align="center"><?php echo $strValue; ?></th>
        </tr>
        <?php
        $i = 0;
        if (isset($showtable['Row_format'])) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $strFormat; ?></td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="<?php echo $cell_align_left; ?>" nowrap="nowrap">
            <?php
            echo '                ';
            if ($showtable['Row_format'] == 'Fixed') {
                echo $strFixed;
            }
            else if ($showtable['Row_format'] == 'Dynamic') {
                echo $strDynamic;
            }
            else {
                echo $showtable['Row_format'];
            }
            echo "\n";
            ?>
            </td>
        </tr>
            <?php
        }
        if (PMA_MYSQL_INT_VERSION >= 40100) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $strCharset; ?></td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="<?php echo $cell_align_left; ?>" nowrap="nowrap">
            <?php
            echo '<dfn title="' . PMA_getCollationDescr($tbl_charset) . '">' . $tbl_charset . '</dfn>';
            ?>
            </td>
        </tr>
            <?php
        }
        if (!$is_innodb && isset($showtable['Rows'])) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $strRows; ?></td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Rows'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
            <?php
        }
        if (!$is_innodb && isset($showtable['Avg_row_length']) && $showtable['Avg_row_length'] > 0) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $strRowLength; ?>&nbsp;&oslash;</td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Avg_row_length'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
            <?php
        }
        if (!$is_innodb && isset($showtable['Data_length']) && $showtable['Rows'] > 0 && $mergetable == FALSE) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $strRowSize; ?>&nbsp;&oslash;</td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="right" nowrap="nowrap">
                <?php echo $avg_size . ' ' . $avg_unit . "\n"; ?>
            </td>
        </tr>
            <?php
        }
        if (isset($showtable['Auto_increment'])) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $strNext; ?>&nbsp;Autoindex</td>
            <td bgcolor="<?php echo $bgcolor; ?>" align="right" nowrap="nowrap">
                <?php echo number_format($showtable['Auto_increment'], 0, $number_decimal_separator, $number_thousands_separator) . "\n"; ?>
            </td>
        </tr>
            <?php
        }
        echo "\n";

        if (isset($showtable['Create_time'])) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $strStatCreateTime; ?></td>
            <td style="font-size: <?php echo $font_smaller; ?>" align="right" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
                <?php echo PMA_localisedDate(strtotime($showtable['Create_time'])) . "\n"; ?>
            </td>
        </tr>
                <?php
        }
        echo "\n";

        if (isset($showtable['Update_time'])) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $strStatUpdateTime; ?></td>
            <td style="font-size: <?php echo $font_smaller; ?>" align="right" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
                <?php echo PMA_localisedDate(strtotime($showtable['Update_time'])) . "\n"; ?>
            </td>
        </tr>
                <?php
        }
        echo "\n";

        if (isset($showtable['Check_time'])) {
            $bgcolor = ((++$i%2) ? $cfg['BgcolorTwo'] : $cfg['BgcolorOne']);
            echo "\n";
            ?>
        <tr>
            <td bgcolor="<?php echo $bgcolor; ?>"><?php echo $strStatCheckTime; ?></td>
            <td style="font-size: <?php echo $font_smaller; ?>" align="right" bgcolor="<?php echo $bgcolor; ?>" nowrap="nowrap">
                <?php echo PMA_localisedDate(strtotime($showtable['Check_time'])) . "\n"; ?>
            </td>
        </tr>
            <?php
        }
        echo "\n";
        ?>
        </table>
    </td>
        <?php
    }
}
// END - Calc Table Space
echo "\n";
?>
</tr>
</table>
<hr />


<?php
/**
 * Work on the table
 */
?>
<!-- TABLE WORK -->
<ul>

    <!-- Printable view of the table -->
    <li>
        <div style="margin-bottom: 10px"><a href="tbl_printview.php?<?php echo $url_query; ?>"><?php echo $strPrintView; ?></a></div>
    </li>

    <!-- Add some new fields -->
    <li>
        <form method="post" action="tbl_addfield.php"
            onsubmit="return checkFormElementInRange(this, 'num_fields', 1)">
            <?php echo PMA_generate_common_hidden_inputs($db, $table); ?>
            <?php echo $strAddNewField; ?>&nbsp;:
            <input type="text" name="num_fields" size="2" maxlength="2" value="1" class="textfield" style="vertical-align: middle" onfocus="this.select()" />
            <select name="after_field" style="vertical-align: middle">
                <option value="--end--"><?php echo $strAtEndOfTable; ?></option>
                <option value="--first--"><?php echo $strAtBeginningOfTable; ?></option>
<?php
foreach($aryFields AS $junk => $fieldname) {
    echo '                <option value="' . htmlspecialchars($fieldname) . '">' . sprintf($strAfter, htmlspecialchars($fieldname)) . '</option>' . "\n";
}
unset($aryFields);
?>
            </select>
            <input type="submit" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
        </form>
    </li>

<?php
if ($cfg['Server']['relation']) {
    ?>
    <!-- Work on Relations -->
    <li>
        <div style="margin-bottom: 10px">
            <a href="tbl_relation.php?<?php echo $url_query; ?>"><?php echo $strRelationView; ?></a>
        </div>
    </li>
    <?php
}
echo "\n";
?>

    <!-- Let MySQL propose the optimal structure -->
    <li>
        <div style="margin-bottom: 10px">
        <a href="sql.php?<?php echo $url_query; ?>&amp;session_max_rows=all&amp;sql_query=<?php echo urlencode('SELECT * FROM ' . PMA_backquote($table) . ' PROCEDURE ANALYSE()'); ?>">
            <?php echo $strStructPropose; ?></a>
        <?php echo PMA_showMySQLDocu('Extending_MySQL', 'procedure_analyse') . "\n"; ?>
        </div>
    </li>

<?php
/**
 * Query box, bookmark, insert data from textfile
 */
$goto = 'tbl_properties_structure.php';
require('./tbl_query_box.php');
?>

</ul>


<?php
/**
 * Displays the footer
 */
require_once('./footer.inc.php');
?>
