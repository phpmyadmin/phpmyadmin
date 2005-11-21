<?php
/*
 * Code for displaying server selection written by nijel
 * $Id$
 */

if (count($cfg['Servers']) > 1) {
    if ($show_server_left) {
        echo '<div class="heada">' . $strServer . ':</div>';
    } else {
        ?> 
<fieldset>
<legend><?php echo $strServerChoice; ?></legend>
        <?php
    }
    if (!$cfg['DisplayServersList']) {
        ?> 
    <form method="post" action="index.php" target="_parent">
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
                    echo '&raquo; <b>' . htmlspecialchars($label) . '</b><br />';
                }else{
                    echo '&raquo; <a class="item" href="index.php?server=' . $key . '&amp;lang=' . $lang . '&amp;convcharset=' . $convcharset . '" target="_top">' . htmlspecialchars($label) . '</a><br />';
                }
            } else {
                echo '            <option value="' . $key . '" ' . ($selected ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>' . "\n";
            }

        } // end if (!empty($val['host']))
    } // end while

    if ( ! $cfg['DisplayServersList'] ) {
        ?> 
        </select>
        <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
        <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
        <noscript>
        <input type="submit" value="<?php echo $strGo; ?>" />
        </noscript>
    </form>
        <?php
    }
    if (!$show_server_left) {
        ?> 
</fieldset>
        <?php
    } else {
        echo '<hr />';
    }
}
?> 
