<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * returns code for selecting databases
 *
 * @return String HTML code
 */
function PMA_replication_db_multibox()
{
    $multi_values = '';
    $multi_values .= '<select name="db_select[]" size="6" multiple="multiple" id="db_select">';

    foreach ($GLOBALS['pma']->databases as $current_db) {
        if (PMA_is_system_schema($current_db)) {
            continue;
        }
        if (! empty($selectall) || (isset($tmp_select) && strpos(' ' . $tmp_select, '|' . $current_db . '|'))) {
            $is_selected = ' selected="selected"';
        } else {
            $is_selected = '';
        }
        $current_db = htmlspecialchars($current_db);
        $multi_values .= '                <option value="' . $current_db . '" ' . $is_selected . '>' . $current_db . '</option>';
    } // end while

    $multi_values .= '</select>';
    $multi_values .= '<br /><a href="#" id="db_reset_href">' . __('Uncheck All') . '</a>';

    return $multi_values;
}

/**
 * prints out code for changing master
 *
 * @param String $submitname - submit button name
 */

function PMA_replication_gui_changemaster($submitname)
{

    list($username_length, $hostname_length) = PMA_replication_get_username_hostname_length();

    echo '<form method="post" action="server_replication.php">';
    echo PMA_generate_common_hidden_inputs('', '');
    echo ' <fieldset id="fieldset_add_user_login">';
    echo '  <legend>' . __('Slave configuration') . ' - ' . __('Change or reconfigure master server') . '</legend>';
    echo __('Make sure, you have unique server-id in your configuration file (my.cnf). If not, please add the following line into [mysqld] section:') . '<br />';
    echo '<pre>server-id=' . time() . '</pre>';
    echo '  <div class="item">';
    echo '    <label for="text_username">' . __('User name') . ':</label>';
    echo '    <input type="text" name="username" id="text_username" maxlength="'. $username_length . '" title="' . __('User name') . '" />';
    echo '  </div>';
    echo '  <div class="item">';
    echo '    <label for="text_pma_pw">' . __('Password') .' :</label>';
    echo '    <input type="password" id="text_pma_pw" name="pma_pw" title="' . __('Password') . '" />';
    echo '  </div>';
    echo '  <div class="item">';
    echo '    <label for="text_hostname">' . __('Host') . ' :</label>';
    echo '    <input type="text" id="text_hostname" name="hostname" maxlength="' . $hostname_length . '" value="" />';
    echo '  </div>';
    echo '  <div class="item">';
    echo '     <label for="text_port">' . __('Port') . ':</label>';
    echo '     <input type="text" id="text_port" name="port" maxlength="6" value="3306"  />';
    echo '  </div>';
    echo ' </fieldset>';
    echo ' <fieldset id="fieldset_user_privtable_footer" class="tblFooters">';
    echo '    <input type="hidden" name="sr_take_action" value="true" />';
    echo '     <input type="submit" name="' . $submitname . '" id="confslave_submit" value="' . __('Go') . '" />';
    echo ' </fieldset>';
    echo '</form>';
}

/**
 * This function prints out table with replication status.
 *
 * @param string  $type   either master or slave
 * @param boolean $hidden if true, then default style is set to hidden, default value false
 * @param boolen  $title  if true, then title is displayed, default true
 */
