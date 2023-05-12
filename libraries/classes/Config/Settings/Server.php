<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function in_array;
use function is_array;

/** @psalm-immutable */
final class Server
{
    /**
     * MySQL hostname or IP address
     *
     * ```php
     * $cfg['Servers'][$i]['host'] = 'localhost';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_host
     */
    public string $host;

    /**
     * MySQL port - leave blank for default port
     *
     * ```php
     * $cfg['Servers'][$i]['port'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_port
     */
    public string $port;

    /**
     * Path to the socket - leave blank for default socket
     *
     * ```php
     * $cfg['Servers'][$i]['socket'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_socket
     */
    public string $socket;

    /**
     * Use SSL for connecting to MySQL server?
     *
     * ```php
     * $cfg['Servers'][$i]['ssl'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl
     */
    public bool $ssl;

    /**
     * Path to the key file when using SSL for connecting to the MySQL server
     *
     * ```php
     * $cfg['Servers'][$i]['ssl_key'] = null;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl_key
     */
    public string|null $sslKey = null;

    /**
     * Path to the cert file when using SSL for connecting to the MySQL server
     *
     * ```php
     * $cfg['Servers'][$i]['ssl_cert'] = null;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl_cert
     */
    public string|null $sslCert = null;

    /**
     * Path to the CA file when using SSL for connecting to the MySQL server
     *
     * ```php
     * $cfg['Servers'][$i]['ssl_ca'] = null;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl_ca
     */
    public string|null $sslCa = null;

    /**
     * Directory containing trusted SSL CA certificates in PEM format
     *
     * ```php
     * $cfg['Servers'][$i]['ssl_ca_path'] = null;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl_ca_path
     */
    public string|null $sslCaPath = null;

    /**
     * List of allowable ciphers for SSL connections to the MySQL server
     *
     * ```php
     * $cfg['Servers'][$i]['ssl_ciphers'] = null;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl_ciphers
     */
    public string|null $sslCiphers = null;

    /**
     * MySQL 5.6 or later triggers the mysqlnd driver in PHP to validate the
     * peer_name of the SSL certifcate
     * For most self-signed certificates this is a problem. Setting this to false
     * will disable the check and allow the connection (PHP 5.6.16 or later)
     *
     * ```php
     * $cfg['Servers'][$i]['ssl_verify'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl_verify
     * @see https://bugs.php.net/68344
     */
    public bool $sslVerify;

    /**
     * Use compressed protocol for the MySQL connection
     *
     * ```php
     * $cfg['Servers'][$i]['compress'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_compress
     */
    public bool $compress;

    /**
     * MySQL control host. This permits to use a host different from the
     * main host, for the phpMyAdmin configuration storage. If left empty,
     * $cfg['Servers'][$i]['host'] is used instead.
     *
     * ```php
     * $cfg['Servers'][$i]['controlhost'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_controlhost
     */
    public string $controlHost;

    /**
     * MySQL control port. This permits to use a port different from the
     * main port, for the phpMyAdmin configuration storage. If left empty,
     * $cfg['Servers'][$i]['port'] is used instead.
     *
     * ```php
     * $cfg['Servers'][$i]['controlport'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_controlport
     */
    public string $controlPort;

    /**
     * MySQL control user settings (this user must have read-only
     * access to the "mysql/user" and "mysql/db" tables). The controluser is also
     * used for all relational features (pmadb)
     *
     * ```php
     * $cfg['Servers'][$i]['controluser'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_controluser
     */
    public string $controlUser;

    /**
     * MySQL control user settings (this user must have read-only
     * access to the "mysql/user" and "mysql/db" tables). The controluser is also
     * used for all relational features (pmadb)
     *
     * ```php
     * $cfg['Servers'][$i]['controlpass'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_controlpass
     */
    public string $controlPass;

    /**
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_control_*
     * @see self::$socket
     */
    public string|null $controlSocket;

    /**
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_control_*
     * @see self::$ssl
     */
    public bool|null $controlSsl;

    /**
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_control_*
     * @see self::$sslKey
     */
    public string|null $controlSslKey = null;

    /**
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_control_*
     * @see self::$sslCert
     */
    public string|null $controlSslCert = null;

    /**
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_control_*
     * @see self::$sslCa
     */
    public string|null $controlSslCa = null;

    /**
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_control_*
     * @see self::$sslCaPath
     */
    public string|null $controlSslCaPath = null;

    /**
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_control_*
     * @see self::$sslCiphers
     */
    public string|null $controlSslCiphers = null;

    /**
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_control_*
     * @see self::$sslVerify
     * @see https://bugs.php.net/68344
     */
    public bool|null $controlSslVerify;

    /**
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_control_*
     * @see self::$compress
     */
    public bool|null $controlCompress;

    /**
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_control_*
     * @see self::$hideConnectionErrors
     */
    public bool|null $controlHideConnectionErrors;

    /**
     * Authentication method (valid choices: config, http, signon or cookie)
     *
     * ```php
     * $cfg['Servers'][$i]['auth_type'] = 'cookie';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_auth_type
     *
     * @psalm-var 'config'|'http'|'signon'|'cookie'
     */
    public string $authType;

