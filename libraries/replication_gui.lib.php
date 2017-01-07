<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for the replication GUI
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Message;

/**
 * returns HTML for error message
 *
 * @return String HTML code
 */
function PMA_getHtmlForErrorMessage()
{
    $html = '';
    if (isset($_SESSION['replication']['sr_action_status'])
        && isset($_SESSION['replication']['sr_action_info'])
    ) {
        if ($_SESSION['replication']['sr_action_status'] == 'error') {
            $error_message = $_SESSION['replication']['sr_action_info'];
            $html .= Message::error($error_message)->getDisplay();
            $_SESSION['replication']['sr_action_status'] = 'unknown';
        } elseif ($_SESSION['replication']['sr_action_status'] == 'success') {
            $success_message = $_SESSION['replication']['sr_action_info'];
            $html .= Message::success($success_message)->getDisplay();
            $_SESSION['replication']['sr_action_status'] = 'unknown';
        }
    }
    return $html;
}

/**
 * returns HTML for master replication
 *
 * @return String HTML code
 */
function PMA_getHtmlForMasterReplication()
{
    $html = '';
    if (! isset($_REQUEST['repl_clear_scr'])) {
        $html .= '<fieldset>';
        $html .= '<legend>' . __('Master replication') . '</legend>';
        $html .= __('This server is configured as master in a replication process.');
        $html .= '<ul>';
        $html .= '  <li><a href="#master_status_href" id="master_status_href">';
        $html .= __('Show master status') . '</a>';
        $html .= PMA_getHtmlForReplicationStatusTable('master', true, false);
        $html .= '  </li>';

        $html .= '  <li><a href="#master_slaves_href" id="master_slaves_href">';
        $html .= __('Show connected slaves') . '</a>';
        $html .= PMA_getHtmlForReplicationSlavesTable(true);
        $html .= '  </li>';

        $_url_params = $GLOBALS['url_params'];
        $_url_params['mr_adduser'] = true;
        $_url_params['repl_clear_scr'] = true;

        $html .= '  <li><a href="server_replication.php';
        $html .= PMA_URL_getCommon($_url_params)
            . '" id="master_addslaveuser_href">';
        $html .= __('Add slave replication user') . '</a></li>';
    }

    // Display 'Add replication slave user' form
    if (isset($_REQUEST['mr_adduser'])) {
        $html .= PMA_getHtmlForReplicationMasterAddSlaveuser();
    } elseif (! isset($_REQUEST['repl_clear_scr'])) {
        $html .= "</ul>";
        $html .= "</fieldset>";
    }

    return $html;
}

/**
 * returns HTML for master replication configuration
 *
 * @return String HTML code
 */
function PMA_getHtmlForMasterConfiguration()
{
    $html  = '<fieldset>';
    $html .= '<legend>' . __('Master configuration') . '</legend>';
    $html .= __(
        'This server is not configured as a master server in a '
        . 'replication process. You can choose from either replicating '
        . 'all databases and ignoring some of them (useful if you want to '
        . 'replicate a majority of the databases) or you can choose to ignore '
        . 'all databases by default and allow only certain databases to be '
        . 'replicated. Please select the mode:'
    ) . '<br /><br />';

    $html .= '<select name="db_type" id="db_type">';
    $html .= '<option value="all">' . __('Replicate all databases; Ignore:');
    $html .= '</option>';
    $html .= '<option value="ign">' . __('Ignore all databases; Replicate:');
    $html .= '</option>';
    $html .= '</select>';
    $html .= '<br /><br />';
    $html .= __('Please select databases:') . '<br />';
    $html .= PMA_getHtmlForReplicationDbMultibox();
    $html .= '<br /><br />';
    $html .= __(
        'Now, add the following lines at the end of [mysqld] section'
        . ' in your my.cnf and please restart the MySQL server afterwards.'
    ) . '<br />';
    $html .= '<pre id="rep"></pre>';
    $html .= __(
        'Once you restarted MySQL server, please click on Go button. '
        . 'Afterwards, you should see a message informing you, that this server'
        . ' <b>is</b> configured as master.'
    );
    $html .= '</fieldset>';
    $html .= '<fieldset class="tblFooters">';
    $html .= ' <form method="post" action="server_replication.php" >';
    $html .= PMA_URL_getHiddenInputs('', '');
    $html .= '  <input type="submit" value="' . __('Go') . '" id="goButton" />';
    $html .= ' </form>';
    $html .= '</fieldset>';

    return $html;
}

/**
 * returns HTML for slave replication configuration
 *
 * @param bool  $server_slave_status      Whether it is Master or Slave
 * @param array $server_slave_replication Slave replication
 *
 * @return String HTML code
 */
