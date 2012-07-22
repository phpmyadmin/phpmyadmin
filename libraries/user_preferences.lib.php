<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for displaying user preferences pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Common initialization for user preferences modification pages
 *
 * @return void
 */
function PMA_userprefsPageInit()
{
    $forms_all_keys = PMA_readUserprefsFieldNames($GLOBALS['forms']);
    $cf = ConfigFile::getInstance();
    $cf->resetConfigData(); // start with a clean instance
    $cf->setAllowedKeys($forms_all_keys);
    $cf->setCfgUpdateReadMapping(
        array(
            'Server/hide_db' => 'Servers/1/hide_db',
            'Server/only_db' => 'Servers/1/only_db'
        )
    );
    $cf->updateWithGlobalConfig($GLOBALS['cfg']);
}

/**
 * Loads user preferences
 *
 * Returns an array:
 * * config_data - path => value pairs
 * * mtime - last modification time
 * * type - 'db' (config read from pmadb) or 'session' (read from user session)
 *
 * @return array
 */
function PMA_loadUserprefs()
{
    $cfgRelation = PMA_getRelationsParam();
    if (! $cfgRelation['userconfigwork']) {
        // no pmadb table, use session storage
        if (! isset($_SESSION['userconfig'])) {
            $_SESSION['userconfig'] = array(
                'db' => array(),
                'ts' => time());
        }
        return array(
            'config_data' => $_SESSION['userconfig']['db'],
            'mtime' => $_SESSION['userconfig']['ts'],
            'type' => 'session');
    }
    // load configuration from pmadb
    $query_table = PMA_Util::backquote($cfgRelation['db']) . '.'
        . PMA_Util::backquote($cfgRelation['userconfig']);
    $query = '
        SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts
        FROM ' . $query_table . '
          WHERE `username` = \'' . PMA_Util::sqlAddSlashes($cfgRelation['user']) . '\'';
    $row = PMA_DBI_fetch_single_row($query, 'ASSOC', $GLOBALS['controllink']);

    return array(
        'config_data' => $row ? (array)json_decode($row['config_data']) : array(),
        'mtime' => $row ? $row['ts'] : time(),
        'type' => 'db');
}

/**
 * Saves user preferences
 *
 * @param array $config_array configuration array
 *
 * @return true|PMA_Message
 */
