<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Various checks and message functions used on index page.
 *
 * @package PhpMyAdmin-Setup
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Initializes message list
 *
 * @return void
 */
function messages_begin()
{
    if (! isset($_SESSION['messages']) || !is_array($_SESSION['messages'])) {
        $_SESSION['messages'] = array('error' => array(), 'notice' => array());
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
 * @param string $type    one of: notice, error
 * @param string $id      unique message identifier
 * @param string $title   language string id (in $str array)
 * @param string $message message text
 *
 * @return void
 */
function messages_set($type, $id, $title, $message)
{
    $fresh = ! isset($_SESSION['messages'][$type][$id]);
    $_SESSION['messages'][$type][$id] = array(
        'fresh' => $fresh,
        'active' => true,
        'title' => $title,
        'message' => $message);
}

/**
 * Cleans up message list
 *
 * @return void
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
 *
 * @return void
 */
function messages_show_html()
{
    $old_ids = array();
    foreach ($_SESSION['messages'] as $type => $messages) {
        foreach ($messages as $id => $msg) {
            echo '<div class="' . $type . '" id="' . $id . '">'
                . '<h4>' . $msg['title'] . '</h4>'
                . $msg['message'] . '</div>';
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
 *
 * @return void
 */
function PMA_version_check()
{
    // version check messages should always be visible so let's make
    // a unique message id each time we run it
    $message_id = uniqid('version_check');
    // wait 3s at most for server response, it's enough to get information
    // from a working server
    $connection_timeout = 3;

    $url = 'http://phpmyadmin.net/home_page/version.json';
    $context = stream_context_create(
        array(
            'http' => array('timeout' => $connection_timeout)
        )
    );
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
            messages_set(
                'error',
                $message_id,
                __('Version check'),
                __('Neither URL wrapper nor CURL is available. Version check is not possible.')
            );
            return;
        }
    }

    if (empty($data)) {
        messages_set(
            'error',
            $message_id,
            __('Version check'),
            __('Reading of version failed. Maybe you\'re offline or the upgrade server does not respond.')
        );
        return;
    }

    $data_list = json_decode($data);
    $releases = $data_list->releases;
    $latestCompatible = PMA_Util::getLatestCompatibleVersion($releases);
    if ($latestCompatible != null) {
        $version = $latestCompatible['version'];
        $date = $latestCompatible['date'];
    } else {
        return;
    }

    $version_upstream = version_to_int($version);
    if ($version_upstream === false) {
        messages_set(
            'error',
            $message_id,
            __('Version check'),
            __('Got invalid version string from server')
        );
        return;
    }

    $version_local = version_to_int($GLOBALS['PMA_Config']->get('PMA_VERSION'));
    if ($version_local === false) {
        messages_set(
            'error',
            $message_id,
            __('Version check'),
            __('Unparsable version string')
        );
        return;
    }

    if ($version_upstream > $version_local) {
        $version = htmlspecialchars($version);
        $date = htmlspecialchars($date);
        messages_set(
            'notice',
            $message_id,
            __('Version check'),
            sprintf(__('A newer version of phpMyAdmin is available and you should consider upgrading. The newest version is %s, released on %s.'), $version, $date)
        );
    } else {
        if ($version_local % 100 == 0) {
            messages_set(
                'notice',
                $message_id,
                __('Version check'),
                PMA_sanitize(sprintf(__('You are using Git version, run [kbd]git pull[/kbd] :-)[br]The latest stable version is %s, released on %s.'), $version, $date))
            );
        } else {
            messages_set(
                'notice',
                $message_id,
                __('Version check'),
                __('No newer stable version is available')
            );
        }
    }
}

/**
 * Calculates numerical equivalent of phpMyAdmin version string
 *
 * @param string $version version
 *
 * @return mixed false on failure, integer on success
 */
function version_to_int($version)
{
    $matches = array();
    if (!preg_match('/^(\d+)\.(\d+)\.(\d+)((\.|-(pl|rc|dev|beta|alpha))(\d+)?(-dev)?)?$/', $version, $matches)) {
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
            messages_set(
                'notice',
                'version_match',
                __('Version check'),
                'Unknown version part: ' . htmlspecialchars($matches[6])
            );
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
 *
 * @return void
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
 *
 * @return void
 */
function perform_config_checks()
{
    $cf = ConfigFile::getInstance();
    $blowfish_secret = $cf->get('blowfish_secret');
    $blowfish_secret_set = false;
    $cookie_auth_used = false;

    $strAllowArbitraryServerWarning = __('This %soption%s should be disabled as it allows attackers to bruteforce login to any MySQL server. If you feel this is necessary, use %strusted proxies list%s. However, IP-based protection may not be reliable if your IP belongs to an ISP where thousands of users, including you, are connected to.');
    $strAllowArbitraryServerWarning = sprintf($strAllowArbitraryServerWarning, '[a@?page=form&amp;formset=Features#tab_Security]', '[/a]', '[a@?page=form&amp;formset=Features#tab_Security]', '[/a]');
    $strBlowfishSecretMsg = __('You didn\'t have blowfish secret set and have enabled cookie authentication, so a key was automatically generated for you. It is used to encrypt cookies; you don\'t need to remember it.');
    $strBZipDumpWarning = __('%sBzip2 compression and decompression%s requires functions (%s) which are unavailable on this system.');
    $strBZipDumpWarning = sprintf($strBZipDumpWarning, '[a@?page=form&amp;formset=Features#tab_Import_export]', '[/a]', '%s');
    $strDirectoryNotice = __('This value should be double checked to ensure that this directory is neither world accessible nor readable or writable by other users on your server.');
    $strForceSSLNotice = __('This %soption%s should be enabled if your web server supports it.');
    $strForceSSLNotice = sprintf($strForceSSLNotice, '[a@?page=form&amp;formset=Features#tab_Security]', '[/a]');
    $strGZipDumpWarning = __('%sGZip compression and decompression%s requires functions (%s) which are unavailable on this system.');
    $strGZipDumpWarning = sprintf($strGZipDumpWarning, '[a@?page=form&amp;formset=Features#tab_Import_export]', '[/a]', '%s');
    $strLoginCookieValidityWarning = __('%sLogin cookie validity%s greater than 1440 seconds may cause random session invalidation if %ssession.gc_maxlifetime%s is lower than its value (currently %d).');
    $strLoginCookieValidityWarning = sprintf($strLoginCookieValidityWarning, '[a@?page=form&amp;formset=Features#tab_Security]', '[/a]', '[a@' . PMA_getPHPDocLink('session.configuration.php#ini.session.gc-maxlifetime') . ']', '[/a]', ini_get('session.gc_maxlifetime'));
    $strLoginCookieValidityWarning2 = __('%sLogin cookie validity%s should be set to 1800 seconds (30 minutes) at most. Values larger than 1800 may pose a security risk such as impersonation.');
    $strLoginCookieValidityWarning2 = sprintf($strLoginCookieValidityWarning2, '[a@?page=form&amp;formset=Features#tab_Security]', '[/a]');
    $strLoginCookieValidityWarning3 = __('If using cookie authentication and %sLogin cookie store%s is not 0, %sLogin cookie validity%s must be set to a value less or equal to it.');
    $strLoginCookieValidityWarning3 = sprintf($strLoginCookieValidityWarning3, '[a@?page=form&amp;formset=Features#tab_Security]', '[/a]', '[a@?page=form&amp;formset=Features#tab_Security]', '[/a]');
    $strSecurityInfoMsg = __('If you feel this is necessary, use additional protection settings - %shost authentication%s settings and %strusted proxies list%s. However, IP-based protection may not be reliable if your IP belongs to an ISP where thousands of users, including you, are connected to.');
    $strSecurityInfoMsg = sprintf($strSecurityInfoMsg, '[a@?page=servers&amp;mode=edit&amp;id=%1$d#tab_Server_config]', '[/a]', '[a@?page=form&amp;formset=Features#tab_Security]', '[/a]');
    $strServerAuthConfigMsg = __('You set the [kbd]config[/kbd] authentication type and included username and password for auto-login, which is not a desirable option for live hosts. Anyone who knows or guesses your phpMyAdmin URL can directly access your phpMyAdmin panel. Set %sauthentication type%s to [kbd]cookie[/kbd] or [kbd]http[/kbd].');
    $strServerAuthConfigMsg = sprintf($strServerAuthConfigMsg, '[a@?page=servers&amp;mode=edit&amp;id=%1$d#tab_Server]', '[/a]');
    $strZipDumpExportWarning = __('%sZip compression%s requires functions (%s) which are unavailable on this system.');
    $strZipDumpExportWarning = sprintf($strZipDumpExportWarning, '[a@?page=form&amp;formset=Features#tab_Import_export]', '[/a]', '%s');
    $strZipDumpImportWarning = __('%sZip decompression%s requires functions (%s) which are unavailable on this system.');
    $strZipDumpImportWarning = sprintf($strZipDumpImportWarning, '[a@?page=form&amp;formset=Features#tab_Import_export]', '[/a]', '%s');

    for ($i = 1, $server_cnt = $cf->getServerCount(); $i <= $server_cnt; $i++) {
        $cookie_auth_server = ($cf->getValue("Servers/$i/auth_type") == 'cookie');
        $cookie_auth_used |= $cookie_auth_server;
        $server_name = $cf->getServerName($i);
        if ($server_name == 'localhost') {
            $server_name .=  " [$i]";
        }
        $server_name = htmlspecialchars($server_name);

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
            $title = PMA_lang(PMA_lang_name('Servers/1/ssl')) . " ($server_name)";
            messages_set(
                'notice',
                "Servers/$i/ssl",
                $title,
                __('You should use SSL connections if your database server supports it.')
            );
        }

        //
        // $cfg['Servers'][$i]['extension']
        // warn about using 'mysql'
        //
        if ($cf->getValue("Servers/$i/extension") == 'mysql') {
            $title = PMA_lang(PMA_lang_name('Servers/1/extension'))
                . " ($server_name)";
            messages_set(
                'notice',
                "Servers/$i/extension",
                $title,
                __('You should use mysqli for performance reasons.')
            );
        }

        //
        // $cfg['Servers'][$i]['auth_type']
        // warn about full user credentials if 'auth_type' is 'config'
        //
        if ($cf->getValue("Servers/$i/auth_type") == 'config'
            && $cf->getValue("Servers/$i/user") != ''
            && $cf->getValue("Servers/$i/password") != ''
        ) {
            $title = PMA_lang(PMA_lang_name('Servers/1/auth_type'))
                . " ($server_name)";
            messages_set(
                'notice',
                "Servers/$i/auth_type",
                $title,
                PMA_lang($strServerAuthConfigMsg, $i) . ' '
                . PMA_lang($strSecurityInfoMsg, $i)
            );
        }

        //
        // $cfg['Servers'][$i]['AllowRoot']
        // $cfg['Servers'][$i]['AllowNoPassword']
        // serious security flaw
        //
        if ($cf->getValue("Servers/$i/AllowRoot")
            && $cf->getValue("Servers/$i/AllowNoPassword")
        ) {
            $title = PMA_lang(PMA_lang_name('Servers/1/AllowNoPassword'))
                . " ($server_name)";
            messages_set(
                'notice',
                "Servers/$i/AllowNoPassword",
                $title,
                __('You allow for connecting to the server without a password.') . ' '
                . PMA_lang($strSecurityInfoMsg, $i)
            );
        }
    }

    //
    // $cfg['blowfish_secret']
    // it's required for 'cookie' authentication
    //
    if ($cookie_auth_used) {
        if ($blowfish_secret_set) {
            // 'cookie' auth used, blowfish_secret was generated
            messages_set(
                'notice',
                'blowfish_secret_created',
                PMA_lang(PMA_lang_name('blowfish_secret')),
                $strBlowfishSecretMsg
            );
        } else {
            $blowfish_warnings = array();
            // check length
            if (strlen($blowfish_secret) < 8) {
                // too short key
                $blowfish_warnings[] = __('Key is too short, it should have at least 8 characters.');
            }
            // check used characters
            $has_digits = (bool) preg_match('/\d/', $blowfish_secret);
            $has_chars = (bool) preg_match('/\S/', $blowfish_secret);
            $has_nonword = (bool) preg_match('/\W/', $blowfish_secret);
            if (!$has_digits || !$has_chars || !$has_nonword) {
                $blowfish_warnings[] = PMA_lang(__('Key should contain letters, numbers [em]and[/em] special characters.'));
            }
            if (!empty($blowfish_warnings)) {
                messages_set(
                    'error',
                    'blowfish_warnings' . count($blowfish_warnings),
                    PMA_lang(PMA_lang_name('blowfish_secret')),
                    implode('<br />', $blowfish_warnings)
                );
            }
        }
    }

    //
    // $cfg['ForceSSL']
    // should be enabled if possible
    //
    if (!$cf->getValue('ForceSSL')) {
        messages_set(
            'notice',
            'ForceSSL',
            PMA_lang(PMA_lang_name('ForceSSL')),
            PMA_lang($strForceSSLNotice)
        );
    }

    //
    // $cfg['AllowArbitraryServer']
    // should be disabled
    //
    if ($cf->getValue('AllowArbitraryServer')) {
        messages_set(
            'notice',
            'AllowArbitraryServer',
            PMA_lang(PMA_lang_name('AllowArbitraryServer')),
            PMA_lang($strAllowArbitraryServerWarning)
        );
    }

    //
    // $cfg['LoginCookieValidity']
    // value greater than session.gc_maxlifetime will cause
    // random session invalidation after that time
    if ($cf->getValue('LoginCookieValidity') > 1440
        || $cf->getValue('LoginCookieValidity') > ini_get('session.gc_maxlifetime')
    ) {
        $message_type = $cf->getValue('LoginCookieValidity') > ini_get('session.gc_maxlifetime')
            ? 'error'
            : 'notice';
        messages_set(
            $message_type,
            'LoginCookieValidity',
            PMA_lang(PMA_lang_name('LoginCookieValidity')),
            PMA_lang($strLoginCookieValidityWarning)
        );
    }

    //
    // $cfg['LoginCookieValidity']
    // should be at most 1800 (30 min)
    //
    if ($cf->getValue('LoginCookieValidity') > 1800) {
        messages_set(
            'notice',
            'LoginCookieValidity',
            PMA_lang(PMA_lang_name('LoginCookieValidity')),
            PMA_lang($strLoginCookieValidityWarning2)
        );
    }

    //
    // $cfg['LoginCookieValidity']
    // $cfg['LoginCookieStore']
    // LoginCookieValidity must be less or equal to LoginCookieStore
    //
    if ($cf->getValue('LoginCookieStore') != 0
        && $cf->getValue('LoginCookieValidity') > $cf->getValue('LoginCookieStore')
    ) {
        messages_set(
            'error',
            'LoginCookieValidity',
            PMA_lang(PMA_lang_name('LoginCookieValidity')),
            PMA_lang($strLoginCookieValidityWarning3)
        );
    }

    //
    // $cfg['SaveDir']
    // should not be world-accessible
    //
    if ($cf->getValue('SaveDir') != '') {
        messages_set(
            'notice',
            'SaveDir',
            PMA_lang(PMA_lang_name('SaveDir')),
            PMA_lang($strDirectoryNotice)
        );
    }

    //
    // $cfg['TempDir']
    // should not be world-accessible
    //
    if ($cf->getValue('TempDir') != '') {
        messages_set(
            'notice',
            'TempDir',
            PMA_lang(PMA_lang_name('TempDir')),
            PMA_lang($strDirectoryNotice)
        );
    }

    //
    // $cfg['GZipDump']
    // requires zlib functions
    //
    if ($cf->getValue('GZipDump')
        && (@!function_exists('gzopen') || @!function_exists('gzencode'))
    ) {
        messages_set(
            'error',
            'GZipDump',
            PMA_lang(PMA_lang_name('GZipDump')),
            PMA_lang($strGZipDumpWarning, 'gzencode')
        );
    }

    //
    // $cfg['BZipDump']
    // requires bzip2 functions
    //
    if ($cf->getValue('BZipDump')
        && (!@function_exists('bzopen') || !@function_exists('bzcompress'))
    ) {
        $functions = @function_exists('bzopen')
                ? '' :
                'bzopen';
        $functions .= @function_exists('bzcompress')
                ? ''
                : ($functions ? ', ' : '') . 'bzcompress';
        messages_set(
            'error',
            'BZipDump',
            PMA_lang(PMA_lang_name('BZipDump')),
            PMA_lang($strBZipDumpWarning, $functions)
        );
    }

    //
    // $cfg['ZipDump']
    // requires zip_open in import
    //
    if ($cf->getValue('ZipDump') && !@function_exists('zip_open')) {
        messages_set(
            'error',
            'ZipDump_import',
            PMA_lang(PMA_lang_name('ZipDump')),
            PMA_lang($strZipDumpImportWarning, 'zip_open')
        );
    }

    //
    // $cfg['ZipDump']
    // requires gzcompress in export
    //
    if ($cf->getValue('ZipDump') && !@function_exists('gzcompress')) {
        messages_set(
            'error',
            'ZipDump_export',
            PMA_lang(PMA_lang_name('ZipDump')),
            PMA_lang($strZipDumpExportWarning, 'gzcompress')
        );
    }
}
?>