function PMA_getHtmlForSlaveConfiguration(
    $server_slave_status, $server_slave_replication
) {
    $html  = '<fieldset>';
    $html .= '<legend>' . __('Slave replication') . '</legend>';
    /**
     * check for multi-master replication functionality
     */
    $server_slave_multi_replication = $GLOBALS['dbi']->fetchResult(
        'SHOW ALL SLAVES STATUS'
    );
    if ($server_slave_multi_replication) {
        $html .= __('Master connection:');
        $html .= '<form method="get" action="server_replication.php">';
        $html .= PMA_URL_getHiddenInputs($GLOBALS['url_params']);
        $html .= ' <select name="master_connection">';
        $html .= '<option value="">' . __('Default') . '</option>';
        foreach ($server_slave_multi_replication as $server) {
            $html .= '<option' . (isset($_REQUEST['master_connection'])
                && $_REQUEST['master_connection'] == $server['Connection_name'] ?
                    ' selected="selected"' : '') . '>' . $server['Connection_name']
                . '</option>';
        }
        $html .= '</select>';
        $html .= ' <input type="submit" value="' . __('Go') . '" id="goButton" />';
        $html .= '</form>';
        $html .= '<br /><br />';
    }
    if ($server_slave_status) {
        $html .= '<div id="slave_configuration_gui">';

        $_url_params = $GLOBALS['url_params'];
        $_url_params['sr_take_action'] = true;
        $_url_params['sr_slave_server_control'] = true;

        if ($server_slave_replication[0]['Slave_IO_Running'] == 'No') {
            $_url_params['sr_slave_action'] = 'start';
        } else {
            $_url_params['sr_slave_action'] = 'stop';
        }

        $_url_params['sr_slave_control_parm'] = 'IO_THREAD';
        $slave_control_io_link = 'server_replication.php'
            . PMA_URL_getCommon($_url_params);

        if ($server_slave_replication[0]['Slave_SQL_Running'] == 'No') {
            $_url_params['sr_slave_action'] = 'start';
        } else {
            $_url_params['sr_slave_action'] = 'stop';
        }

        $_url_params['sr_slave_control_parm'] = 'SQL_THREAD';
        $slave_control_sql_link = 'server_replication.php'
            . PMA_URL_getCommon($_url_params);

        if ($server_slave_replication[0]['Slave_IO_Running'] == 'No'
            || $server_slave_replication[0]['Slave_SQL_Running'] == 'No'
        ) {
            $_url_params['sr_slave_action'] = 'start';
        } else {
            $_url_params['sr_slave_action'] = 'stop';
        }

        $_url_params['sr_slave_control_parm'] = null;
        $slave_control_full_link = 'server_replication.php'
            . PMA_URL_getCommon($_url_params);

        $_url_params['sr_slave_action'] = 'reset';
        $slave_control_reset_link = 'server_replication.php'
            . PMA_URL_getCommon($_url_params);

        $_url_params = $GLOBALS['url_params'];
        $_url_params['sr_take_action'] = true;
        $_url_params['sr_slave_skip_error'] = true;
        $slave_skip_error_link = 'server_replication.php'
            . PMA_URL_getCommon($_url_params);

        if ($server_slave_replication[0]['Slave_SQL_Running'] == 'No') {
            $html .= Message::error(
                __('Slave SQL Thread not running!')
            )->getDisplay();
        }
        if ($server_slave_replication[0]['Slave_IO_Running'] == 'No') {
            $html .= Message::error(
                __('Slave IO Thread not running!')
            )->getDisplay();
        }

        $_url_params = $GLOBALS['url_params'];
        $_url_params['sl_configure'] = true;
        $_url_params['repl_clear_scr'] = true;

        $reconfiguremaster_link = 'server_replication.php'
            . PMA_URL_getCommon($_url_params);

        $html .= __(
            'Server is configured as slave in a replication process. Would you ' .
            'like to:'
        );
        $html .= '<br />';
        $html .= '<ul>';
        $html .= ' <li><a href="#slave_status_href" id="slave_status_href">';
        $html .= __('See slave status table') . '</a>';
        $html .= PMA_getHtmlForReplicationStatusTable('slave', true, false);
        $html .= ' </li>';

        $html .= ' <li><a href="#slave_control_href" id="slave_control_href">';
        $html .= __('Control slave:') . '</a>';
        $html .= ' <div id="slave_control_gui" style="display: none">';
        $html .= '  <ul>';
        $html .= '   <li><a href="' . $slave_control_full_link . '">';
        $html .= (($server_slave_replication[0]['Slave_IO_Running'] == 'No' ||
                   $server_slave_replication[0]['Slave_SQL_Running'] == 'No')
                 ? __('Full start')
                 : __('Full stop')) . ' </a></li>';
        $html .= '   <li><a class="ajax" id="reset_slave"'
            . ' href="' . $slave_control_reset_link . '">';
        $html .= __('Reset slave') . '</a></li>';
        if ($server_slave_replication[0]['Slave_SQL_Running'] == 'No') {
            $html .= '   <li><a href="' . $slave_control_sql_link . '">';
            $html .= __('Start SQL Thread only') . '</a></li>';
        } else {
            $html .= '   <li><a href="' . $slave_control_sql_link . '">';
            $html .= __('Stop SQL Thread only') . '</a></li>';
        }
        if ($server_slave_replication[0]['Slave_IO_Running'] == 'No') {
            $html .= '   <li><a href="' . $slave_control_io_link . '">';
            $html .= __('Start IO Thread only') . '</a></li>';
        } else {
            $html .= '   <li><a href="' . $slave_control_io_link . '">';
            $html .= __('Stop IO Thread only') . '</a></li>';
        }
        $html .= '  </ul>';
        $html .= ' </div>';
        $html .= ' </li>';
        $html .= ' <li>';
        $html .= PMA_getHtmlForSlaveErrorManagement($slave_skip_error_link);
        $html .= ' </li>';
        $html .= ' <li><a href="' . $reconfiguremaster_link . '">';
        $html .=  __('Change or reconfigure master server') . '</a></li>';
        $html .= '</ul>';
        $html .= '</div>';

    } elseif (! isset($_REQUEST['sl_configure'])) {
        $_url_params = $GLOBALS['url_params'];
        $_url_params['sl_configure'] = true;
        $_url_params['repl_clear_scr'] = true;

        $html .= sprintf(
            __(
                'This server is not configured as slave in a replication process. '
                . 'Would you like to <a href="%s">configure</a> it?'
            ),
            'server_replication.php' . PMA_URL_getCommon($_url_params)
        );
    }
    $html .= '</fieldset>';

    return $html;
}

