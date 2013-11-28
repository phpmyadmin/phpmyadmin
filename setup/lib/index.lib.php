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
function PMA_messagesBegin()
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
function PMA_messagesSet($type, $id, $title, $message)
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
function PMA_messagesEnd()
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
 * Prints message list, must be called after PMA_messagesEnd()
 *
 * @return void
 */
function PMA_messagesShowHtml()
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
function PMA_versionCheck()
{
    // version check messages should always be visible so let's make
    // a unique message id each time we run it
    $message_id = uniqid('version_check');

    // Fetch data
    $version_data = PMA_Util::getLatestVersion();

    if (empty($version_data)) {
        PMA_messagesSet(
            'error',
            $message_id,
            __('Version check'),
            __('Reading of version failed. Maybe you\'re offline or the upgrade server does not respond.')
        );
        return;
    }

    $version = $version_data->version;
    $date = $version_data->date;

    $version_upstream = PMA_Util::versionToInt($version);
    if ($version_upstream === false) {
        PMA_messagesSet(
            'error',
            $message_id,
            __('Version check'),
            __('Got invalid version string from server')
        );
        return;
    }

    $version_local = PMA_Util::versionToInt(
        $GLOBALS['PMA_Config']->get('PMA_VERSION')
    );
    if ($version_local === false) {
        PMA_messagesSet(
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
        PMA_messagesSet(
            'notice',
            $message_id,
            __('Version check'),
            sprintf(__('A newer version of phpMyAdmin is available and you should consider upgrading. The newest version is %s, released on %s.'), $version, $date)
        );
    } else {
        if ($version_local % 100 == 0) {
            PMA_messagesSet(
                'notice',
                $message_id,
                __('Version check'),
                PMA_sanitize(sprintf(__('You are using Git version, run [kbd]git pull[/kbd] :-)[br]The latest stable version is %s, released on %s.'), $version, $date))
            );
        } else {
            PMA_messagesSet(
                'notice',
                $message_id,
                __('Version check'),
                __('No newer stable version is available')
            );
        }
    }
}

/**
 * Checks whether config file is readable/writable
 *
 * @param bool &$is_readable whether the file is readable
 * @param bool &$is_writable whether the file is writable
 * @param bool &$file_exists whether the file exists
 *
 * @return void
 */
function PMA_checkConfigRw(&$is_readable, &$is_writable, &$file_exists)
{
    $file_path = $GLOBALS['ConfigFile']->getFilePath();
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
 * Outputs results to message list, must be called between PMA_messagesBegin()
 * and PMA_messagesEnd()
 *
 * @return void
 */
function PMA_performConfigChecks()
{
    $cf = $GLOBALS['ConfigFile'];
    $blowfish_secret = $cf->get('blowfish_secret');
    $blowfish_secret_set = false;
    $cookie_auth_used = false;

    $strAllowArbitraryServerWarning = __('This %soption%s should be disabled as it allows attackers to bruteforce login to any MySQL server. If you feel this is necessary, use %strusted proxies list%s. However, IP-based protection may not be reliable if your IP belongs to an ISP where thousands of users, including you, are connected to.');
    $strAllowArbitraryServerWarning = sprintf(
        $strAllowArbitraryServerWarning,
        '[a@?page=form&amp;formset=Features#tab_Security]',
        '[/a]', '[a@?page=form&amp;formset=Features#tab_Security]',
        '[/a]'
    );
    $strBlowfishSecretMsg = __('You didn\'t have blowfish secret set and have enabled cookie authentication, so a key was automatically generated for you. It is used to encrypt cookies; you don\'t need to remember it.');
    $strBZipDumpWarning = __('%sBzip2 compression and decompression%s requires functions (%s) which are unavailable on this system.');
    $strBZipDumpWarning = sprintf(
        $strBZipDumpWarning,
        '[a@?page=form&amp;formset=Features#tab_Import_export]',
        '[/a]', '%s'
    );
    $strDirectoryNotice = __('This value should be double checked to ensure that this directory is neither world accessible nor readable or writable by other users on your server.');
    $strForceSSLNotice = __('This %soption%s should be enabled if your web server supports it.');
    $strForceSSLNotice = sprintf(
        $strForceSSLNotice,
        '[a@?page=form&amp;formset=Features#tab_Security]',
        '[/a]'
    );
    $strGZipDumpWarning = __('%sGZip compression and decompression%s requires functions (%s) which are unavailable on this system.');
    $strGZipDumpWarning = sprintf(
        $strGZipDumpWarning,
        '[a@?page=form&amp;formset=Features#tab_Import_export]',
        '[/a]',
        '%s'
    );
    $strLoginCookieValidityWarning = __('%sLogin cookie validity%s greater than %ssession.gc_maxlifetime%s may cause random session invalidation (currently session.gc_maxlifetime is %d).');
    $strLoginCookieValidityWarning = sprintf(
        $strLoginCookieValidityWarning,
        '[a@?page=form&amp;formset=Features#tab_Security]',
        '[/a]',
        '[a@' . PMA_getPHPDocLink(
            'session.configuration.php#ini.session.gc-maxlifetime'
        ) . ']',
        '[/a]',
        ini_get('session.gc_maxlifetime')
    );
    $strLoginCookieValidityWarning2 = __('%sLogin cookie validity%s should be set to 1800 seconds (30 minutes) at most. Values larger than 1800 may pose a security risk such as impersonation.');
    $strLoginCookieValidityWarning2 = sprintf(
        $strLoginCookieValidityWarning2,
        '[a@?page=form&amp;formset=Features#tab_Security]',
        '[/a]'
    );
    $strLoginCookieValidityWarning3 = __('If using cookie authentication and %sLogin cookie store%s is not 0, %sLogin cookie validity%s must be set to a value less or equal to it.');
    $strLoginCookieValidityWarning3 = sprintf(
        $strLoginCookieValidityWarning3,
        '[a@?page=form&amp;formset=Features#tab_Security]',
        '[/a]', '[a@?page=form&amp;formset=Features#tab_Security]',
        '[/a]'
    );
    $strSecurityInfoMsg = __('If you feel this is necessary, use additional protection settings - %shost authentication%s settings and %strusted proxies list%s. However, IP-based protection may not be reliable if your IP belongs to an ISP where thousands of users, including you, are connected to.');
    $strSecurityInfoMsg = sprintf(
        $strSecurityInfoMsg,
        '[a@?page=servers&amp;mode=edit&amp;id=%1$d#tab_Server_config]',
        '[/a]',
        '[a@?page=form&amp;formset=Features#tab_Security]',
        '[/a]'
    );
    $strServerAuthConfigMsg = __('You set the [kbd]config[/kbd] authentication type and included username and password for auto-login, which is not a desirable option for live hosts. Anyone who knows or guesses your phpMyAdmin URL can directly access your phpMyAdmin panel. Set %sauthentication type%s to [kbd]cookie[/kbd] or [kbd]http[/kbd].');
    $strServerAuthConfigMsg = sprintf(
        $strServerAuthConfigMsg,
        '[a@?page=servers&amp;mode=edit&amp;id=%1$d#tab_Server]',
        '[/a]'
    );
    $strZipDumpExportWarning = __('%sZip compression%s requires functions (%s) which are unavailable on this system.');
    $strZipDumpExportWarning = sprintf(
        $strZipDumpExportWarning,
        '[a@?page=form&amp;formset=Features#tab_Import_export]',
        '[/a]',
        '%s'
    );
    $strZipDumpImportWarning = __('%sZip decompression%s requires functions (%s) which are unavailable on this system.');
    $strZipDumpImportWarning = sprintf(
        $strZipDumpImportWarning,
        '[a@?page=form&amp;formset=Features#tab_Import_export]',
        '[/a]',
        '%s'
    );

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
            $title = PMA_lang(PMA_langName('Servers/1/ssl')) . " ($server_name)";
            PMA_messagesSet(
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
            $title = PMA_lang(PMA_langName('Servers/1/extension'))
                . " ($server_name)";
            PMA_messagesSet(
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
            $title = PMA_lang(PMA_langName('Servers/1/auth_type'))
                . " ($server_name)";
            PMA_messagesSet(
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
            $title = PMA_lang(PMA_langName('Servers/1/AllowNoPassword'))
                . " ($server_name)";
            PMA_messagesSet(
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
            PMA_messagesSet(
                'notice',
                'blowfish_secret_created',
                PMA_lang(PMA_langName('blowfish_secret')),
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
                PMA_messagesSet(
                    'error',
                    'blowfish_warnings' . count($blowfish_warnings),
                    PMA_lang(PMA_langName('blowfish_secret')),
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
        PMA_messagesSet(
            'notice',
            'ForceSSL',
            PMA_lang(PMA_langName('ForceSSL')),
            PMA_lang($strForceSSLNotice)
        );
    }

    //
    // $cfg['AllowArbitraryServer']
    // should be disabled
    //
    if ($cf->getValue('AllowArbitraryServer')) {
        PMA_messagesSet(
            'notice',
            'AllowArbitraryServer',
            PMA_lang(PMA_langName('AllowArbitraryServer')),
            PMA_lang($strAllowArbitraryServerWarning)
        );
    }

    //
    // $cfg['LoginCookieValidity']
    // value greater than session.gc_maxlifetime will cause
    // random session invalidation after that time
    if ($cf->getValue('LoginCookieValidity') > ini_get('session.gc_maxlifetime')) {
        PMA_messagesSet(
            'error',
            'LoginCookieValidity',
            PMA_lang(PMA_langName('LoginCookieValidity')),
            PMA_lang($strLoginCookieValidityWarning)
        );
    }

    //
    // $cfg['LoginCookieValidity']
    // should be at most 1800 (30 min)
    //
    if ($cf->getValue('LoginCookieValidity') > 1800) {
        PMA_messagesSet(
            'notice',
            'LoginCookieValidity',
            PMA_lang(PMA_langName('LoginCookieValidity')),
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
        PMA_messagesSet(
            'error',
            'LoginCookieValidity',
            PMA_lang(PMA_langName('LoginCookieValidity')),
            PMA_lang($strLoginCookieValidityWarning3)
        );
    }

    //
    // $cfg['SaveDir']
    // should not be world-accessible
    //
    if ($cf->getValue('SaveDir') != '') {
        PMA_messagesSet(
            'notice',
            'SaveDir',
            PMA_lang(PMA_langName('SaveDir')),
            PMA_lang($strDirectoryNotice)
        );
    }

    //
    // $cfg['TempDir']
    // should not be world-accessible
    //
    if ($cf->getValue('TempDir') != '') {
        PMA_messagesSet(
            'notice',
            'TempDir',
            PMA_lang(PMA_langName('TempDir')),
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
        PMA_messagesSet(
            'error',
            'GZipDump',
            PMA_lang(PMA_langName('GZipDump')),
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
        PMA_messagesSet(
            'error',
            'BZipDump',
            PMA_lang(PMA_langName('BZipDump')),
            PMA_lang($strBZipDumpWarning, $functions)
        );
    }

    //
    // $cfg['ZipDump']
    // requires zip_open in import
    //
    if ($cf->getValue('ZipDump') && !@function_exists('zip_open')) {
        PMA_messagesSet(
            'error',
            'ZipDump_import',
            PMA_lang(PMA_langName('ZipDump')),
            PMA_lang($strZipDumpImportWarning, 'zip_open')
        );
    }

    //
    // $cfg['ZipDump']
    // requires gzcompress in export
    //
    if ($cf->getValue('ZipDump') && !@function_exists('gzcompress')) {
        PMA_messagesSet(
            'error',
            'ZipDump_export',
            PMA_lang(PMA_langName('ZipDump')),
            PMA_lang($strZipDumpExportWarning, 'gzcompress')
        );
    }
}
?>
