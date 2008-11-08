<?php
/**
 * Various validation functions
 *
 * Validation function takes two argument: id for which it is called
 * and array of fields' values (usually values for entire formset, as defined
 * in forms.inc.php).
 * The function must always return an array with an error (or error array)
 * assigned to a form element (formset name or field path). Even if there are
 * no errors, key must be set with an empty value.
 *
 * Valdiation functions are assigned in $cfg_db['_validators'] (config_info.inc.php).
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

/**
 * Runs validation $validator_id on values $values and returns error list.
 *
 * Return values:
 * o array, keys - field path or formset id, values - array of errors
 *   when $isPostSource is true values is an empty array to allow for error list
 *   cleanup in HTML documen
 * o false - when no validators match name(s) given by $validator_id
 *
 * @param string|array  $validator_id
 * @param array         $values
 * @param bool          $isPostSource  tells whether $values are directly from POST request
 * @return bool|array
 */
function validate($validator_id, &$values, $isPostSource)
{
    // find validators
    $cf = ConfigFile::getInstance();
    $validator_id = (array) $validator_id;
    $validators = $cf->getDbEntry('_validators');
    $vids = array();
    foreach ($validator_id as &$vid) {
        $vid = $cf->getCanonicalPath($vid);
        if (isset($validators[$vid])) {
            $vids[] = $vid;
        }
    }
    if (empty($vids)) {
        return false;
    }

    // create argument list with canonical paths and remember path mapping
    $arguments = array();
    $key_map = array();
    foreach ($values as $k => $v) {
        $k2 = $isPostSource ? str_replace('-', '/', $k) : $k;
        $k2 = strpos($k2, '/') ? $cf->getCanonicalPath($k2) : $k2;
        $key_map[$k2] = $k;
        $arguments[$k2] = $v;
    }

    // validate
    $result = array();
    foreach ($vids as $vid) {
        $r = call_user_func($validators[$vid], $vid, $arguments);
        // merge results
        if (is_array($r)) {
            foreach ($r as $key => $error_list) {
                // skip empty values if $isPostSource is false
                if (!$isPostSource && empty($error_list)) {
                    continue;
                }
                if (!isset($result[$key])) {
                    $result[$key] = array();
                }
                $result[$key] = array_merge($result[$key], (array)$error_list);
            }
        }
    }

    // restore original paths
    $new_result = array();
    foreach ($result as $k => $v) {
        $k2 = isset($key_map[$k]) ? $key_map[$k] : $k;
        $new_result[$k2] = $v;
    }
    return empty($new_result) ? true : $new_result;
}

/**
 * Ensures that $php_errormsg variable will be registered in case of an error
 * and enables output buffering (when $start = true).
 * Called with $start = false disables output buffering end restores
 * html_errors and track_errors.
 *
 * @param boolean $start
 */
function test_php_errormsg($start = true)
{
    static $old_html_errors, $old_track_errors;
    if ($start) {
        $old_html_errors = ini_get('html_errors');
        $old_track_errors = ini_get('track_errors');
        ini_set('html_errors', false);
        ini_set('track_errors', true);
        ob_start();
    } else {
        ob_end_clean();
        ini_set('html_errors', $old_html_errors);
        ini_set('track_errors', $old_track_errors);
    }
}

/**
 * Test database connection
 *
 * @param string $extension     'mysql' or 'mysqli'
 * @param string $connect_type  'tcp' or 'socket'
 * @param string $host
 * @param string $port
 * @param string $socket
 * @param string $user
 * @param string $pass
 * @param string $error_key
 * @return bool|array
 */
function test_db_connection($extension, $connect_type, $host, $port, $socket, $user, $pass = null, $error_key = 'Server')
{
    //    test_php_errormsg();
    $socket = empty($socket) || $connect_type == 'tcp' ? null : ':' . $socket;
    $port = empty($port) || $connect_type == 'socket' ? null : ':' . $port;
    $error = null;
    if ($extension == 'mysql') {
        $conn = @mysql_connect($host . $socket . $port, $user, $pass);
        if (!$conn) {
            $error = PMA_lang('error_connection');
        } else {
            mysql_close($conn);
        }
    } else {
        $conn = @mysqli_connect($host, $user, $pass, null, $port, $socket);
        if (!$conn) {
            $error = PMA_lang('error_connection');
        } else {
            mysqli_close($conn);
        }
    }
    //    test_php_errormsg(false);
    if (isset($php_errormsg)) {
        $error .= " - $php_errormsg";
    }
    return is_null($error) ? true : array($error_key => $error);
}

/**
 * Validate server config
 *
 * @param string $path
 * @param array  $values
 * @return array
 */
function validate_server($path, $values)
{
    $result = array('Server' => '', 'Servers/1/user' => '', 'Servers/1/SignonSession' => '', 'Servers/1/SignonURL' => '');
    $error = false;
    if ($values['Servers/1/auth_type'] == 'config' && empty($values['Servers/1/user'])) {
        $result['Servers/1/user'] = PMA_lang('error_empty_user_for_config_auth');
        $error = true;
    }
    if ($values['Servers/1/auth_type'] == 'signon' && empty($values['Servers/1/SignonSession'])) {
        $result['Servers/1/SignonSession'] = PMA_lang('error_empty_signon_session');
        $error = true;
    }
    if ($values['Servers/1/auth_type'] == 'signon' && empty($values['Servers/1/SignonURL'])) {
        $result['Servers/1/SignonURL'] = PMA_lang('error_empty_signon_url');
        $error = true;
    }

    if (!$error && $values['Servers/1/auth_type'] == 'config') {
        $password = $values['Servers/1/nopassword'] ? null : $values['Servers/1/password'];
        $test = test_db_connection($values['Servers/1/extension'], $values['Servers/1/connect_type'], $values['Servers/1/host'], $values['Servers/1/port'], $values['Servers/1/socket'], $values['Servers/1/user'], $password, 'Server');
        if ($test !== true) {
            $result = array_merge($result, $test);
        }
    }
    return $result;
}