    /**
     * HTTP Basic Auth Realm name to display (only used with 'HTTP' auth_type)
     *
     * ```php
     * $cfg['Servers'][$i]['auth_http_realm'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_auth_http_realm
     */
    public string $authHttpRealm;

    /**
     * MySQL user
     *
     * ```php
     * $cfg['Servers'][$i]['user'] = 'root';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_user
     */
    public string $user;

    /**
     * MySQL password (only needed with 'config' auth_type)
     *
     * ```php
     * $cfg['Servers'][$i]['password'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_password
     */
    public string $password;

    /**
     * Session to use for 'signon' authentication method
     *
     * ```php
     * $cfg['Servers'][$i]['SignonSession'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_SignonSession
     */
    public string $signonSession;

    /**
     * Cookie params to match session to use for 'signon' authentication method
     * It should be an associative array matching result of session_get_cookie_params() in other system
     *
     * ```php
     * $cfg['Servers'][$i]['SignonCookieParams'] = [];
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_SignonCookieParams
     *
     * @var array<string, int|string|bool>
     * @psalm-var array{
     *   lifetime: 0|positive-int, path: string, domain: string, secure: bool, httponly: bool, samesite?: 'Lax'|'Strict'
     * }
     */
    public array $signonCookieParams;

    /**
     * PHP script to use for 'signon' authentication method
     *
     * ```php
     * $cfg['Servers'][$i]['SignonScript'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_SignonScript
     */
    public string $signonScript;

    /**
     * URL where to redirect user to login for 'signon' authentication method
     *
     * ```php
     * $cfg['Servers'][$i]['SignonURL'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_SignonURL
     */
    public string $signonUrl;

    /**
     * URL where to redirect user after logout
     *
     * ```php
     * $cfg['Servers'][$i]['LogoutURL'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_LogoutURL
     */
    public string $logoutUrl;

    /**
     * If set to a db-name, only this db is displayed in navigation panel
     * It may also be an array of db-names
     *
     * ```php
     * $cfg['Servers'][$i]['only_db'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_only_db
     *
     * @var string|string[]
     */
    public string|array $onlyDb;

    /**
     * Database name to be hidden from listings
     *
     * ```php
     * $cfg['Servers'][$i]['hide_db'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_hide_db
     */
    public string $hideDb;

    /**
     * Verbose name for this host - leave blank to show the hostname
     * (for HTTP authentication, all non-US-ASCII characters will be stripped)
     *
     * ```php
     * $cfg['Servers'][$i]['verbose'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_verbose
     */
    public string $verbose;

    /**
     * Database used for Relation, Bookmark and PDF Features
     * (see sql/create_tables.sql)
     *   - leave blank for no support
     *     SUGGESTED: 'phpmyadmin'
     *
     * ```php
     * $cfg['Servers'][$i]['pmadb'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_pmadb
     */
    public string $pmaDb;

    /**
     * Bookmark table
     *   - leave blank for no bookmark support
     *     SUGGESTED: 'pma__bookmark'
     *
     * ```php
     * $cfg['Servers'][$i]['bookmarktable'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_bookmarktable
     */
    public string|false $bookmarkTable;

    /**
     * table to describe the relation between links (see doc)
     *   - leave blank for no relation-links support
     *     SUGGESTED: 'pma__relation'
     *
     * ```php
     * $cfg['Servers'][$i]['relation'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_relation
     */
    public string|false $relation;

    /**
     * table to describe the display fields
     *   - leave blank for no display fields support
     *     SUGGESTED: 'pma__table_info'
     *
     * ```php
     * $cfg['Servers'][$i]['table_info'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_table_info
     */
    public string|false $tableInfo;

    /**
     * table to describe the tables position for the designer and PDF schema
     *   - leave blank for no PDF schema support
     *     SUGGESTED: 'pma__table_coords'
     *
     * ```php
     * $cfg['Servers'][$i]['table_coords'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_table_coords
     */
    public string|false $tableCoords;

    /**
     * table to describe pages of relationpdf
     *   - leave blank if you don't want to use this
     *     SUGGESTED: 'pma__pdf_pages'
     *
     * ```php
     * $cfg['Servers'][$i]['pdf_pages'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_pdf_pages
     */
    public string|false $pdfPages;

    /**
     * table to store column information
     *   - leave blank for no column comments/mime types
     *     SUGGESTED: 'pma__column_info'
     *
     * ```php
     * $cfg['Servers'][$i]['column_info'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_column_info
     */
    public string|false $columnInfo;

    /**
     * table to store SQL history
     *   - leave blank for no SQL query history
     *     SUGGESTED: 'pma__history'
     *
     * ```php
     * $cfg['Servers'][$i]['history'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_history
     */
    public string|false $history;

    /**
     * table to store recently used tables
     *   - leave blank for no "persistent" recently used tables
     *     SUGGESTED: 'pma__recent'
     *
     * ```php
     * $cfg['Servers'][$i]['recent'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_recent
     */
    public string|false $recent;

