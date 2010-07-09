<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for displaying user preferences pages
 *
 * @package phpMyAdmin
 */

/**
 * Loads user preferences
 *
 * Returns false or an array:
 * * config_data - path => value pairs
 * * mtime - last modification time
 * * type - 'db' (config read from pmadb) or 'session' (read from user session)
 *
 * @uses $_SESSION['userconfig']
 * @uses PMA_array_merge_recursive
 * @uses PMA_backquote()
 * @uses PMA_DBI_fetch_single_row()
 * @uses PMA_getRelationsParam()
 * @uses PMA_sqlAddslashes()
 * @uses $GLOBALS['controllink']
 * @return false|array
 */
function PMA_load_userprefs()
{
    $cfgRelation = PMA_getRelationsParam();
    if (!$cfgRelation['userconfigwork']) {
        // no pmadb table, use session storage
        if (!isset($_SESSION['userconfig'])) {
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
    $query_table = PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['userconfig']);
    $query = '
        SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts
        FROM ' . $query_table . '
          WHERE `username` = \'' . PMA_sqlAddslashes($cfgRelation['user']) . '\'';
    $row = PMA_DBI_fetch_single_row($query, 'ASSOC', $GLOBALS['controllink']);
    if (!$row) {
        return false;
    }
    $config_data = unserialize($row['config_data']);
    return array(
        'config_data' => $config_data,
        'mtime' => $row['ts'],
        'type' => 'db');
}

/**
 * Saves user preferences
 *
 * @uses $GLOBALS['controllink']
 * @uses $_SESSION['cache']['userprefs']
 * @uses $_SESSION['userconfig']
 * @uses ConfigFile::getConfigArray()
 * @uses ConfigFile::getInstance()
 * @uses PMA_backquote()
 * @uses PMA_DBI_fetch_value
 * @uses PMA_DBI_getError()
 * @uses PMA_DBI_try_query()
 * @uses PMA_Message::addMessage()
 * @uses PMA_Message::error()
 * @uses PMA_Message::rawError()
 * @uses PMA_sqlAddslashes()
 * @uses PMA_getRelationsParam()
 * @param array $config_data
 * @return true|PMA_Message
 */
function PMA_save_userprefs(array $config_array)
{
    $cfgRelation = PMA_getRelationsParam();
    if (!$cfgRelation['userconfigwork']) {
        // no pmadb table, use session storage
        $_SESSION['userconfig'] = array(
            'db' => $config_array,
            'ts' => time());
        if (isset($_SESSION['cache']['userprefs'])) {
           unset($_SESSION['cache']['userprefs']);
        }
        return true;
    }

    // save configuration to pmadb
    $query_table = PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['userconfig']);
    $query = '
        SELECT `username`
        FROM ' . $query_table . '
          WHERE `username` = \'' . PMA_sqlAddslashes($cfgRelation['user']) . '\'';

    $has_config = PMA_DBI_fetch_value($query, 0, 0, $GLOBALS['controllink']);
    $config_data = serialize($config_array);
    if ($has_config) {
        $query = '
            UPDATE ' . $query_table . '
            SET `config_data` = \'' . PMA_sqlAddslashes($config_data) . '\'
            WHERE `username` = \'' . PMA_sqlAddslashes($cfgRelation['user']) . '\'';
    } else {
        $query = '
            INSERT INTO ' . $query_table . ' (`username`, `config_data`)
            VALUES (\'' . PMA_sqlAddslashes($cfgRelation['user']) . '\',
                \'' . PMA_sqlAddslashes($config_data) . '\')';
    }
    if (isset($_SESSION['cache']['userprefs'])) {
        unset($_SESSION['cache']['userprefs']);
    }
    if (!PMA_DBI_try_query($query, $GLOBALS['controllink'])) {
        $message = PMA_Message::error(__('Could not save configuration'));
        $message->addMessage('<br /><br />');
        $message->addMessage(PMA_Message::rawError(PMA_DBI_getError($GLOBALS['controllink'])));
        return $message;
    }
    return true;
}

/**
 * Returns a user preferences array filtered by $cfg['UserprefsDisallow']
 * (blacklist) and keys from user preferences form (whitelist)
 *
 * @uses PMA_array_write()
 * @uses PMA_read_userprefs_fieldnames()
 * @param array $config_data path => value pairs
 * @return array
 */
function PMA_apply_userprefs(array $config_data)
{
    $cfg = array();
    $blacklist = array_flip($GLOBALS['cfg']['UserprefsDisallow']);
    $whitelist = array_flip(PMA_read_userprefs_fieldnames());
    $whitelist['ThemeDefault'] = true;
    $whitelist['fontsize'] = true;
    foreach ($config_data as $path => $value) {
        if (!isset($whitelist[$path]) || isset($blacklist[$path])) {
            continue;
        }
        PMA_array_write($path, $cfg, $value);
    }
    return $cfg;
}

/**
 * Reads user preferences field names
 *
 * @param array|null $forms
 * @return array
 */
function PMA_read_userprefs_fieldnames(array $forms = null)
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
 * @uses PMA_save_userprefs()
 * @param string $cfg_name
 * @param mixed $value
 * @return void
 */
function PMA_persist_option($path, $value, $default_value)
{
    $prefs = PMA_load_userprefs();
    if ($value === $default_value) {
        if (isset($prefs['config_data'][$path])) {
            unset($prefs['config_data'][$path]);
        } else {
            return;
        }
    } else {
        $prefs['config_data'][$path] = $value;
    }
    PMA_save_userprefs($prefs['config_data']);
}

/**
 * Redirects after saving new user preferences
 *
 * @param array  $forms
 * @param array  $old_settings
 * @param string $file_name
 * @param array  $params
 */
function PMA_userprefs_redirect(array $forms, array $old_settings, $file_name, $params = null)
{
    // compute differences and check whether left frame should be refreshed
    $old_settings = isset($old_settings['config_data'])
            ? $old_settings['config_data']
            : array();
    $new_settings = ConfigFile::getInstance()->getConfigArray();
    $diff_keys = array_keys(array_diff_assoc($old_settings, $new_settings)
            + array_diff_assoc($new_settings, $old_settings));
    $check_keys = array('NaturalOrder', 'MainPageIconic', 'DefaultTabDatabase');
    $check_keys = array_merge($check_keys, $forms['Left_frame']['Left_frame'],
         $forms['Left_frame']['Left_servers'], $forms['Left_frame']['Left_databases']);
    $diff = array_intersect($check_keys, $diff_keys);
    $refresh_left_frame = !empty($diff);

    // redirect
    $url_params = array(
        'saved' => 1,
        'refresh_left_frame' => $refresh_left_frame);
    if (is_array($params)) {
        $url_params = array_merge($params, $url_params);
    }
    PMA_sendHeaderLocation($GLOBALS['cfg']['PmaAbsoluteUri'] . $file_name
            . PMA_generate_common_url($url_params, '&'));
}
?>