<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server config checks management
 *
 * @package PhpMyAdmin
 */

/**
 * Performs various compatibility, security and consistency checks on current config
 *
 * Outputs results to message list, must be called between PMA_messagesBegin()
 * and PMA_messagesEnd()
 *
 * @package PhpMyAdmin
 */
class ServerConfigChecks
{
    /**
     * @var ConfigFile configurations being checked
     */
    protected $cfg;

    /**
     * Constructor.
     *
     * @param ConfigFile $cfg Configuration
     */
    public function __construct(ConfigFile $cfg)
    {
        $this->cfg = $cfg;
    }

    /**
     * Perform config checks
     *
     * @return void
     */
    public function performConfigChecks()
    {
        $blowfishSecret = $this->cfg->get('blowfish_secret');
        $blowfishSecretSet = false;
        $cookieAuthUsed = false;

        list(
            $sAllowArbitraryServerWarn, $sBlowfishSecretMsg,
            $sBZipDumpWarn, $sDirectoryNotice, $sForceSSLNotice,
            $sGZipDumpWarn, $sLoginCookieValidityWarn,
            $sLoginCookieValidityWarn2, $sLoginCookieValidityWarn3,
            $sSecurityInfoMsg, $sSrvAuthCfgMsg, $sZipDumpExportWarn,
            $sZipDumpImportWarn
        ) = self::defineMessages();

        list($cookieAuthUsed, $blowfishSecret, $blowfishSecretSet)
            = $this->performConfigChecksServers(
                $cookieAuthUsed, $blowfishSecret, $sSrvAuthCfgMsg,
                $sSecurityInfoMsg, $blowfishSecretSet
            );

        $this->performConfigChecksCookieAuthUsed(
            $cookieAuthUsed, $blowfishSecretSet, $sBlowfishSecretMsg,
            $blowfishSecret
        );

        //
        // $cfg['ForceSSL']
        // should be enabled if possible
        //
        if (!$this->cfg->getValue('ForceSSL')) {
            PMA_messagesSet(
                'notice',
                'ForceSSL',
                PMA_lang(PMA_langName('ForceSSL')),
                PMA_lang($sForceSSLNotice)
            );
        }

        //
        // $cfg['AllowArbitraryServer']
        // should be disabled
        //
        if ($this->cfg->getValue('AllowArbitraryServer')) {
            PMA_messagesSet(
                'notice',
                'AllowArbitraryServer',
                PMA_lang(PMA_langName('AllowArbitraryServer')),
                PMA_lang($sAllowArbitraryServerWarn)
            );
        }

        $this->performConfigChecksLoginCookie(
            $sLoginCookieValidityWarn, $sLoginCookieValidityWarn2,
            $sLoginCookieValidityWarn3
        );

        //
        // $cfg['SaveDir']
        // should not be world-accessible
        //
        if ($this->cfg->getValue('SaveDir') != '') {
            PMA_messagesSet(
                'notice',
                'SaveDir',
                PMA_lang(PMA_langName('SaveDir')),
                PMA_lang($sDirectoryNotice)
            );
        }

        //
        // $cfg['TempDir']
        // should not be world-accessible
        //
        if ($this->cfg->getValue('TempDir') != '') {
            PMA_messagesSet(
                'notice',
                'TempDir',
                PMA_lang(PMA_langName('TempDir')),
                PMA_lang($sDirectoryNotice)
            );
        }

        $this->performConfigChecksZips(
            $sGZipDumpWarn, $sBZipDumpWarn, $sZipDumpImportWarn,
            $sZipDumpExportWarn
        );
    }