function PMA_replication_print_status_table($type, $hidden = false, $title = true)
{
    global ${"{$type}_variables"};
    global ${"{$type}_variables_alerts"};
    global ${"{$type}_variables_oks"};
    global ${"server_{$type}_replication"};
    global ${"strReplicationStatus_{$type}"};

    // TODO check the Masters server id?
    // seems to default to '1' when queried via SHOW VARIABLES , but resulted in error on the master when slave connects
    // [ERROR] Error reading packet from server: Misconfigured master - server id was not set ( server_errno=1236)
    // [ERROR] Got fatal error 1236: 'Misconfigured master - server id was not set' from master when reading data from binary log
    //
    //$server_id = PMA_DBI_fetch_value("SHOW VARIABLES LIKE 'server_id'", 0, 1);

    echo '<div id="replication_' . $type . '_section" style="' . ($hidden ? 'display: none;' : '') . '"> ';

    if ($title) {
        if ($type == 'master') {
            echo '<h4><a name="replication_' . $type . '"></a>' . __('Master status') . '</h4>';
        } else {
            echo '<h4><a name="replication_' . $type . '"></a>' . __('Slave status') . '</h4>';
        }
    } else {
        echo '<br />';
    }

    echo '   <table id="server' . $type . 'replicationsummary" class="data"> ';
    echo '   <thead>';
    echo '    <tr>';
    echo '     <th>' . __('Variable') . '</th>';
    echo '        <th>' . __('Value') . '</th>';
    echo '    </tr>';
    echo '   </thead>';
    echo '   <tbody>';

    $odd_row = true;
    foreach (${"{$type}_variables"} as $variable) {
        echo '   <tr class="' . ($odd_row ? 'odd' : 'even') . '">';
        echo '     <td class="name">';
        echo        $variable;
        echo '     </td>';
        echo '     <td class="value">';


        // TODO change to regexp or something, to allow for negative match
        if (isset(${"{$type}_variables_alerts"}[$variable])
            && ${"{$type}_variables_alerts"}[$variable] == ${"server_{$type}_replication"}[0][$variable]
        ) {
            echo '<span class="attention">';

        } elseif (isset(${"{$type}_variables_oks"}[$variable])
            && ${"{$type}_variables_oks"}[$variable] == ${"server_{$type}_replication"}[0][$variable]
        ) {
            echo '<span class="allfine">';
        } else {
            echo '<span>';
        }
        echo ${"server_{$type}_replication"}[0][$variable];
        echo '</span>';

        echo '  </td>';
        echo ' </tr>';

        $odd_row = ! $odd_row;
    }

    echo '   </tbody>';
    echo ' </table>';
    echo ' <br />';
    echo '</div>';

}

/**
 * Prints table with slave users connected to this master
 *
 * @param boolean $hidden - if true, then default style is set to hidden, default value false
 */
function PMA_replication_print_slaves_table($hidden = false)
{

    // Fetch data
    $data = PMA_DBI_fetch_result('SHOW SLAVE HOSTS', null, null);

    echo '  <br />';
    echo '  <div id="replication_slaves_section" style="' . ($hidden ? 'display: none;' : '') . '"> ';
    echo '    <table class="data">';
    echo '    <thead>';
    echo '      <tr>';
    echo '        <th>' . __('Server ID') . '</th>';
    echo '        <th>' . __('Host') . '</th>';
    echo '      </tr>';
    echo '    </thead>';
    echo '    <tbody>';

    $odd_row = true;
    foreach ($data as $slave) {
        echo '    <tr class="' . ($odd_row ? 'odd' : 'even') . '">';
        echo '      <td class="value">' . $slave['Server_id'] . '</td>';
        echo '      <td class="value">' . $slave['Host'] . '</td>';
        echo '    </tr>';

        $odd_row = ! $odd_row;
    }

    echo '    </tbody>';
    echo '    </table>';
    echo '    <br />';
    PMA_Message::notice(__('Only slaves started with the --report-host=host_name option are visible in this list.'))->display();
    echo '    <br />';
    echo '  </div>';
}

/**
 * get the correct username and hostname lengths for this MySQL server
 *
 * @return  array   username length, hostname length
 */

function PMA_replication_get_username_hostname_length()
{
    $fields_info = PMA_DBI_get_columns('mysql', 'user');
    $username_length = 16;
    $hostname_length = 41;
    foreach ($fields_info as $val) {
        if ($val['Field'] == 'User') {
            strtok($val['Type'], '()');
            $v = strtok('()');
            if (is_int($v)) {
                $username_length = $v;
            }
        } elseif ($val['Field'] == 'Host') {
            strtok($val['Type'], '()');
            $v = strtok('()');
            if (is_int($v)) {
                $hostname_length = $v;
            }
        }
    }
    return array($username_length, $hostname_length);
}

