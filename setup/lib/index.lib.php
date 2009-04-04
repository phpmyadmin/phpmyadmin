<?php

/**
 * Various checks and message functions used on index page.
 *
 * Security checks are the idea of Aung Khant <aungkhant[at]yehg.net>, http://yehg.net/lab
 * Version check taken from the old setup script by Michal Čihař <michal@cihar.com>
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Load vendor config.
 */
require_once('./libraries/vendor_config.php');

/**
 * Initializes message list
 */
function messages_begin()
{
    if (!isset($_SESSION['messages']) || !is_array($_SESSION['messages'])) {
        $_SESSION['messages'] = array('error' => array(), 'warning' => array(), 'notice' => array());
    } else {
        // reset message states
        foreach ($_SESSION['messages'] as &$messages) {
            foreach ($messages as &$msg) {
                $msg['fresh'] = false;
                $msg['active'] = false;
            }
        }
    }
}

/**
 * Adds a new message to message list
 *
 * @param string $id unique message identifier
 * @param string $type one of: notice, warning, error
 * @param string $title language string id (in $str array)
 * @param string $message message text
 */
function messages_set($type, $id, $title, $message)
{
    $fresh = !isset($_SESSION['messages'][$type][$id]);
    $title = PMA_lang($title);
    $_SESSION['messages'][$type][$id] = array(
        'fresh' => $fresh,
        'active' => true,
        'title' => $title,
        'message' => $message);
}

/**
 * Cleans up message list
 */
function messages_end()
{
    foreach ($_SESSION['messages'] as &$messages) {
        $remove_ids = array();
        foreach ($messages as $id => &$msg) {
            if ($msg['active'] == false) {
                $remove_ids[] = $id;
            }
        }
        foreach ($remove_ids as $id) {
            unset($messages[$id]);
        }
    }
}

/**
 * Prints message list, must be called after messages_end()
 */
function messages_show_html()
{
    $old_ids = array();
    foreach ($_SESSION['messages'] as $type => $messages) {
        foreach ($messages as $id => $msg) {
            echo '<div class="' . $type . '" id="' . $id . '">' . '<h4>' . $msg['title'] . '</h4>' . $msg['message'] . '</div>';
            if (!$msg['fresh'] && $type != 'error') {
                $old_ids[] = $id;
            }
        }
    }

    echo "\n" . '<script type="text/javascript">';
    foreach ($old_ids as $id) {
        echo "\nhiddenMessages.push('$id');";
    }
    echo "\n</script>\n";
}

/**
 * Checks for newest phpMyAdmin version and sets result as a new notice
 */
function PMA_version_check()
{
    // version check messages should always be visible so let's make
    // a unique message id each time we run it
    $message_id = uniqid('version_check');
    // wait 3s at most for server response, it's enough to get information
    // from a working server
    $connection_timeout = 3;

    $url = 'http://phpmyadmin.net/home_page/version.php';
    $context = stream_context_create(array(
        'http' => array(
            'timeout' => $connection_timeout)));
    $data = @file_get_contents($url, null, $context);
    if ($data === false) {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $connection_timeout);
            $data = curl_exec($ch);
            curl_close($ch);
        } else {
            messages_set('error', $message_id, 'VersionCheck',
                PMA_lang('VersionCheckWrapperError'));
            return;
        }
    }

    if (empty($data)) {
        messages_set('error', $message_id, 'VersionCheck',
            PMA_lang('VersionCheckDataError'));
        return;
    }

    /* Format: version\ndate\n(download\n)* */
    $data_list = explode("\n", $data);

    if (count($data_list) > 1) {
        $version = $data_list[0];
        $date = $data_list[1];
    } else {
        $version = $date = '';
    }

    $version_upstream = version_to_int($version);
    if ($version_upstream === false) {
        messages_set('error', $message_id, 'VersionCheck',
            PMA_lang('VersionCheckInvalid'));
        return;
    }

    $version_local = version_to_int($_SESSION['PMA_Config']->get('PMA_VERSION'));
    if ($version_local === false) {
        messages_set('error', $message_id, 'VersionCheck',
            PMA_lang('VersionCheckUnparsable'));
        return;
    }

    if ($version_upstream > $version_local) {
        $version = htmlspecialchars($version);
        $date = htmlspecialchars($date);
        messages_set('notice', $message_id, 'VersionCheck',
            PMA_lang('VersionCheckNewAvailable', $version, $date));
    } else {
        if ($version_local % 100 == 0) {
            messages_set('notice', $message_id, 'VersionCheck',
                PMA_lang('VersionCheckNewAvailableSvn', $version, $date));
        } else {
            messages_set('notice', $message_id, 'VersionCheck',
                PMA_lang('VersionCheckNone'));
        }
    }
}