    /**
     * Check config of servers
     *
     * @param boolean $cookieAuthUsed    Cookie auth is used
     * @param string  $blowfishSecret    Blowfish secret
     * @param string  $sServerAuthCfgMsg Message for server auth config
     * @param string  $sSecurityInfoMsg  Message for security information
     * @param boolean $blowfishSecretSet Blowfish secret set
     *
     * @return array
     */
    protected function performConfigChecksServers(
        $cookieAuthUsed, $blowfishSecret, $sServerAuthCfgMsg,
        $sSecurityInfoMsg, $blowfishSecretSet
    ) {
        $serverCnt = $this->cfg->getServerCount();
        for ($i = 1; $i <= $serverCnt; $i++) {
            $cookieAuthServer
                = ($this->cfg->getValue("Servers/$i/auth_type") == 'cookie');
            $cookieAuthUsed |= $cookieAuthServer;
            $serverName = $this->performConfigChecksServersGetServerName(
                $this->cfg->getServerName($i), $i
            );
            $serverName = htmlspecialchars($serverName);

            list($blowfishSecret, $blowfishSecretSet)
                = $this->performConfigChecksServersSetBlowfishSecret(
                    $blowfishSecret, $cookieAuthServer, $blowfishSecretSet
                );

            //
            // $cfg['Servers'][$i]['ssl']
            // should be enabled if possible
            //
            if (!$this->cfg->getValue("Servers/$i/ssl")) {
                $title = PMA_lang(PMA_langName('Servers/1/ssl')) . " ($serverName)";
                PMA_messagesSet(
                    'notice',
                    "Servers/$i/ssl",
                    $title,
                    __(
                        'You should use SSL connections if your database server '
                        . 'supports it.'
                    )
                );
            }

            //
            // $cfg['Servers'][$i]['auth_type']
            // warn about full user credentials if 'auth_type' is 'config'
            //
            if ($this->cfg->getValue("Servers/$i/auth_type") == 'config'
                && $this->cfg->getValue("Servers/$i/user") != ''
                && $this->cfg->getValue("Servers/$i/password") != ''
            ) {
                $title = PMA_lang(PMA_langName('Servers/1/auth_type'))
                    . " ($serverName)";
                PMA_messagesSet(
                    'notice',
                    "Servers/$i/auth_type",
                    $title,
                    PMA_lang($sServerAuthCfgMsg, $i) . ' '
                    . PMA_lang($sSecurityInfoMsg, $i)
                );
            }

            //
            // $cfg['Servers'][$i]['AllowRoot']
            // $cfg['Servers'][$i]['AllowNoPassword']
            // serious security flaw
            //
            if ($this->cfg->getValue("Servers/$i/AllowRoot")
                && $this->cfg->getValue("Servers/$i/AllowNoPassword")
            ) {
                $title = PMA_lang(PMA_langName('Servers/1/AllowNoPassword'))
                    . " ($serverName)";
                PMA_messagesSet(
                    'notice',
                    "Servers/$i/AllowNoPassword",
                    $title,
                    __('You allow for connecting to the server without a password.')
                    . ' ' . PMA_lang($sSecurityInfoMsg, $i)
                );
            }
        }
        return array($cookieAuthUsed, $blowfishSecret, $blowfishSecretSet);
    }

    /**
     * Set blowfish secret
     *
     * @param string  $blowfishSecret    Blowfish secret
     * @param boolean $cookieAuthServer  Cookie auth is used
     * @param boolean $blowfishSecretSet Blowfish secret set
     *
     * @return array
     */
    protected function performConfigChecksServersSetBlowfishSecret(
        $blowfishSecret, $cookieAuthServer, $blowfishSecretSet
    ) {
        if ($cookieAuthServer && $blowfishSecret === null) {
            $blowfishSecret = uniqid('', true);
            $blowfishSecretSet = true;
            $this->cfg->set('blowfish_secret', $blowfishSecret);
            return array($blowfishSecret, $blowfishSecretSet);
        }
        return array($blowfishSecret, $blowfishSecretSet);
    }

    /**
     * Define server name
     *
     * @param string $serverName Server name
     * @param int    $serverId   Server id
     *
     * @return string Server name
     */
    protected function performConfigChecksServersGetServerName(
        $serverName, $serverId
    ) {
        if ($serverName == 'localhost') {
            $serverName .= " [$serverId]";
            return $serverName;
        }
        return $serverName;
    }

