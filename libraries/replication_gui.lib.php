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

$jscode['master_replication'] = 'divShowHideFunc(\'master_status_href\', \'replication_master_section\');'."\n";

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
    '	  $(\'rep\').set(\'html\', c_text+c_ignore+c_output+\';\');'."\n".
    '	else'."\n". 
    '	  $(\'rep\').set(\'html\', c_text+c_do+c_output+\';\');'."\n".
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
    $multi_values .= '<br /><a href="#" id="db_reset_href">Uncheck all</a>';

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
    echo '  <legend>Slave configuration - change master server</legend>'."\n";
    echo $GLOBALS['strSlaveConfigure'].'<br />'."\n";
    echo '<pre>server-id='.time().'</pre>'."\n";
    echo '  <div class="item">'."\n";
    echo '    <label for="select_pred_username">'. $GLOBALS['strUserName'].':</label>'."\n";
    echo '    <input type="text" name="username" maxlength="'. $username_length .'" title="'. $GLOBALS['strUserName'] .'" onchange="pred_username.value = \'userdefined\';" />'."\n";
    echo '  </div>'."\n";
    echo '  <div class="item">'."\n";
    echo '    <label for="select_pred_password">'. $GLOBALS['strPassword'] .' :</label>'."\n";
    echo '    <input type="password" id="text_pma_pw" name="pma_pw" title="'. $GLOBALS['strPassword'] .'" onchange="pred_password.value = \'userdefined\';" />'."\n";
    echo '  </div>'."\n";
    echo '  <div class="item">'."\n";
    echo '    <label for="select_pred_hostname">'.$GLOBALS['strHost'].' :</label>'."\n";
    echo '    <input type="text" name="hostname" maxlength="'. $hostname_length .'" value="" />'."\n";
    echo '  </div>'."\n";
    echo '  <div class="item">'."\n";
    echo '     <label for="select_pred_hostname">'.$GLOBALS['strPort'].':</label>'."\n";
    echo '     <input type="text" name="port" maxlength="6" value="3306"  />'."\n";
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
function PMA_replication_print_status_table ($type, $hidden = false, $title = true) {
    global ${"{$type}_variables"};
    global ${"{$type}_variables_alerts"};
    global ${"{$type}_variables_oks"};
    global ${"server_{$type}_replication"};
    global ${"strReplicationStatus_{$type}"};

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

        if (isset(${"{$type}_variables_alerts"}[$variable]) 
            && ${"{$type}_variables_alerts"}[$variable] == ${"server_{$type}_replication"}[0][$variable]
        ) {
            echo '<span class="attention">'."\n";
        }

        if (isset(${"{$type}_variables_oks"}[$variable]) 
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
?>