/**
 * Validate pmadb config
 *
 * @param string $path
 * @param array  $values
 * @return array
 */
function validate_pmadb($path, $values)
{
    $tables = array('Servers/1/bookmarktable', 'Servers/1/relation', 'Servers/1/table_info', 'Servers/1/table_coords', 'Servers/1/pdf_pages', 'Servers/1/column_info', 'Servers/1/history', 'Servers/1/designer_coords');
    $result = array('Server_pmadb' => '', 'Servers/1/controluser' => '', 'Servers/1/controlpass' => '');
    $error = false;

    if ($values['Servers/1/pmadb'] == '') {
        return $result;
    }

    $result = array();
    if ($values['Servers/1/controluser'] == '') {
        $result['Servers/1/controluser'] = PMA_lang('error_empty_pmadb_user');
        $error = true;
    }
    if ($values['Servers/1/controlpass'] == '') {
        $result['Servers/1/controlpass'] = PMA_lang('error_empty_pmadb_password');
        $error = true;
    }
    if (!$error) {
        $test = test_db_connection($values['Servers/1/extension'], $values['Servers/1/connect_type'], $values['Servers/1/host'], $values['Servers/1/port'], $values['Servers/1/socket'], $values['Servers/1/controluser'], $values['Servers/1/controlpass'], 'Server_pmadb');
        if ($test !== true) {
            $result = array_merge($result, $test);
        }
    }
    return $result;
}


/**
 * Validates regular expression
 *
 * @param string $path
 * @param array  $values
 * @return array
 */
function validate_regex($path, $values)
{
    $result = array($path => '');

    if ($values[$path] == '') {
        return $result;
    }

    test_php_errormsg();

    $matches = array();
    preg_match($values[$path], '', $matches);
    ob_end_clean();

    test_php_errormsg(false);

    if (isset($php_errormsg)) {
        $error = preg_replace('/^preg_match\(\): /', '', $php_errormsg);
        return array($path => $error);
    }

    return $result;
}

/**
 * Validates TrustedProxies field
 *
 * @param string $path
 * @param array  $values
 * @return array
 */
function validate_trusted_proxies($path, $values)
{
    $result = array($path => array());

    if (empty($values[$path])) {
        return $result;
    }

    if (is_array($values[$path])) {
        // value already processed by FormDisplay::save
        $lines = array();
        foreach ($values[$path] as $ip => $v) {
            $lines[] = preg_match('/^-\d+$/', $ip)
            	? $v
            	: $ip . ': ' . $v;
        }
    } else {
        // AJAX validation
        $lines = explode("\n", $values[$path]);
    }
    foreach ($lines as $line) {
        $line = trim($line);
        $matches = array();
        // we catch anything that may (or may not) be an IP
        if (!preg_match("/^(.+):(?:[ ]?)\\w+$/", $line, $matches)) {
            $result[$path][] = PMA_lang('error_incorrect_value') . ': ' . $line;
            continue;
        }
        // now let's check whether we really have an IP address
        if (filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false
            && filter_var($matches[1], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false) {
            $ip = htmlspecialchars(trim($matches[1]));
            $result[$path][] = PMA_lang('error_incorrect_ip_address', $ip);
            continue;
        }
    }

    return $result;
}


/**
 * Tests integer value
 *
 * @param string $path
 * @param array  $values
 * @param bool   $allow_neg       allow negative values
 * @param bool   $allow_zero      allow zero
 * @param int    $max_value       max allowed value
 * @param string $error_lang_key  error message key: $GLOBALS["strSetup$error_lang_key"]
 * @return string  empty string if test is successful
 */
function test_number($path, $values, $allow_neg, $allow_zero, $max_value, $error_lang_key)
{
    if ($values[$path] === '') {
        return '';
    }

    if (intval($values[$path]) != $values[$path] || (!$allow_neg && $values[$path] < 0) || (!$allow_zero && $values[$path] == 0) || $values[$path] > $max_value) {
        return PMA_lang($error_lang_key);
    }

    return '';
}

/**
 * Validates port number
 *
 * @param string $path
 * @param array  $values
 * @return array
 */
function validate_port_number($path, $values)
{
    return array($path => test_number($path, $values, false, false, 65536, 'error_incorrect_port'));
}

/**
 * Validates positive number
 *
 * @param string $path
 * @param array  $values
 * @return array
 */
function validate_positive_number($path, $values)
{
    return array($path => test_number($path, $values, false, false, PHP_INT_MAX, 'error_nan_p'));
}

/**
 * Validates non-negative number
 *
 * @param string $path
 * @param array  $values
 * @return array
 */
function validate_non_negative_number($path, $values)
{
    return array($path => test_number($path, $values, false, true, PHP_INT_MAX, 'error_nan_nneg'));
}
?>