    /**
     * Perform config checks for zip part.
     *
     * @param string $sGZipDumpWarning   Gzip dump warning
     * @param string $sBZipDumpWarning   Bzip dump warning
     * @param string $sZipDumpImportWarn Zip dump import warning
     * @param string $sZipDumpExportWarn Zip dump export warning
     *
     * @return void
     */
    protected function performConfigChecksZips(
        $sGZipDumpWarning, $sBZipDumpWarning, $sZipDumpImportWarn,
        $sZipDumpExportWarn
    ) {
        $this->performConfigChecksServerGZipdump($sGZipDumpWarning);
        $this->performConfigChecksServerBZipdump($sBZipDumpWarning);
        $this->performConfigChecksServersZipdump(
            $sZipDumpImportWarn, $sZipDumpExportWarn
        );
    }

    /**
     * Perform config checks for zip part.
     *
     * @param string $sZipDumpImportWarn Zip dump import warning
     * @param string $sZipDumpExportWarn Zip dump export warning
     *
     * @return void
     */
    protected function performConfigChecksServersZipdump(
        $sZipDumpImportWarn, $sZipDumpExportWarn
    ) {
        //
        // $cfg['ZipDump']
        // requires zip_open in import
        //
        if ($this->cfg->getValue('ZipDump') && !@function_exists('zip_open')) {
            PMA_messagesSet(
                'error',
                'ZipDump_import',
                PMA_lang(PMA_langName('ZipDump')),
                PMA_lang($sZipDumpImportWarn, 'zip_open')
            );
        }

        //
        // $cfg['ZipDump']
        // requires gzcompress in export
        //
        if ($this->cfg->getValue('ZipDump') && !@function_exists('gzcompress')) {
            PMA_messagesSet(
                'error',
                'ZipDump_export',
                PMA_lang(PMA_langName('ZipDump')),
                PMA_lang($sZipDumpExportWarn, 'gzcompress')
            );
        }
    }

    /**
     * Check config of servers
     *
     * @param boolean $cookieAuthUsed     Cookie auth is used
     * @param boolean $blowfishSecretSet  Blowfish secret set
     * @param string  $sBlowfishSecretMsg Blowfish secret message
     * @param string  $blowfishSecret     Blowfish secret
     *
     * @return array
     */
    protected function performConfigChecksCookieAuthUsed(
        $cookieAuthUsed, $blowfishSecretSet, $sBlowfishSecretMsg,
        $blowfishSecret
    ) {
        //
        // $cfg['blowfish_secret']
        // it's required for 'cookie' authentication
        //
        if ($cookieAuthUsed) {
            if ($blowfishSecretSet) {
                // 'cookie' auth used, blowfish_secret was generated
                PMA_messagesSet(
                    'notice',
                    'blowfish_secret_created',
                    PMA_lang(PMA_langName('blowfish_secret')),
                    PMA_lang($sBlowfishSecretMsg)
                );
            } else {
                $blowfishWarnings = array();
                // check length
                if (/*overload*/mb_strlen($blowfishSecret) < 8) {
                    // too short key
                    $blowfishWarnings[] = __(
                        'Key is too short, it should have at least 8 characters.'
                    );
                }
                // check used characters
                $hasDigits = (bool)preg_match('/\d/', $blowfishSecret);
                $hasChars = (bool)preg_match('/\S/', $blowfishSecret);
                $hasNonword = (bool)preg_match('/\W/', $blowfishSecret);
                if (!$hasDigits || !$hasChars || !$hasNonword) {
                    $blowfishWarnings[] = PMA_lang(
                        __(
                            'Key should contain letters, numbers [em]and[/em] '
                            . 'special characters.'
                        )
                    );
                }
                if (!empty($blowfishWarnings)) {
                    PMA_messagesSet(
                        'error',
                        'blowfish_warnings' . count($blowfishWarnings),
                        PMA_lang(PMA_langName('blowfish_secret')),
                        implode('<br />', $blowfishWarnings)
                    );
                }
            }
        }
    }