    /**
     * table to store favorite tables
     *   - leave blank for no favorite tables
     *     SUGGESTED: 'pma__favorite'
     *
     * ```php
     * $cfg['Servers'][$i]['favorite'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_favorite
     */
    public string|false $favorite;

    /**
     * table to store UI preferences for tables
     *   - leave blank for no "persistent" UI preferences
     *     SUGGESTED: 'pma__table_uiprefs'
     *
     * ```php
     * $cfg['Servers'][$i]['table_uiprefs'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_table_uiprefs
     */
    public string|false $tableUiPrefs;

    /**
     * table to store SQL tracking
     *   - leave blank for no SQL tracking
     *     SUGGESTED: 'pma__tracking'
     *
     * ```php
     * $cfg['Servers'][$i]['tracking'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_tracking
     */
    public string|false $tracking;

    /**
     * table to store user preferences
     *   - leave blank to disable server storage
     *     SUGGESTED: 'pma__userconfig'
     *
     * ```php
     * $cfg['Servers'][$i]['userconfig'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_userconfig
     */
    public string|false $userConfig;

    /**
     * table to store users and their assignment to user groups
     *   - leave blank to disable configurable menus feature
     *     SUGGESTED: 'pma__users'
     *
     * ```php
     * $cfg['Servers'][$i]['users'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_users
     */
    public string|false $users;

    /**
     * table to store allowed menu items for each user group
     *   - leave blank to disable configurable menus feature
     *     SUGGESTED: 'pma__usergroups'
     *
     * ```php
     * $cfg['Servers'][$i]['usergroups'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_usergroups
     */
    public string|false $userGroups;

    /**
     * table to store information about item hidden from navigation tree
     *   - leave blank to disable hide/show navigation items feature
     *     SUGGESTED: 'pma__navigationhiding'
     *
     * ```php
     * $cfg['Servers'][$i]['navigationhiding'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_navigationhiding
     */
    public string|false $navigationHiding;

    /**
     * table to store information about saved searches from query-by-example on a db
     *   - leave blank to disable saved searches feature
     *     SUGGESTED: 'pma__savedsearches'
     *
     * ```php
     * $cfg['Servers'][$i]['savedsearches'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_savedsearches
     */
    public string|false $savedSearches;

    /**
     * table to store central list of columns per database
     *   - leave blank to disable central list of columns feature
     *     SUGGESTED: 'pma__central_columns'
     *
     * ```php
     * $cfg['Servers'][$i]['central_columns'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_central_columns
     */
    public string|false $centralColumns;

    /**
     * table to store designer settings
     *   - leave blank to disable the storage of designer settings
     *     SUGGESTED: 'pma__designer_settings'
     *
     * ```php
     * $cfg['Servers'][$i]['designer_settings'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_designer_settings
     */
    public string|false $designerSettings;

    /**
     * table to store export templates
     *   - leave blank to disable saved searches feature
     *     SUGGESTED: 'pma__export_templates'
     *
     * ```php
     * $cfg['Servers'][$i]['export_templates'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_export_templates
     */
    public string|false $exportTemplates;

    /**
     * Maximum number of records saved in $cfg['Servers'][$i]['table_uiprefs'] table.
     *
     * In case where tables in databases are modified (e.g. dropped or renamed),
     * table_uiprefs may contains invalid data (referring to tables which do not
     * exist anymore).
     * This configuration makes sure that we only keep N (N = MaxTableUiprefs)
     * newest records in table_uiprefs and automatically delete older records.
     *
     * ```php
     * $cfg['Servers'][$i]['MaxTableUiprefs'] = 100;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_MaxTableUiprefs
     *
     * @psalm-var positive-int
     */
    public int $maxTableUiPrefs;

    /**
     * Sets the time zone used by phpMyAdmin. Possible values are explained at
     * https://dev.mysql.com/doc/refman/5.7/en/time-zone-support.html
     *
     * ```php
     * $cfg['Servers'][$i]['SessionTimeZone'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_SessionTimeZone
     */
    public string $sessionTimeZone;

    /**
     * whether to allow root login
     *
     * ```php
     * $cfg['Servers'][$i]['AllowRoot'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_AllowRoot
     */
    public bool $allowRoot;

    /**
     * whether to allow login of any user without a password
     *
     * ```php
     * $cfg['Servers'][$i]['AllowNoPassword'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_AllowNoPassword
     */
    public bool $allowNoPassword;

    /**
     * Host authentication
     *
     * - Host authentication order, leave blank to not use
     * - Host authentication rules, leave blank for defaults
     *
     * ```php
     * $cfg['Servers'][$i]['AllowDeny']['order'] = '';
     * $cfg['Servers'][$i]['AllowDeny']['rules'] = [];
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_AllowDeny_order
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_AllowDeny_rules
     *
     * @var array<string, string|string[]>
     * @psalm-var array{order: ''|'deny,allow'|'allow,deny'|'explicit', rules: string[]}
     */
    public array $allowDeny;