/**
 * returns HTML for Slave Error Management
 *
 * @param String $slave_skip_error_link error link
 *
 * @return String HTML code
 */
function PMA_getHtmlForSlaveErrorManagement($slave_skip_error_link)
{
    $html  = '<a href="#slave_errormanagement_href" '
        . 'id="slave_errormanagement_href">';
    $html .= __('Error management:') . '</a>';
    $html .= ' <div id="slave_errormanagement_gui" style="display: none">';
    $html .= Message::error(
        __('Skipping errors might lead into unsynchronized master and slave!')
    )->getDisplay();
    $html .= '  <ul>';
    $html .= '   <li><a href="' . $slave_skip_error_link . '">';
    $html .= __('Skip current error') . '</a></li>';
    $html .= '   <li>';
    $html .= '    <form method="post" action="server_replication.php">';
    $html .= PMA_URL_getHiddenInputs('', '');
    $html .= sprintf(
        __('Skip next %s errors.'),
        '<input type="text" name="sr_skip_errors_count" value="1" '
        . 'style="width: 30px" />'
    );
    $html .= '              <input type="submit" name="sr_slave_skip_error" ';
    $html .= 'value="' . __('Go') . '" />';
    $html .= '      <input type="hidden" name="sr_take_action" value="1" />';
    $html .= '    </form></li>';
    $html .= '  </ul>';
    $html .= ' </div>';
    return $html;
}

/**
 * returns HTML for not configure for a server replication
 *
 * @return String HTML code
 */
function PMA_getHtmlForNotServerReplication()
{
    $_url_params = $GLOBALS['url_params'];
    $_url_params['mr_configure'] = true;

    $html  = '<fieldset>';
    $html .= '<legend>' . __('Master replication') . '</legend>';
    $html .= sprintf(
        __(
            'This server is not configured as master in a replication process. '
            . 'Would you like to <a href="%s">configure</a> it?'
        ),
        'server_replication.php' . PMA_URL_getCommon($_url_params)
    );
    $html .= '</fieldset>';
    return $html;
}

/**
 * returns HTML code for selecting databases
 *
 * @return String HTML code
 */
function PMA_getHtmlForReplicationDbMultibox()
{
    $multi_values = '';
    $multi_values .= '<select name="db_select[]" '
        . 'size="6" multiple="multiple" id="db_select">';

    foreach ($GLOBALS['dblist']->databases as $current_db) {
        if ($GLOBALS['dbi']->isSystemSchema($current_db)) {
            continue;
        }
        $current_db = htmlspecialchars($current_db);
        $multi_values .= '                <option value="' . $current_db . '" ';
        $multi_values .= '>';
        $multi_values .= $current_db . '</option>';
    } // end while

    $multi_values .= '</select><br />';
    $multi_values .= '<a href="#" id="db_select_href">' . __('Select all') . '</a>';
    $multi_values .= '&nbsp;/&nbsp;';
    $multi_values .= '<a href="#" id="db_reset_href">' . __('Unselect all') . '</a>';

    return $multi_values;
}

