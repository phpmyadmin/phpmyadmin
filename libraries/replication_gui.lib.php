<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

$jscode['master_replication'] = 'divShowHideFunc(\'master_status_href\', \'replication_master_section\');' . "\n"
    . 'divShowHideFunc(\'master_slaves_href\', \'replication_slaves_section\');' . "\n";

// Add JS events to generate example my.cnf config lines
$jscode['configure_master'] = 
    'var c_output = "";'."\n".
    'var c_text = "server-id='.$serverid.'<br />log-bin=mysql-bin<br />log-error=mysql-bin.err<br />";'."\n".
    'var c_ignore = "binlog_ignore_db=";'."\n".
    'var c_do = "binlog_do_db=";'."\n".

    '$(\'db_reset_href\').addEvent(\'click\', function() {'."\n".
    '   $(\'db_select\').getSelected().each(function(el) {'."\n".
    '	  el.selected = false;'."\n".
    '	});'."\n".
    '	$(\'rep\').set(\'html\', c_text);'."\n".
    '});'."\n".
    '$(\'db_type\').addEvent(\'change\',function() {'."\n".
    '	if ($(\'db_type\').getSelected().get(\'value\')=="all")'."\n".
    '	  $(\'rep\').set(\'html\', c_text+c_ignore+c_output);'."\n".
    '	else'."\n". 
    '	  $(\'rep\').set(\'html\', c_text+c_do+c_output);'."\n".
    '});'."\n".

    '$(\'db_select\').addEvent(\'change\',function() {'."\n".
    '  var count = 0;'."\n".

    '  $(\'db_select\').getSelected().each(function(el) {      '."\n".
    '	      if (count==0)'."\n".
    '		c_output = el.get(\'value\');'."\n".
    '	      else'."\n".
    '		c_output = c_output + \',\' +el.get(\'value\');'."\n".

    '	      count=count+1;'."\n".

    '	      if ($(\'db_select\').getSelected().length==count) {'."\n".
    '		if ($(\'db_type\').getSelected().get(\'value\')=="all")'."\n".
    '		  $(\'rep\').set(\'html\', c_text+c_ignore+c_output);'."\n".
    '		else'."\n". 
    '		  $(\'rep\').set(\'html\', c_text+c_do+c_output);'."\n".
    '		count = 0;'."\n".
    '	      }'."\n".
    ' });'."\n".
    '});'."\n";

$jscode['slave_control'] = 
    'divShowHideFunc(\'slave_status_href\', \'replication_slave_section\');'."\n".
    'divShowHideFunc(\'slave_control_href\', \'slave_control_gui\');'."\n".
    'divShowHideFunc(\'slave_errormanagement_href\',\'slave_errormanagement_gui\'); '."\n";

$jscode['slave_control_sync']  = 
    'divShowHideFunc(\'slave_synchronization_href\', \'slave_synchronization_gui\');'."\n";

/**
 * returns code for selecting databases
 *
 * @return String HTML code
 */
function PMA_replication_db_multibox()
{
    $multi_values = '';
    $multi_values .= '<select name="db_select[]" size="6" multiple="multiple" id="db_select">';
    $multi_values .= "\n";

    foreach ($GLOBALS['pma']->databases as $current_db) {
        if (!empty($selectall) || (isset($tmp_select) && strpos(' ' . $tmp_select, '|' . $current_db . '|'))) {
            $is_selected = ' selected="selected"';
        } else {
            $is_selected = '';
        }
        $current_db = htmlspecialchars($current_db);
        $multi_values .= '                <option value="' . $current_db . '" ' . $is_selected . '>' . $current_db . '</option>' . "\n";
    } // end while

    $multi_values .= "\n";
    $multi_values .= '</select>';
    $multi_values .= '<br /><a href="#" id="db_reset_href">' . $GLOBALS['strUncheckAll'] . '</a>';

    return $multi_values;
}

/**
 * prints out code for changing master
 *
 * @param String $submitname - submit button name 
 */

