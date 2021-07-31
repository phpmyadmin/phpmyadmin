<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function count;
use function in_array;
use function is_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * @psalm-immutable
 */
final class Server
{
    /**
     * MySQL hostname or IP address
     *
     * @var string
     */
    public $host = 'localhost';

    /**
     * MySQL port - leave blank for default port
     *
     * @var string
     */
    public $port = '';

    /**
     * Path to the socket - leave blank for default socket
     *
     * @var string
     */
    public $socket = '';

    /**
     * Use SSL for connecting to MySQL server?
     *
     * @var bool
     */
    public $ssl = false;

    /**
     * Path to the key file when using SSL for connecting to the MySQL server
     *
     * @var string|null
     */
    public $ssl_key = null;

    /**
     * Path to the cert file when using SSL for connecting to the MySQL server
     *
     * @var string|null
     */
    public $ssl_cert = null;

    /**
     * Path to the CA file when using SSL for connecting to the MySQL server
     *
     * @var string|null
     */
    public $ssl_ca = null;

    /**
     * Directory containing trusted SSL CA certificates in PEM format
     *
     * @var string|null
     */
    public $ssl_ca_path = null;

    /**
     * List of allowable ciphers for SSL connections to the MySQL server
     *
     * @var string|null
     */
    public $ssl_ciphers = null;

    /**
     * MySQL 5.6 or later triggers the mysqlnd driver in PHP to validate the
     * peer_name of the SSL certifcate
     * For most self-signed certificates this is a problem. Setting this to false
     * will disable the check and allow the connection (PHP 5.6.16 or later)
     *
     * @link https://bugs.php.net/68344
     *
     * @var bool
     */
    public $ssl_verify = true;

    /**
     * Use compressed protocol for the MySQL connection
     *
     * @var bool
     */
    public $compress = false;

    /**
     * MySQL control host. This permits to use a host different than the
     * main host, for the phpMyAdmin configuration storage. If left empty,
     * $cfg['Servers'][$i]['host'] is used instead.
     *
     * @var string
     */
    public $controlhost = '';

    /**
     * MySQL control port. This permits to use a port different than the
     * main port, for the phpMyAdmin configuration storage. If left empty,
     * $cfg['Servers'][$i]['port'] is used instead.
     *
     * @var string
     */
    public $controlport = '';

    /**
     * MySQL control user settings (this user must have read-only
     * access to the "mysql/user" and "mysql/db" tables). The controluser is also
     * used for all relational features (pmadb)
     *
     * @var string
     */
    public $controluser = '';

    /**
     * MySQL control user settings (this user must have read-only
     * access to the "mysql/user" and "mysql/db" tables). The controluser is also
     * used for all relational features (pmadb)
     *
     * @var string
     */
    public $controlpass = '';

    /**
     * Authentication method (valid choices: config, http, signon or cookie)
     *
     * @var string
     * @psalm-var 'config'|'http'|'signon'|'cookie'
     */
    public $auth_type = 'cookie';

    /**
     * HTTP Basic Auth Realm name to display (only used with 'HTTP' auth_type)
     *
     * @var string
     */
    public $auth_http_realm = '';

    /**
     * MySQL user
     *
     * @var string
     */
    public $user = 'root';

    /**
     * MySQL password (only needed with 'config' auth_type)
     *
     * @var string
     */
    public $password = '';

    /**
     * Session to use for 'signon' authentication method
     *
     * @var string
     */
    public $SignonSession = '';