/**
 * returns HTML for changing master
 *
 * @param String $submitname - submit button name
 *
 * @return String HTML code
 */
function PMA_getHtmlForReplicationChangeMaster($submitname)
{
    $html = '';
    list($username_length, $hostname_length)
        = PMA_replicationGetUsernameHostnameLength();

    $html .= '<form method="post" action="server_replication.php">';
    $html .= PMA_URL_getHiddenInputs('', '');
    $html .= ' <fieldset id="fieldset_add_user_login">';
    $html .= '  <legend>' . __('Slave configuration');
    $html .= ' - ' . __('Change or reconfigure master server') . '</legend>';
    $html .= __(
        'Make sure you have a unique server-id in your configuration file (my.cnf). '
        . 'If not, please add the following line into [mysqld] section:'
    );
    $html .= '<br />';
    $html .= '<pre>server-id=' . time() . '</pre>';

    $html .= PMA_getHtmlForAddUserInputDiv(
        array('text'=>__('User name:'), 'for'=>"text_username"),
        array(
            'type'=>'text',
            'name'=>'username',
            'id'=>'text_username',
            'maxlength'=>$username_length,
            'title'=>__('User name'),
            'required'=>'required'
        )
    );

    $html .= PMA_getHtmlForAddUserInputDiv(
        array('text'=>__('Password:'), 'for'=>"text_pma_pw"),
        array(
            'type'=>'password',
            'name'=>'pma_pw',
            'id'=>'text_pma_pw',
            'title'=>__('Password'),
            'required'=>'required'
        )
    );

    $html .= PMA_getHtmlForAddUserInputDiv(
        array('text'=>__('Host:'), 'for'=>"text_hostname"),
        array(
            'type'=>'text',
            'name'=>'hostname',
            'id'=>'text_hostname',
            'maxlength'=>$hostname_length,
            'value'=>'',
            'required'=>'required'
        )
    );

    $html .= PMA_getHtmlForAddUserInputDiv(
        array('text'=>__('Port:'), 'for'=>"text_port"),
        array(
            'type'=>'number',
            'name'=>'text_port',
            'id'=>'text_port',
            'maxlength'=>6,
            'value'=>'3306',
            'required'=>'required'
         )
    );

    $html .= ' </fieldset>';
    $html .= ' <fieldset id="fieldset_user_privtable_footer" class="tblFooters">';
    $html .= '    <input type="hidden" name="sr_take_action" value="true" />';
    $html .= '     <input type="hidden" name="' . $submitname . '" value="1" />';
    $html .= '     <input type="submit" id="confslave_submit" value="';
    $html .= __('Go') . '" />';
    $html .= ' </fieldset>';
    $html .= '</form>';

    return $html;
}

/**
 * returns HTML code for Add user input div
 *
 * @param array $label_array label tag elements
 * @param array $input_array input tag elements
 *
 * @return String HTML code
 */
function PMA_getHtmlForAddUserInputDiv($label_array, $input_array)
{
    $html  = '  <div class="item">';
    $html .= '     <label for="' . $label_array['for'] . '">';
    $html .=  $label_array['text'] . '</label>';

    $html .= '    <input ';
    foreach ($input_array as $key=>$value) {
        $html .= ' ' . $key . '="' . $value . '" ';
    }
    $html .= ' />';
    $html .= '  </div>';
    return $html;
}

/**
 * This function returns html code for table with replication status.
 *
 * @param string  $type   either master or slave
 * @param boolean $hidden if true, then default style is set to hidden,
 *                        default value false
 * @param boolean $title  if true, then title is displayed, default true
 *
 * @return String HTML code
 */