function PMA_replication_gui_changemaster($submitname) {
    global $username_length;
    global $hostname_length;

    echo '<form method="post" action="server_replication.php">'."\n";
    echo PMA_generate_common_hidden_inputs('', ''); 
    echo ' <fieldset id="fieldset_add_user_login">'."\n";
    echo '  <legend>' . $GLOBALS['strReplicationSlaveConfiguration']. ' - ' . $GLOBALS['strReplicationSlaveChangeMaster'] . '</legend>'."\n";
    echo $GLOBALS['strSlaveConfigure'].'<br />'."\n";
    echo '<pre>server-id='.time().'</pre>'."\n";
    echo '  <div class="item">'."\n";
    echo '    <label for="text_username">'. $GLOBALS['strUserName'].':</label>'."\n";
    echo '    <input type="text" name="username" id="text_username" maxlength="'. $username_length .'" title="'. $GLOBALS['strUserName'] .'" />'."\n";
    echo '  </div>'."\n";
    echo '  <div class="item">'."\n";
    echo '    <label for="text_pma_pw">'. $GLOBALS['strPassword'] .' :</label>'."\n";
    echo '    <input type="password" id="text_pma_pw" name="pma_pw" title="'. $GLOBALS['strPassword'] .'" />'."\n";
    echo '  </div>'."\n";
    echo '  <div class="item">'."\n";
    echo '    <label for="text_hostname">'.$GLOBALS['strHost'].' :</label>'."\n";
    echo '    <input type="text" id="text_hostname" name="hostname" maxlength="'. $hostname_length .'" value="" />'."\n";
    echo '  </div>'."\n";
    echo '  <div class="item">'."\n";
    echo '     <label for="text_port">'.$GLOBALS['strPort'].':</label>'."\n";
    echo '     <input type="text" id="text_port" name="port" maxlength="6" value="3306"  />'."\n";
    echo '  </div>'."\n";
    echo ' </fieldset>'."\n";
    echo ' <fieldset id="fieldset_user_privtable_footer" class="tblFooters">'."\n";
    echo '    <input type="hidden" name="sr_take_action" value="true" />'."\n";
    echo '     <input type="submit" name="'.$submitname.'" id="confslave_submit" value="'. $GLOBALS['strGo'] .'" />'."\n";
    echo ' </fieldset>'."\n";
    echo '</form>'."\n";

}

/**
 * This function prints out table with replication status.
 *
 * @param String type - either master or slave
 * @param boolean $hidden - if true, then default style is set to hidden, default value false
 * @param boolen $title - if true, then title is displayed, default true
 */
function PMA_replication_print_status_table($type, $hidden = false, $title = true) {
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

    echo '<div id="replication_'. $type .'_section" style="'. ($hidden ? 'display: none' : '') .'"> '."\n";

    if ($title) {
        echo '<h4><a name="replication_'. $type .'"></a>'.${"strReplicationStatus_{$type}"} .'</h4>'."\n";
    } else {
        echo '<br />'."\n";
    }

    echo '   <table id="server'. $type .'replicationsummary" class="data"> '."\n";
    echo '   <thead>'."\n";
    echo '    <tr>'."\n";
    echo ' 	<th>'. $GLOBALS['strVar'] .'</th>'."\n";
    echo '		<th>'. $GLOBALS['strValue'] .'</th>'."\n";
    echo '    </tr>'."\n";
    echo '   </thead>'."\n";
    echo '   <tbody>'."\n";

    $odd_row = true;
    foreach (${"{$type}_variables"} as $variable) {
        echo '   <tr class="'. ($odd_row ? 'odd' : 'even') .'">'."\n";
        echo '     <td class="name">'."\n";
        echo        $variable."\n";
        echo '     </td>'."\n";
        echo '     <td class="value">'."\n";


        // TODO change to regexp or something, to allow for negative match
        if (isset(${"{$type}_variables_alerts"}[$variable]) 
            && ${"{$type}_variables_alerts"}[$variable] == ${"server_{$type}_replication"}[0][$variable]
        ) {
            echo '<span class="attention">'."\n";

        } elseif (isset(${"{$type}_variables_oks"}[$variable]) 
            && ${"{$type}_variables_oks"}[$variable] == ${"server_{$type}_replication"}[0][$variable]
        ) {
            echo '<span class="allfine">'."\n";
        } else {
            echo '<span>'."\n";
        }
        echo ${"server_{$type}_replication"}[0][$variable]; 
        echo '</span>'."\n";

        echo '  </td>'."\n";
        echo ' </tr>'."\n";

        $odd_row = !$odd_row;
    }

    echo '   </tbody>'."\n";
    echo ' </table>'."\n";
    echo ' <br />'."\n";
    echo '</div>'."\n";

}

/**
 * Prints table with slave users connected to this master
 *
 * @param boolean $hidden - if true, then default style is set to hidden, default value false
 */