    /**
     * Cookie params to match session to use for 'signon' authentication method
     * It should be an associative array matching result of session_get_cookie_params() in other system
     *
     * @var array<string, int|string|bool>
     * @psalm-var array{
     *   lifetime: int, path: string, domain: string, secure: bool, httponly: bool, samesite?: 'Lax'|'Strict'
     * }
     */
    public $SignonCookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => false,
    ];

    /**
     * PHP script to use for 'signon' authentication method
     *
     * @var string
     */
    public $SignonScript = '';

    /**
     * URL where to redirect user to login for 'signon' authentication method
     *
     * @var string
     */
    public $SignonURL = '';

    /**
     * URL where to redirect user after logout
     *
     * @var string
     */
    public $LogoutURL = '';

    /**
     * If set to a db-name, only this db is displayed in navigation panel
     * It may also be an array of db-names
     *
     * @var string|string[]
     */
    public $only_db = '';

    /**
     * Database name to be hidden from listings
     *
     * @var string
     */
    public $hide_db = '';

    /**
     * Verbose name for this host - leave blank to show the hostname
     * (for HTTP authentication, all non-US-ASCII characters will be stripped)
     *
     * @var string
     */
    public $verbose = '';

    /**
     * Database used for Relation, Bookmark and PDF Features
     * (see sql/create_tables.sql)
     *   - leave blank for no support
     *     SUGGESTED: 'phpmyadmin'
     *
     * @var string
     */
    public $pmadb = '';

    /**
     * Bookmark table
     *   - leave blank for no bookmark support
     *     SUGGESTED: 'pma__bookmark'
     *
     * @var string|false
     */
    public $bookmarktable = '';

    /**
     * table to describe the relation between links (see doc)
     *   - leave blank for no relation-links support
     *     SUGGESTED: 'pma__relation'
     *
     * @var string|false
     */
    public $relation = '';

    /**
     * table to describe the display fields
     *   - leave blank for no display fields support
     *     SUGGESTED: 'pma__table_info'
     *
     * @var string|false
     */
    public $table_info = '';

    /**
     * table to describe the tables position for the designer and PDF schema
     *   - leave blank for no PDF schema support
     *     SUGGESTED: 'pma__table_coords'
     *
     * @var string|false
     */
    public $table_coords = '';

    /**
     * table to describe pages of relationpdf
     *   - leave blank if you don't want to use this
     *     SUGGESTED: 'pma__pdf_pages'
     *
     * @var string|false
     */
    public $pdf_pages = '';

    /**
     * table to store column information
     *   - leave blank for no column comments/mime types
     *     SUGGESTED: 'pma__column_info'
     *
     * @var string|false
     */
    public $column_info = '';

    /**
     * table to store SQL history
     *   - leave blank for no SQL query history
     *     SUGGESTED: 'pma__history'
     *
     * @var string|false
     */
    public $history = '';

    /**
     * table to store recently used tables
     *   - leave blank for no "persistent" recently used tables
     *     SUGGESTED: 'pma__recent'
     *
     * @var string|false
     */
    public $recent = '';

    /**
     * table to store favorite tables
     *   - leave blank for no favorite tables
     *     SUGGESTED: 'pma__favorite'
     *
     * @var string|false
     */
    public $favorite = '';

    /**
     * table to store UI preferences for tables
     *   - leave blank for no "persistent" UI preferences
     *     SUGGESTED: 'pma__table_uiprefs'
     *
     * @var string|false
     */
    public $table_uiprefs = '';

    /**
     * table to store SQL tracking
     *   - leave blank for no SQL tracking
     *     SUGGESTED: 'pma__tracking'
     *
     * @var string|false
     */
    public $tracking = '';

    /**
     * table to store user preferences
     *   - leave blank to disable server storage
     *     SUGGESTED: 'pma__userconfig'
     *
     * @var string|false
     */
    public $userconfig = '';

    /**
     * table to store users and their assignment to user groups
     *   - leave blank to disable configurable menus feature
     *     SUGGESTED: 'pma__users'
     *
     * @var string|false
     */
    public $users = '';

    /**
     * table to store allowed menu items for each user group
     *   - leave blank to disable configurable menus feature
     *     SUGGESTED: 'pma__usergroups'
     *
     * @var string|false
     */
    public $usergroups = '';

    /**
     * table to store information about item hidden from navigation tree
     *   - leave blank to disable hide/show navigation items feature
     *     SUGGESTED: 'pma__navigationhiding'
     *
     * @var string|false
     */
    public $navigationhiding = '';

    /**
     * table to store information about saved searches from query-by-example on a db
     *   - leave blank to disable saved searches feature
     *     SUGGESTED: 'pma__savedsearches'
     *
     * @var string|false
     */
    public $savedsearches = '';

    /**
     * table to store central list of columns per database
     *   - leave blank to disable central list of columns feature
     *     SUGGESTED: 'pma__central_columns'
     *
     * @var string|false
     */
    public $central_columns = '';

    /**
     * table to store designer settings
     *   - leave blank to disable the storage of designer settings
     *     SUGGESTED: 'pma__designer_settings'
     *
     * @var string|false
     */
    public $designer_settings = '';

    /**
     * table to store export templates
     *   - leave blank to disable saved searches feature
     *     SUGGESTED: 'pma__export_templates'
     *
     * @var string|false
     */
    public $export_templates = '';

    /**
     * Maximum number of records saved in $cfg['Servers'][$i]['table_uiprefs'] table.
     *
     * In case where tables in databases is modified (e.g. dropped or renamed),
     * table_uiprefs may contains invalid data (referring to tables which are not
     * exist anymore).
     * This configuration make sure that we only keep N (N = MaxTableUiprefs)
     * newest record in table_uiprefs and automatically delete older records.
     *
     * @var int
     */
    public $MaxTableUiprefs = 100;

    /**
     * Sets the time zone used by phpMyAdmin. Possible values are explained at
     * https://dev.mysql.com/doc/refman/5.7/en/time-zone-support.html
     *
     * @var string
     */
    public $SessionTimeZone = '';

    /**
     * whether to allow root login
     *
     * @var bool
     */
    public $AllowRoot = true;

    /**
     * whether to allow login of any user without a password
     *
     * @var bool
     */
    public $AllowNoPassword = false;

    /**
     * Host authentication
     *
     * Host authentication order, leave blank to not use
     * Host authentication rules, leave blank for defaults
     *
     * @var array<string, string|string[]>
     * @psalm-var array{order: string, rules: string[]}
     */
    public $AllowDeny = ['order' => '', 'rules' => []];

    /**
     * Disable use of INFORMATION_SCHEMA.
     *
     * @see https://github.com/phpmyadmin/phpmyadmin/issues/8970
     * @see https://bugs.mysql.com/19588
     *
     * @var bool
     */
    public $DisableIS = false;

    /**
     * Whether the tracking mechanism creates
     * versions for tables and views automatically.
     *
     * @var bool
     */
    public $tracking_version_auto_create = false;

    /**
     * Defines the list of statements
     * the auto-creation uses for new versions.
     *
     * @var string
     */
    public $tracking_default_statements = 'CREATE TABLE,ALTER TABLE,DROP TABLE,RENAME TABLE,CREATE INDEX,'
        . 'DROP INDEX,INSERT,UPDATE,DELETE,TRUNCATE,REPLACE,CREATE VIEW,'
        . 'ALTER VIEW,DROP VIEW,CREATE DATABASE,ALTER DATABASE,DROP DATABASE';

    /**
     * Whether a DROP VIEW IF EXISTS statement will be added
     * as first line to the log when creating a view.
     *
     * @var bool
     */
    public $tracking_add_drop_view = true;

    /**
     * Whether a DROP TABLE IF EXISTS statement will be added
     * as first line to the log when creating a table.
     *
     * @var bool
     */
    public $tracking_add_drop_table = true;

    /**
     * Whether a DROP DATABASE IF EXISTS statement will be added
     * as first line to the log when creating a database.
     *
     * @var bool
     */
    public $tracking_add_drop_database = true;

    /**
     * @param array<int|string, mixed> $server
     */
    public function __construct(array $server = [])
    {
        if (isset($server['host'])) {
            $this->host = (string) $server['host'];
        }

        if (isset($server['port'])) {
            $this->port = (string) $server['port'];
        }

        if (isset($server['socket'])) {
            $this->socket = (string) $server['socket'];
        }

        if (isset($server['ssl'])) {
            $this->ssl = (bool) $server['ssl'];
        }

        if (isset($server['ssl_key'])) {
            $this->ssl_key = (string) $server['ssl_key'];
        }

        if (isset($server['ssl_cert'])) {
            $this->ssl_cert = (string) $server['ssl_cert'];
        }

        if (isset($server['ssl_ca'])) {
            $this->ssl_ca = (string) $server['ssl_ca'];
        }

        if (isset($server['ssl_ca_path'])) {
            $this->ssl_ca_path = (string) $server['ssl_ca_path'];
        }

        if (isset($server['ssl_ciphers'])) {
            $this->ssl_ciphers = (string) $server['ssl_ciphers'];
        }

        if (isset($server['ssl_verify'])) {
            $this->ssl_verify = (bool) $server['ssl_verify'];
        }

        if (isset($server['compress'])) {
            $this->compress = (bool) $server['compress'];
        }

        if (isset($server['controlhost'])) {
            $this->controlhost = (string) $server['controlhost'];
        }

        if (isset($server['controlport'])) {
            $this->controlport = (string) $server['controlport'];
        }

        if (isset($server['controluser'])) {
            $this->controluser = (string) $server['controluser'];
        }

        if (isset($server['controlpass'])) {
            $this->controlpass = (string) $server['controlpass'];
        }

        if (
            isset($server['auth_type']) && in_array($server['auth_type'], ['config', 'http', 'signon', 'cookie'], true)
        ) {
            $this->auth_type = $server['auth_type'];
        }

        if (isset($server['auth_http_realm'])) {
            $this->auth_http_realm = (string) $server['auth_http_realm'];
        }

        if (isset($server['user'])) {
            $this->user = (string) $server['user'];
        }

        if (isset($server['password'])) {
            $this->password = (string) $server['password'];
        }

        if (isset($server['SignonSession'])) {
            $this->SignonSession = (string) $server['SignonSession'];
        }

        if (isset($server['SignonCookieParams']) && is_array($server['SignonCookieParams'])) {
            if (isset($server['SignonCookieParams']['lifetime'])) {
                $this->SignonCookieParams['lifetime'] = (int) $server['SignonCookieParams']['lifetime'];
            }

            if (isset($server['SignonCookieParams']['path'])) {
                $this->SignonCookieParams['path'] = (string) $server['SignonCookieParams']['path'];
            }

            if (isset($server['SignonCookieParams']['domain'])) {
                $this->SignonCookieParams['domain'] = (string) $server['SignonCookieParams']['domain'];
            }

            if (isset($server['SignonCookieParams']['secure'])) {
                $this->SignonCookieParams['secure'] = (bool) $server['SignonCookieParams']['secure'];
            }

            if (isset($server['SignonCookieParams']['httponly'])) {
                $this->SignonCookieParams['httponly'] = (bool) $server['SignonCookieParams']['httponly'];
            }

            if (
                isset($server['SignonCookieParams']['samesite'])
                && in_array($server['SignonCookieParams']['samesite'], ['Lax', 'Strict'], true)
            ) {
                $this->SignonCookieParams['samesite'] = $server['SignonCookieParams']['samesite'];
            }
        }

        if (isset($server['SignonScript'])) {
            $this->SignonScript = (string) $server['SignonScript'];
        }

        if (isset($server['SignonURL'])) {
            $this->SignonURL = (string) $server['SignonURL'];
        }

        if (isset($server['LogoutURL'])) {
            $this->LogoutURL = (string) $server['LogoutURL'];
        }

        if (isset($server['only_db'])) {
            if (! is_array($server['only_db'])) {
                $this->only_db = (string) $server['only_db'];
            } elseif (count($server['only_db']) > 0) {
                $this->only_db = [];
                /** @var mixed $database */
                foreach ($server['only_db'] as $database) {
                    $this->only_db[] = (string) $database;
                }
            }
        }

        if (isset($server['hide_db'])) {
            $this->hide_db = (string) $server['hide_db'];
        }

        if (isset($server['verbose'])) {
            $this->verbose = (string) $server['verbose'];
        }

        if (isset($server['pmadb'])) {
            $this->pmadb = (string) $server['pmadb'];
        }

        if (isset($server['bookmarktable'])) {
            $this->bookmarktable = $server['bookmarktable'] === false ? false : (string) $server['bookmarktable'];
        }

        if (isset($server['relation'])) {
            $this->relation = $server['relation'] === false ? false : (string) $server['relation'];
        }

        if (isset($server['table_info'])) {
            $this->table_info = $server['table_info'] === false ? false : (string) $server['table_info'];
        }

        if (isset($server['table_coords'])) {
            $this->table_coords = $server['table_coords'] === false ? false : (string) $server['table_coords'];
        }

        if (isset($server['pdf_pages'])) {
            $this->pdf_pages = $server['pdf_pages'] === false ? false : (string) $server['pdf_pages'];
        }

        if (isset($server['column_info'])) {
            $this->column_info = $server['column_info'] === false ? false : (string) $server['column_info'];
        }

        if (isset($server['history'])) {
            $this->history = $server['history'] === false ? false : (string) $server['history'];
        }

        if (isset($server['recent'])) {
            $this->recent = $server['recent'] === false ? false : (string) $server['recent'];
        }

        if (isset($server['favorite'])) {
            $this->favorite = $server['favorite'] === false ? false : (string) $server['favorite'];
        }

        if (isset($server['table_uiprefs'])) {
            $this->table_uiprefs = $server['table_uiprefs'] === false ? false : (string) $server['table_uiprefs'];
        }

        if (isset($server['tracking'])) {
            $this->tracking = $server['tracking'] === false ? false : (string) $server['tracking'];
        }

        if (isset($server['userconfig'])) {
            $this->userconfig = $server['userconfig'] === false ? false : (string) $server['userconfig'];
        }

        if (isset($server['users'])) {
            $this->users = $server['users'] === false ? false : (string) $server['users'];
        }

        if (isset($server['usergroups'])) {
            $this->usergroups = $server['usergroups'] === false ? false : (string) $server['usergroups'];
        }

        if (isset($server['navigationhiding'])) {
            $this->navigationhiding = $server['navigationhiding'] === false
                ? false
                : (string) $server['navigationhiding'];
        }

        if (isset($server['savedsearches'])) {
            $this->savedsearches = $server['savedsearches'] === false ? false : (string) $server['savedsearches'];
        }

        if (isset($server['central_columns'])) {
            $this->central_columns = $server['central_columns'] === false ? false : (string) $server['central_columns'];
        }

        if (isset($server['designer_settings'])) {
            $this->designer_settings = $server['designer_settings'] === false
                ? false
                : (string) $server['designer_settings'];
        }

        if (isset($server['export_templates'])) {
            $this->export_templates = $server['export_templates'] === false
                ? false
                : (string) $server['export_templates'];
        }

        if (isset($server['MaxTableUiprefs'])) {
            $this->MaxTableUiprefs = (int) $server['MaxTableUiprefs'];
        }

        if (isset($server['SessionTimeZone'])) {
            $this->SessionTimeZone = (string) $server['SessionTimeZone'];
        }

        if (isset($server['AllowRoot'])) {
            $this->AllowRoot = (bool) $server['AllowRoot'];
        }

        if (isset($server['AllowNoPassword'])) {
            $this->AllowNoPassword = (bool) $server['AllowNoPassword'];
        }

        if (isset($server['AllowDeny']) && is_array($server['AllowDeny'])) {
            if (isset($server['AllowDeny']['order'])) {
                $this->AllowDeny['order'] = (string) $server['AllowDeny']['order'];
            }

            if (isset($server['AllowDeny']['rules']) && is_array($server['AllowDeny']['rules'])) {
                /** @var mixed $rule */
                foreach ($server['AllowDeny']['rules'] as $rule) {
                    $this->AllowDeny['rules'][] = (string) $rule;
                }
            }
        }

        if (isset($server['DisableIS'])) {
            $this->DisableIS = (bool) $server['DisableIS'];
        }

        if (isset($server['tracking_version_auto_create'])) {
            $this->tracking_version_auto_create = (bool) $server['tracking_version_auto_create'];
        }

        if (isset($server['tracking_default_statements'])) {
            $this->tracking_default_statements = (string) $server['tracking_default_statements'];
        }

        if (isset($server['tracking_add_drop_view'])) {
            $this->tracking_add_drop_view = (bool) $server['tracking_add_drop_view'];
        }

        if (isset($server['tracking_add_drop_table'])) {
            $this->tracking_add_drop_table = (bool) $server['tracking_add_drop_table'];
        }

        if (! isset($server['tracking_add_drop_database'])) {
            return;
        }

        $this->tracking_add_drop_database = (bool) $server['tracking_add_drop_database'];
    }
}
