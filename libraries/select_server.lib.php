<?php
/*
 * Code for displaying server selection written by nijel
 * $Id$
 */

/**
 * display server selection in list or selectbox form, or option tags only
 *
 * @todo    make serverlist a real html-list
 * @globals $lang
 * @globals $convcharset
 * @uses    $GLOBALS['cfg']['DisplayServersList']
 * @uses    $GLOBALS['strServer']
 * @uses    $GLOBALS['cfg']['Servers']
 * @uses    $GLOBALS['strGo']
 * @uses    implode()
 * @uses    htmlspecialchars()
 * @param   boolean $not_only_options   whether to include form tags or not
 * @param   boolean $ommit_fieldset     whether to ommit fieldset tag or not
 */
function PMA_select_server($not_only_options, $ommit_fieldset)
{
    global $lang, $convcharset;

    // Show as list?
    if ($not_only_options) {
        $list = $GLOBALS['cfg']['DisplayServersList'];
        $not_only_options =! $list;
    } else {
        $list = false;
    }

    if ($not_only_options) {
        echo '<form method="post" action="index.php" target="_parent">';

        if (! $ommit_fieldset) {
            echo '<fieldset>';
        }
        echo '<label for="select_server">' . $GLOBALS['strServer'] . ':</label> ';

        echo '<select name="server" id="select_server"'
            . ' onchange="if (this.value != \'\') this.form.submit();">';
        // TODO FIXME replace with $GLOBALS['strServers']
        echo '<option value="">(' . $GLOBALS['strServer'] . ') ...</option>' . "\n";
    } elseif ($list) {
        echo $GLOBALS['strServer'] . ':<br />';
        // TODO FIXME display server list as 'list'
        // echo '<ol>';
    }

    foreach ($GLOBALS['cfg']['Servers'] as $key => $server) {
        if (empty($server['host'])) {
            continue;
        }

        if (!empty($GLOBALS['server']) && (int) $GLOBALS['server'] === (int) $key) {
            $selected = 1;
        } else {
            $selected = 0;
        }

        if (!empty($server['verbose'])) {
            $label = $server['verbose'];
        } else {
            $label = $server['host'];
            if (!empty($server['port'])) {
                $label .= ':' . $server['port'];
            }
        }
        // loic1: if 'only_db' is an array and there is more than one
        //        value, displaying such informations may not be a so good
        //        idea
        if (!empty($server['only_db'])) {
            // TODO FIXME this can become a really big/long/wide selectbox ...
            $label .= ' - ' . (is_array($server['only_db']) ? implode(', ', $server['only_db']) : $server['only_db']);
        }
        if (!empty($server['user']) && $server['auth_type'] == 'config') {
            $label .= '  (' . $server['user'] . ')';
        }

        if ($list) {
            // TODO FIXME display server list as 'list'
            // echo '<li>';
            if ($selected && !$ommit_fieldset) {
                echo '&raquo; <b>' . htmlspecialchars($label) . '</b><br />';
            } else {
                echo '&raquo; <a class="item" href="index.php?server=' . $key . '&amp;lang=' . $lang . '&amp;convcharset=' . $convcharset . '" target="_top">' . htmlspecialchars($label) . '</a><br />';
            }
            // echo '</li>';
        } else {
            echo '            <option value="' . $key . '" ' . ($selected ? ' selected="selected"' : '') . '>' . htmlspecialchars($label) . '</option>' . "\n";
        }
    } // end while

    if ($not_only_options) {
        echo '</select>';
        if ($ommit_fieldset) {
            echo '<hr />';
        } else {
            echo '</fieldset>';
        }
        ?>
        <input type="hidden" name="lang" value="<?php echo $lang; ?>" />
        <input type="hidden" name="convcharset" value="<?php echo $convcharset; ?>" />
        <?php
        // Show submit button if we have just one server (this happens with no default)
        echo '<noscript>';
        echo '<input type="submit" value="' . $GLOBALS['strGo'] . '" />';
        echo '</noscript>';
        echo '</form>';
    } elseif ($list) {
        // TODO FIXME display server list as 'list'
        // echo '</ol>';
    }
}
?>