function PMA_replication_print_slaves_table($hidden = false) {

    // Fetch data
    $data = PMA_DBI_fetch_result('SHOW SLAVE HOSTS', null, null); 

    echo '  <br />' . "\n";
    echo '  <div id="replication_slaves_section" style="'. ($hidden ? 'display: none' : '') .'"> ' . "\n";
    echo '    <table class="data"> ' . "\n";
    echo '    <thead>' . "\n";
    echo '      <tr>'."\n";
    echo '        <th>' . $GLOBALS['strBinLogServerId'] . '</th>' . "\n";
    echo '        <th>' . $GLOBALS['strHost'] . '</th>' . "\n";
    echo '      </tr>'."\n";
    echo '    </thead>'."\n";
    echo '    <tbody>'."\n";

    $odd_row = true;
    foreach ($data as $slave) {
        echo '    <tr class="'. ($odd_row ? 'odd' : 'even') .'">' . "\n";
        echo '      <td class="value">' . $slave['Server_id'] . '</td>' . "\n";
        echo '      <td class="value">' . $slave['Host'] . '</td>' . "\n";
        echo '    </tr>' . "\n";

        $odd_row = !$odd_row;
    }

    echo '    </tbody>'."\n";
    echo '    </table>'."\n";
    echo '    <br />'."\n";
    PMA_Message::notice('strReplicationShowConnectedSlavesNote')->display();
    echo '    <br />'."\n";
    echo '  </div>'."\n";

}


/**
 * Print code to add a replication slave user to the master
 */