    /**
     * Disable use of INFORMATION_SCHEMA.
     *
     * ```php
     * $cfg['Servers'][$i]['DisableIS'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_DisableIS
     * @see https://github.com/phpmyadmin/phpmyadmin/issues/8970
     * @see https://bugs.mysql.com/19588
     */
    public bool $disableIS;

    /**
     * Whether the tracking mechanism creates
     * versions for tables and views automatically.
     *
     * ```php
     * $cfg['Servers'][$i]['tracking_version_auto_create'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_tracking_version_auto_create
     */
    public bool $trackingVersionAutoCreate;

    /**
     * Defines the list of statements
     * the auto-creation uses for new versions.
     *
     * ```php
     * $cfg['Servers'][$i]['tracking_default_statements'] = 'CREATE TABLE,ALTER TABLE,DROP TABLE,RENAME TABLE,'
     *     . 'CREATE INDEX,DROP INDEX,INSERT,UPDATE,DELETE,TRUNCATE,REPLACE,CREATE VIEW,'
     *     . 'ALTER VIEW,DROP VIEW,CREATE DATABASE,ALTER DATABASE,DROP DATABASE';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_tracking_default_statements
     */
    public string $trackingDefaultStatements;

    /**
     * Whether a DROP VIEW IF EXISTS statement will be added
     * as first line to the log when creating a view.
     *
     * ```php
     * $cfg['Servers'][$i]['tracking_add_drop_view'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_tracking_add_drop_view
     */
    public bool $trackingAddDropView;

    /**
     * Whether a DROP TABLE IF EXISTS statement will be added
     * as first line to the log when creating a table.
     *
     * ```php
     * $cfg['Servers'][$i]['tracking_add_drop_table'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_tracking_add_drop_table
     */
    public bool $trackingAddDropTable;

    /**
     * Whether a DROP DATABASE IF EXISTS statement will be added
     * as first line to the log when creating a database.
     *
     * ```php
     * $cfg['Servers'][$i]['tracking_add_drop_database'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_tracking_add_drop_database
     */
    public bool $trackingAddDropDatabase;

    /**
     * Whether to show or hide detailed MySQL/MariaDB connection errors on the login page.
     *
     * ```php
     * $cfg['Servers'][$i]['hide_connection_errors'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_hide_connection_errors
     */
    public bool $hideConnectionErrors;

    /** @param array<int|string, mixed> $server */
    public function __construct(array $server = [])
    {
        $this->host = $this->setHost($server);
        $this->port = $this->setPort($server);
        $this->socket = $this->setSocket($server);
        $this->ssl = $this->setSsl($server);
        $this->sslKey = $this->setSslKey($server);
        $this->sslCert = $this->setSslCert($server);
        $this->sslCa = $this->setSslCa($server);
        $this->sslCaPath = $this->setSslCaPath($server);
        $this->sslCiphers = $this->setSslCiphers($server);
        $this->sslVerify = $this->setSslVerify($server);
        $this->compress = $this->setCompress($server);
        $this->controlHost = $this->setControlHost($server);
        $this->controlPort = $this->setControlPort($server);
        $this->controlUser = $this->setControlUser($server);
        $this->controlPass = $this->setControlPass($server);
        $this->controlSocket = $this->setControlSocket($server);
        $this->controlSsl = $this->setControlSsl($server);
        $this->controlSslKey = $this->setControlSslKey($server);
        $this->controlSslCert = $this->setControlSslCert($server);
        $this->controlSslCa = $this->setControlSslCa($server);
        $this->controlSslCaPath = $this->setControlSslCaPath($server);
        $this->controlSslCiphers = $this->setControlSslCiphers($server);
        $this->controlSslVerify = $this->setControlSslVerify($server);
        $this->controlCompress = $this->setControlCompress($server);
        $this->controlHideConnectionErrors = $this->setControlHideConnectionErrors($server);
        $this->authType = $this->setAuthType($server);
        $this->authHttpRealm = $this->setAuthHttpRealm($server);
        $this->user = $this->setUser($server);
        $this->password = $this->setPassword($server);
        $this->signonSession = $this->setSignonSession($server);
        $this->signonCookieParams = $this->setSignonCookieParams($server);
        $this->signonScript = $this->setSignonScript($server);
        $this->signonUrl = $this->setSignonUrl($server);
        $this->logoutUrl = $this->setLogoutUrl($server);
        $this->onlyDb = $this->setOnlyDb($server);
        $this->hideDb = $this->setHideDb($server);
        $this->verbose = $this->setVerbose($server);
        $this->pmaDb = $this->setPmaDb($server);
        $this->bookmarkTable = $this->setBookmarkTable($server);
        $this->relation = $this->setRelation($server);
        $this->tableInfo = $this->setTableInfo($server);
        $this->tableCoords = $this->setTableCoords($server);
        $this->pdfPages = $this->setPdfPages($server);
        $this->columnInfo = $this->setColumnInfo($server);
        $this->history = $this->setHistory($server);
        $this->recent = $this->setRecent($server);
        $this->favorite = $this->setFavorite($server);
        $this->tableUiPrefs = $this->setTableUiPrefs($server);
        $this->tracking = $this->setTracking($server);
        $this->userConfig = $this->setUserConfig($server);
        $this->users = $this->setUsers($server);
        $this->userGroups = $this->setUserGroups($server);
        $this->navigationHiding = $this->setNavigationHiding($server);
        $this->savedSearches = $this->setSavedSearches($server);
        $this->centralColumns = $this->setCentralColumns($server);
        $this->designerSettings = $this->setDesignerSettings($server);
        $this->exportTemplates = $this->setExportTemplates($server);
        $this->maxTableUiPrefs = $this->setMaxTableUiPrefs($server);
        $this->sessionTimeZone = $this->setSessionTimeZone($server);
        $this->allowRoot = $this->setAllowRoot($server);
        $this->allowNoPassword = $this->setAllowNoPassword($server);
        $this->allowDeny = $this->setAllowDeny($server);
        $this->disableIS = $this->setDisableIs($server);
        $this->trackingVersionAutoCreate = $this->setTrackingVersionAutoCreate($server);
        $this->trackingDefaultStatements = $this->setTrackingDefaultStatements($server);
        $this->trackingAddDropView = $this->setTrackingAddDropView($server);
        $this->trackingAddDropTable = $this->setTrackingAddDropTable($server);
        $this->trackingAddDropDatabase = $this->setTrackingAddDropDatabase($server);
        $this->hideConnectionErrors = $this->setHideConnectionErrors($server);
    }