function PMA_getHtmlForReplicationStatusTable($type, $hidden = false, $title = true)
{
    global ${"{$type}_variables"};
    global ${"{$type}_variables_alerts"};
    global ${"{$type}_variables_oks"};
    global ${"server_{$type}_replication"};
    global ${"strReplicationStatus_{$type}"};

    $html = '';

    // TODO check the Masters server id?
    // seems to default to '1' when queried via SHOW VARIABLES ,
    // but resulted in error on the master when slave connects
    // [ERROR] Error reading packet from server: Misconfigured master
    // - server id was not set ( server_errno=1236)
    // [ERROR] Got fatal error 1236: 'Misconfigured master
    // - server id was not set' from master when reading data from binary log
    //
    //$server_id = $GLOBALS['dbi']->fetchValue(
    //    "SHOW VARIABLES LIKE 'server_id'", 0, 1
    //);

    $html .= '<div id="replication_' . $type . '_section" style="';
    $html .= ($hidden ? 'display: none;' : '') . '"> ';

    if ($title) {
        if ($type == 'master') {
            $html .= '<h4><a name="replication_' . $type . '"></a>';
            $html .= __('Master status') . '</h4>';
        } else {
            $html .= '<h4><a name="replication_' . $type . '"></a>';
            $html .= __('Slave status') . '</h4>';
        }
    } else {
        $html .= '<br />';
    }

    $html .= '   <table id="server' . $type . 'replicationsummary" class="data"> ';
    $html .= '   <thead>';
    $html .= '    <tr>';
    $html .= '     <th>' . __('Variable') . '</th>';
    $html .= '        <th>' . __('Value') . '</th>';
    $html .= '    </tr>';
    $html .= '   </thead>';
    $html .= '   <tbody>';

    $odd_row = true;
    foreach (${"{$type}_variables"} as $variable) {
        $html .= '   <tr class="' . ($odd_row ? 'odd' : 'even') . '">';
        $html .= '     <td class="name">';
        $html .= htmlspecialchars($variable);
        $html .= '     </td>';
        $html .= '     <td class="value">';

        // TODO change to regexp or something, to allow for negative match
        if (isset(${"{$type}_variables_alerts"}[$variable])
            && ${"{$type}_variables_alerts"}[$variable] == ${"server_{$type}_replication"}[0][$variable]
        ) {
            $html .= '<span class="attention">';

        } elseif (isset(${"{$type}_variables_oks"}[$variable])
            && ${"{$type}_variables_oks"}[$variable] == ${"server_{$type}_replication"}[0][$variable]
        ) {
            $html .= '<span class="allfine">';
        } else {
            $html .= '<span>';
        }
        // allow wrapping long table lists into multiple lines
        static $variables_wrap = array(
            'Replicate_Do_DB', 'Replicate_Ignore_DB',
            'Replicate_Do_Table', 'Replicate_Ignore_Table',
            'Replicate_Wild_Do_Table', 'Replicate_Wild_Ignore_Table');
        if (in_array($variable, $variables_wrap)) {
            $html .= htmlspecialchars(str_replace(
                ',',
                ', ',
                ${"server_{$type}_replication"}[0][$variable]
            ));
        } else {
            $html .= htmlspecialchars(${"server_{$type}_replication"}[0][$variable]);
        }
        $html .= '</span>';

        $html .= '  </td>';
        $html .= ' </tr>';

        $odd_row = ! $odd_row;
    }

    $html .= '   </tbody>';
    $html .= ' </table>';
    $html .= ' <br />';
    $html .= '</div>';

    return $html;
}

/**
 * returns html code for table with slave users connected to this master
 *
 * @param boolean $hidden - if true, then default style is set to hidden,
 *                        - default value false
 *
 * @return string
 */
function PMA_getHtmlForReplicationSlavesTable($hidden = false)
{
    $html = '';
    // Fetch data
    $data = $GLOBALS['dbi']->fetchResult('SHOW SLAVE HOSTS', null, null);

    $html .= '  <br />';
    $html .= '  <div id="replication_slaves_section" style="';
    $html .=  ($hidden ? 'display: none;' : '') . '"> ';
    $html .= '    <table class="data">';
    $html .= '    <thead>';
    $html .= '      <tr>';
    $html .= '        <th>' . __('Server ID') . '</th>';
    $html .= '        <th>' . __('Host') . '</th>';
    $html .= '      </tr>';
    $html .= '    </thead>';
    $html .= '    <tbody>';

    $odd_row = true;
    foreach ($data as $slave) {
        $html .= '    <tr class="' . ($odd_row ? 'odd' : 'even') . '">';
        $html .= '      <td class="value">' . $slave['Server_id'] . '</td>';
        $html .= '      <td class="value">' . $slave['Host'] . '</td>';
        $html .= '    </tr>';

        $odd_row = ! $odd_row;
    }

    $html .= '    </tbody>';
    $html .= '    </table>';
    $html .= '    <br />';
    $html .= Message::notice(
        __(
            'Only slaves started with the '
            . '--report-host=host_name option are visible in this list.'
        )
    )->getDisplay();
    $html .= '    <br />';
    $html .= '  </div>';

    return $html;
}

/**
 * get the correct username and hostname lengths for this MySQL server
 *
 * @return array   username length, hostname length
 */