function PMA_saveUserprefs(array $config_array)
{
    $cfgRelation = PMA_getRelationsParam();
    $server = isset($GLOBALS['server'])
        ? $GLOBALS['server']
        : $GLOBALS['cfg']['ServerDefault'];
    $cache_key = 'server_' . $server;
    if (! $cfgRelation['userconfigwork']) {
        // no pmadb table, use session storage
        $_SESSION['userconfig'] = array(
            'db' => $config_array,
            'ts' => time());
        if (isset($_SESSION['cache'][$cache_key]['userprefs'])) {
            unset($_SESSION['cache'][$cache_key]['userprefs']);
        }
        return true;
    }

    // save configuration to pmadb
    $query_table = PMA_Util::backquote($cfgRelation['db']) . '.'
        . PMA_Util::backquote($cfgRelation['userconfig']);
    $query = '
        SELECT `username`
        FROM ' . $query_table . '
          WHERE `username` = \'' . PMA_Util::sqlAddSlashes($cfgRelation['user']) . '\'';

    $has_config = PMA_DBI_fetch_value($query, 0, 0, $GLOBALS['controllink']);
    $config_data = json_encode($config_array);
    if ($has_config) {
        $query = '
            UPDATE ' . $query_table . '
            SET `config_data` = \'' . PMA_Util::sqlAddSlashes($config_data) . '\'
            WHERE `username` = \'' . PMA_Util::sqlAddSlashes($cfgRelation['user']) . '\'';
    } else {
        $query = '
            INSERT INTO ' . $query_table . ' (`username`, `config_data`)
            VALUES (\'' . PMA_Util::sqlAddSlashes($cfgRelation['user']) . '\',
                \'' . PMA_Util::sqlAddSlashes($config_data) . '\')';
    }
    if (isset($_SESSION['cache'][$cache_key]['userprefs'])) {
        unset($_SESSION['cache'][$cache_key]['userprefs']);
    }
    if (!PMA_DBI_try_query($query, $GLOBALS['controllink'])) {
        $message = PMA_Message::error(__('Could not save configuration'));
        $message->addMessage('<br /><br />');
        $message->addMessage(
            PMA_Message::rawError(PMA_DBI_getError($GLOBALS['controllink']))
        );
        return $message;
    }
    return true;
}

/**
 * Returns a user preferences array filtered by $cfg['UserprefsDisallow']
 * (blacklist) and keys from user preferences form (whitelist)
 *
 * @param array $config_data path => value pairs
 *
 * @return array
 */
function PMA_applyUserprefs(array $config_data)
{
    $cfg = array();
    $blacklist = array_flip($GLOBALS['cfg']['UserprefsDisallow']);
    if (!$GLOBALS['cfg']['UserprefsDeveloperTab']) {
        // disallow everything in the Developers tab
        $blacklist['Error_Handler/display'] = true;
        $blacklist['Error_Handler/gather'] = true;
        $blacklist['DBG/sql'] = true;
    }
    $whitelist = array_flip(PMA_readUserprefsFieldNames());
    // whitelist some additional fields which are custom handled
    $whitelist['ThemeDefault'] = true;
    $whitelist['fontsize'] = true;
    $whitelist['lang'] = true;
    $whitelist['collation_connection'] = true;
    $whitelist['Server/hide_db'] = true;
    $whitelist['Server/only_db'] = true;
    foreach ($config_data as $path => $value) {
        if (! isset($whitelist[$path]) || isset($blacklist[$path])) {
            continue;
        }
        PMA_arrayWrite($path, $cfg, $value);
    }
    return $cfg;
}

/**
 * Reads user preferences field names
 *
 * @param array|null $forms
 *
 * @return array
 */
function PMA_readUserprefsFieldNames(array $forms = null)
{
    static $names;

    // return cached results
    if ($names !== null) {
        return $names;
    }
    if (is_null($forms)) {
        $forms = array();
        include 'libraries/config/user_preferences.forms.php';
    }
    $names = array();
    foreach ($forms as $formset) {
        foreach ($formset as $form) {
            foreach ($form as $k => $v) {
                $names[] = is_int($k) ? $v : $k;
            }
        }
    }
    return $names;
}

/**
 * Updates one user preferences option (loads and saves to database).
 *
 * No validation is done!
 *
 * @param string $path          configuration
 * @param mixed  $value         value
 * @param mixed  $default_value default value
 *
 * @return void
 */
function PMA_persistOption($path, $value, $default_value)
{
    $prefs = PMA_loadUserprefs();
    if ($value === $default_value) {
        if (isset($prefs['config_data'][$path])) {
            unset($prefs['config_data'][$path]);
        } else {
            return;
        }
    } else {
        $prefs['config_data'][$path] = $value;
    }
    PMA_saveUserprefs($prefs['config_data']);
}

/**
 * Redirects after saving new user preferences
 *
 * @param string $file_name
 * @param array  $params
 * @param string $hash
 *
 * @return void
 */
function PMA_userprefsRedirect($file_name,
    $params = null, $hash = null
) {
    // redirect
    $url_params = array('saved' => 1);
    if (is_array($params)) {
        $url_params = array_merge($params, $url_params);
    }
    if ($hash) {
        $hash = '#' . urlencode($hash);
    }
    PMA_sendHeaderLocation(
        $GLOBALS['cfg']['PmaAbsoluteUri'] . $file_name
        . PMA_generate_common_url($url_params, '&') . $hash
    );
}

/**
 * Shows form which allows to quickly load
 * settings stored in browser's local storage
 *
 * @return string
 */
function PMA_userprefsAutoloadGetHeader()
{
    $retval = '';

    if (isset($_REQUEST['prefs_autoload'])
        && $_REQUEST['prefs_autoload'] == 'hide'
    ) {
        $_SESSION['userprefs_autoload'] = true;
    } else {
        $script_name = basename(basename($GLOBALS['PMA_PHP_SELF']));
        $return_url = htmlspecialchars(
            $script_name . '?' . http_build_query($_GET, '', '&')
        );

        $retval .= '<div id="prefs_autoload" class="notice" style="display:none">';
        $retval .= '<form action="prefs_manage.php" method="post">';
        $retval .= PMA_generate_common_hidden_inputs();
        $retval .= '<input type="hidden" name="json" value="" />';
        $retval .= '<input type="hidden" name="submit_import" value="1" />';
        $retval .= '<input type="hidden" name="return_url" value="' . $return_url . '" />';
        $retval .=  __(
            'Your browser has phpMyAdmin configuration for this domain. '
            . 'Would you like to import it for current session?'
        );
        $retval .= '<br />';
        $retval .= '<a href="#yes">' . __('Yes') . '</a>';
        $retval .= ' / ';
        $retval .= '<a href="#no">' . __('No') . '</a>';
        $retval .= '</form>';
        $retval .= '</div>';
    }
    return $retval;
}
?>