    /** @return array<string, string|bool|int|array<mixed>|null> */
    public function asArray(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'socket' => $this->socket,
            'ssl' => $this->ssl,
            'ssl_key' => $this->sslKey,
            'ssl_cert' => $this->sslCert,
            'ssl_ca' => $this->sslCa,
            'ssl_ca_path' => $this->sslCaPath,
            'ssl_ciphers' => $this->sslCiphers,
            'ssl_verify' => $this->sslVerify,
            'compress' => $this->compress,
            'controlhost' => $this->controlHost,
            'controlport' => $this->controlPort,
            'controluser' => $this->controlUser,
            'controlpass' => $this->controlPass,
            'control_socket' => $this->controlSocket,
            'control_ssl' => $this->controlSsl,
            'control_ssl_key' => $this->controlSslKey,
            'control_ssl_cert' => $this->controlSslCert,
            'control_ssl_ca' => $this->controlSslCa,
            'control_ssl_ca_path' => $this->controlSslCaPath,
            'control_ssl_ciphers' => $this->controlSslCiphers,
            'control_ssl_verify' => $this->controlSslVerify,
            'control_compress' => $this->controlCompress,
            'control_hide_connection_errors' => $this->controlHideConnectionErrors,
            'auth_type' => $this->authType,
            'auth_http_realm' => $this->authHttpRealm,
            'user' => $this->user,
            'password' => $this->password,
            'SignonSession' => $this->signonSession,
            'SignonCookieParams' => $this->signonCookieParams,
            'SignonScript' => $this->signonScript,
            'SignonURL' => $this->signonUrl,
            'LogoutURL' => $this->logoutUrl,
            'only_db' => $this->onlyDb,
            'hide_db' => $this->hideDb,
            'verbose' => $this->verbose,
            'pmadb' => $this->pmaDb,
            'bookmarktable' => $this->bookmarkTable,
            'relation' => $this->relation,
            'table_info' => $this->tableInfo,
            'table_coords' => $this->tableCoords,
            'pdf_pages' => $this->pdfPages,
            'column_info' => $this->columnInfo,
            'history' => $this->history,
            'recent' => $this->recent,
            'favorite' => $this->favorite,
            'table_uiprefs' => $this->tableUiPrefs,
            'tracking' => $this->tracking,
            'userconfig' => $this->userConfig,
            'users' => $this->users,
            'usergroups' => $this->userGroups,
            'navigationhiding' => $this->navigationHiding,
            'savedsearches' => $this->savedSearches,
            'central_columns' => $this->centralColumns,
            'designer_settings' => $this->designerSettings,
            'export_templates' => $this->exportTemplates,
            'MaxTableUiprefs' => $this->maxTableUiPrefs,
            'SessionTimeZone' => $this->sessionTimeZone,
            'AllowRoot' => $this->allowRoot,
            'AllowNoPassword' => $this->allowNoPassword,
            'AllowDeny' => $this->allowDeny,
            'DisableIS' => $this->disableIS,
            'tracking_version_auto_create' => $this->trackingVersionAutoCreate,
            'tracking_default_statements' => $this->trackingDefaultStatements,
            'tracking_add_drop_view' => $this->trackingAddDropView,
            'tracking_add_drop_table' => $this->trackingAddDropTable,
            'tracking_add_drop_database' => $this->trackingAddDropDatabase,
            'hide_connection_errors' => $this->hideConnectionErrors,
        ];
    }

    /** @return static */
    public function withSSL(bool $ssl): Server
    {
        $clone = clone $this;
        $clone->ssl = $ssl;

        return $clone;
    }

    /** @param array<int|string, mixed> $server */
    private function setHost(array $server): string
    {
        if (isset($server['host'])) {
            return (string) $server['host'];
        }

        return 'localhost';
    }

    /** @param array<int|string, mixed> $server */
    private function setPort(array $server): string
    {
        if (isset($server['port'])) {
            return (string) $server['port'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setSocket(array $server): string
    {
        if (isset($server['socket'])) {
            return (string) $server['socket'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setSsl(array $server): bool
    {
        if (isset($server['ssl'])) {
            return (bool) $server['ssl'];
        }

        return false;
    }

    /** @param array<int|string, mixed> $server */
    private function setSslKey(array $server): string|null
    {
        if (isset($server['ssl_key'])) {
            return (string) $server['ssl_key'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setSslCert(array $server): string|null
    {
        if (isset($server['ssl_cert'])) {
            return (string) $server['ssl_cert'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setSslCa(array $server): string|null
    {
        if (isset($server['ssl_ca'])) {
            return (string) $server['ssl_ca'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setSslCaPath(array $server): string|null
    {
        if (isset($server['ssl_ca_path'])) {
            return (string) $server['ssl_ca_path'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setSslCiphers(array $server): string|null
    {
        if (isset($server['ssl_ciphers'])) {
            return (string) $server['ssl_ciphers'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setSslVerify(array $server): bool
    {
        if (isset($server['ssl_verify'])) {
            return (bool) $server['ssl_verify'];
        }

        return true;
    }

    /** @param array<int|string, mixed> $server */
    private function setCompress(array $server): bool
    {
        if (isset($server['compress'])) {
            return (bool) $server['compress'];
        }

        return false;
    }

    /** @param array<int|string, mixed> $server */
    private function setControlHost(array $server): string
    {
        if (isset($server['controlhost'])) {
            return (string) $server['controlhost'];
        }

        if (isset($server['control_host'])) {
            return (string) $server['control_host'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setControlPort(array $server): string
    {
        if (isset($server['controlport'])) {
            return (string) $server['controlport'];
        }

        if (isset($server['control_port'])) {
            return (string) $server['control_port'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setControlUser(array $server): string
    {
        if (isset($server['controluser'])) {
            return (string) $server['controluser'];
        }

        if (isset($server['control_user'])) {
            return (string) $server['control_user'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setControlPass(array $server): string
    {
        if (isset($server['controlpass'])) {
            return (string) $server['controlpass'];
        }

        if (isset($server['control_pass'])) {
            return (string) $server['control_pass'];
        }

        if (isset($server['control_password'])) {
            return (string) $server['control_password'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setControlSocket(array $server): string|null
    {
        if (isset($server['control_socket'])) {
            return (string) $server['control_socket'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setControlSsl(array $server): bool|null
    {
        if (isset($server['control_ssl'])) {
            return (bool) $server['control_ssl'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setControlSslKey(array $server): string|null
    {
        if (isset($server['control_ssl_key'])) {
            return (string) $server['control_ssl_key'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setControlSslCert(array $server): string|null
    {
        if (isset($server['control_ssl_cert'])) {
            return (string) $server['control_ssl_cert'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setControlSslCa(array $server): string|null
    {
        if (isset($server['control_ssl_ca'])) {
            return (string) $server['control_ssl_ca'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setControlSslCaPath(array $server): string|null
    {
        if (isset($server['control_ssl_ca_path'])) {
            return (string) $server['control_ssl_ca_path'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setControlSslCiphers(array $server): string|null
    {
        if (isset($server['control_ssl_ciphers'])) {
            return (string) $server['control_ssl_ciphers'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setControlSslVerify(array $server): bool|null
    {
        if (isset($server['control_ssl_verify'])) {
            return (bool) $server['control_ssl_verify'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setControlCompress(array $server): bool|null
    {
        if (isset($server['control_compress'])) {
            return (bool) $server['control_compress'];
        }

        return null;
    }

    /** @param array<int|string, mixed> $server */
    private function setControlHideConnectionErrors(array $server): bool|null
    {
        if (isset($server['control_hide_connection_errors'])) {
            return (bool) $server['control_hide_connection_errors'];
        }

        return null;
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @psalm-return 'config'|'http'|'signon'|'cookie'
     */
    private function setAuthType(array $server): string
    {
        if (isset($server['auth_type']) && in_array($server['auth_type'], ['config', 'http', 'signon'], true)) {
            return $server['auth_type'];
        }

        return 'cookie';
    }

    /** @param array<int|string, mixed> $server */
    private function setAuthHttpRealm(array $server): string
    {
        if (isset($server['auth_http_realm'])) {
            return (string) $server['auth_http_realm'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setUser(array $server): string
    {
        if (isset($server['user'])) {
            return (string) $server['user'];
        }

        return 'root';
    }

    /** @param array<int|string, mixed> $server */
    private function setPassword(array $server): string
    {
        if (isset($server['password'])) {
            return (string) $server['password'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setSignonSession(array $server): string
    {
        if (isset($server['SignonSession'])) {
            return (string) $server['SignonSession'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return array<string, int|string|bool>
     * @psalm-return array{
     *   lifetime: 0|positive-int, path: string, domain: string, secure: bool, httponly: bool, samesite?: 'Lax'|'Strict'
     * }
     */
    private function setSignonCookieParams(array $server): array
    {
        $params = ['lifetime' => 0, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => false];
        if (isset($server['SignonCookieParams']) && is_array($server['SignonCookieParams'])) {
            if (isset($server['SignonCookieParams']['lifetime'])) {
                $lifetime = (int) $server['SignonCookieParams']['lifetime'];
                if ($lifetime >= 1) {
                    $params['lifetime'] = $lifetime;
                }
            }

            if (isset($server['SignonCookieParams']['path'])) {
                $params['path'] = (string) $server['SignonCookieParams']['path'];
            }

            if (isset($server['SignonCookieParams']['domain'])) {
                $params['domain'] = (string) $server['SignonCookieParams']['domain'];
            }

            if (isset($server['SignonCookieParams']['secure'])) {
                $params['secure'] = (bool) $server['SignonCookieParams']['secure'];
            }

            if (isset($server['SignonCookieParams']['httponly'])) {
                $params['httponly'] = (bool) $server['SignonCookieParams']['httponly'];
            }

            if (
                isset($server['SignonCookieParams']['samesite'])
                && in_array($server['SignonCookieParams']['samesite'], ['Lax', 'Strict'], true)
            ) {
                $params['samesite'] = $server['SignonCookieParams']['samesite'];
            }
        }

        return $params;
    }

    /** @param array<int|string, mixed> $server */
    private function setSignonScript(array $server): string
    {
        if (isset($server['SignonScript'])) {
            return (string) $server['SignonScript'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setSignonUrl(array $server): string
    {
        if (isset($server['SignonURL'])) {
            return (string) $server['SignonURL'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setLogoutUrl(array $server): string
    {
        if (isset($server['LogoutURL'])) {
            return (string) $server['LogoutURL'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return string|string[]
     */
    private function setOnlyDb(array $server): string|array
    {
        $onlyDb = '';
        if (isset($server['only_db'])) {
            if (! is_array($server['only_db'])) {
                $onlyDb = (string) $server['only_db'];
            } elseif ($server['only_db'] !== []) {
                $onlyDb = [];
                /** @var mixed $database */
                foreach ($server['only_db'] as $database) {
                    $onlyDb[] = (string) $database;
                }
            }
        }

        return $onlyDb;
    }

    /** @param array<int|string, mixed> $server */
    private function setHideDb(array $server): string
    {
        if (isset($server['hide_db'])) {
            return (string) $server['hide_db'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setVerbose(array $server): string
    {
        if (isset($server['verbose'])) {
            return (string) $server['verbose'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setPmaDb(array $server): string
    {
        if (isset($server['pmadb'])) {
            return (string) $server['pmadb'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setBookmarkTable(array $server): false|string
    {
        if (isset($server['bookmarktable'])) {
            return $server['bookmarktable'] === false ? false : (string) $server['bookmarktable'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setRelation(array $server): false|string
    {
        if (isset($server['relation'])) {
            return $server['relation'] === false ? false : (string) $server['relation'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setTableInfo(array $server): false|string
    {
        if (isset($server['table_info'])) {
            return $server['table_info'] === false ? false : (string) $server['table_info'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setTableCoords(array $server): false|string
    {
        if (isset($server['table_coords'])) {
            return $server['table_coords'] === false ? false : (string) $server['table_coords'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setPdfPages(array $server): false|string
    {
        if (isset($server['pdf_pages'])) {
            return $server['pdf_pages'] === false ? false : (string) $server['pdf_pages'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setColumnInfo(array $server): false|string
    {
        if (isset($server['column_info'])) {
            return $server['column_info'] === false ? false : (string) $server['column_info'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setHistory(array $server): false|string
    {
        if (isset($server['history'])) {
            return $server['history'] === false ? false : (string) $server['history'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setRecent(array $server): false|string
    {
        if (isset($server['recent'])) {
            return $server['recent'] === false ? false : (string) $server['recent'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setFavorite(array $server): false|string
    {
        if (isset($server['favorite'])) {
            return $server['favorite'] === false ? false : (string) $server['favorite'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setTableUiPrefs(array $server): false|string
    {
        if (isset($server['table_uiprefs'])) {
            return $server['table_uiprefs'] === false ? false : (string) $server['table_uiprefs'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setTracking(array $server): false|string
    {
        if (isset($server['tracking'])) {
            return $server['tracking'] === false ? false : (string) $server['tracking'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setUserConfig(array $server): false|string
    {
        if (isset($server['userconfig'])) {
            return $server['userconfig'] === false ? false : (string) $server['userconfig'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setUsers(array $server): false|string
    {
        if (isset($server['users'])) {
            return $server['users'] === false ? false : (string) $server['users'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setUserGroups(array $server): false|string
    {
        if (isset($server['usergroups'])) {
            return $server['usergroups'] === false ? false : (string) $server['usergroups'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setNavigationHiding(array $server): false|string
    {
        if (isset($server['navigationhiding'])) {
            return $server['navigationhiding'] === false
                ? false
                : (string) $server['navigationhiding'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setSavedSearches(array $server): false|string
    {
        if (isset($server['savedsearches'])) {
            return $server['savedsearches'] === false ? false : (string) $server['savedsearches'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setCentralColumns(array $server): false|string
    {
        if (isset($server['central_columns'])) {
            return $server['central_columns'] === false ? false : (string) $server['central_columns'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setDesignerSettings(array $server): false|string
    {
        if (isset($server['designer_settings'])) {
            return $server['designer_settings'] === false
                ? false
                : (string) $server['designer_settings'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setExportTemplates(array $server): false|string
    {
        if (isset($server['export_templates'])) {
            return $server['export_templates'] === false
                ? false
                : (string) $server['export_templates'];
        }

        return '';
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @psalm-return positive-int
     */
    private function setMaxTableUiPrefs(array $server): int
    {
        if (isset($server['MaxTableUiprefs'])) {
            $maxTableUiprefs = (int) $server['MaxTableUiprefs'];
            if ($maxTableUiprefs >= 1) {
                return $maxTableUiprefs;
            }
        }

        return 100;
    }

    /** @param array<int|string, mixed> $server */
    private function setSessionTimeZone(array $server): string
    {
        if (isset($server['SessionTimeZone'])) {
            return (string) $server['SessionTimeZone'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setAllowRoot(array $server): bool
    {
        if (isset($server['AllowRoot'])) {
            return (bool) $server['AllowRoot'];
        }

        return true;
    }

    /** @param array<int|string, mixed> $server */
    private function setAllowNoPassword(array $server): bool
    {
        if (isset($server['AllowNoPassword'])) {
            return (bool) $server['AllowNoPassword'];
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $server
     *
     * @return array<string, string|string[]>
     * @psalm-return array{order: ''|'deny,allow'|'allow,deny'|'explicit', rules: string[]}
     */
    private function setAllowDeny(array $server): array
    {
        $allowDeny = ['order' => '', 'rules' => []];
        if (isset($server['AllowDeny']) && is_array($server['AllowDeny'])) {
            if (
                isset($server['AllowDeny']['order'])
                && in_array($server['AllowDeny']['order'], ['deny,allow', 'allow,deny', 'explicit'], true)
            ) {
                $allowDeny['order'] = $server['AllowDeny']['order'];
            }

            if (isset($server['AllowDeny']['rules']) && is_array($server['AllowDeny']['rules'])) {
                /** @var mixed $rule */
                foreach ($server['AllowDeny']['rules'] as $rule) {
                    $allowDeny['rules'][] = (string) $rule;
                }
            }
        }

        return $allowDeny;
    }

    /** @param array<int|string, mixed> $server */
    private function setDisableIs(array $server): bool
    {
        if (isset($server['DisableIS'])) {
            return (bool) $server['DisableIS'];
        }

        return false;
    }

    /** @param array<int|string, mixed> $server */
    private function setTrackingVersionAutoCreate(array $server): bool
    {
        if (isset($server['tracking_version_auto_create'])) {
            return (bool) $server['tracking_version_auto_create'];
        }

        return false;
    }

    /** @param array<int|string, mixed> $server */
    private function setTrackingDefaultStatements(array $server): string
    {
        if (isset($server['tracking_default_statements'])) {
            return (string) $server['tracking_default_statements'];
        }

        return 'CREATE TABLE,ALTER TABLE,DROP TABLE,RENAME TABLE,CREATE INDEX,DROP INDEX,INSERT,UPDATE,DELETE,'
            . 'TRUNCATE,REPLACE,CREATE VIEW,ALTER VIEW,DROP VIEW,CREATE DATABASE,ALTER DATABASE,DROP DATABASE';
    }

    /** @param array<int|string, mixed> $server */
    private function setTrackingAddDropView(array $server): bool
    {
        if (isset($server['tracking_add_drop_view'])) {
            return (bool) $server['tracking_add_drop_view'];
        }

        return true;
    }

    /** @param array<int|string, mixed> $server */
    private function setTrackingAddDropTable(array $server): bool
    {
        if (isset($server['tracking_add_drop_table'])) {
            return (bool) $server['tracking_add_drop_table'];
        }

        return true;
    }

    /** @param array<int|string, mixed> $server */
    private function setTrackingAddDropDatabase(array $server): bool
    {
        if (isset($server['tracking_add_drop_database'])) {
            return (bool) $server['tracking_add_drop_database'];
        }

        return true;
    }

    /** @param array<int|string, mixed> $server */
    private function setHideConnectionErrors(array $server): bool
    {
        if (isset($server['hide_connection_errors'])) {
            return (bool) $server['hide_connection_errors'];
        }

        return false;
    }
}