/**
 * Calculates numerical equivalent of phpMyAdmin version string
 *
 * @param string  version
 * @return mixed  false on failure, integer on success
 */
function version_to_int($version)
{
    $matches = array();
    if (!preg_match('/^(\d+)\.(\d+)\.(\d+)((\.|-(pl|rc|dev|beta|alpha))(\d+)?)?$/', $version, $matches)) {
        return false;
    }
    if (!empty($matches[6])) {
        switch ($matches[6]) {
            case 'pl':
                $added = 60;
                break;
            case 'rc':
                $added = 30;
                break;
            case 'beta':
                $added = 20;
                break;
            case 'alpha':
                $added = 10;
                break;
            case 'dev':
                $added = 0;
                break;
            default:
                messages_set('notice', 'version_match', 'VersionCheck',
                    'Unknown version part: ' . htmlspecialchars($matches[6]));
                $added = 0;
                break;
        }
    } else {
        $added = 50; // for final
    }
    if (!empty($matches[7])) {
        $added = $added + $matches[7];
    }
    return $matches[1] * 1000000 + $matches[2] * 10000 + $matches[3] * 100 + $added;
}

/**
 * Checks whether config file is readable/writable
 *
 * @param bool &$is_readable
 * @param bool &$is_writable
 * @param bool &$file_exists
 */
function check_config_rw(&$is_readable, &$is_writable, &$file_exists)
{
    $file_path = ConfigFile::getInstance()->getFilePath();
    $file_dir = dirname($file_path);
    $is_readable = true;
    $is_writable = is_dir($file_dir);
    if (SETUP_DIR_WRITABLE) {
        $is_writable = $is_writable && is_writable($file_dir);
    }
    $file_exists = file_exists($file_path);
    if ($file_exists) {
        $is_readable = is_readable($file_path);
        $is_writable = $is_writable && is_writable($file_path);
    }
}

/**
 * Performs various compatibility, security and consistency checks on current config
 *
 * Outputs results to message list, must be called between messages_begin()
 * and messages_end()
 */
