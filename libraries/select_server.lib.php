<?php
/*
 * Code for displaying server selection written by nijel
 * $Id$
 */

function PMA_select_server($form, $left) {
    global $cfg, $lang, $convcharset;
   
    // Show as list?
    $list = $cfg['DisplayServersList'];
    if (!$form) {
        $list = FALSE;
    }
   
    if ($form) {
        if ($left) {
            echo '<div class="heada">' . $GLOBALS['strServer']. ':</div>';
        } else {
            ?> 
    <fieldset>
    <legend><?php echo $GLOBALS['strServerChoice']; ?></legend>
            <?php
        }
        if (!$list) {
            ?> 
        <form method="post" action="index.php" target="_parent">
            <select name="server" onchange="this.form.submit();">
            <?php
        }
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

            if ($list){
                if ($selected && !$left) {
                    echo '&raquo; <b>' . htmlspecialchars($label) . '</b><br />';
                }else{
                    echo '&raquo; <a class="item" href="index.php?server=' . $key . '&amp;lang=' . $lang . '&amp;convcharset=' . $convcharset . '" target="_top">' . htmlspecialchars($label) . '</a><br />';
                }
            } else {
                echo '            <option value="' . $key . '" ' . ($selected ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>' . "\n";
            }

        } // end if (!empty($val['host']))
    } // end while

    if ($form) {
        if ( ! $list ) {
        ?> 
        </select>
        <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
        <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
        <noscript>
        <input type="submit" value="<?php echo $GLOBALS['strGo']; ?>" />
        </noscript>
    </form>
        <?php
    }
        if (!$left) {
        ?> 
</fieldset>
        <?php
        } else {
            echo '<hr />';
        }
    }
}
?> 
