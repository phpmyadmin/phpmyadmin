<?php
/* $Id$ */


/**
 * Gets tables informations and displays top links
 */
require('./tbl_properties_common.php3');
require('./tbl_properties_table_info.php3');


/**
 * Displays form controls
 */
if (PMA_MYSQL_INT_VERSION >= 32322) {
    ?>
<ul>
    <!-- Table comments -->
    <li>
        <form method="post" action="tbl_properties.php3">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <?php echo $strTableComments; ?>&nbsp;:&nbsp;
            <input type="hidden" name="prev_comment" value="<?php echo urlencode($show_comment); ?>" />&nbsp;
            <input type="text" name="comment" maxlength="60" size="30" value="<?php echo str_replace('"', '&quot;', $show_comment); ?>" class="textfield" style="vertical-align: middle" onfocus="this.select()" />&nbsp;
            <input type="submit" name="submitcomment" value="<?php echo $strGo; ?>" style="vertical-align: middle" />
        </form>
    </li>

    <!-- Table type -->
    <?php
    // modify robbat2 code - staybyte - 11. June 2001
    $query  = 'SHOW VARIABLES LIKE \'have_%\'';
    $result = mysql_query($query);
    if ($result != FALSE && mysql_num_rows($result) > 0) {
        while ($tmp = mysql_fetch_array($result)) {
            if (isset($tmp['Variable_name'])) {
                switch ($tmp['Variable_name']) {
                    case 'have_bdb':
                        if ($tmp['Value'] == 'YES') {
                            $tbl_bdb    = TRUE;
                        }
                        break;
                    case 'have_gemini':
                        if ($tmp['Value'] == 'YES') {
                            $tbl_gemini = TRUE;
                        }
                        break;
                    case 'have_innodb':
                        if ($tmp['Value'] == 'YES') {
                            $tbl_innodb = TRUE;
                        }
                        break;
                    case 'have_isam':
                        if ($tmp['Value'] == 'YES') {
                            $tbl_isam   = TRUE;
                        }
                        break;
                } // end switch
            } // end if isset($tmp['Variable_name'])
        } // end while
    } // end if $result

    mysql_free_result($result);
    echo "\n";
    ?>
    <li>
        <form method="post" action="tbl_properties.php3">
            <input type="hidden" name="server" value="<?php echo $server; ?>" />
            <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
            <input type="hidden" name="db" value="<?php echo $db; ?>" />
            <input type="hidden" name="table" value="<?php echo $table; ?>" />
            <?php echo $strTableType; ?>&nbsp;:&nbsp;
            <select name="tbl_type" style="vertical-align: middle">
                <option value="MYISAM"<?php if ($tbl_type == 'MYISAM') echo ' selected="selected"'; ?>>MyISAM</option>
                <option value="HEAP"<?php if ($tbl_type == 'HEAP') echo ' selected="selected"'; ?>>Heap</option>
                <?php if (isset($tbl_bdb)) { ?><option value="BDB"<?php if ($tbl_type == 'BERKELEYDB') echo ' selected="selected"'; ?>>Berkeley DB</option><?php } ?>
                <?php if (isset($tbl_gemini)) { ?><option value="GEMINI"<?php if ($tbl_type == 'GEMINI') echo ' selected="selected"'; ?>>Gemini</option><?php } ?>
                <?php if (isset($tbl_innodb)) { ?><option value="INNODB"<?php if ($tbl_type == 'INNODB') echo ' selected="selected"'; ?>>INNO DB</option><?php } ?>
                <?php if (isset($tbl_isam)) { ?><option value="ISAM"<?php if ($tbl_type == 'ISAM') echo ' selected="selected"'; ?>>ISAM</option><?php } ?>
                <option value="MERGE"<?php if ($tbl_type == 'MRG_MYISAM') echo ' selected="selected"'; ?>>Merge</option>
            </select>&nbsp;
            <input type="submit" name="submittype" value="<?php echo $strGo; ?>" style="vertical-align: middle" />&nbsp;
            <?php echo PMA_showDocuShort('T/a/Table_types.html') . "\n"; ?>
        </form>
    </li>

    <!-- Table options -->
    <li style="vertical-align: top">
        <table border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td>
                <form method="post" action="tbl_properties.php3">
                    <input type="hidden" name="server" value="<?php echo $server; ?>" />
                    <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
                    <input type="hidden" name="db" value="<?php echo $db; ?>" />
                    <input type="hidden" name="table" value="<?php echo $table; ?>" />
 
                    <table border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td>
                            <input type="checkbox" name="pack_keys" id="pack_keys_opt"
                                <?php echo (isset($pack_keys) && $pack_keys == 1) ? ' checked="checked"' : ''; ?> />
                            <label for="pack_keys_opt">pack_keys</label>&nbsp;&nbsp;
                            <br />
                            <input type="checkbox" name="checksum" id="checksum_opt"
                                <?php echo (isset($checksum) && $checksum == 1) ? ' checked="checked"' : ''; ?> />
                            <label for="checksum_opt">checksum</label>&nbsp;&nbsp;
                            <br />
                            <input type="checkbox" name="delay_key_write" id="delay_key_write_opt"
                                <?php echo (isset($delay_key_write) && $delay_key_write == 1) ? ' checked="checked"' : ''; ?> />
                            <label for="delay_key_write_opt">delay_key_write</label>&nbsp;&nbsp;
                            &nbsp;&nbsp;
                        </td>
                        <td>
                            <input type="submit" name="submitoptions" value="<?php echo $strGo; ?>" />
                        </td>
                    </tr>
                    </table>
                </form>
            </td>
        </tr>
        </table>
    </li>
</ul>
    <?php
} // end if (PMA_MYSQL_INT_VERSION >= 32322)


/**
 * Displays the footer
 */
echo "\n";
require('./footer.inc.php3');
?>