/**
 * Print code to add a replication slave user to the master
 */
function PMA_replication_gui_master_addslaveuser()
{

    list($username_length, $hostname_length) = PMA_replication_get_username_hostname_length();

    if (isset($GLOBALS['username']) && strlen($GLOBALS['username']) === 0) {
        $GLOBALS['pred_username'] = 'any';
    }
    echo '<div id="master_addslaveuser_gui">';
    echo '<form autocomplete="off" method="post" action="server_privileges.php" onsubmit="return checkAddUser(this);">';
    echo PMA_generate_common_hidden_inputs('', '');
    echo '<fieldset id="fieldset_add_user_login">'
        . '<legend>'.__('Add slave replication user').'</legend>'
    . '<input type="hidden" name="grant_count" value="25" />'
    . '<input type="hidden" name="createdb" id="createdb_0" value="0" />'
        . '<input id="checkbox_Repl_slave_priv" type="hidden" title="Needed for the replication slaves." value="Y" name="Repl_slave_priv"/>'
        . '<input id="checkbox_Repl_client_priv" type="hidden" title="Needed for the replication slaves." value="Y" name="Repl_client_priv"/>'
    . ''
        . '<input type="hidden" name="sr_take_action" value="true" />'
        . '<div class="item">'
        . '<label for="select_pred_username">'
        . '    ' . __('User name') . ':'
        . '</label>'
        . '<span class="options">'
        . '    <select name="pred_username" id="select_pred_username" title="' . __('User name') . '"'
        . '        onchange="if (this.value == \'any\') { username.value = \'\'; } else if (this.value == \'userdefined\') { username.focus(); username.select(); }">'
        . '        <option value="any"' . ((isset($GLOBALS['pred_username']) && $GLOBALS['pred_username'] == 'any') ? ' selected="selected"' : '') . '>' . __('Any user') . '</option>'
        . '        <option value="userdefined"' . ((! isset($GLOBALS['pred_username']) || $GLOBALS['pred_username'] == 'userdefined') ? ' selected="selected"' : '') . '>' . __('Use text field') . ':</option>'
        . '    </select>'
        . '</span>'
        . '<input type="text" name="username" maxlength="'
        . $username_length . '" title="' . __('User name') . '"'
        . (empty($GLOBALS['username'])
        ? ''
        : ' value="' . (isset($GLOBALS['new_username'])
        ? $GLOBALS['new_username']
        : $GLOBALS['username']) . '"')
        . ' onchange="pred_username.value = \'userdefined\';" />'
        . '</div>'
        . '<div class="item">'
        . '<label for="select_pred_hostname">'
        . '    ' . __('Host') . ':'
        . '</label>'
        . '<span class="options">'
        . '    <select name="pred_hostname" id="select_pred_hostname" title="' . __('Host') . '"';
    $_current_user = PMA_DBI_fetch_value('SELECT USER();');
    if (! empty($_current_user)) {
        $thishost = str_replace("'", '', substr($_current_user, (strrpos($_current_user, '@') + 1)));
        if ($thishost == 'localhost' || $thishost == '127.0.0.1') {
            unset($thishost);
        }
    }
    echo '    onchange="if (this.value == \'any\') { hostname.value = \'%\'; } else if (this.value == \'localhost\') { hostname.value = \'localhost\'; } '
        . (empty($thishost) ? '' : 'else if (this.value == \'thishost\') { hostname.value = \'' . addslashes(htmlspecialchars($thishost)) . '\'; } ')
        . 'else if (this.value == \'hosttable\') { hostname.value = \'\'; } else if (this.value == \'userdefined\') { hostname.focus(); hostname.select(); }">' . "\n";
    unset($_current_user);

    // when we start editing a user, $GLOBALS['pred_hostname'] is not defined
    if (! isset($GLOBALS['pred_hostname']) && isset($GLOBALS['hostname'])) {
        switch (strtolower($GLOBALS['hostname'])) {
        case 'localhost':
        case '127.0.0.1':
            $GLOBALS['pred_hostname'] = 'localhost';
            break;
        case '%':
            $GLOBALS['pred_hostname'] = 'any';
            break;
        default:
            $GLOBALS['pred_hostname'] = 'userdefined';
            break;
        }
    }
    echo '        <option value="any"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'any')
        ? ' selected="selected"' : '') . '>' . __('Any host')
        . '</option>'
        . '        <option value="localhost"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'localhost')
        ? ' selected="selected"' : '') . '>' . __('Local')
        . '</option>';

    if (!empty($thishost)) {
        echo '        <option value="thishost"'
            . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'thishost')
            ? ' selected="selected"' : '') . '>' . __('This Host')
            . '</option>';
    }
    unset($thishost);
    echo '        <option value="hosttable"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'hosttable')
        ? ' selected="selected"' : '') . '>' . __('Use Host Table')
        . '</option>'
        . '        <option value="userdefined"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'userdefined')
        ? ' selected="selected"' : '')
        . '>' . __('Use text field') . ':</option>'
        . '    </select>'
        . '</span>'
        . '<input type="text" name="hostname" maxlength="'
        . $hostname_length . '" value="'
        . (isset($GLOBALS['hostname']) ? $GLOBALS['hostname'] : '')
        . '" title="' . __('Host')
        . '" onchange="pred_hostname.value = \'userdefined\';" />'
        . PMA_showHint(__('When Host table is used, this field is ignored and values stored in Host table are used instead.'))
        . '</div>'
        . '<div class="item">'
        . '<label for="select_pred_password">'
        . '    ' . __('Password') . ':'
        . '</label>'
        . '<span class="options">'
        . '    <select name="pred_password" id="select_pred_password" title="'
        . __('Password') . '"'
        . '            onchange="if (this.value == \'none\') { pma_pw.value = \'\'; pma_pw2.value = \'\'; } else if (this.value == \'userdefined\') { pma_pw.focus(); pma_pw.select(); }">'
        . '        <option value="none"';
    if (isset($GLOBALS['username']) && $mode != 'change') {
        echo '  selected="selected"';
    }
    echo '>' . __('No Password') . '</option>'
        . '        <option value="userdefined"' . (isset($GLOBALS['username']) ? '' : ' selected="selected"') . '>' . __('Use text field') . ':</option>'
        . '    </select>'
        . '</span>'
        . '<input type="password" id="text_pma_pw" name="pma_pw" title="' . __('Password') . '" onchange="pred_password.value = \'userdefined\';" />'
        . '</div>'
        . '<div class="item">'
        . '<label for="text_pma_pw2">'
        . '    ' . __('Re-type') . ':'
        . '</label>'
        . '<span class="options">&nbsp;</span>'
        . '<input type="password" name="pma_pw2" id="text_pma_pw2" title="' . __('Re-type') . '" onchange="pred_password.value = \'userdefined\';" />'
        . '</div>'
        . '<div class="item">'
        . '<label for="button_generate_password">'
        . '    ' . __('Generate Password') . ':'
        . '</label>'
        . '<span class="options">'
        . '    <input type="button" id="button_generate_password" value="' . __('Generate') . '" onclick="suggestPassword(this.form)" />'
        . '</span>'
        . '<input type="text" name="generated_pw" id="generated_pw" />'
        . '</div>'
        . '</fieldset>';
    echo '<fieldset id="fieldset_user_privtable_footer" class="tblFooters">'
        . '    <input type="submit" name="adduser_submit" id="adduser_submit" value="' . __('Go') . '" />'
        . '</fieldset>';
    echo '</form>';
    echo '</div>';
}
?>
