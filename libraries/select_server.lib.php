<?php
/*
 * Code for displaying server selection written by nijel
 * $Id$
 */

if (count($cfg['Servers']) > 1) {
    if (!$cfg['DisplayServersList']) {
    ?>
    <form method="post" action="index.php" target="_parent" style="margin: 0px; padding: 0px;">
    <?php
    }
    if ($show_server_left) {
        echo '<div class="heada">' . $strServer . ':</div>';
    } else {
    ?>
<!-- MySQL servers choice form -->
<table border="0" cellpadding="3" cellspacing="0" bgcolor="<?php echo $cfg['BgcolorOne']; ?>">
    <tr>
        <th class="tblHeaders"><?php echo $strServerChoice; ?></th>
    </tr>
    <tr>
        <td>
    <?php
    }
    if (!$cfg['DisplayServersList']) {
    ?>
    <form method="post" action="index.php" target="_parent" style="margin: 0px; padding: 0px;">
        <select name="server" onchange="this.form.submit();">
    <?php
    }
    foreach ($cfg['Servers'] AS $key => $val) {
        if (!empty($val['host'])) {
             $selected = 0;
            if (!empty($server) && ($server == $key)) {
                $selected = 1;
            }
            if (!empty($val['verbose'])) {
                $label = $val['verbose'];
            } else {
                $label = $val['host'];
                if (!empty($val['port'])) {
                    $label .= ':' . $val['port'];
                }
            }
            // loic1: if 'only_db' is an array and there is more than one
            //        value, displaying such informations may not be a so good
            //        idea
            if (!empty($val['only_db'])) {
                $label .= ' - ' . (is_array($val['only_db']) ? implode(', ', $val['only_db']) : $val['only_db']);
            }
            if (!empty($val['user']) && ($val['auth_type'] == 'config')) {
                $label .= '  (' . $val['user'] . ')';
            }

            if ($cfg['DisplayServersList']){
                if ($selected && !$show_server_left) {
                    echo '&raquo; <b>' . $label . '</b><br />';
                }else{
                    echo '&raquo; <a class="item" href="index.php?server=' . $key . '&amp;lang=' . $lang . '&amp;convcharset=' . $convcharset . '" target="_top">' . $label . '</a><br />';
                }
            } else {
                echo '                <option value="' . $key . '" ' . ($selected ? ' selected="selected"' : '') . '>' . $label . '</option>' . "\n";
            }

        } // end if (!empty($val['host']))
    } // end while

    if (!$cfg['DisplayServersList']){
?>
        </select>
        <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
        <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
        <input type="submit" value="<?php echo $strGo; ?>" />
    </form>
<?php
    }
    if (!$show_server_left) {
    ?>
        </td>
    </tr>
</table>
<br />
<?php
    } else {
        echo '<hr />' . "\n";
    }
}
?>