function perform_config_checks()
{
    $cf = ConfigFile::getInstance();
    $blowfish_secret = $cf->get('blowfish_secret');
    $blowfish_secret_set = false;
    $cookie_auth_used = false;
    for ($i = 1, $server_cnt = $cf->getServerCount(); $i <= $server_cnt; $i++) {
        $cookie_auth_server = ($cf->getValue("Servers/$i/auth_type") == 'cookie');
        $cookie_auth_used |= $cookie_auth_server;
        $server_name = $cf->getServerName($i);
        if ($server_name == 'localhost') {
            $server_name .=  " [$i]";
        }

        if ($cookie_auth_server && $blowfish_secret === null) {
            $blowfish_secret = uniqid('', true);
            $blowfish_secret_set = true;
            $cf->set('blowfish_secret', $blowfish_secret);
        }

        //
        // $cfg['Servers'][$i]['ssl']
        // should be enabled if possible
        //
        if (!$cf->getValue("Servers/$i/ssl")) {
            $title = PMA_lang_name('Servers/1/ssl') . " ($server_name)";
            messages_set('notice', "Servers/$i/ssl", $title,
                PMA_lang('ServerSslMsg'));
        }

        //
        // $cfg['Servers'][$i]['extension']
        // warn about using 'mysql'
        //
        if ($cf->getValue("Servers/$i/extension") == 'mysql') {
            $title = PMA_lang_name('Servers/1/extension') . " ($server_name)";
            messages_set('notice', "Servers/$i/extension", $title,
                PMA_lang('ServerExtensionMsg'));
        }

        //
        // $cfg['Servers'][$i]['auth_type']
        // warn about full user credentials if 'auth_type' is 'config'
        //
        if ($cf->getValue("Servers/$i/auth_type") == 'config'
            && $cf->getValue("Servers/$i/user") != ''
            && $cf->getValue("Servers/$i/password") != '') {
            $title = PMA_lang_name('Servers/1/auth_type') . " ($server_name)";
            messages_set('warning', "Servers/$i/auth_type", $title,
                PMA_lang('ServerAuthConfigMsg', $i) . ' ' .
                PMA_lang('ServerSecurityInfoMsg', $i));
        }

        //
        // $cfg['Servers'][$i]['AllowRoot']
        // $cfg['Servers'][$i]['AllowNoPassword']
        // serious security flaw
        //
        if ($cf->getValue("Servers/$i/AllowRoot")
            && $cf->getValue("Servers/$i/AllowNoPassword")) {
            $title = PMA_lang_name('Servers/1/AllowNoPassword') . " ($server_name)";
            messages_set('warning', "Servers/$i/AllowNoPassword", $title,
                PMA_lang('ServerNoPasswordMsg') . ' ' .
                PMA_lang('ServerSecurityInfoMsg', $i));
        }
    }

    //
    // $cfg['blowfish_secret']
    // it's required for 'cookie' authentication
    //
    if ($cookie_auth_used) {
        if ($blowfish_secret_set) {
            // 'cookie' auth used, blowfish_secret was generated
            messages_set('notice', 'blowfish_secret_created', 'blowfish_secret_name',
                 PMA_lang('BlowfishSecretMsg'));
        } else {
            $blowfish_warnings = array();
            // check length
            if (strlen($blowfish_secret) < 8) {
                // too short key
                $blowfish_warnings[] = PMA_lang('BlowfishSecretLengthMsg');
            }
            // check used characters
            $has_digits = (bool) preg_match('/\d/', $blowfish_secret);
            $has_chars = (bool) preg_match('/\S/', $blowfish_secret);
            $has_nonword = (bool) preg_match('/\W/', $blowfish_secret);
            if (!$has_digits || !$has_chars || !$has_nonword) {
                $blowfish_warnings[] = PMA_lang('BlowfishSecretCharsMsg');
            }
            if (!empty($blowfish_warnings)) {
                messages_set('warning', 'blowfish_warnings' . count($blowfish_warnings),
                    'blowfish_secret_name', implode("<br />", $blowfish_warnings));
            }
        }
    }

    //
    // $cfg['ForceSSL']
    // should be enabled if possible
    //
    if (!$cf->getValue('ForceSSL')) {
        messages_set('notice', 'ForceSSL', 'ForceSSL_name',
            PMA_lang('ForceSSLMsg'));
    }

    //
    // $cfg['AllowArbitraryServer']
    // should be disabled
    //
    if ($cf->getValue('AllowArbitraryServer')) {
        messages_set('warning', 'AllowArbitraryServer', 'AllowArbitraryServer_name',
            PMA_lang('AllowArbitraryServerMsg'));
    }

    //
    // $cfg['LoginCookieValidity']
    // should be at most 1800 (30 min)
    //
    if ($cf->getValue('LoginCookieValidity') > 1800) {
        messages_set('warning', 'LoginCookieValidity', 'LoginCookieValidity_name',
            PMA_lang('LoginCookieValidityMsg'));
    }

    //
    // $cfg['SaveDir']
    // should not be world-accessible
    //
    if ($cf->getValue('SaveDir') != '') {
        messages_set('notice', 'SaveDir', 'SaveDir_name',
            PMA_lang('DirectoryNotice'));
    }

    //
    // $cfg['TempDir']
    // should not be world-accessible
    //
    if ($cf->getValue('TempDir') != '') {
        messages_set('notice', 'TempDir', 'TempDir_name',
            PMA_lang('DirectoryNotice'));
    }

    //
    // $cfg['GZipDump']
    // requires zlib functions
    //
    if ($cf->getValue('GZipDump')
        && (@!function_exists('gzopen') || @!function_exists('gzencode'))) {
        messages_set('warning', 'GZipDump', 'GZipDump_name',
            PMA_lang('GZipDumpWarning', 'gzencode'));
    }

    //
    // $cfg['BZipDump']
    // requires bzip2 functions
    //
    if ($cf->getValue('BZipDump')
        && (!@function_exists('bzopen') || !@function_exists('bzcompress'))) {
        $functions = @function_exists('bzopen')
            ? '' :
            'bzopen';
        $functions .= @function_exists('bzcompress')
            ? ''
            : ($functions ? ', ' : '') . 'bzcompress';
        messages_set('warning', 'BZipDump', 'BZipDump_name',
            PMA_lang('BZipDumpWarning', $functions));
    }

    //
    // $cfg['ZipDump']
    // requires zip_open in import
    //
    if ($cf->getValue('ZipDump') && !@function_exists('zip_open')) {
        messages_set('warning', 'ZipDump_import', 'ZipDump_name',
            PMA_lang('ZipDumpImportWarning', 'zip_open'));
    }

    //
    // $cfg['ZipDump']
    // requires gzcompress in export
    //
    if ($cf->getValue('ZipDump') && !@function_exists('gzcompress')) {
        messages_set('warning', 'ZipDump_export', 'ZipDump_name',
            PMA_lang('ZipDumpExportWarning', 'gzcompress'));
    }
}
?>