function PMA_replication_gui_master_addslaveuser() {
    $fields_info = PMA_DBI_get_fields('mysql', 'user');
    $username_length = 16;
    $hostname_length = 41;
    foreach ($fields_info as $key => $val) {
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
    unset($fields_info);

    if (isset($GLOBALS['username']) && strlen($GLOBALS['username']) === 0) {
        $GLOBALS['pred_username'] = 'any';
    }
    echo '<div id="master_addslaveuser_gui">'."\n";
    echo '<form autocomplete="off" method="post" action="server_privileges.php" onsubmit="return checkAddUser(this);">'."\n";
    echo PMA_generate_common_hidden_inputs('', '');
    echo '<fieldset id="fieldset_add_user_login">' . "\n"
        . '<legend>'.$GLOBALS['strReplicationAddSlaveUser'].'</legend>' . "\n"
	. '<input type="hidden" name="grant_count" value="25" />' . "\n"
	. '<input type="hidden" name="createdb" id="createdb_0" value="0" />' . "\n"
        . '<input id="checkbox_Repl_slave_priv" type="hidden" title="Needed for the replication slaves." value="Y" name="Repl_slave_priv"/>'. "\n"
        . '<input id="checkbox_Repl_client_priv" type="hidden" title="Needed for the replication slaves." value="Y" name="Repl_client_priv"/>'. "\n"
        . '<input type="hidden" name="sr_take_action" value="true" />'. "\n"
        . '<div class="item">' . "\n"
        . '<label for="select_pred_username">' . "\n"
        . '    ' . $GLOBALS['strUserName'] . ':' . "\n"
        . '</label>' . "\n"
        . '<span class="options">' . "\n"
        . '    <select name="pred_username" id="select_pred_username" title="' . $GLOBALS['strUserName'] . '"' . "\n"
        . '        onchange="if (this.value == \'any\') { username.value = \'\'; } else if (this.value == \'userdefined\') { username.focus(); username.select(); }">' . "\n"
        . '        <option value="any"' . ((isset($GLOBALS['pred_username']) && $GLOBALS['pred_username'] == 'any') ? ' selected="selected"' : '') . '>' . $GLOBALS['strAnyUser'] . '</option>' . "\n"
        . '        <option value="userdefined"' . ((!isset($GLOBALS['pred_username']) || $GLOBALS['pred_username'] == 'userdefined') ? ' selected="selected"' : '') . '>' . $GLOBALS['strUseTextField'] . ':</option>' . "\n"
        . '    </select>' . "\n"
        . '</span>' . "\n"
        . '<input type="text" name="username" maxlength="'
        . $username_length . '" title="' . $GLOBALS['strUserName'] . '"'
        . (empty($GLOBALS['username'])
        ? ''
        : ' value="' . (isset($GLOBALS['new_username'])
        ? $GLOBALS['new_username']
        : $GLOBALS['username']) . '"')
        . ' onchange="pred_username.value = \'userdefined\';" />' . "\n"
        . '</div>' . "\n"
        . '<div class="item">' . "\n"
        . '<label for="select_pred_hostname">' . "\n"
        . '    ' . $GLOBALS['strHost'] . ':' . "\n"
        . '</label>' . "\n"
        . '<span class="options">' . "\n"
        . '    <select name="pred_hostname" id="select_pred_hostname" title="' . $GLOBALS['strHost'] . '"' . "\n";
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
    if (!isset($GLOBALS['pred_hostname']) && isset($GLOBALS['hostname'])) {
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
        ? ' selected="selected"' : '') . '>' . $GLOBALS['strAnyHost']
        . '</option>' . "\n"
        . '        <option value="localhost"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'localhost')
        ? ' selected="selected"' : '') . '>' . $GLOBALS['strLocalhost']
        . '</option>' . "\n";
    if (!empty($thishost)) {
        echo '        <option value="thishost"'
            . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'thishost')
            ? ' selected="selected"' : '') . '>' . $GLOBALS['strThisHost']
            . '</option>' . "\n";
    }
    unset($thishost);
    echo '        <option value="hosttable"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'hosttable')
        ? ' selected="selected"' : '') . '>' . $GLOBALS['strUseHostTable']
        . '</option>' . "\n"
        . '        <option value="userdefined"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'userdefined')
        ? ' selected="selected"' : '')
        . '>' . $GLOBALS['strUseTextField'] . ':</option>' . "\n"
        . '    </select>' . "\n"
        . '</span>' . "\n"
        . '<input type="text" name="hostname" maxlength="'
        . $hostname_length . '" value="'
        . (isset($GLOBALS['hostname']) ? $GLOBALS['hostname'] : '')
        . '" title="' . $GLOBALS['strHost']
        . '" onchange="pred_hostname.value = \'userdefined\';" />' . "\n"
        . PMA_showHint($GLOBALS['strHostTableExplanation'])
        . '</div>' . "\n"
        . '<div class="item">' . "\n"
        . '<label for="select_pred_password">' . "\n"
        . '    ' . $GLOBALS['strPassword'] . ':' . "\n"
        . '</label>' . "\n"
        . '<span class="options">' . "\n"
        . '    <select name="pred_password" id="select_pred_password" title="'
        . $GLOBALS['strPassword'] . '"' . "\n"
        . '            onchange="if (this.value == \'none\') { pma_pw.value = \'\'; pma_pw2.value = \'\'; } else if (this.value == \'userdefined\') { pma_pw.focus(); pma_pw.select(); }">' . "\n"
        . '        <option value="none"';
    if (isset($GLOBALS['username']) && $mode != 'change') {
        echo '  selected="selected"';
    }
    echo '>' . $GLOBALS['strNoPassword'] . '</option>' . "\n"
        . '        <option value="userdefined"' . (isset($GLOBALS['username']) ? '' : ' selected="selected"') . '>' . $GLOBALS['strUseTextField'] . ':</option>' . "\n"
        . '    </select>' . "\n"
        . '</span>' . "\n"
        . '<input type="password" id="text_pma_pw" name="pma_pw" title="' . $GLOBALS['strPassword'] . '" onchange="pred_password.value = \'userdefined\';" />' . "\n"
        . '</div>' . "\n"
        . '<div class="item">' . "\n"
        . '<label for="text_pma_pw2">' . "\n"
        . '    ' . $GLOBALS['strReType'] . ':' . "\n"
        . '</label>' . "\n"
        . '<span class="options">&nbsp;</span>' . "\n"
        . '<input type="password" name="pma_pw2" id="text_pma_pw2" title="' . $GLOBALS['strReType'] . '" onchange="pred_password.value = \'userdefined\';" />' . "\n"
        . '</div>' . "\n"
        . '<div class="item">' . "\n"
        . '<label for="button_generate_password">' . "\n"
        . '    ' . $GLOBALS['strGeneratePassword'] . ':' . "\n"
        . '</label>' . "\n"
        . '<span class="options">' . "\n"
        . '    <input type="button" id="button_generate_password" value="' . $GLOBALS['strGenerate'] . '" onclick="suggestPassword(this.form)" />' . "\n"
        . '</span>' . "\n"
        . '<input type="text" name="generated_pw" id="generated_pw" />' . "\n"
        . '</div>' . "\n"
        . '</fieldset>' . "\n";
    echo '<fieldset id="fieldset_user_privtable_footer" class="tblFooters">' . "\n"
        . '    <input type="submit" name="adduser_submit" id="adduser_submit" value="' . $GLOBALS['strGo'] . '" />' . "\n"
        . '</fieldset>' . "\n";
    echo '</form>'."\n";
    echo '</div>'."\n";
}
?>