    /**
     * Define all messages
     *
     * @return array
     */
    protected static function defineMessages()
    {
        $sAllowArbitraryServerWarn = __(
            'This %soption%s should be disabled as it allows attackers to '
            . 'bruteforce login to any MySQL server. If you feel this is necessary, '
            . 'use %srestrict login to MySQL server%s or %strusted proxies list%s. '
            . 'However, IP-based protection with trusted proxies list may not be '
            . 'reliable if your IP belongs to an ISP where thousands of users, '
            . 'including you, are connected to.'
        );
        $sAllowArbitraryServerWarn = sprintf(
            $sAllowArbitraryServerWarn,
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Security]',
            '[/a]',
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Security]',
            '[/a]',
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Security]',
            '[/a]'
        );
        $sBlowfishSecretMsg = __(
            'You didn\'t have blowfish secret set and have enabled '
            . '[kbd]cookie[/kbd] authentication, so a key was automatically '
            . 'generated for you. It is used to encrypt cookies; you don\'t need to '
            . 'remember it.'
        );
        $sBZipDumpWarning = __(
            '%sBzip2 compression and decompression%s requires functions (%s) which '
            . 'are unavailable on this system.'
        );
        $sBZipDumpWarning = sprintf(
            $sBZipDumpWarning,
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Import_export]',
            '[/a]', '%s'
        );
        $sDirectoryNotice = __(
            'This value should be double checked to ensure that this directory is '
            . 'neither world accessible nor readable or writable by other users on '
            . 'your server.'
        );
        $sForceSSLNotice = __(
            'This %soption%s should be enabled if your web server supports it.'
        );
        $sForceSSLNotice = sprintf(
            $sForceSSLNotice,
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Security]',
            '[/a]'
        );
        $sGZipDumpWarning = __(
            '%sGZip compression and decompression%s requires functions (%s) which '
            . 'are unavailable on this system.'
        );
        $sGZipDumpWarning = sprintf(
            $sGZipDumpWarning,
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Import_export]',
            '[/a]',
            '%s'
        );
        $sLoginCookieValidityWarn = __(
            '%sLogin cookie validity%s greater than %ssession.gc_maxlifetime%s may '
            . 'cause random session invalidation (currently session.gc_maxlifetime '
            . 'is %d).'
        );
        $sLoginCookieValidityWarn = sprintf(
            $sLoginCookieValidityWarn,
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Security]',
            '[/a]',
            '[a@' . PMA_getPHPDocLink(
                'session.configuration.php#ini.session.gc-maxlifetime'
            ) . ']',
            '[/a]',
            ini_get('session.gc_maxlifetime')
        );
        $sLoginCookieValidityWarn2 = __(
            '%sLogin cookie validity%s should be set to 1800 seconds (30 minutes) '
            . 'at most. Values larger than 1800 may pose a security risk such as '
            . 'impersonation.'
        );
        $sLoginCookieValidityWarn2 = sprintf(
            $sLoginCookieValidityWarn2,
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Security]',
            '[/a]'
        );
        $sLoginCookieValidityWarn3 = __(
            'If using [kbd]cookie[/kbd] authentication and %sLogin cookie store%s '
            . 'is not 0, %sLogin cookie validity%s must be set to a value less or '
            . 'equal to it.'
        );
        $sLoginCookieValidityWarn3 = sprintf(
            $sLoginCookieValidityWarn3,
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Security]',
            '[/a]',
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Security]',
            '[/a]'
        );
        $sSecurityInfoMsg = __(
            'If you feel this is necessary, use additional protection settings - '
            . '%shost authentication%s settings and %strusted proxies list%s. '
            . 'However, IP-based protection may not be reliable if your IP belongs '
            . 'to an ISP where thousands of users, including you, are connected to.'
        );
        $sSecurityInfoMsg = sprintf(
            $sSecurityInfoMsg,
            '[a@?page=servers' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;mode=edit&amp;id=%1$d#tab_Server_config]',
            '[/a]',
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Security]',
            '[/a]'
        );
        $sServerAuthConfigMsg = __(
            'You set the [kbd]config[/kbd] authentication type and included '
            . 'username and password for auto-login, which is not a desirable '
            . 'option for live hosts. Anyone who knows or guesses your phpMyAdmin '
            . 'URL can directly access your phpMyAdmin panel. Set %sauthentication '
            . 'type%s to [kbd]cookie[/kbd] or [kbd]http[/kbd].'
        );
        $sServerAuthConfigMsg = sprintf(
            $sServerAuthConfigMsg,
            '[a@?page=servers' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;mode=edit&amp;id=%1$d#tab_Server]',
            '[/a]'
        );
        $sZipDumpExportWarn = __(
            '%sZip compression%s requires functions (%s) which are unavailable on '
            . 'this system.'
        );
        $sZipDumpExportWarn = sprintf(
            $sZipDumpExportWarn,
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Import_export]',
            '[/a]',
            '%s'
        );
        $sZipDumpImportWarn = __(
            '%sZip decompression%s requires functions (%s) which are unavailable '
            . 'on this system.'
        );
        $sZipDumpImportWarn = sprintf(
            $sZipDumpImportWarn,
            '[a@?page=form' . PMA_URL_getCommon(array(), 'html', '&')
            . '&amp;formset=Features#tab_Import_export]',
            '[/a]',
            '%s'
        );
        return array(
            $sAllowArbitraryServerWarn, $sBlowfishSecretMsg, $sBZipDumpWarning,
            $sDirectoryNotice, $sForceSSLNotice, $sGZipDumpWarning,
            $sLoginCookieValidityWarn, $sLoginCookieValidityWarn2,
            $sLoginCookieValidityWarn3, $sSecurityInfoMsg, $sServerAuthConfigMsg,
            $sZipDumpExportWarn, $sZipDumpImportWarn
        );
    }

    /**
     * Check configuration for login cookie
     *
     * @param string $sLoginCookieValidityWarn  Warning 1 for login cookie validity
     * @param string $sLoginCookieValidityWarn2 Warning 2 for login cookie validity
     * @param string $sLoginCookieValidityWarn3 Warning 3 for login cookie validity
     *
     * @return void
     */
    protected function performConfigChecksLoginCookie(
        $sLoginCookieValidityWarn, $sLoginCookieValidityWarn2,
        $sLoginCookieValidityWarn3
    ) {
        //
        // $cfg['LoginCookieValidity']
        // value greater than session.gc_maxlifetime will cause
        // random session invalidation after that time
        $loginCookieValidity = $this->cfg->getValue('LoginCookieValidity');
        if ($loginCookieValidity > ini_get('session.gc_maxlifetime')
        ) {
            PMA_messagesSet(
                'error',
                'LoginCookieValidity',
                PMA_lang(PMA_langName('LoginCookieValidity')),
                PMA_lang($sLoginCookieValidityWarn)
            );
        }

        //
        // $cfg['LoginCookieValidity']
        // should be at most 1800 (30 min)
        //
        if ($loginCookieValidity > 1800) {
            PMA_messagesSet(
                'notice',
                'LoginCookieValidity',
                PMA_lang(PMA_langName('LoginCookieValidity')),
                PMA_lang($sLoginCookieValidityWarn2)
            );
        }

        //
        // $cfg['LoginCookieValidity']
        // $cfg['LoginCookieStore']
        // LoginCookieValidity must be less or equal to LoginCookieStore
        //
        if (($this->cfg->getValue('LoginCookieStore') != 0)
            && ($loginCookieValidity > $this->cfg->getValue('LoginCookieStore'))
        ) {
            PMA_messagesSet(
                'error',
                'LoginCookieValidity',
                PMA_lang(PMA_langName('LoginCookieValidity')),
                PMA_lang($sLoginCookieValidityWarn3)
            );
        }
    }

    /**
     * Check GZipDump configuration
     *
     * @param string $sBZipDumpWarn Warning for BZipDumpWarning
     *
     * @return void
     */
    protected function performConfigChecksServerBZipdump($sBZipDumpWarn)
    {
        //
        // $cfg['BZipDump']
        // requires bzip2 functions
        //
        if ($this->cfg->getValue('BZipDump')
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
                PMA_lang($sBZipDumpWarn, $functions)
            );
        }
    }

    /**
     * Check GZipDump configuration
     *
     * @param string $sGZipDumpWarn Warning for GZipDumpWarning
     *
     * @return void
     */
    protected function performConfigChecksServerGZipdump($sGZipDumpWarn)
    {
        //
        // $cfg['GZipDump']
        // requires zlib functions
        //
        if ($this->cfg->getValue('GZipDump')
            && (@!function_exists('gzopen') || @!function_exists('gzencode'))
        ) {
            PMA_messagesSet(
                'error',
                'GZipDump',
                PMA_lang(PMA_langName('GZipDump')),
                PMA_lang($sGZipDumpWarn, 'gzencode')
            );
        }
    }
}