function PMA_replicationGetUsernameHostnameLength()
{
    $fields_info = $GLOBALS['dbi']->getColumns('mysql', 'user');
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
 * returns html code to add a replication slave user to the master
 *
 * @return String HTML code
 */
function PMA_getHtmlForReplicationMasterAddSlaveuser()
{
    $html = '';
    list($username_length, $hostname_length)
        = PMA_replicationGetUsernameHostnameLength();

    if (isset($_REQUEST['username'])
        && mb_strlen($_REQUEST['username']) === 0
    ) {
        $GLOBALS['pred_username'] = 'any';
    }
    $html .= '<div id="master_addslaveuser_gui">';
    $html .= '<form autocomplete="off" method="post" ';
    $html .= 'action="server_privileges.php"';
    $html .= ' onsubmit="return checkAddUser(this);">';
    $html .= PMA_URL_getHiddenInputs('', '');
    $html .= '<fieldset id="fieldset_add_user_login">'
        . '<legend>' . __('Add slave replication user') . '</legend>'
        . PMA_getHtmlForAddUserLoginForm($username_length)
        . '<div class="item">'
        . '<label for="select_pred_hostname">'
        . '    ' . __('Host:')
        . '</label>'
        . '<span class="options">'
        . '    <select name="pred_hostname" id="select_pred_hostname" title="'
        . __('Host') . '"';

    $_current_user = $GLOBALS['dbi']->fetchValue('SELECT USER();');
    if (! empty($_current_user)) {
        $thishost = str_replace(
            "'",
            '',
            mb_substr(
                $_current_user,
                (mb_strrpos($_current_user, '@') + 1)
            )
        );
        if ($thishost != 'localhost' && $thishost != '127.0.0.1') {
            $html .= ' data-thishost="' . htmlspecialchars($thishost) . '" ';
        } else {
            unset($thishost);
        }
    }
    $html .= '>' . "\n";
    unset($_current_user);

    // when we start editing a user, $GLOBALS['pred_hostname'] is not defined
    if (! isset($GLOBALS['pred_hostname']) && isset($_REQUEST['hostname'])) {
        switch (mb_strtolower($_REQUEST['hostname'])) {
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
    $html .= '        <option value="any"'
        . ((isset($GLOBALS['pred_hostname']) && $GLOBALS['pred_hostname'] == 'any')
        ? ' selected="selected"' : '') . '>' . __('Any host')
        . '</option>'
        . '        <option value="localhost"'
        . ((isset($GLOBALS['pred_hostname'])
            && $GLOBALS['pred_hostname'] == 'localhost')
        ? ' selected="selected"' : '') . '>' . __('Local')
        . '</option>';

    if (!empty($thishost)) {
        $html .= '        <option value="thishost"'
            . ((isset($GLOBALS['pred_hostname'])
                && $GLOBALS['pred_hostname'] == 'thishost')
            ? ' selected="selected"' : '') . '>' . __('This Host')
            . '</option>';
    }
    unset($thishost);

    $html .= PMA_getHtmlForTableInfoForm($hostname_length);
    $html .= '</form>';
    $html .= '</div>';

    return $html;
}
/**
 *  returns html code to add a replication slave user to the master
 *
 * @param int $username_length Username length
 *
 * @return String HTML code
 */
function PMA_getHtmlForAddUserLoginForm($username_length)
{
    $html = '<input type="hidden" name="grant_count" value="25" />'
        . '<input type="hidden" name="createdb" id="createdb_0" value="0" />'
        . '<input id="checkbox_Repl_slave_priv" type="hidden"'
        . ' title="Needed for the replication slaves." '
        . 'value="Y" name="Repl_slave_priv"/>'
        . '<input id="checkbox_Repl_client_priv" type="hidden" '
        . 'title="Needed for the replication slaves."'
        . ' value="Y" name="Repl_client_priv"/> '
        . '<input type="hidden" name="sr_take_action" value="true" />'
        . '<div class="item">'
        . '<label for="select_pred_username">'
        . '    ' . __('User name:')
        . '</label>'
        . '<span class="options">'
        . '    <select name="pred_username" id="select_pred_username" '
        .         'title="' . __('User name') . '">'
        . '        <option value="any"'
        . ((isset($GLOBALS['pred_username'])
            && $GLOBALS['pred_username'] == 'any') ? ' selected="selected"' : '')
        . '>' . __('Any user') . '</option>'
        . '        <option value="userdefined"'
        . ((! isset($GLOBALS['pred_username'])
            || $GLOBALS['pred_username'] == 'userdefined')
            ? ' selected="selected"' : '')
        . '>' . __('Use text field:') . '</option>'
        . '    </select>'
        . '</span>'
        . '<input type="text" name="username" id="pma_username" maxlength="'
        . $username_length . '" title="' . __('User name') . '"'
        . (empty($_REQUEST['username']) ? '' : ' value="'
        . (isset($GLOBALS['new_username'])
            ? $GLOBALS['new_username']
            : htmlspecialchars($_REQUEST['username'])) . '"')
        . ' />'
        . '</div>';

    return $html;
}

/**
 * returns HTML for TableInfoForm
 *
 * @param int $hostname_length Selected hostname length
 *
 * @return String HTML code
 */
function PMA_getHtmlForTableInfoForm($hostname_length)
{
    $html = '        <option value="hosttable"'
        . ((isset($GLOBALS['pred_hostname'])
            && $GLOBALS['pred_hostname'] == 'hosttable')
        ? ' selected="selected"' : '') . '>' . __('Use Host Table')
        . '</option>'
        . '        <option value="userdefined"'
        . ((isset($GLOBALS['pred_hostname'])
            && $GLOBALS['pred_hostname'] == 'userdefined')
        ? ' selected="selected"' : '')
        . '>' . __('Use text field:') . '</option>'
        . '    </select>'
        . '</span>'
        . '<input type="text" name="hostname" id="pma_hostname" maxlength="'
        . $hostname_length . '" value="'
        . (isset($_REQUEST['hostname']) ? htmlspecialchars($_REQUEST['hostname']) : '')
        . '" title="' . __('Host')
        . '" />'
        . PMA\libraries\Util::showHint(
            __(
                'When Host table is used, this field is ignored '
                . 'and values stored in Host table are used instead.'
            )
        )
        . '</div>'
        . '<div class="item">'
        . '<label for="select_pred_password">'
        . '    ' . __('Password:')
        . '</label>'
        . '<span class="options">'
        . '    <select name="pred_password" id="select_pred_password" title="'
        . __('Password') . '">'
        . '        <option value="none"';
    if (isset($_REQUEST['username'])) {
        $html .= '  selected="selected"';
    }
    $html .= '>' . __('No Password') . '</option>'
        . '        <option value="userdefined"'
        . (isset($_REQUEST['username']) ? '' : ' selected="selected"')
        . '>' . __('Use text field:') . '</option>'
        . '    </select>'
        . '</span>'
        . '<input type="password" id="text_pma_pw" name="pma_pw" title="'
        . __('Password') . '" />'
        . '</div>'
        . '<div class="item">'
        . '<label for="text_pma_pw2">'
        . '    ' . __('Re-type:')
        . '</label>'
        . '<span class="options">&nbsp;</span>'
        . '<input type="password" name="pma_pw2" id="text_pma_pw2" title="'
        . __('Re-type') . '" />'
        . '</div>'
        . '<div class="item">'
        . '<label for="button_generate_password">'
        . '    ' . __('Generate password:')
        . '</label>'
        . '<span class="options">'
        . '    <input type="button" class="button" '
        . 'id="button_generate_password" value="' . __('Generate')
        . '" onclick="suggestPassword(this.form)" />'
        . '</span>'
        . '<input type="text" name="generated_pw" id="generated_pw" />'
        . '</div>'
        . '</fieldset>';
    $html .= '<fieldset id="fieldset_user_privtable_footer" class="tblFooters">'
        . '    <input type="hidden" name="adduser_submit" value="1" />'
        . '    <input type="submit" id="adduser_submit" value="' . __('Go') . '" />'
        . '</fieldset>';
    return $html;
}

/**
 * handle control requests
 *
 * @return NULL
 */
function PMA_handleControlRequest()
{
    if (isset($_REQUEST['sr_take_action'])) {
        $refresh = false;
        $result = false;
        $messageSuccess = null;
        $messageError = null;

        if (isset($_REQUEST['slave_changemaster']) && ! $GLOBALS['cfg']['AllowArbitraryServer']) {
            $_SESSION['replication']['sr_action_status'] = 'error';
            $_SESSION['replication']['sr_action_info'] = __('Connection to server is disabled, please enable $cfg[\'AllowArbitraryServer\'] in phpMyAdmin configuration.');
        } elseif (isset($_REQUEST['slave_changemaster'])) {
            $result = PMA_handleRequestForSlaveChangeMaster();
        } elseif (isset($_REQUEST['sr_slave_server_control'])) {
            $result = PMA_handleRequestForSlaveServerControl();
            $refresh = true;

            switch ($_REQUEST['sr_slave_action']) {
            case 'start':
                $messageSuccess = __('Replication started successfully.');
                $messageError = __('Error starting replication.');
                break;
            case 'stop':
                $messageSuccess = __('Replication stopped successfully.');
                $messageError = __('Error stopping replication.');
                break;
            case 'reset':
                $messageSuccess = __('Replication resetting successfully.');
                $messageError = __('Error resetting replication.');
                break;
            default:
                $messageSuccess = __('Success.');
                $messageError = __('Error.');
                break;
            }
        } elseif (isset($_REQUEST['sr_slave_skip_error'])) {
            $result = PMA_handleRequestForSlaveSkipError();
        }

        if ($refresh) {
            $response = PMA\libraries\Response::getInstance();
            if ($response->isAjax()) {
                $response->setRequestStatus($result);
                $response->addJSON(
                    'message',
                    $result
                    ? Message::success($messageSuccess)
                    : Message::error($messageError)
                );
            } else {
                PMA_sendHeaderLocation(
                    './server_replication.php'
                    . PMA_URL_getCommon($GLOBALS['url_params'], 'text')
                );
            }
        }
        unset($refresh);
    }
}
/**
 * handle control requests for Slave Change Master
 *
 * @return boolean
 */
function PMA_handleRequestForSlaveChangeMaster()
{
    $sr = array();
    $_SESSION['replication']['m_username'] = $sr['username']
        = $GLOBALS['dbi']->escapeString($_REQUEST['username']);
    $_SESSION['replication']['m_password'] = $sr['pma_pw']
        = $GLOBALS['dbi']->escapeString($_REQUEST['pma_pw']);
    $_SESSION['replication']['m_hostname'] = $sr['hostname']
        = $GLOBALS['dbi']->escapeString($_REQUEST['hostname']);
    $_SESSION['replication']['m_port']     = $sr['port']
        = $GLOBALS['dbi']->escapeString($_REQUEST['text_port']);
    $_SESSION['replication']['m_correct']  = '';
    $_SESSION['replication']['sr_action_status'] = 'error';
    $_SESSION['replication']['sr_action_info'] = __('Unknown error');

    // Attempt to connect to the new master server
    $link_to_master = PMA_Replication_connectToMaster(
        $sr['username'], $sr['pma_pw'], $sr['hostname'], $sr['port']
    );

    if (! $link_to_master) {
        $_SESSION['replication']['sr_action_status'] = 'error';
        $_SESSION['replication']['sr_action_info'] = sprintf(
            __('Unable to connect to master %s.'),
            htmlspecialchars($sr['hostname'])
        );
    } else {
        // Read the current master position
        $position = PMA_Replication_Slave_binLogMaster($link_to_master);

        if (empty($position)) {
            $_SESSION['replication']['sr_action_status'] = 'error';
            $_SESSION['replication']['sr_action_info']
                = __(
                    'Unable to read master log position. '
                    . 'Possible privilege problem on master.'
                );
        } else {
            $_SESSION['replication']['m_correct']  = true;

            if (! PMA_Replication_Slave_changeMaster(
                $sr['username'],
                $sr['pma_pw'],
                $sr['hostname'],
                $sr['port'],
                $position,
                true,
                false
            )
            ) {
                $_SESSION['replication']['sr_action_status'] = 'error';
                $_SESSION['replication']['sr_action_info']
                    = __('Unable to change master!');
            } else {
                $_SESSION['replication']['sr_action_status'] = 'success';
                $_SESSION['replication']['sr_action_info'] = sprintf(
                    __('Master server changed successfully to %s.'),
                    htmlspecialchars($sr['hostname'])
                );
            }
        }
    }

    return $_SESSION['replication']['sr_action_status'] === 'success';
}

/**
 * handle control requests for Slave Server Control
 *
 * @return boolean
 */
function PMA_handleRequestForSlaveServerControl()
{
    if (empty($_REQUEST['sr_slave_control_parm'])) {
        $_REQUEST['sr_slave_control_parm'] = null;
    }
    if ($_REQUEST['sr_slave_action'] == 'reset') {
        $qStop = PMA_Replication_Slave_control("STOP");
        $qReset = $GLOBALS['dbi']->tryQuery("RESET SLAVE;");
        $qStart = PMA_Replication_Slave_control("START");

        $result = ($qStop !== false && $qStop !== -1 &&
            $qReset !== false && $qReset !== -1 &&
            $qStart !== false && $qStart !== -1);
    } else {
        $qControl = PMA_Replication_Slave_control(
            $_REQUEST['sr_slave_action'],
            $_REQUEST['sr_slave_control_parm']
        );

        $result = ($qControl !== false && $qControl !== -1);
    }

    return $result;
}

/**
 * handle control requests for Slave Skip Error
 *
 * @return boolean
 */
function PMA_handleRequestForSlaveSkipError()
{
    $count = 1;
    if (isset($_REQUEST['sr_skip_errors_count'])) {
        $count = $_REQUEST['sr_skip_errors_count'] * 1;
    }

    $qStop = PMA_Replication_Slave_control("STOP");
    $qSkip = $GLOBALS['dbi']->tryQuery(
        "SET GLOBAL SQL_SLAVE_SKIP_COUNTER = " . $count . ";"
    );
    $qStart = PMA_Replication_Slave_control("START");

    $result = ($qStop !== false && $qStop !== -1 &&
        $qSkip !== false && $qSkip !== -1 &&
        $qStart !== false && $qStart !== -1);

    return $result;
}
