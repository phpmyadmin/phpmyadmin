<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function count;
use function in_array;
use function is_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

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
    public string|null $ssl_key = null;

    /**
     * Path to the cert file when using SSL for connecting to the MySQL server
     *
     * ```php
     * $cfg['Servers'][$i]['ssl_cert'] = null;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl_cert
     */
    public string|null $ssl_cert = null;

    /**
     * Path to the CA file when using SSL for connecting to the MySQL server
     *
     * ```php
     * $cfg['Servers'][$i]['ssl_ca'] = null;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl_ca
     */
    public string|null $ssl_ca = null;

    /**
     * Directory containing trusted SSL CA certificates in PEM format
     *
     * ```php
     * $cfg['Servers'][$i]['ssl_ca_path'] = null;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl_ca_path
     */
    public string|null $ssl_ca_path = null;

    /**
     * List of allowable ciphers for SSL connections to the MySQL server
     *
     * ```php
     * $cfg['Servers'][$i]['ssl_ciphers'] = null;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_ssl_ciphers
     */
    public string|null $ssl_ciphers = null;

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
    public bool $ssl_verify;

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
    public string $controlhost;

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
    public string $controlport;

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
    public string $controluser;

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
    public string $controlpass;

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
    public string $auth_type;

    /**
     * HTTP Basic Auth Realm name to display (only used with 'HTTP' auth_type)
     *
     * ```php
     * $cfg['Servers'][$i]['auth_http_realm'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_auth_http_realm
     */
    public string $auth_http_realm;

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
    public string $SignonSession;

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
    public array $SignonCookieParams;

    /**
     * PHP script to use for 'signon' authentication method
     *
     * ```php
     * $cfg['Servers'][$i]['SignonScript'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_SignonScript
     */
    public string $SignonScript;

    /**
     * URL where to redirect user to login for 'signon' authentication method
     *
     * ```php
     * $cfg['Servers'][$i]['SignonURL'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_SignonURL
     */
    public string $SignonURL;

    /**
     * URL where to redirect user after logout
     *
     * ```php
     * $cfg['Servers'][$i]['LogoutURL'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_LogoutURL
     */
    public string $LogoutURL;

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
    public string|array $only_db;

    /**
     * Database name to be hidden from listings
     *
     * ```php
     * $cfg['Servers'][$i]['hide_db'] = '';
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_hide_db
     */
    public string $hide_db;

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
    public string $pmadb;

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
    public string|false $bookmarktable;

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
    public string|false $table_info;

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
    public string|false $table_coords;

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
    public string|false $pdf_pages;

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
    public string|false $column_info;

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
    public string|false $table_uiprefs;

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
    public string|false $userconfig;

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
    public string|false $usergroups;

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
    public string|false $navigationhiding;

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
    public string|false $savedsearches;

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
    public string|false $central_columns;

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
    public string|false $designer_settings;

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
    public string|false $export_templates;

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
    public int $MaxTableUiprefs;

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
    public string $SessionTimeZone;

    /**
     * whether to allow root login
     *
     * ```php
     * $cfg['Servers'][$i]['AllowRoot'] = true;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_AllowRoot
     */
    public bool $AllowRoot;

    /**
     * whether to allow login of any user without a password
     *
     * ```php
     * $cfg['Servers'][$i]['AllowNoPassword'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_AllowNoPassword
     */
    public bool $AllowNoPassword;

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
    public array $AllowDeny;

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
    public bool $DisableIS;

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
    public bool $tracking_version_auto_create;

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
    public string $tracking_default_statements;

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
    public bool $tracking_add_drop_view;

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
    public bool $tracking_add_drop_table;

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
    public bool $tracking_add_drop_database;

    /**
     * Whether to show or hide detailed MySQL/MariaDB connection errors on the login page.
     *
     * ```php
     * $cfg['Servers'][$i]['hide_connection_errors'] = false;
     * ```
     *
     * @link https://docs.phpmyadmin.net/en/latest/config.html#cfg_Servers_hide_connection_errors
     */
    public bool $hide_connection_errors;

    /** @param array<int|string, mixed> $server */
    public function __construct(array $server = [])
    {
        $this->host = $this->setHost($server);
        $this->port = $this->setPort($server);
        $this->socket = $this->setSocket($server);
        $this->ssl = $this->setSsl($server);
        $this->ssl_key = $this->setSslKey($server);
        $this->ssl_cert = $this->setSslCert($server);
        $this->ssl_ca = $this->setSslCa($server);
        $this->ssl_ca_path = $this->setSslCaPath($server);
        $this->ssl_ciphers = $this->setSslCiphers($server);
        $this->ssl_verify = $this->setSslVerify($server);
        $this->compress = $this->setCompress($server);
        $this->controlhost = $this->setControlhost($server);
        $this->controlport = $this->setControlport($server);
        $this->controluser = $this->setControluser($server);
        $this->controlpass = $this->setControlpass($server);
        $this->auth_type = $this->setAuthType($server);
        $this->auth_http_realm = $this->setAuthHttpRealm($server);
        $this->user = $this->setUser($server);
        $this->password = $this->setPassword($server);
        $this->SignonSession = $this->setSignonSession($server);
        $this->SignonCookieParams = $this->setSignonCookieParams($server);
        $this->SignonScript = $this->setSignonScript($server);
        $this->SignonURL = $this->setSignonUrl($server);
        $this->LogoutURL = $this->setLogoutUrl($server);
        $this->only_db = $this->setOnlyDb($server);
        $this->hide_db = $this->setHideDb($server);
        $this->verbose = $this->setVerbose($server);
        $this->pmadb = $this->setPmadb($server);
        $this->bookmarktable = $this->setBookmarktable($server);
        $this->relation = $this->setRelation($server);
        $this->table_info = $this->setTableInfo($server);
        $this->table_coords = $this->setTableCoords($server);
        $this->pdf_pages = $this->setPdfPages($server);
        $this->column_info = $this->setColumnInfo($server);
        $this->history = $this->setHistory($server);
        $this->recent = $this->setRecent($server);
        $this->favorite = $this->setFavorite($server);
        $this->table_uiprefs = $this->setTableUiprefs($server);
        $this->tracking = $this->setTracking($server);
        $this->userconfig = $this->setUserconfig($server);
        $this->users = $this->setUsers($server);
        $this->usergroups = $this->setUsergroups($server);
        $this->navigationhiding = $this->setNavigationhiding($server);
        $this->savedsearches = $this->setSavedsearches($server);
        $this->central_columns = $this->setCentralColumns($server);
        $this->designer_settings = $this->setDesignerSettings($server);
        $this->export_templates = $this->setExportTemplates($server);
        $this->MaxTableUiprefs = $this->setMaxTableUiprefs($server);
        $this->SessionTimeZone = $this->setSessionTimeZone($server);
        $this->AllowRoot = $this->setAllowRoot($server);
        $this->AllowNoPassword = $this->setAllowNoPassword($server);
        $this->AllowDeny = $this->setAllowDeny($server);
        $this->DisableIS = $this->setDisableIs($server);
        $this->tracking_version_auto_create = $this->setTrackingVersionAutoCreate($server);
        $this->tracking_default_statements = $this->setTrackingDefaultStatements($server);
        $this->tracking_add_drop_view = $this->setTrackingAddDropView($server);
        $this->tracking_add_drop_table = $this->setTrackingAddDropTable($server);
        $this->tracking_add_drop_database = $this->setTrackingAddDropDatabase($server);
        $this->hide_connection_errors = $this->setHideConnectionErrors($server);
    }

    /** @return array<string, string|bool|int|array<mixed>|null> */
    public function asArray(): array
    {
        return [
            'host' => $this->host,
            'port' => $this->port,
            'socket' => $this->socket,
            'ssl' => $this->ssl,
            'ssl_key' => $this->ssl_key,
            'ssl_cert' => $this->ssl_cert,
            'ssl_ca' => $this->ssl_ca,
            'ssl_ca_path' => $this->ssl_ca_path,
            'ssl_ciphers' => $this->ssl_ciphers,
            'ssl_verify' => $this->ssl_verify,
            'compress' => $this->compress,
            'controlhost' => $this->controlhost,
            'controlport' => $this->controlport,
            'controluser' => $this->controluser,
            'controlpass' => $this->controlpass,
            'auth_type' => $this->auth_type,
            'auth_http_realm' => $this->auth_http_realm,
            'user' => $this->user,
            'password' => $this->password,
            'SignonSession' => $this->SignonSession,
            'SignonCookieParams' => $this->SignonCookieParams,
            'SignonScript' => $this->SignonScript,
            'SignonURL' => $this->SignonURL,
            'LogoutURL' => $this->LogoutURL,
            'only_db' => $this->only_db,
            'hide_db' => $this->hide_db,
            'verbose' => $this->verbose,
            'pmadb' => $this->pmadb,
            'bookmarktable' => $this->bookmarktable,
            'relation' => $this->relation,
            'table_info' => $this->table_info,
            'table_coords' => $this->table_coords,
            'pdf_pages' => $this->pdf_pages,
            'column_info' => $this->column_info,
            'history' => $this->history,
            'recent' => $this->recent,
            'favorite' => $this->favorite,
            'table_uiprefs' => $this->table_uiprefs,
            'tracking' => $this->tracking,
            'userconfig' => $this->userconfig,
            'users' => $this->users,
            'usergroups' => $this->usergroups,
            'navigationhiding' => $this->navigationhiding,
            'savedsearches' => $this->savedsearches,
            'central_columns' => $this->central_columns,
            'designer_settings' => $this->designer_settings,
            'export_templates' => $this->export_templates,
            'MaxTableUiprefs' => $this->MaxTableUiprefs,
            'SessionTimeZone' => $this->SessionTimeZone,
            'AllowRoot' => $this->AllowRoot,
            'AllowNoPassword' => $this->AllowNoPassword,
            'AllowDeny' => $this->AllowDeny,
            'DisableIS' => $this->DisableIS,
            'tracking_version_auto_create' => $this->tracking_version_auto_create,
            'tracking_default_statements' => $this->tracking_default_statements,
            'tracking_add_drop_view' => $this->tracking_add_drop_view,
            'tracking_add_drop_table' => $this->tracking_add_drop_table,
            'tracking_add_drop_database' => $this->tracking_add_drop_database,
            'hide_connection_errors' => $this->hide_connection_errors,
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
    private function setControlhost(array $server): string
    {
        if (isset($server['controlhost'])) {
            return (string) $server['controlhost'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setControlport(array $server): string
    {
        if (isset($server['controlport'])) {
            return (string) $server['controlport'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setControluser(array $server): string
    {
        if (isset($server['controluser'])) {
            return (string) $server['controluser'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setControlpass(array $server): string
    {
        if (isset($server['controlpass'])) {
            return (string) $server['controlpass'];
        }

        return '';
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
            } elseif (count($server['only_db']) > 0) {
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
    private function setPmadb(array $server): string
    {
        if (isset($server['pmadb'])) {
            return (string) $server['pmadb'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setBookmarktable(array $server): false|string
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
    private function setTableUiprefs(array $server): false|string
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
    private function setUserconfig(array $server): false|string
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
    private function setUsergroups(array $server): false|string
    {
        if (isset($server['usergroups'])) {
            return $server['usergroups'] === false ? false : (string) $server['usergroups'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setNavigationhiding(array $server): false|string
    {
        if (isset($server['navigationhiding'])) {
            return $server['navigationhiding'] === false
                ? false
                : (string) $server['navigationhiding'];
        }

        return '';
    }

    /** @param array<int|string, mixed> $server */
    private function setSavedsearches(array $server): false|string
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
    private function setMaxTableUiprefs(array $server): int
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
