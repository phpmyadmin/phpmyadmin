<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config\Settings\Export;
use PhpMyAdmin\Config\Settings\Import;
use PhpMyAdmin\Config\Settings\Schema;
use PhpMyAdmin\Config\Settings\Server;
use PhpMyAdmin\Config\Settings\Transformations;

use function count;
use function defined;
use function in_array;
use function is_array;
use function is_int;
use function min;
use function strlen;

use const DIRECTORY_SEPARATOR;
use const ROOT_PATH;
use const TEMP_DIR;
use const VERSION_CHECK_DEFAULT;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

final class Settings
{
    /**
     * Your phpMyAdmin URL.
     *
     * Complete the variable below with the full URL ie
     *    https://example.com/path_to_your_phpMyAdmin_directory/
     *
     * It must contain characters that are valid for a URL, and the path is
     * case sensitive on some Web servers, for example Unix-based servers.
     *
     * In most cases you can leave this variable empty, as the correct value
     * will be detected automatically. However, we recommend that you do
     * test to see that the auto-detection code works in your system. A good
     * test is to browse a table, then edit a row and save it.  There will be
     * an error message if phpMyAdmin cannot auto-detect the correct value.
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $PmaAbsoluteUri = '';

    /**
     * Configure authentication logging destination
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $AuthLog = 'auto';

    /**
     * Whether to log successful authentication attempts
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $AuthLogSuccess = false;

    /**
     * Disable the default warning that is displayed on the DB Details Structure page if
     * any of the required Tables for the configuration storage could not be found
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $PmaNoRelation_DisableWarning = false;

    /**
     * Disable the default warning that is displayed if Suhosin is detected
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $SuhosinDisableWarning = false;

    /**
     * Disable the default warning that is displayed if session.gc_maxlifetime
     * is less than `LoginCookieValidity`
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $LoginCookieValidityDisableWarning = false;

    /**
     * Disable the default warning about MySQL reserved words in column names
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ReservedWordDisableWarning = false;

    /**
     * Show warning about incomplete translations on certain threshold.
     *
     * @var int
     * @psalm-readonly-allow-private-mutation
     */
    public $TranslationWarningThreshold = 80;

    /**
     * Allows phpMyAdmin to be included from a other document in a frame;
     * setting this to true is a potential security hole. Setting this to
     * 'sameorigin' prevents phpMyAdmin to be included from another document
     * in a frame, unless that document belongs to the same domain.
     *
     * @var bool|string
     * @psalm-var bool|'sameorigin'
     * @psalm-readonly-allow-private-mutation
     */
    public $AllowThirdPartyFraming = false;

    /**
     * The 'cookie' auth_type uses AES algorithm to encrypt the password. If
     * at least one server configuration uses 'cookie' auth_type, enter here a
     * pass phrase that will be used by AES. The maximum length seems to be 46
     * characters.
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $blowfish_secret = '';

    /**
     * Server(s) configuration
     *
     * The $cfg['Servers'] array starts with $cfg['Servers'][1].  Do not use
     * $cfg['Servers'][0]. You can disable a server configuration entry by setting host
     * to ''. If you want more than one server, just copy following section
     * (including $i incrementation) several times. There is no need to define
     * full server array, just define values you need to change.
     *
     * @var array<int, Server>
     * @psalm-readonly-allow-private-mutation
     */
    public $Servers = [];

    /**
     * @var Server
     * @psalm-readonly-allow-private-mutation
     */
    public $Server;

    /**
     * Default server (0 = no default server)
     *
     * If you have more than one server configured, you can set $cfg['ServerDefault']
     * to any one of them to auto-connect to that server when phpMyAdmin is started,
     * or set it to 0 to be given a list of servers without logging in
     * If you have only one server configured, $cfg['ServerDefault'] *MUST* be
     * set to that server.
     *
     * @var int
     * @psalm-var 0|positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $ServerDefault = 1;

    /**
     * whether version check is active
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $VersionCheck = true;

    /**
     * The url of the proxy to be used when retrieving the information about
     * the latest version of phpMyAdmin or error reporting. You need this if
     * the server where phpMyAdmin is installed does not have direct access to
     * the internet.
     * The format is: "hostname:portnumber"
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $ProxyUrl = '';

    /**
     * The username for authenticating with the proxy. By default, no
     * authentication is performed. If a username is supplied, Basic
     * Authentication will be performed. No other types of authentication
     * are currently supported.
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $ProxyUser = '';

    /**
     * The password for authenticating with the proxy.
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $ProxyPass = '';

    /**
     * maximum number of db's displayed in database list
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $MaxDbList = 100;

    /**
     * maximum number of tables displayed in table list
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $MaxTableList = 250;

    /**
     * whether to show hint or not
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowHint = true;

    /**
     * maximum number of characters when a SQL query is displayed
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $MaxCharactersInDisplayedSQL = 1000;

    /**
     * use GZIP output buffering if possible (true|false|'auto')
     *
     * @var string|bool
     * @psalm-var 'auto'|bool
     * @psalm-readonly-allow-private-mutation
     */
    public $OBGzip = 'auto';

    /**
     * use persistent connections to MySQL database
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $PersistentConnections = false;

    /**
     * maximum execution time in seconds (0 for no limit)
     *
     * @var int
     * @psalm-var 0|positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $ExecTimeLimit = 300;

    /**
     * Path for storing session data (session_save_path PHP parameter).
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $SessionSavePath = '';

    /**
     * Hosts or IPs to consider safe when checking if SSL is used or not
     *
     * @var string[]
     * @psalm-readonly-allow-private-mutation
     */
    public $MysqlSslWarningSafeHosts = ['127.0.0.1', 'localhost'];

    /**
     * maximum allocated bytes ('-1' for no limit, '0' for no change)
     * this is a string because '16M' is a valid value; we must put here
     * a string as the default value so that /setup accepts strings
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $MemoryLimit = '-1';

    /**
     * mark used tables, make possible to show locked tables (since MySQL 3.23.30)
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $SkipLockedTables = false;

    /**
     * show SQL queries as run
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowSQL = true;

    /**
     * retain SQL input on Ajax execute
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $RetainQueryBox = false;

    /**
     * use CodeMirror syntax highlighting for editing SQL
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $CodemirrorEnable = true;

    /**
     * use the parser to find any errors in the query before executing
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $LintEnable = true;

    /**
     * show a 'Drop database' link to normal users
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $AllowUserDropDatabase = false;

    /**
     * confirm some commands that can result in loss of data
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $Confirm = true;

    /**
     * sets SameSite attribute of the Set-Cookie HTTP response header
     *
     * @var string
     * @psalm-var 'Lax'|'Strict'|'None'
     * @psalm-readonly-allow-private-mutation
     */
    public $CookieSameSite = 'Strict';

    /**
     * recall previous login in cookie authentication mode or not
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $LoginCookieRecall = true;

    /**
     * validity of cookie login (in seconds; 1440 matches php.ini's
     * session.gc_maxlifetime)
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $LoginCookieValidity = 1440;

    /**
     * how long login cookie should be stored (in seconds)
     *
     * @var int
     * @psalm-var 0|positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $LoginCookieStore = 0;

    /**
     * whether to delete all login cookies on logout
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $LoginCookieDeleteAll = true;

    /**
     * whether to enable the "database search" feature or not
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $UseDbSearch = true;

    /**
     * if set to true, PMA continues computing multiple-statement queries
     * even if one of the queries failed
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $IgnoreMultiSubmitErrors = false;

    /**
     * allow login to any user entered server in cookie based authentication
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $AllowArbitraryServer = false;

    /**
     * restrict by IP (with regular expression) the MySQL servers the user can enter
     * when $cfg['AllowArbitraryServer'] = true
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $ArbitraryServerRegexp = '';

    /**
     * To enable reCaptcha v2 checkbox mode if necessary
     *
     * @var string
     * @psalm-var 'invisible'|'checkbox'
     * @psalm-readonly-allow-private-mutation
     */
    public $CaptchaMethod = 'invisible';

    /**
     * URL for the reCaptcha v2 compatible API to use
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $CaptchaApi = 'https://www.google.com/recaptcha/api.js';

    /**
     * Content-Security-Policy snippet for the reCaptcha v2 compatible API
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $CaptchaCsp = 'https://apis.google.com https://www.google.com/recaptcha/'
        . ' https://www.gstatic.com/recaptcha/ https://ssl.gstatic.com/';

    /**
     * reCaptcha API's request parameter name
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $CaptchaRequestParam = 'g-recaptcha';

    /**
     * reCaptcha API's response parameter name
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $CaptchaResponseParam = 'g-recaptcha-response';

    /**
     * if reCaptcha is enabled it needs public key to connect with the service
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $CaptchaLoginPublicKey = '';

    /**
     * if reCaptcha is enabled it needs private key to connect with the service
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $CaptchaLoginPrivateKey = '';

    /**
     * if reCaptcha is enabled may need an URL for site verify
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $CaptchaSiteVerifyURL = '';

    /**
     * Enable drag and drop import
     *
     * @see https://github.com/phpmyadmin/phpmyadmin/issues/13155
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $enable_drag_drop_import = true;

    /**
     * In the navigation panel, replaces the database tree with a selector
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowDatabasesNavigationAsTree = true;

    /**
     * maximum number of first level databases displayed in navigation panel
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $FirstLevelNavigationItems = 100;

    /**
     * maximum number of items displayed in navigation panel
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $MaxNavigationItems = 50;

    /**
     * turn the select-based light menu into a tree
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeEnableGrouping = true;

    /**
     * the separator to sub-tree the select-based light menu tree
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeDbSeparator = '_';

    /**
     * Which string will be used to generate table prefixes
     * to split/nest tables into multiple categories
     *
     * @var string|string[]|false
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeTableSeparator = '__';

    /**
     * How many sublevels should be displayed when splitting up tables
     * by the above Separator
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeTableLevel = 1;

    /**
     * link with main panel by highlighting the current db/table
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationLinkWithMainPanel = true;

    /**
     * display logo at top of navigation panel
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationDisplayLogo = true;

    /**
     * where should logo link point to (can also contain an external URL)
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationLogoLink = 'index.php';

    /**
     * whether to open the linked page in the main window ('main') or
     * in a new window ('new')
     *
     * @var string
     * @psalm-var 'main'|'new'
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationLogoLinkWindow = 'main';

    /**
     * number of recently used tables displayed in the navigation panel
     *
     * @var int
     * @psalm-var 0|positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $NumRecentTables = 10;

    /**
     * number of favorite tables displayed in the navigation panel
     *
     * @var int
     * @psalm-var 0|positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $NumFavoriteTables = 10;

    /**
     * display a JavaScript table filter in the navigation panel
     * when more then x tables are present
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeDisplayItemFilterMinimum = 30;

    /**
     * display server choice at top of navigation panel
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationDisplayServers = true;

    /**
     * server choice as links
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $DisplayServersList = false;

    /**
     * display a JavaScript database filter in the navigation panel
     * when more then x databases are present
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeDisplayDbFilterMinimum = 30;

    /**
     * target of the navigation panel quick access icon
     *
     * Possible values:
     * 'structure' = fields list
     * 'sql' = SQL form
     * 'search' = search page
     * 'insert' = insert row page
     * 'browse' = browse page
     *
     * @var string
     * @psalm-var 'structure'|'sql'|'search'|'insert'|'browse'
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeDefaultTabTable = 'structure';

    /**
     * target of the navigation panel quick second access icon
     *
     * Possible values:
     * 'structure' = fields list
     * 'sql' = SQL form
     * 'search' = search page
     * 'insert' = insert row page
     * 'browse' = browse page
     * '' = no link
     *
     * @var string
     * @psalm-var 'structure'|'sql'|'search'|'insert'|'browse'|''
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeDefaultTabTable2 = '';

    /**
     * Enables the possibility of navigation tree expansion
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeEnableExpansion = true;

    /**
     * Show tables in navigation panel
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeShowTables = true;

    /**
     * Show views in navigation panel
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeShowViews = true;

    /**
     * Show functions in navigation panel
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeShowFunctions = true;

    /**
     * Show procedures in navigation panel
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeShowProcedures = true;

    /**
     * Show events in navigation panel
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeShowEvents = true;

    /**
     * Width of navigation panel
     *
     * @var int
     * @psalm-var 0|positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationWidth = 240;

    /**
     * Automatically expands single database in navigation panel
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreeAutoexpandSingleDb = true;

    /**
     * allow to display statistics and space usage in the pages about database
     * details and table properties
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowStats = true;

    /**
     * show PHP info link
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowPhpInfo = false;

    /**
     * show MySQL server and web server information
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowServerInfo = true;

    /**
     * show change password link
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowChgPassword = true;

    /**
     * show create database form
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowCreateDb = true;

    /**
     * show charset column in database structure (true|false)?
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowDbStructureCharset = false;

    /**
     * show comment column in database structure (true|false)?
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowDbStructureComment = false;

    /**
     * show creation timestamp column in database structure (true|false)?
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowDbStructureCreation = false;

    /**
     * show last update timestamp column in database structure (true|false)?
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowDbStructureLastUpdate = false;

    /**
     * show last check timestamp column in database structure (true|false)?
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowDbStructureLastCheck = false;

    /**
     * allow hide action columns to drop down menu in database structure (true|false)?
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $HideStructureActions = true;

    /**
     * Show column comments in table structure view (true|false)?
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowColumnComments = true;

    /**
     * Use icons instead of text for the navigation bar buttons (table browse)
     * ('text'|'icons'|'both')
     *
     * @var string
     * @psalm-var 'text'|'icons'|'both'
     * @psalm-readonly-allow-private-mutation
     */
    public $TableNavigationLinksMode = 'icons';

    /**
     * Defines whether a user should be displayed a "show all (records)"
     * button in browse mode or not.
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowAll = false;

    /**
     * Number of rows displayed when browsing a result set. If the result
     * set contains more rows, "Previous" and "Next".
     * Possible values: 25,50,100,250,500
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $MaxRows = 25;

    /**
     * default for 'ORDER BY' clause (valid values are 'ASC', 'DESC' or 'SMART' -ie
     * descending order for fields of type TIME, DATE, DATETIME & TIMESTAMP,
     * ascending order else-)
     *
     * @var string
     * @psalm-var 'ASC'|'DESC'|'SMART'
     * @psalm-readonly-allow-private-mutation
     */
    public $Order = 'SMART';

    /**
     * grid editing: save edited cell(s) in browse-mode at once
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $SaveCellsAtOnce = false;

    /**
     * grid editing: which action triggers it, or completely disable the feature
     *
     * Possible values:
     * 'click'
     * 'double-click'
     * 'disabled'
     *
     * @var string
     * @psalm-var 'double-click'|'click'|'disabled'
     * @psalm-readonly-allow-private-mutation
     */
    public $GridEditing = 'double-click';

    /**
     * Options > Relational display
     *
     * Possible values:
     * 'K' for key value
     * 'D' for display column
     *
     * @var string
     * @psalm-var 'K'|'D'
     * @psalm-readonly-allow-private-mutation
     */
    public $RelationalDisplay = 'K';

    /**
     * disallow editing of binary fields
     * valid values are:
     *   false    allow editing
     *   'blob'   allow editing except for BLOB fields
     *   'noblob' disallow editing except for BLOB fields
     *   'all'    disallow editing
     *
     * @var string|false
     * @psalm-var 'blob'|'noblob'|'all'|false
     * @psalm-readonly-allow-private-mutation
     */
    public $ProtectBinary = 'blob';

    /**
     * Display the function fields in edit/insert mode
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowFunctionFields = true;

    /**
     * Display the type fields in edit/insert mode
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowFieldTypesInDataEditView = true;

    /**
     * Which editor should be used for CHAR/VARCHAR fields:
     *  input - allows limiting of input length
     *  textarea - allows newlines in fields
     *
     * @var string
     * @psalm-var 'input'|'textarea'
     * @psalm-readonly-allow-private-mutation
     */
    public $CharEditing = 'input';

    /**
     * The minimum size for character input fields
     *
     * @var int
     * @psalm-var 0|positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $MinSizeForInputField = 4;

    /**
     * The maximum size for character input fields
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $MaxSizeForInputField = 60;

    /**
     * How many rows can be inserted at one time
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $InsertRows = 2;

    /**
     * Sort order for items in a foreign-key drop-down list.
     * 'content' is the referenced data, 'id' is the key value.
     *
     * @var string[]
     * @psalm-var array{0: 'content-id'|'id-content', 1?: 'content-id'|'id-content'}
     * @psalm-readonly-allow-private-mutation
     */
    public $ForeignKeyDropdownOrder = ['content-id', 'id-content'];

    /**
     * A drop-down list will be used if fewer items are present
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $ForeignKeyMaxLimit = 100;

    /**
     * Whether to disable foreign key checks while importing
     *
     * @var string
     * @psalm-var 'default'|'enable'|'disable'
     * @psalm-readonly-allow-private-mutation
     */
    public $DefaultForeignKeyChecks = 'default';

    /**
     * Allow for the use of zip compression (requires zip support to be enabled)
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ZipDump = true;

    /**
     * Allow for the use of gzip compression (requires zlib)
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $GZipDump = true;

    /**
     * Allow for the use of bzip2 decompression (requires bz2 extension)
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $BZipDump = true;

    /**
     * Will compress gzip exports on the fly without the need for much memory.
     * If you encounter problems with created gzip files disable this feature.
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $CompressOnFly = true;

    /**
     * How to display the menu tabs ('icons'|'text'|'both')
     *
     * @var string
     * @psalm-var 'icons'|'text'|'both'
     * @psalm-readonly-allow-private-mutation
     */
    public $TabsMode = 'both';

    /**
     * How to display various action links ('icons'|'text'|'both')
     *
     * @var string
     * @psalm-var 'icons'|'text'|'both'
     * @psalm-readonly-allow-private-mutation
     */
    public $ActionLinksMode = 'both';

    /**
     * How many columns should be used for table display of a database?
     * (a value larger than 1 results in some information being hidden)
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $PropertiesNumColumns = 1;

    /**
     * Possible values:
     * 'welcome' = the welcome page (recommended for multiuser setups)
     * 'databases' = list of databases
     * 'status' = runtime information
     * 'variables' = MySQL server variables
     * 'privileges' = user management
     *
     * @var string
     * @psalm-var 'welcome'|'databases'|'status'|'variables'|'privileges'
     * @psalm-readonly-allow-private-mutation
     */
    public $DefaultTabServer = 'welcome';

    /**
     * Possible values:
     * 'structure' = tables list
     * 'sql' = SQL form
     * 'search' = search query
     * 'operations' = operations on database
     *
     * @var string
     * @psalm-var 'structure'|'sql'|'search'|'operations'
     * @psalm-readonly-allow-private-mutation
     */
    public $DefaultTabDatabase = 'structure';

    /**
     * Possible values:
     * 'structure' = fields list
     * 'sql' = SQL form
     * 'search' = search page
     * 'insert' = insert row page
     * 'browse' = browse page
     *
     * @var string
     * @psalm-var 'structure'|'sql'|'search'|'insert'|'browse'
     * @psalm-readonly-allow-private-mutation
     */
    public $DefaultTabTable = 'browse';

    /**
     * Whether to display image or text or both image and text in table row
     * action segment. Value can be either of ``image``, ``text`` or ``both``.
     *
     * @var string
     * @psalm-var 'icons'|'text'|'both'
     * @psalm-readonly-allow-private-mutation
     */
    public $RowActionType = 'both';

    /**
     * @var Export
     * @psalm-readonly-allow-private-mutation
     */
    public $Export;

    /**
     * @var Import
     * @psalm-readonly-allow-private-mutation
     */
    public $Import;

    /**
     * @var Schema
     * @psalm-readonly-allow-private-mutation
     */
    public $Schema;

    /**
     * @var string[]
     * @psalm-readonly-allow-private-mutation
     */
    public $PDFPageSizes = ['A3', 'A4', 'A5', 'letter', 'legal'];

    /**
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $PDFDefaultPageSize = 'A4';

    /**
     * Default language to use, if not browser-defined or user-defined
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $DefaultLang = 'en';

    /**
     * Default connection collation
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $DefaultConnectionCollation = 'utf8mb4_unicode_ci';

    /**
     * Force: always use this language, e.g. 'en'
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $Lang = '';

    /**
     * Regular expression to limit listed languages, e.g. '^(cs|en)' for Czech and
     * English only
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $FilterLanguages = '';

    /**
     * You can select here which functions will be used for character set conversion.
     * Possible values are:
     *      auto   - automatically use available one (first is tested iconv, then recode)
     *      iconv  - use iconv or libiconv functions
     *      recode - use recode_string function
     *      mb     - use mbstring extension
     *      none   - disable encoding conversion
     *
     * @var string
     * @psalm-var 'auto'|'iconv'|'recode'|'mb'|'none'
     * @psalm-readonly-allow-private-mutation
     */
    public $RecodingEngine = 'auto';

    /**
     * Specify some parameters for iconv used in character set conversion. See iconv
     * documentation for details:
     * https://www.gnu.org/savannah-checkouts/gnu/libiconv/documentation/libiconv-1.15/iconv_open.3.html
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $IconvExtraParams = '//TRANSLIT';

    /**
     * Available character sets for MySQL conversion. currently contains all which could
     * be found in lang/* files and few more.
     * Character sets will be shown in same order as here listed, so if you frequently
     * use some of these move them to the top.
     *
     * @var string[]
     * @psalm-readonly-allow-private-mutation
     */
    public $AvailableCharsets = [
        'iso-8859-1',
        'iso-8859-2',
        'iso-8859-3',
        'iso-8859-4',
        'iso-8859-5',
        'iso-8859-6',
        'iso-8859-7',
        'iso-8859-8',
        'iso-8859-9',
        'iso-8859-10',
        'iso-8859-11',
        'iso-8859-12',
        'iso-8859-13',
        'iso-8859-14',
        'iso-8859-15',
        'windows-1250',
        'windows-1251',
        'windows-1252',
        'windows-1256',
        'windows-1257',
        'koi8-r',
        'big5',
        'gb2312',
        'utf-16',
        'utf-8',
        'utf-7',
        'x-user-defined',
        'euc-jp',
        'ks_c_5601-1987',
        'tis-620',
        'SHIFT_JIS',
        'SJIS',
        'SJIS-win',
    ];

    /**
     * enable the left panel pointer
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NavigationTreePointerEnable = true;

    /**
     * enable the browse pointer
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $BrowsePointerEnable = true;

    /**
     * enable the browse marker
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $BrowseMarkerEnable = true;

    /**
     * textarea size (columns) in edit mode
     * (this value will be emphasized (*2) for SQL
     * query textareas and (*1.25) for query window)
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $TextareaCols = 40;

    /**
     * textarea size (rows) in edit mode
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $TextareaRows = 15;

    /**
     * double size of textarea size for LONGTEXT columns
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $LongtextDoubleTextarea = true;

    /**
     * auto-select when clicking in the textarea of the query-box
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $TextareaAutoSelect = false;

    /**
     * textarea size (columns) for CHAR/VARCHAR
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $CharTextareaCols = 40;

    /**
     * textarea size (rows) for CHAR/VARCHAR
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $CharTextareaRows = 7;

    /**
     * Max field data length in browse mode for all non-numeric fields
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $LimitChars = 50;

    /**
     * Where to show the edit/copy/delete links in browse mode
     * Possible values are 'left', 'right', 'both' and 'none'.
     *
     * @var string
     * @psalm-var 'left'|'right'|'both'|'none'
     * @psalm-readonly-allow-private-mutation
     */
    public $RowActionLinks = 'left';

    /**
     * Whether to show row links (Edit, Copy, Delete) and checkboxes for
     * multiple row operations even when the selection does not have a unique key.
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $RowActionLinksWithoutUnique = false;

    /**
     * Default sort order by primary key.
     *
     * @var string
     * @psalm-var 'NONE'|'ASC'|'DESC'
     * @psalm-readonly-allow-private-mutation
     */
    public $TablePrimaryKeyOrder = 'NONE';

    /**
     * remember the last way a table sorted
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $RememberSorting = true;

    /**
     * shows column comments in 'browse' mode.
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowBrowseComments = true;

    /**
     * shows column comments in 'table property' mode.
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowPropertyComments = true;

    /**
     * repeat header names every X cells? (0 = deactivate)
     *
     * @var int
     * @psalm-var 0|positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $RepeatCells = 100;

    /**
     * Set to true if you want DB-based query history.If false, this utilizes
     * JS-routines to display query history (lost by window close)
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $QueryHistoryDB = false;

    /**
     * When using DB-based query history, how many entries should be kept?
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $QueryHistoryMax = 25;

    /**
     * Use MIME-Types (stored in column comments table) for
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $BrowseMIME = true;

    /**
     * When approximate count < this, PMA will get exact count for table rows.
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $MaxExactCount = 50000;

    /**
     * Zero means that no row count is done for views; see the doc
     *
     * @var int
     * @psalm-var 0|positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $MaxExactCountViews = 0;

    /**
     * Sort table and database in natural order
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $NaturalOrder = true;

    /**
     * Initial state for sliders
     * (open | closed | disabled)
     *
     * @var string
     * @psalm-var 'open'|'closed'|'disabled'
     * @psalm-readonly-allow-private-mutation
     */
    public $InitialSlidersState = 'closed';

    /**
     * User preferences: disallow these settings
     * For possible setting names look in libraries/config/user_preferences.forms.php
     *
     * @var string[]
     * @psalm-readonly-allow-private-mutation
     */
    public $UserprefsDisallow = [];

    /**
     * User preferences: enable the Developer tab
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $UserprefsDeveloperTab = false;

    /**
     * title of browser window when a table is selected
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $TitleTable = '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ / @TABLE@ | @PHPMYADMIN@';

    /**
     * title of browser window when a database is selected
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $TitleDatabase = '@HTTP_HOST@ / @VSERVER@ / @DATABASE@ | @PHPMYADMIN@';

    /**
     * title of browser window when a server is selected
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $TitleServer = '@HTTP_HOST@ / @VSERVER@ | @PHPMYADMIN@';

    /**
     * title of browser window when nothing is selected
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $TitleDefault = '@HTTP_HOST@ | @PHPMYADMIN@';

    /**
     * if you want to use selectable themes and if ThemesPath not empty
     * set it to true, else set it to false (default is false);
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ThemeManager = true;

    /**
     * set up default theme, you can set up here an valid
     * path to themes or 'original' for the original pma-theme
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $ThemeDefault = 'pmahomme';

    /**
     * allow different theme for each configured server
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ThemePerServer = false;

    /**
     * Default query for table
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $DefaultQueryTable = 'SELECT * FROM @TABLE@ WHERE 1';

    /**
     * Default query for database
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $DefaultQueryDatabase = '';

    /**
     * SQL Query box settings
     * These are the links display in all of the SQL Query boxes
     *
     * @var array<string, bool>
     * @psalm-var array{Edit: bool, Explain: bool, ShowAsPHP: bool, Refresh: bool}
     * @psalm-readonly-allow-private-mutation
     */
    public $SQLQuery = [
        // Display an "Edit" link on the results page to change a query
        'Edit' => true,

        // Display an "Explain SQL" link on the results page
        'Explain' => true,

        // Display a "Create PHP code" link on the results page to wrap a query in PHP
        'ShowAsPHP' => true,

        // Display a "Refresh" link on the results page
        'Refresh' => true,
    ];

    /**
     * Enables autoComplete for table & column names in SQL queries
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $EnableAutocompleteForTablesAndColumns = true;

    /**
     * Directory for uploaded files that can be executed by phpMyAdmin.
     * For example './upload'. Leave empty for no upload directory support.
     * Use %u for username inclusion.
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $UploadDir = '';

    /**
     * Directory where phpMyAdmin can save exported data on server.
     * For example './save'. Leave empty for no save directory support.
     * Use %u for username inclusion.
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $SaveDir = '';

    /**
     * Directory where phpMyAdmin can save temporary files.
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $TempDir = ROOT_PATH . 'tmp' . DIRECTORY_SEPARATOR;

    /**
     * Is GD >= 2 available? Set to yes/no/auto. 'auto' does auto-detection,
     * which is the only safe way to determine GD version.
     *
     * @var string
     * @psalm-var 'auto'|'yes'|'no'
     * @psalm-readonly-allow-private-mutation
     */
    public $GD2Available = 'auto';

    /**
     * Lists proxy IP and HTTP header combinations which are trusted for IP allow/deny
     *
     * @var array<string, string>
     * @psalm-readonly-allow-private-mutation
     */
    public $TrustedProxies = [];

    /**
     * We normally check the permissions on the configuration file to ensure
     * it's not world writable. However, phpMyAdmin could be installed on
     * a NTFS filesystem mounted on a non-Windows server, in which case the
     * permissions seems wrong but in fact cannot be detected. In this case
     * a sysadmin would set the following to false.
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $CheckConfigurationPermissions = true;

    /**
     * Limit for length of URL in links. When length would be above this limit, it
     * is replaced by form with button.
     * This is required as some web servers (IIS) have problems with long URLs.
     * The recommended limit is 2000
     * (see https://www.boutell.com/newfaq/misc/urllength.html) but we put
     * 1000 to accommodate Suhosin, see bug #3358750.
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $LinkLengthLimit = 1000;

    /**
     * Additional string to allow in CSP headers.
     *
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $CSPAllow = '';

    /**
     * Disable the table maintenance mass operations, like optimizing or
     * repairing the selected tables of a database. An accidental execution
     * of such a maintenance task can enormously slow down a bigger database.
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $DisableMultiTableMaintenance = false;

    /**
     * Whether or not to query the user before sending the error report to
     * the phpMyAdmin team when a JavaScript error occurs
     *
     * Available options
     * (ask | always | never)
     *
     * @var string
     * @psalm-var 'ask'|'always'|'never'
     * @psalm-readonly-allow-private-mutation
     */
    public $SendErrorReports = 'ask';

    /**
     * Whether Enter or Ctrl+Enter executes queries in the console.
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ConsoleEnterExecutes = false;

    /**
     * Zero Configuration mode.
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ZeroConf = true;

    /**
     * Developers ONLY!
     *
     * @var array<string, bool>
     * @psalm-var array{sql: bool, sqllog: bool, demo: bool, simple2fa: bool}
     * @psalm-readonly-allow-private-mutation
     */
    public $DBG = [
        // Output executed queries and their execution times
        'sql' => false,

        // Log executed queries and their execution times to syslog
        'sqllog' => false,

        // Enable to let server present itself as demo server.
        'demo' => false,

        // Enable Simple two-factor authentication
        'simple2fa' => false,
    ];

    /**
     * Sets the working environment
     *
     * This only needs to be changed when you are developing phpMyAdmin itself.
     * The development mode may display debug information in some places.
     *
     * Possible values are 'production' or 'development'
     *
     * @var string
     * @psalm-var 'production'|'development'
     * @psalm-readonly-allow-private-mutation
     */
    public $environment = 'production';

    /**
     * Default functions for above defined groups
     *
     * @var array<string, string>
     * @psalm-readonly-allow-private-mutation
     */
    public $DefaultFunctions = [
        'FUNC_CHAR' => '',
        'FUNC_DATE' => '',
        'FUNC_NUMBER' => '',
        'FUNC_SPATIAL' => 'GeomFromText',
        'FUNC_UUID' => 'UUID',
        'first_timestamp' => 'NOW',
    ];

    /**
     * Max rows retrieved for zoom search
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $maxRowPlotLimit = 500;

    /**
     * Show Git revision if applicable
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $ShowGitRevision = true;

    /**
     * MySQL minimal version required
     *
     * @var array<string, int|string>
     * @psalm-var array{internal: int, human: string}
     * @psalm-readonly-allow-private-mutation
     */
    public $MysqlMinVersion = ['internal' => 50500, 'human' => '5.5.0'];

    /**
     * Disable shortcuts
     *
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $DisableShortcutKeys = false;

    /**
     * Console configuration
     *
     * This is mostly meant for user preferences.
     *
     * @var array<string, string|int|bool>
     * @psalm-var array{
     *   StartHistory: bool,
     *   AlwaysExpand: bool,
     *   CurrentQuery: bool,
     *   EnterExecutes: bool,
     *   DarkTheme: bool,
     *   Mode: 'info'|'show'|'collapse',
     *   Height: positive-int,
     *   GroupQueries: bool,
     *   OrderBy: 'exec'|'time'|'count',
     *   Order: 'asc'|'desc'
     * }
     * @psalm-readonly-allow-private-mutation
     */
    public $Console = [
        'StartHistory' => false,
        'AlwaysExpand' => false,
        'CurrentQuery' => true,
        'EnterExecutes' => false,
        'DarkTheme' => false,
        'Mode' => 'info',
        'Height' => 92,
        'GroupQueries' => false,
        'OrderBy' => 'exec',
        'Order' => 'asc',
    ];

    /**
     * Initialize default transformations array
     *
     * @var Transformations
     * @psalm-readonly-allow-private-mutation
     */
    public $DefaultTransformations;

    /**
     * Set default for FirstDayOfCalendar
     *
     * @var int
     * @psalm-var 0|positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $FirstDayOfCalendar = 0;

    /**
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $is_setup = false;

    /**
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $PMA_IS_WINDOWS = false;

    /**
     * @var int
     * @psalm-var 0|1
     * @psalm-readonly-allow-private-mutation
     */
    public $PMA_IS_IIS = 0;

    /**
     * @var int
     * @psalm-var 0|1
     * @psalm-readonly-allow-private-mutation
     */
    public $PMA_IS_GD2 = 0;

    /**
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $PMA_USR_OS = 'Other';

    /**
     * @var string|int
     * @psalm-readonly-allow-private-mutation
     */
    public $PMA_USR_BROWSER_VER = 0;

    /**
     * @var string
     * @psalm-readonly-allow-private-mutation
     */
    public $PMA_USR_BROWSER_AGENT = 'OTHER';

    /**
     * @var bool
     * @psalm-readonly-allow-private-mutation
     */
    public $enable_upload = false;

    /**
     * Default: 2M (2 * 1024 * 1024)
     *
     * @var int
     * @psalm-var positive-int
     * @psalm-readonly-allow-private-mutation
     */
    public $max_upload_size = 2097152;

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    public function __construct(array $settings)
    {
        $this->setPmaAbsoluteUri($settings);
        $this->setAuthLog($settings);
        $this->setAuthLogSuccess($settings);
        $this->setPmaNoRelationDisableWarning($settings);
        $this->setSuhosinDisableWarning($settings);
        $this->setLoginCookieValidityDisableWarning($settings);
        $this->setReservedWordDisableWarning($settings);
        $this->setTranslationWarningThreshold($settings);
        $this->setAllowThirdPartyFraming($settings);
        $this->setBlowfishSecret($settings);
        $this->setServers($settings);
        $this->setServerDefault($settings);
        $this->setVersionCheck($settings);
        $this->setProxyUrl($settings);
        $this->setProxyUser($settings);
        $this->setProxyPass($settings);
        $this->setMaxDbList($settings);
        $this->setMaxTableList($settings);
        $this->setShowHint($settings);
        $this->setMaxCharactersInDisplayedSQL($settings);
        $this->setOBGzip($settings);
        $this->setPersistentConnections($settings);
        $this->setExecTimeLimit($settings);
        $this->setSessionSavePath($settings);
        $this->setMysqlSslWarningSafeHosts($settings);
        $this->setMemoryLimit($settings);
        $this->setSkipLockedTables($settings);
        $this->setShowSQL($settings);
        $this->setRetainQueryBox($settings);
        $this->setCodemirrorEnable($settings);
        $this->setLintEnable($settings);
        $this->setAllowUserDropDatabase($settings);
        $this->setConfirm($settings);
        $this->setCookieSameSite($settings);
        $this->setLoginCookieRecall($settings);
        $this->setLoginCookieValidity($settings);
        $this->setLoginCookieStore($settings);
        $this->setLoginCookieDeleteAll($settings);
        $this->setUseDbSearch($settings);
        $this->setIgnoreMultiSubmitErrors($settings);
        $this->setAllowArbitraryServer($settings);
        $this->setArbitraryServerRegexp($settings);
        $this->setCaptchaMethod($settings);
        $this->setCaptchaApi($settings);
        $this->setCaptchaCsp($settings);
        $this->setCaptchaRequestParam($settings);
        $this->setCaptchaResponseParam($settings);
        $this->setCaptchaLoginPublicKey($settings);
        $this->setCaptchaLoginPrivateKey($settings);
        $this->setCaptchaSiteVerifyURL($settings);
        $this->setEnableDragDropImport($settings);
        $this->setShowDatabasesNavigationAsTree($settings);
        $this->setFirstLevelNavigationItems($settings);
        $this->setMaxNavigationItems($settings);
        $this->setNavigationTreeEnableGrouping($settings);
        $this->setNavigationTreeDbSeparator($settings);
        $this->setNavigationTreeTableSeparator($settings);
        $this->setNavigationTreeTableLevel($settings);
        $this->setNavigationLinkWithMainPanel($settings);
        $this->setNavigationDisplayLogo($settings);
        $this->setNavigationLogoLink($settings);
        $this->setNavigationLogoLinkWindow($settings);
        $this->setNumRecentTables($settings);
        $this->setNumFavoriteTables($settings);
        $this->setNavigationTreeDisplayItemFilterMinimum($settings);
        $this->setNavigationDisplayServers($settings);
        $this->setDisplayServersList($settings);
        $this->setNavigationTreeDisplayDbFilterMinimum($settings);
        $this->setNavigationTreeDefaultTabTable($settings);
        $this->setNavigationTreeDefaultTabTable2($settings);
        $this->setNavigationTreeEnableExpansion($settings);
        $this->setNavigationTreeShowTables($settings);
        $this->setNavigationTreeShowViews($settings);
        $this->setNavigationTreeShowFunctions($settings);
        $this->setNavigationTreeShowProcedures($settings);
        $this->setNavigationTreeShowEvents($settings);
        $this->setNavigationWidth($settings);
        $this->setNavigationTreeAutoexpandSingleDb($settings);
        $this->setShowStats($settings);
        $this->setShowPhpInfo($settings);
        $this->setShowServerInfo($settings);
        $this->setShowChgPassword($settings);
        $this->setShowCreateDb($settings);
        $this->setShowDbStructureCharset($settings);
        $this->setShowDbStructureComment($settings);
        $this->setShowDbStructureCreation($settings);
        $this->setShowDbStructureLastUpdate($settings);
        $this->setShowDbStructureLastCheck($settings);
        $this->setHideStructureActions($settings);
        $this->setShowColumnComments($settings);
        $this->setTableNavigationLinksMode($settings);
        $this->setShowAll($settings);
        $this->setMaxRows($settings);
        $this->setOrder($settings);
        $this->setSaveCellsAtOnce($settings);
        $this->setGridEditing($settings);
        $this->setRelationalDisplay($settings);
        $this->setProtectBinary($settings);
        $this->setShowFunctionFields($settings);
        $this->setShowFieldTypesInDataEditView($settings);
        $this->setCharEditing($settings);
        $this->setMinSizeForInputField($settings);
        $this->setMaxSizeForInputField($settings);
        $this->setInsertRows($settings);
        $this->setForeignKeyDropdownOrder($settings);
        $this->setForeignKeyMaxLimit($settings);
        $this->setDefaultForeignKeyChecks($settings);
        $this->setZipDump($settings);
        $this->setGZipDump($settings);
        $this->setBZipDump($settings);
        $this->setCompressOnFly($settings);
        $this->setTabsMode($settings);
        $this->setActionLinksMode($settings);
        $this->setPropertiesNumColumns($settings);
        $this->setDefaultTabServer($settings);
        $this->setDefaultTabDatabase($settings);
        $this->setDefaultTabTable($settings);
        $this->setRowActionType($settings);
        $this->setExport($settings);
        $this->setImport($settings);
        $this->setSchema($settings);
        $this->setPDFPageSizes($settings);
        $this->setPDFDefaultPageSize($settings);
        $this->setDefaultLang($settings);
        $this->setDefaultConnectionCollation($settings);
        $this->setLang($settings);
        $this->setFilterLanguages($settings);
        $this->setRecodingEngine($settings);
        $this->setIconvExtraParams($settings);
        $this->setAvailableCharsets($settings);
        $this->setNavigationTreePointerEnable($settings);
        $this->setBrowsePointerEnable($settings);
        $this->setBrowseMarkerEnable($settings);
        $this->setTextareaCols($settings);
        $this->setTextareaRows($settings);
        $this->setLongtextDoubleTextarea($settings);
        $this->setTextareaAutoSelect($settings);
        $this->setCharTextareaCols($settings);
        $this->setCharTextareaRows($settings);
        $this->setLimitChars($settings);
        $this->setRowActionLinks($settings);
        $this->setRowActionLinksWithoutUnique($settings);
        $this->setTablePrimaryKeyOrder($settings);
        $this->setRememberSorting($settings);
        $this->setShowBrowseComments($settings);
        $this->setShowPropertyComments($settings);
        $this->setRepeatCells($settings);
        $this->setQueryHistoryDB($settings);
        $this->setQueryHistoryMax($settings);
        $this->setBrowseMIME($settings);
        $this->setMaxExactCount($settings);
        $this->setMaxExactCountViews($settings);
        $this->setNaturalOrder($settings);
        $this->setInitialSlidersState($settings);
        $this->setUserprefsDisallow($settings);
        $this->setUserprefsDeveloperTab($settings);
        $this->setTitleTable($settings);
        $this->setTitleDatabase($settings);
        $this->setTitleServer($settings);
        $this->setTitleDefault($settings);
        $this->setThemeManager($settings);
        $this->setThemeDefault($settings);
        $this->setThemePerServer($settings);
        $this->setDefaultQueryTable($settings);
        $this->setDefaultQueryDatabase($settings);
        $this->setSQLQuery($settings);
        $this->setEnableAutocompleteForTablesAndColumns($settings);
        $this->setUploadDir($settings);
        $this->setSaveDir($settings);
        $this->setTempDir($settings);
        $this->setGD2Available($settings);
        $this->setTrustedProxies($settings);
        $this->setCheckConfigurationPermissions($settings);
        $this->setLinkLengthLimit($settings);
        $this->setCSPAllow($settings);
        $this->setDisableMultiTableMaintenance($settings);
        $this->setSendErrorReports($settings);
        $this->setConsoleEnterExecutes($settings);
        $this->setZeroConf($settings);
        $this->setDBG($settings);
        $this->setEnvironment($settings);
        $this->setDefaultFunctions($settings);
        $this->setMaxRowPlotLimit($settings);
        $this->setShowGitRevision($settings);
        $this->setMysqlMinVersion($settings);
        $this->setDisableShortcutKeys($settings);
        $this->setConsole($settings);
        $this->setDefaultTransformations($settings);
        $this->setFirstDayOfCalendar($settings);
        $this->setIsSetup($settings);
        $this->setIsWindows($settings);
        $this->setIsIIS($settings);
        $this->setIsGD2($settings);
        $this->setUserOperatingSystem($settings);
        $this->setUserBrowserVersion($settings);
        $this->setUserBrowserAgent($settings);
        $this->setEnableUpload($settings);
        $this->setMaxUploadSize($settings);
        $this->setServer($settings);
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setPmaAbsoluteUri(array $settings): void
    {
        if (! isset($settings['PmaAbsoluteUri'])) {
            return;
        }

        $this->PmaAbsoluteUri = (string) $settings['PmaAbsoluteUri'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setAuthLog(array $settings): void
    {
        if (! isset($settings['AuthLog'])) {
            return;
        }

        $this->AuthLog = (string) $settings['AuthLog'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setAuthLogSuccess(array $settings): void
    {
        if (! isset($settings['AuthLogSuccess'])) {
            return;
        }

        $this->AuthLogSuccess = (bool) $settings['AuthLogSuccess'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setPmaNoRelationDisableWarning(array $settings): void
    {
        if (! isset($settings['PmaNoRelation_DisableWarning'])) {
            return;
        }

        $this->PmaNoRelation_DisableWarning = (bool) $settings['PmaNoRelation_DisableWarning'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setSuhosinDisableWarning(array $settings): void
    {
        if (! isset($settings['SuhosinDisableWarning'])) {
            return;
        }

        $this->SuhosinDisableWarning = (bool) $settings['SuhosinDisableWarning'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setLoginCookieValidityDisableWarning(array $settings): void
    {
        if (! isset($settings['LoginCookieValidityDisableWarning'])) {
            return;
        }

        $this->LoginCookieValidityDisableWarning = (bool) $settings['LoginCookieValidityDisableWarning'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setReservedWordDisableWarning(array $settings): void
    {
        if (! isset($settings['ReservedWordDisableWarning'])) {
            return;
        }

        $this->ReservedWordDisableWarning = (bool) $settings['ReservedWordDisableWarning'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTranslationWarningThreshold(array $settings): void
    {
        if (! isset($settings['TranslationWarningThreshold'])) {
            return;
        }

        $threshold = (int) $settings['TranslationWarningThreshold'];
        if ($threshold < 0) {
            return;
        }

        $this->TranslationWarningThreshold = min($threshold, 100);
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setAllowThirdPartyFraming(array $settings): void
    {
        if (! isset($settings['AllowThirdPartyFraming'])) {
            return;
        }

        if ($settings['AllowThirdPartyFraming'] === 'sameorigin') {
            $this->AllowThirdPartyFraming = 'sameorigin';

            return;
        }

        $this->AllowThirdPartyFraming = (bool) $settings['AllowThirdPartyFraming'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setBlowfishSecret(array $settings): void
    {
        if (! isset($settings['blowfish_secret'])) {
            return;
        }

        $this->blowfish_secret = (string) $settings['blowfish_secret'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setServers(array $settings): void
    {
        if (! isset($settings['Servers']) || ! is_array($settings['Servers'])) {
            $this->Servers[1] = new Server();

            return;
        }

        /**
         * @var int $key
         * @var array<string, mixed> $server
         */
        foreach ($settings['Servers'] as $key => $server) {
            $this->Servers[$key] = new Server($server);
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setServerDefault(array $settings): void
    {
        if (! isset($settings['ServerDefault'])) {
            return;
        }

        $serverDefault = (int) $settings['ServerDefault'];
        if ($serverDefault < 0) {
            return;
        }

        $this->ServerDefault = $serverDefault;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setVersionCheck(array $settings): void
    {
        if (defined('VERSION_CHECK_DEFAULT')) {
            $this->VersionCheck = (bool) VERSION_CHECK_DEFAULT;
        }

        if (! isset($settings['VersionCheck'])) {
            return;
        }

        $this->VersionCheck = (bool) $settings['VersionCheck'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setProxyUrl(array $settings): void
    {
        if (! isset($settings['ProxyUrl'])) {
            return;
        }

        $this->ProxyUrl = (string) $settings['ProxyUrl'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setProxyUser(array $settings): void
    {
        if (! isset($settings['ProxyUser'])) {
            return;
        }

        $this->ProxyUser = (string) $settings['ProxyUser'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setProxyPass(array $settings): void
    {
        if (! isset($settings['ProxyPass'])) {
            return;
        }

        $this->ProxyPass = (string) $settings['ProxyPass'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMaxDbList(array $settings): void
    {
        if (! isset($settings['MaxDbList'])) {
            return;
        }

        $maxDbList = (int) $settings['MaxDbList'];
        if ($maxDbList <= 0) {
            return;
        }

        $this->MaxDbList = $maxDbList;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMaxTableList(array $settings): void
    {
        if (! isset($settings['MaxTableList'])) {
            return;
        }

        $maxTableList = (int) $settings['MaxTableList'];
        if ($maxTableList <= 0) {
            return;
        }

        $this->MaxTableList = $maxTableList;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowHint(array $settings): void
    {
        if (! isset($settings['ShowHint'])) {
            return;
        }

        $this->ShowHint = (bool) $settings['ShowHint'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMaxCharactersInDisplayedSQL(array $settings): void
    {
        if (! isset($settings['MaxCharactersInDisplayedSQL'])) {
            return;
        }

        $maxCharactersInDisplayedSQL = (int) $settings['MaxCharactersInDisplayedSQL'];
        if ($maxCharactersInDisplayedSQL <= 0) {
            return;
        }

        $this->MaxCharactersInDisplayedSQL = $maxCharactersInDisplayedSQL;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setOBGzip(array $settings): void
    {
        if (! isset($settings['OBGzip']) || $settings['OBGzip'] === 'auto') {
            return;
        }

        $this->OBGzip = (bool) $settings['OBGzip'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setPersistentConnections(array $settings): void
    {
        if (! isset($settings['PersistentConnections'])) {
            return;
        }

        $this->PersistentConnections = (bool) $settings['PersistentConnections'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setExecTimeLimit(array $settings): void
    {
        if (! isset($settings['ExecTimeLimit'])) {
            return;
        }

        $execTimeLimit = (int) $settings['ExecTimeLimit'];
        if ($execTimeLimit < 0) {
            return;
        }

        $this->ExecTimeLimit = $execTimeLimit;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setSessionSavePath(array $settings): void
    {
        if (! isset($settings['SessionSavePath'])) {
            return;
        }

        $this->SessionSavePath = (string) $settings['SessionSavePath'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMysqlSslWarningSafeHosts(array $settings): void
    {
        if (! isset($settings['MysqlSslWarningSafeHosts']) || ! is_array($settings['MysqlSslWarningSafeHosts'])) {
            return;
        }

        $this->MysqlSslWarningSafeHosts = [];
        /** @var mixed $host */
        foreach ($settings['MysqlSslWarningSafeHosts'] as $host) {
            $safeHost = (string) $host;
            if (strlen($safeHost) === 0) {
                continue;
            }

            $this->MysqlSslWarningSafeHosts[] = $safeHost;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMemoryLimit(array $settings): void
    {
        if (! isset($settings['MemoryLimit'])) {
            return;
        }

        $this->MemoryLimit = (string) $settings['MemoryLimit'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setSkipLockedTables(array $settings): void
    {
        if (! isset($settings['SkipLockedTables'])) {
            return;
        }

        $this->SkipLockedTables = (bool) $settings['SkipLockedTables'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowSQL(array $settings): void
    {
        if (! isset($settings['ShowSQL'])) {
            return;
        }

        $this->ShowSQL = (bool) $settings['ShowSQL'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setRetainQueryBox(array $settings): void
    {
        if (! isset($settings['RetainQueryBox'])) {
            return;
        }

        $this->RetainQueryBox = (bool) $settings['RetainQueryBox'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCodemirrorEnable(array $settings): void
    {
        if (! isset($settings['CodemirrorEnable'])) {
            return;
        }

        $this->CodemirrorEnable = (bool) $settings['CodemirrorEnable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setLintEnable(array $settings): void
    {
        if (! isset($settings['LintEnable'])) {
            return;
        }

        $this->LintEnable = (bool) $settings['LintEnable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setAllowUserDropDatabase(array $settings): void
    {
        if (! isset($settings['AllowUserDropDatabase'])) {
            return;
        }

        $this->AllowUserDropDatabase = (bool) $settings['AllowUserDropDatabase'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setConfirm(array $settings): void
    {
        if (! isset($settings['Confirm'])) {
            return;
        }

        $this->Confirm = (bool) $settings['Confirm'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCookieSameSite(array $settings): void
    {
        if (
            ! isset($settings['CookieSameSite'])
            || ! in_array($settings['CookieSameSite'], ['Lax', 'Strict', 'None'], true)
        ) {
            return;
        }

        $this->CookieSameSite = $settings['CookieSameSite'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setLoginCookieRecall(array $settings): void
    {
        if (! isset($settings['LoginCookieRecall'])) {
            return;
        }

        $this->LoginCookieRecall = (bool) $settings['LoginCookieRecall'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setLoginCookieValidity(array $settings): void
    {
        if (! isset($settings['LoginCookieValidity'])) {
            return;
        }

        $loginCookieValidity = (int) $settings['LoginCookieValidity'];
        if ($loginCookieValidity <= 0) {
            return;
        }

        $this->LoginCookieValidity = $loginCookieValidity;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setLoginCookieStore(array $settings): void
    {
        if (! isset($settings['LoginCookieStore'])) {
            return;
        }

        $loginCookieStore = (int) $settings['LoginCookieStore'];
        if ($loginCookieStore < 0) {
            return;
        }

        $this->LoginCookieStore = $loginCookieStore;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setLoginCookieDeleteAll(array $settings): void
    {
        if (! isset($settings['LoginCookieDeleteAll'])) {
            return;
        }

        $this->LoginCookieDeleteAll = (bool) $settings['LoginCookieDeleteAll'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setUseDbSearch(array $settings): void
    {
        if (! isset($settings['UseDbSearch'])) {
            return;
        }

        $this->UseDbSearch = (bool) $settings['UseDbSearch'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setIgnoreMultiSubmitErrors(array $settings): void
    {
        if (! isset($settings['IgnoreMultiSubmitErrors'])) {
            return;
        }

        $this->IgnoreMultiSubmitErrors = (bool) $settings['IgnoreMultiSubmitErrors'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setAllowArbitraryServer(array $settings): void
    {
        if (! isset($settings['AllowArbitraryServer'])) {
            return;
        }

        $this->AllowArbitraryServer = (bool) $settings['AllowArbitraryServer'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setArbitraryServerRegexp(array $settings): void
    {
        if (! isset($settings['ArbitraryServerRegexp'])) {
            return;
        }

        $this->ArbitraryServerRegexp = (string) $settings['ArbitraryServerRegexp'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCaptchaMethod(array $settings): void
    {
        if (
            ! isset($settings['CaptchaMethod'])
            || ! in_array($settings['CaptchaMethod'], ['invisible', 'checkbox'], true)
        ) {
            return;
        }

        $this->CaptchaMethod = $settings['CaptchaMethod'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCaptchaApi(array $settings): void
    {
        if (! isset($settings['CaptchaApi'])) {
            return;
        }

        $this->CaptchaApi = (string) $settings['CaptchaApi'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCaptchaCsp(array $settings): void
    {
        if (! isset($settings['CaptchaCsp'])) {
            return;
        }

        $this->CaptchaCsp = (string) $settings['CaptchaCsp'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCaptchaRequestParam(array $settings): void
    {
        if (! isset($settings['CaptchaRequestParam'])) {
            return;
        }

        $this->CaptchaRequestParam = (string) $settings['CaptchaRequestParam'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCaptchaResponseParam(array $settings): void
    {
        if (! isset($settings['CaptchaResponseParam'])) {
            return;
        }

        $this->CaptchaResponseParam = (string) $settings['CaptchaResponseParam'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCaptchaLoginPublicKey(array $settings): void
    {
        if (! isset($settings['CaptchaLoginPublicKey'])) {
            return;
        }

        $this->CaptchaLoginPublicKey = (string) $settings['CaptchaLoginPublicKey'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCaptchaLoginPrivateKey(array $settings): void
    {
        if (! isset($settings['CaptchaLoginPrivateKey'])) {
            return;
        }

        $this->CaptchaLoginPrivateKey = (string) $settings['CaptchaLoginPrivateKey'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCaptchaSiteVerifyURL(array $settings): void
    {
        if (! isset($settings['CaptchaSiteVerifyURL'])) {
            return;
        }

        $this->CaptchaSiteVerifyURL = (string) $settings['CaptchaSiteVerifyURL'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setEnableDragDropImport(array $settings): void
    {
        if (! isset($settings['enable_drag_drop_import'])) {
            return;
        }

        $this->enable_drag_drop_import = (bool) $settings['enable_drag_drop_import'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowDatabasesNavigationAsTree(array $settings): void
    {
        if (! isset($settings['ShowDatabasesNavigationAsTree'])) {
            return;
        }

        $this->ShowDatabasesNavigationAsTree = (bool) $settings['ShowDatabasesNavigationAsTree'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setFirstLevelNavigationItems(array $settings): void
    {
        if (! isset($settings['FirstLevelNavigationItems'])) {
            return;
        }

        $firstLevelNavigationItems = (int) $settings['FirstLevelNavigationItems'];
        if ($firstLevelNavigationItems <= 0) {
            return;
        }

        $this->FirstLevelNavigationItems = $firstLevelNavigationItems;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMaxNavigationItems(array $settings): void
    {
        if (! isset($settings['MaxNavigationItems'])) {
            return;
        }

        $maxNavigationItems = (int) $settings['MaxNavigationItems'];
        if ($maxNavigationItems <= 0) {
            return;
        }

        $this->MaxNavigationItems = $maxNavigationItems;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeEnableGrouping(array $settings): void
    {
        if (! isset($settings['NavigationTreeEnableGrouping'])) {
            return;
        }

        $this->NavigationTreeEnableGrouping = (bool) $settings['NavigationTreeEnableGrouping'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeDbSeparator(array $settings): void
    {
        if (! isset($settings['NavigationTreeDbSeparator'])) {
            return;
        }

        $this->NavigationTreeDbSeparator = (string) $settings['NavigationTreeDbSeparator'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeTableSeparator(array $settings): void
    {
        if (! isset($settings['NavigationTreeTableSeparator'])) {
            return;
        }

        if ($settings['NavigationTreeTableSeparator'] === false) {
            $this->NavigationTreeTableSeparator = false;
        } elseif (! is_array($settings['NavigationTreeTableSeparator'])) {
            $this->NavigationTreeTableSeparator = (string) $settings['NavigationTreeTableSeparator'];
        } elseif (count($settings['NavigationTreeTableSeparator']) > 0) {
            $this->NavigationTreeTableSeparator = [];
            /** @var mixed $separator */
            foreach ($settings['NavigationTreeTableSeparator'] as $separator) {
                $this->NavigationTreeTableSeparator[] = (string) $separator;
            }
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeTableLevel(array $settings): void
    {
        if (! isset($settings['NavigationTreeTableLevel'])) {
            return;
        }

        $navigationTreeTableLevel = (int) $settings['NavigationTreeTableLevel'];
        if ($navigationTreeTableLevel <= 0) {
            return;
        }

        $this->NavigationTreeTableLevel = $navigationTreeTableLevel;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationLinkWithMainPanel(array $settings): void
    {
        if (! isset($settings['NavigationLinkWithMainPanel'])) {
            return;
        }

        $this->NavigationLinkWithMainPanel = (bool) $settings['NavigationLinkWithMainPanel'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationDisplayLogo(array $settings): void
    {
        if (! isset($settings['NavigationDisplayLogo'])) {
            return;
        }

        $this->NavigationDisplayLogo = (bool) $settings['NavigationDisplayLogo'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationLogoLink(array $settings): void
    {
        if (! isset($settings['NavigationLogoLink'])) {
            return;
        }

        $this->NavigationLogoLink = (string) $settings['NavigationLogoLink'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationLogoLinkWindow(array $settings): void
    {
        if (
            ! isset($settings['NavigationLogoLinkWindow'])
            || ! in_array($settings['NavigationLogoLinkWindow'], ['main', 'new'], true)
        ) {
            return;
        }

        $this->NavigationLogoLinkWindow = $settings['NavigationLogoLinkWindow'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNumRecentTables(array $settings): void
    {
        if (! isset($settings['NumRecentTables'])) {
            return;
        }

        $numRecentTables = (int) $settings['NumRecentTables'];
        if ($numRecentTables < 0) {
            return;
        }

        $this->NumRecentTables = $numRecentTables;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNumFavoriteTables(array $settings): void
    {
        if (! isset($settings['NumFavoriteTables'])) {
            return;
        }

        $numFavoriteTables = (int) $settings['NumFavoriteTables'];
        if ($numFavoriteTables < 0) {
            return;
        }

        $this->NumFavoriteTables = $numFavoriteTables;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeDisplayItemFilterMinimum(array $settings): void
    {
        if (! isset($settings['NavigationTreeDisplayItemFilterMinimum'])) {
            return;
        }

        $navigationTreeDisplayItemFilterMinimum = (int) $settings['NavigationTreeDisplayItemFilterMinimum'];
        if ($navigationTreeDisplayItemFilterMinimum <= 0) {
            return;
        }

        $this->NavigationTreeDisplayItemFilterMinimum = $navigationTreeDisplayItemFilterMinimum;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationDisplayServers(array $settings): void
    {
        if (! isset($settings['NavigationDisplayServers'])) {
            return;
        }

        $this->NavigationDisplayServers = (bool) $settings['NavigationDisplayServers'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDisplayServersList(array $settings): void
    {
        if (! isset($settings['DisplayServersList'])) {
            return;
        }

        $this->DisplayServersList = (bool) $settings['DisplayServersList'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeDisplayDbFilterMinimum(array $settings): void
    {
        if (! isset($settings['NavigationTreeDisplayDbFilterMinimum'])) {
            return;
        }

        $navigationTreeDisplayDbFilterMinimum = (int) $settings['NavigationTreeDisplayDbFilterMinimum'];
        if ($navigationTreeDisplayDbFilterMinimum <= 0) {
            return;
        }

        $this->NavigationTreeDisplayDbFilterMinimum = $navigationTreeDisplayDbFilterMinimum;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeDefaultTabTable(array $settings): void
    {
        if (! isset($settings['NavigationTreeDefaultTabTable'])) {
            return;
        }

        switch ($settings['NavigationTreeDefaultTabTable']) {
            case 'structure':
            case 'tbl_structure.php':
                $this->NavigationTreeDefaultTabTable = 'structure';
                break;
            case 'sql':
            case 'tbl_sql.php':
                $this->NavigationTreeDefaultTabTable = 'sql';
                break;
            case 'search':
            case 'tbl_select.php':
                $this->NavigationTreeDefaultTabTable = 'search';
                break;
            case 'insert':
            case 'tbl_change.php':
                $this->NavigationTreeDefaultTabTable = 'insert';
                break;
            case 'browse':
            case 'sql.php':
                $this->NavigationTreeDefaultTabTable = 'browse';
                break;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeDefaultTabTable2(array $settings): void
    {
        if (! isset($settings['NavigationTreeDefaultTabTable2'])) {
            return;
        }

        switch ($settings['NavigationTreeDefaultTabTable2']) {
            case 'structure':
            case 'tbl_structure.php':
                $this->NavigationTreeDefaultTabTable2 = 'structure';
                break;
            case 'sql':
            case 'tbl_sql.php':
                $this->NavigationTreeDefaultTabTable2 = 'sql';
                break;
            case 'search':
            case 'tbl_select.php':
                $this->NavigationTreeDefaultTabTable2 = 'search';
                break;
            case 'insert':
            case 'tbl_change.php':
                $this->NavigationTreeDefaultTabTable2 = 'insert';
                break;
            case 'browse':
            case 'sql.php':
                $this->NavigationTreeDefaultTabTable2 = 'browse';
                break;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeEnableExpansion(array $settings): void
    {
        if (! isset($settings['NavigationTreeEnableExpansion'])) {
            return;
        }

        $this->NavigationTreeEnableExpansion = (bool) $settings['NavigationTreeEnableExpansion'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeShowTables(array $settings): void
    {
        if (! isset($settings['NavigationTreeShowTables'])) {
            return;
        }

        $this->NavigationTreeShowTables = (bool) $settings['NavigationTreeShowTables'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeShowViews(array $settings): void
    {
        if (! isset($settings['NavigationTreeShowViews'])) {
            return;
        }

        $this->NavigationTreeShowViews = (bool) $settings['NavigationTreeShowViews'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeShowFunctions(array $settings): void
    {
        if (! isset($settings['NavigationTreeShowFunctions'])) {
            return;
        }

        $this->NavigationTreeShowFunctions = (bool) $settings['NavigationTreeShowFunctions'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeShowProcedures(array $settings): void
    {
        if (! isset($settings['NavigationTreeShowProcedures'])) {
            return;
        }

        $this->NavigationTreeShowProcedures = (bool) $settings['NavigationTreeShowProcedures'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeShowEvents(array $settings): void
    {
        if (! isset($settings['NavigationTreeShowEvents'])) {
            return;
        }

        $this->NavigationTreeShowEvents = (bool) $settings['NavigationTreeShowEvents'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationWidth(array $settings): void
    {
        if (! isset($settings['NavigationWidth'])) {
            return;
        }

        $navigationWidth = (int) $settings['NavigationWidth'];
        if ($navigationWidth < 0) {
            return;
        }

        $this->NavigationWidth = $navigationWidth;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreeAutoexpandSingleDb(array $settings): void
    {
        if (! isset($settings['NavigationTreeAutoexpandSingleDb'])) {
            return;
        }

        $this->NavigationTreeAutoexpandSingleDb = (bool) $settings['NavigationTreeAutoexpandSingleDb'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowStats(array $settings): void
    {
        if (! isset($settings['ShowStats'])) {
            return;
        }

        $this->ShowStats = (bool) $settings['ShowStats'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowPhpInfo(array $settings): void
    {
        if (! isset($settings['ShowPhpInfo'])) {
            return;
        }

        $this->ShowPhpInfo = (bool) $settings['ShowPhpInfo'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowServerInfo(array $settings): void
    {
        if (! isset($settings['ShowServerInfo'])) {
            return;
        }

        $this->ShowServerInfo = (bool) $settings['ShowServerInfo'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowChgPassword(array $settings): void
    {
        if (! isset($settings['ShowChgPassword'])) {
            return;
        }

        $this->ShowChgPassword = (bool) $settings['ShowChgPassword'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowCreateDb(array $settings): void
    {
        if (! isset($settings['ShowCreateDb'])) {
            return;
        }

        $this->ShowCreateDb = (bool) $settings['ShowCreateDb'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowDbStructureCharset(array $settings): void
    {
        if (! isset($settings['ShowDbStructureCharset'])) {
            return;
        }

        $this->ShowDbStructureCharset = (bool) $settings['ShowDbStructureCharset'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowDbStructureComment(array $settings): void
    {
        if (! isset($settings['ShowDbStructureComment'])) {
            return;
        }

        $this->ShowDbStructureComment = (bool) $settings['ShowDbStructureComment'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowDbStructureCreation(array $settings): void
    {
        if (! isset($settings['ShowDbStructureCreation'])) {
            return;
        }

        $this->ShowDbStructureCreation = (bool) $settings['ShowDbStructureCreation'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowDbStructureLastUpdate(array $settings): void
    {
        if (! isset($settings['ShowDbStructureLastUpdate'])) {
            return;
        }

        $this->ShowDbStructureLastUpdate = (bool) $settings['ShowDbStructureLastUpdate'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowDbStructureLastCheck(array $settings): void
    {
        if (! isset($settings['ShowDbStructureLastCheck'])) {
            return;
        }

        $this->ShowDbStructureLastCheck = (bool) $settings['ShowDbStructureLastCheck'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setHideStructureActions(array $settings): void
    {
        if (! isset($settings['HideStructureActions'])) {
            return;
        }

        $this->HideStructureActions = (bool) $settings['HideStructureActions'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowColumnComments(array $settings): void
    {
        if (! isset($settings['ShowColumnComments'])) {
            return;
        }

        $this->ShowColumnComments = (bool) $settings['ShowColumnComments'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTableNavigationLinksMode(array $settings): void
    {
        if (
            ! isset($settings['TableNavigationLinksMode'])
            || ! in_array($settings['TableNavigationLinksMode'], ['text', 'icons', 'both'], true)
        ) {
            return;
        }

        $this->TableNavigationLinksMode = $settings['TableNavigationLinksMode'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowAll(array $settings): void
    {
        if (! isset($settings['ShowAll'])) {
            return;
        }

        $this->ShowAll = (bool) $settings['ShowAll'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMaxRows(array $settings): void
    {
        if (! isset($settings['MaxRows'])) {
            return;
        }

        $maxRows = (int) $settings['MaxRows'];
        if ($maxRows <= 0) {
            return;
        }

        $this->MaxRows = $maxRows;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setOrder(array $settings): void
    {
        if (! isset($settings['Order']) || ! in_array($settings['Order'], ['ASC', 'DESC', 'SMART'], true)) {
            return;
        }

        $this->Order = $settings['Order'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setSaveCellsAtOnce(array $settings): void
    {
        if (! isset($settings['SaveCellsAtOnce'])) {
            return;
        }

        $this->SaveCellsAtOnce = (bool) $settings['SaveCellsAtOnce'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setGridEditing(array $settings): void
    {
        if (
            ! isset($settings['GridEditing'])
            || ! in_array($settings['GridEditing'], ['double-click', 'click', 'disabled'], true)
        ) {
            return;
        }

        $this->GridEditing = $settings['GridEditing'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setRelationalDisplay(array $settings): void
    {
        if (! isset($settings['RelationalDisplay']) || ! in_array($settings['RelationalDisplay'], ['K', 'D'], true)) {
            return;
        }

        $this->RelationalDisplay = $settings['RelationalDisplay'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setProtectBinary(array $settings): void
    {
        if (
            ! isset($settings['ProtectBinary'])
            || ! in_array($settings['ProtectBinary'], ['blob', 'noblob', 'all', false], true)
        ) {
            return;
        }

        $this->ProtectBinary = $settings['ProtectBinary'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowFunctionFields(array $settings): void
    {
        if (! isset($settings['ShowFunctionFields'])) {
            return;
        }

        $this->ShowFunctionFields = (bool) $settings['ShowFunctionFields'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowFieldTypesInDataEditView(array $settings): void
    {
        if (! isset($settings['ShowFieldTypesInDataEditView'])) {
            return;
        }

        $this->ShowFieldTypesInDataEditView = (bool) $settings['ShowFieldTypesInDataEditView'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCharEditing(array $settings): void
    {
        if (! isset($settings['CharEditing']) || ! in_array($settings['CharEditing'], ['input', 'textarea'], true)) {
            return;
        }

        $this->CharEditing = $settings['CharEditing'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMinSizeForInputField(array $settings): void
    {
        if (! isset($settings['MinSizeForInputField'])) {
            return;
        }

        $minSizeForInputField = (int) $settings['MinSizeForInputField'];
        if ($minSizeForInputField < 0) {
            return;
        }

        $this->MinSizeForInputField = $minSizeForInputField;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMaxSizeForInputField(array $settings): void
    {
        if (! isset($settings['MaxSizeForInputField'])) {
            return;
        }

        $maxSizeForInputField = (int) $settings['MaxSizeForInputField'];
        if ($maxSizeForInputField <= 0) {
            return;
        }

        $this->MaxSizeForInputField = $maxSizeForInputField;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setInsertRows(array $settings): void
    {
        if (! isset($settings['InsertRows'])) {
            return;
        }

        $insertRows = (int) $settings['InsertRows'];
        if ($insertRows <= 0) {
            return;
        }

        $this->InsertRows = $insertRows;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setForeignKeyDropdownOrder(array $settings): void
    {
        if (
            ! isset($settings['ForeignKeyDropdownOrder'])
            || ! is_array($settings['ForeignKeyDropdownOrder'])
            || ! isset($settings['ForeignKeyDropdownOrder'][0])
            || ! in_array($settings['ForeignKeyDropdownOrder'][0], ['content-id', 'id-content'], true)
        ) {
            return;
        }

        $this->ForeignKeyDropdownOrder = [0 => $settings['ForeignKeyDropdownOrder'][0]];
        if (
            ! isset($settings['ForeignKeyDropdownOrder'][1])
            || ! in_array($settings['ForeignKeyDropdownOrder'][1], ['content-id', 'id-content'], true)
        ) {
            return;
        }

        $this->ForeignKeyDropdownOrder[1] = $settings['ForeignKeyDropdownOrder'][1];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setForeignKeyMaxLimit(array $settings): void
    {
        if (! isset($settings['ForeignKeyMaxLimit'])) {
            return;
        }

        $foreignKeyMaxLimit = (int) $settings['ForeignKeyMaxLimit'];
        if ($foreignKeyMaxLimit <= 0) {
            return;
        }

        $this->ForeignKeyMaxLimit = $foreignKeyMaxLimit;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDefaultForeignKeyChecks(array $settings): void
    {
        if (
            ! isset($settings['DefaultForeignKeyChecks'])
            || ! in_array($settings['DefaultForeignKeyChecks'], ['default', 'enable', 'disable'], true)
        ) {
            return;
        }

        $this->DefaultForeignKeyChecks = $settings['DefaultForeignKeyChecks'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setZipDump(array $settings): void
    {
        if (! isset($settings['ZipDump'])) {
            return;
        }

        $this->ZipDump = (bool) $settings['ZipDump'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setGZipDump(array $settings): void
    {
        if (! isset($settings['GZipDump'])) {
            return;
        }

        $this->GZipDump = (bool) $settings['GZipDump'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setBZipDump(array $settings): void
    {
        if (! isset($settings['BZipDump'])) {
            return;
        }

        $this->BZipDump = (bool) $settings['BZipDump'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCompressOnFly(array $settings): void
    {
        if (! isset($settings['CompressOnFly'])) {
            return;
        }

        $this->CompressOnFly = (bool) $settings['CompressOnFly'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTabsMode(array $settings): void
    {
        if (! isset($settings['TabsMode']) || ! in_array($settings['TabsMode'], ['icons', 'text', 'both'], true)) {
            return;
        }

        $this->TabsMode = $settings['TabsMode'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setActionLinksMode(array $settings): void
    {
        if (
            ! isset($settings['ActionLinksMode'])
            || ! in_array($settings['ActionLinksMode'], ['icons', 'text', 'both'], true)
        ) {
            return;
        }

        $this->ActionLinksMode = $settings['ActionLinksMode'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setPropertiesNumColumns(array $settings): void
    {
        if (! isset($settings['PropertiesNumColumns'])) {
            return;
        }

        $propertiesNumColumns = (int) $settings['PropertiesNumColumns'];
        if ($propertiesNumColumns <= 0) {
            return;
        }

        $this->PropertiesNumColumns = $propertiesNumColumns;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDefaultTabServer(array $settings): void
    {
        if (! isset($settings['DefaultTabServer'])) {
            return;
        }

        switch ($settings['DefaultTabServer']) {
            case 'welcome':
            case 'index.php':
                $this->DefaultTabServer = 'welcome';
                break;
            case 'databases':
            case 'server_databases.php':
                $this->DefaultTabServer = 'databases';
                break;
            case 'status':
            case 'server_status.php':
                $this->DefaultTabServer = 'status';
                break;
            case 'variables':
            case 'server_variables.php':
                $this->DefaultTabServer = 'variables';
                break;
            case 'privileges':
            case 'server_privileges.php':
                $this->DefaultTabServer = 'privileges';
                break;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDefaultTabDatabase(array $settings): void
    {
        if (! isset($settings['DefaultTabDatabase'])) {
            return;
        }

        switch ($settings['DefaultTabDatabase']) {
            case 'structure':
            case 'db_structure.php':
                $this->DefaultTabDatabase = 'structure';
                break;
            case 'sql':
            case 'db_sql.php':
                $this->DefaultTabDatabase = 'sql';
                break;
            case 'search':
            case 'db_search.php':
                $this->DefaultTabDatabase = 'search';
                break;
            case 'operations':
            case 'db_operations.php':
                $this->DefaultTabDatabase = 'operations';
                break;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDefaultTabTable(array $settings): void
    {
        if (! isset($settings['DefaultTabTable'])) {
            return;
        }

        switch ($settings['DefaultTabTable']) {
            case 'structure':
            case 'tbl_structure.php':
                $this->DefaultTabTable = 'structure';
                break;
            case 'sql':
            case 'tbl_sql.php':
                $this->DefaultTabTable = 'sql';
                break;
            case 'search':
            case 'tbl_select.php':
                $this->DefaultTabTable = 'search';
                break;
            case 'insert':
            case 'tbl_change.php':
                $this->DefaultTabTable = 'insert';
                break;
            case 'browse':
            case 'sql.php':
                $this->DefaultTabTable = 'browse';
                break;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setRowActionType(array $settings): void
    {
        if (
            ! isset($settings['RowActionType'])
            || ! in_array($settings['RowActionType'], ['icons', 'text', 'both'], true)
        ) {
            return;
        }

        $this->RowActionType = $settings['RowActionType'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setExport(array $settings): void
    {
        if (isset($settings['Export']) && is_array($settings['Export'])) {
            $this->Export = new Export($settings['Export']);
        } else {
            $this->Export = new Export();
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setImport(array $settings): void
    {
        if (isset($settings['Import']) && is_array($settings['Import'])) {
            $this->Import = new Import($settings['Import']);
        } else {
            $this->Import = new Import();
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setSchema(array $settings): void
    {
        if (isset($settings['Schema']) && is_array($settings['Schema'])) {
            $this->Schema = new Schema($settings['Schema']);
        } else {
            $this->Schema = new Schema();
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setPDFPageSizes(array $settings): void
    {
        if (
            ! isset($settings['PDFPageSizes'])
            || ! is_array($settings['PDFPageSizes'])
            || $settings['PDFPageSizes'] === []
        ) {
            return;
        }

        $this->PDFPageSizes = [];
        /** @var mixed $pageSize */
        foreach ($settings['PDFPageSizes'] as $pageSize) {
            $this->PDFPageSizes[] = (string) $pageSize;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setPDFDefaultPageSize(array $settings): void
    {
        if (! isset($settings['PDFDefaultPageSize'])) {
            return;
        }

        $this->PDFDefaultPageSize = (string) $settings['PDFDefaultPageSize'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDefaultLang(array $settings): void
    {
        if (! isset($settings['DefaultLang'])) {
            return;
        }

        $this->DefaultLang = (string) $settings['DefaultLang'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDefaultConnectionCollation(array $settings): void
    {
        if (! isset($settings['DefaultConnectionCollation'])) {
            return;
        }

        $this->DefaultConnectionCollation = (string) $settings['DefaultConnectionCollation'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setLang(array $settings): void
    {
        if (! isset($settings['Lang'])) {
            return;
        }

        $this->Lang = (string) $settings['Lang'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setFilterLanguages(array $settings): void
    {
        if (! isset($settings['FilterLanguages'])) {
            return;
        }

        $this->FilterLanguages = (string) $settings['FilterLanguages'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setRecodingEngine(array $settings): void
    {
        if (
            ! isset($settings['RecodingEngine'])
            || ! in_array($settings['RecodingEngine'], ['auto', 'iconv', 'recode', 'mb', 'none'], true)
        ) {
            return;
        }

        $this->RecodingEngine = $settings['RecodingEngine'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setIconvExtraParams(array $settings): void
    {
        if (! isset($settings['IconvExtraParams'])) {
            return;
        }

        $this->IconvExtraParams = (string) $settings['IconvExtraParams'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setAvailableCharsets(array $settings): void
    {
        if (! isset($settings['AvailableCharsets']) || ! is_array($settings['AvailableCharsets'])) {
            return;
        }

        $this->AvailableCharsets = [];
        /** @var mixed $availableCharset */
        foreach ($settings['AvailableCharsets'] as $availableCharset) {
            $this->AvailableCharsets[] = (string) $availableCharset;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNavigationTreePointerEnable(array $settings): void
    {
        if (! isset($settings['NavigationTreePointerEnable'])) {
            return;
        }

        $this->NavigationTreePointerEnable = (bool) $settings['NavigationTreePointerEnable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setBrowsePointerEnable(array $settings): void
    {
        if (! isset($settings['BrowsePointerEnable'])) {
            return;
        }

        $this->BrowsePointerEnable = (bool) $settings['BrowsePointerEnable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setBrowseMarkerEnable(array $settings): void
    {
        if (! isset($settings['BrowseMarkerEnable'])) {
            return;
        }

        $this->BrowseMarkerEnable = (bool) $settings['BrowseMarkerEnable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTextareaCols(array $settings): void
    {
        if (! isset($settings['TextareaCols'])) {
            return;
        }

        $textareaCols = (int) $settings['TextareaCols'];
        if ($textareaCols <= 0) {
            return;
        }

        $this->TextareaCols = $textareaCols;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTextareaRows(array $settings): void
    {
        if (! isset($settings['TextareaRows'])) {
            return;
        }

        $textareaRows = (int) $settings['TextareaRows'];
        if ($textareaRows <= 0) {
            return;
        }

        $this->TextareaRows = $textareaRows;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setLongtextDoubleTextarea(array $settings): void
    {
        if (! isset($settings['LongtextDoubleTextarea'])) {
            return;
        }

        $this->LongtextDoubleTextarea = (bool) $settings['LongtextDoubleTextarea'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTextareaAutoSelect(array $settings): void
    {
        if (! isset($settings['TextareaAutoSelect'])) {
            return;
        }

        $this->TextareaAutoSelect = (bool) $settings['TextareaAutoSelect'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCharTextareaCols(array $settings): void
    {
        if (! isset($settings['CharTextareaCols'])) {
            return;
        }

        $charTextareaCols = (int) $settings['CharTextareaCols'];
        if ($charTextareaCols <= 0) {
            return;
        }

        $this->CharTextareaCols = $charTextareaCols;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCharTextareaRows(array $settings): void
    {
        if (! isset($settings['CharTextareaRows'])) {
            return;
        }

        $charTextareaRows = (int) $settings['CharTextareaRows'];
        if ($charTextareaRows <= 0) {
            return;
        }

        $this->CharTextareaRows = $charTextareaRows;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setLimitChars(array $settings): void
    {
        if (! isset($settings['LimitChars'])) {
            return;
        }

        $limitChars = (int) $settings['LimitChars'];
        if ($limitChars <= 0) {
            return;
        }

        $this->LimitChars = $limitChars;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setRowActionLinks(array $settings): void
    {
        if (
            ! isset($settings['RowActionLinks'])
            || ! in_array($settings['RowActionLinks'], ['left', 'right', 'both', 'none'], true)
        ) {
            return;
        }

        $this->RowActionLinks = $settings['RowActionLinks'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setRowActionLinksWithoutUnique(array $settings): void
    {
        if (! isset($settings['RowActionLinksWithoutUnique'])) {
            return;
        }

        $this->RowActionLinksWithoutUnique = (bool) $settings['RowActionLinksWithoutUnique'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTablePrimaryKeyOrder(array $settings): void
    {
        if (
            ! isset($settings['TablePrimaryKeyOrder'])
            || ! in_array($settings['TablePrimaryKeyOrder'], ['NONE', 'ASC', 'DESC'], true)
        ) {
            return;
        }

        $this->TablePrimaryKeyOrder = $settings['TablePrimaryKeyOrder'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setRememberSorting(array $settings): void
    {
        if (! isset($settings['RememberSorting'])) {
            return;
        }

        $this->RememberSorting = (bool) $settings['RememberSorting'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowBrowseComments(array $settings): void
    {
        if (! isset($settings['ShowBrowseComments'])) {
            return;
        }

        $this->ShowBrowseComments = (bool) $settings['ShowBrowseComments'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowPropertyComments(array $settings): void
    {
        if (! isset($settings['ShowPropertyComments'])) {
            return;
        }

        $this->ShowPropertyComments = (bool) $settings['ShowPropertyComments'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setRepeatCells(array $settings): void
    {
        if (! isset($settings['RepeatCells'])) {
            return;
        }

        $repeatCells = (int) $settings['RepeatCells'];
        if ($repeatCells < 0) {
            return;
        }

        $this->RepeatCells = $repeatCells;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setQueryHistoryDB(array $settings): void
    {
        if (! isset($settings['QueryHistoryDB'])) {
            return;
        }

        $this->QueryHistoryDB = (bool) $settings['QueryHistoryDB'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setQueryHistoryMax(array $settings): void
    {
        if (! isset($settings['QueryHistoryMax'])) {
            return;
        }

        $queryHistoryMax = (int) $settings['QueryHistoryMax'];
        if ($queryHistoryMax <= 0) {
            return;
        }

        $this->QueryHistoryMax = $queryHistoryMax;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setBrowseMIME(array $settings): void
    {
        if (! isset($settings['BrowseMIME'])) {
            return;
        }

        $this->BrowseMIME = (bool) $settings['BrowseMIME'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMaxExactCount(array $settings): void
    {
        if (! isset($settings['MaxExactCount'])) {
            return;
        }

        $maxExactCount = (int) $settings['MaxExactCount'];
        if ($maxExactCount <= 0) {
            return;
        }

        $this->MaxExactCount = $maxExactCount;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMaxExactCountViews(array $settings): void
    {
        if (! isset($settings['MaxExactCountViews'])) {
            return;
        }

        $maxExactCountViews = (int) $settings['MaxExactCountViews'];
        if ($maxExactCountViews < 0) {
            return;
        }

        $this->MaxExactCountViews = $maxExactCountViews;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setNaturalOrder(array $settings): void
    {
        if (! isset($settings['NaturalOrder'])) {
            return;
        }

        $this->NaturalOrder = (bool) $settings['NaturalOrder'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setInitialSlidersState(array $settings): void
    {
        if (
            ! isset($settings['InitialSlidersState'])
            || ! in_array($settings['InitialSlidersState'], ['open', 'closed', 'disabled'], true)
        ) {
            return;
        }

        $this->InitialSlidersState = $settings['InitialSlidersState'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setUserprefsDisallow(array $settings): void
    {
        if (! isset($settings['UserprefsDisallow']) || ! is_array($settings['UserprefsDisallow'])) {
            return;
        }

        $this->UserprefsDisallow = [];
        /** @var mixed $userPreference */
        foreach ($settings['UserprefsDisallow'] as $userPreference) {
            $this->UserprefsDisallow[] = (string) $userPreference;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setUserprefsDeveloperTab(array $settings): void
    {
        if (! isset($settings['UserprefsDeveloperTab'])) {
            return;
        }

        $this->UserprefsDeveloperTab = (bool) $settings['UserprefsDeveloperTab'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTitleTable(array $settings): void
    {
        if (! isset($settings['TitleTable'])) {
            return;
        }

        $this->TitleTable = (string) $settings['TitleTable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTitleDatabase(array $settings): void
    {
        if (! isset($settings['TitleDatabase'])) {
            return;
        }

        $this->TitleDatabase = (string) $settings['TitleDatabase'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTitleServer(array $settings): void
    {
        if (! isset($settings['TitleServer'])) {
            return;
        }

        $this->TitleServer = (string) $settings['TitleServer'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTitleDefault(array $settings): void
    {
        if (! isset($settings['TitleDefault'])) {
            return;
        }

        $this->TitleDefault = (string) $settings['TitleDefault'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setThemeManager(array $settings): void
    {
        if (! isset($settings['ThemeManager'])) {
            return;
        }

        $this->ThemeManager = (bool) $settings['ThemeManager'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setThemeDefault(array $settings): void
    {
        if (! isset($settings['ThemeDefault'])) {
            return;
        }

        $this->ThemeDefault = (string) $settings['ThemeDefault'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setThemePerServer(array $settings): void
    {
        if (! isset($settings['ThemePerServer'])) {
            return;
        }

        $this->ThemePerServer = (bool) $settings['ThemePerServer'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDefaultQueryTable(array $settings): void
    {
        if (! isset($settings['DefaultQueryTable'])) {
            return;
        }

        $this->DefaultQueryTable = (string) $settings['DefaultQueryTable'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDefaultQueryDatabase(array $settings): void
    {
        if (! isset($settings['DefaultQueryDatabase'])) {
            return;
        }

        $this->DefaultQueryDatabase = (string) $settings['DefaultQueryDatabase'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setSQLQuery(array $settings): void
    {
        if (! isset($settings['SQLQuery']) || ! is_array($settings['SQLQuery'])) {
            return;
        }

        if (isset($settings['SQLQuery']['Edit'])) {
            $this->SQLQuery['Edit'] = (bool) $settings['SQLQuery']['Edit'];
        }

        if (isset($settings['SQLQuery']['Explain'])) {
            $this->SQLQuery['Explain'] = (bool) $settings['SQLQuery']['Explain'];
        }

        if (isset($settings['SQLQuery']['ShowAsPHP'])) {
            $this->SQLQuery['ShowAsPHP'] = (bool) $settings['SQLQuery']['ShowAsPHP'];
        }

        if (! isset($settings['SQLQuery']['Refresh'])) {
            return;
        }

        $this->SQLQuery['Refresh'] = (bool) $settings['SQLQuery']['Refresh'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setEnableAutocompleteForTablesAndColumns(array $settings): void
    {
        if (! isset($settings['EnableAutocompleteForTablesAndColumns'])) {
            return;
        }

        $this->EnableAutocompleteForTablesAndColumns = (bool) $settings['EnableAutocompleteForTablesAndColumns'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setUploadDir(array $settings): void
    {
        if (! isset($settings['UploadDir'])) {
            return;
        }

        $this->UploadDir = (string) $settings['UploadDir'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setSaveDir(array $settings): void
    {
        if (! isset($settings['SaveDir'])) {
            return;
        }

        $this->SaveDir = (string) $settings['SaveDir'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTempDir(array $settings): void
    {
        if (defined('TEMP_DIR')) {
            $this->TempDir = TEMP_DIR;
        }

        if (! isset($settings['TempDir'])) {
            return;
        }

        $this->TempDir = (string) $settings['TempDir'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setGD2Available(array $settings): void
    {
        if (! isset($settings['GD2Available']) || ! in_array($settings['GD2Available'], ['auto', 'yes', 'no'], true)) {
            return;
        }

        $this->GD2Available = $settings['GD2Available'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setTrustedProxies(array $settings): void
    {
        if (! isset($settings['TrustedProxies']) || ! is_array($settings['TrustedProxies'])) {
            return;
        }

        $this->TrustedProxies = [];
        /**
         * @var int|string $proxy
         * @var mixed $header
         */
        foreach ($settings['TrustedProxies'] as $proxy => $header) {
            $this->TrustedProxies[(string) $proxy] = (string) $header;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCheckConfigurationPermissions(array $settings): void
    {
        if (! isset($settings['CheckConfigurationPermissions'])) {
            return;
        }

        $this->CheckConfigurationPermissions = (bool) $settings['CheckConfigurationPermissions'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setLinkLengthLimit(array $settings): void
    {
        if (! isset($settings['LinkLengthLimit'])) {
            return;
        }

        $linkLengthLimit = (int) $settings['LinkLengthLimit'];
        if ($linkLengthLimit <= 0) {
            return;
        }

        $this->LinkLengthLimit = $linkLengthLimit;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setCSPAllow(array $settings): void
    {
        if (! isset($settings['CSPAllow'])) {
            return;
        }

        $this->CSPAllow = (string) $settings['CSPAllow'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDisableMultiTableMaintenance(array $settings): void
    {
        if (! isset($settings['DisableMultiTableMaintenance'])) {
            return;
        }

        $this->DisableMultiTableMaintenance = (bool) $settings['DisableMultiTableMaintenance'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setSendErrorReports(array $settings): void
    {
        if (
            ! isset($settings['SendErrorReports'])
            || ! in_array($settings['SendErrorReports'], ['ask', 'always', 'never'], true)
        ) {
            return;
        }

        $this->SendErrorReports = $settings['SendErrorReports'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setConsoleEnterExecutes(array $settings): void
    {
        if (! isset($settings['ConsoleEnterExecutes'])) {
            return;
        }

        $this->ConsoleEnterExecutes = (bool) $settings['ConsoleEnterExecutes'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setZeroConf(array $settings): void
    {
        if (! isset($settings['ZeroConf'])) {
            return;
        }

        $this->ZeroConf = (bool) $settings['ZeroConf'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDBG(array $settings): void
    {
        if (! isset($settings['DBG']) || ! is_array($settings['DBG'])) {
            return;
        }

        if (isset($settings['DBG']['sql'])) {
            $this->DBG['sql'] = (bool) $settings['DBG']['sql'];
        }

        if (isset($settings['DBG']['sqllog'])) {
            $this->DBG['sqllog'] = (bool) $settings['DBG']['sqllog'];
        }

        if (isset($settings['DBG']['demo'])) {
            $this->DBG['demo'] = (bool) $settings['DBG']['demo'];
        }

        if (! isset($settings['DBG']['simple2fa'])) {
            return;
        }

        $this->DBG['simple2fa'] = (bool) $settings['DBG']['simple2fa'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setEnvironment(array $settings): void
    {
        if (
            ! isset($settings['environment'])
            || ! in_array($settings['environment'], ['production', 'development'], true)
        ) {
            return;
        }

        $this->environment = $settings['environment'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDefaultFunctions(array $settings): void
    {
        if (! isset($settings['DefaultFunctions']) || ! is_array($settings['DefaultFunctions'])) {
            return;
        }

        $this->DefaultFunctions = [];
        /**
         * @var int|string $key
         * @var mixed $value
         */
        foreach ($settings['DefaultFunctions'] as $key => $value) {
            $this->DefaultFunctions[(string) $key] = (string) $value;
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMaxRowPlotLimit(array $settings): void
    {
        if (! isset($settings['maxRowPlotLimit'])) {
            return;
        }

        $maxRowPlotLimit = (int) $settings['maxRowPlotLimit'];
        if ($maxRowPlotLimit <= 0) {
            return;
        }

        $this->maxRowPlotLimit = $maxRowPlotLimit;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setShowGitRevision(array $settings): void
    {
        if (! isset($settings['ShowGitRevision'])) {
            return;
        }

        $this->ShowGitRevision = (bool) $settings['ShowGitRevision'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMysqlMinVersion(array $settings): void
    {
        if (! isset($settings['MysqlMinVersion']) || ! is_array($settings['MysqlMinVersion'])) {
            return;
        }

        if (isset($settings['MysqlMinVersion']['internal'])) {
            $this->MysqlMinVersion['internal'] = (int) $settings['MysqlMinVersion']['internal'];
        }

        if (! isset($settings['MysqlMinVersion']['human'])) {
            return;
        }

        $this->MysqlMinVersion['human'] = (string) $settings['MysqlMinVersion']['human'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDisableShortcutKeys(array $settings): void
    {
        if (! isset($settings['DisableShortcutKeys'])) {
            return;
        }

        $this->DisableShortcutKeys = (bool) $settings['DisableShortcutKeys'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setConsole(array $settings): void
    {
        if (! isset($settings['Console']) || ! is_array($settings['Console'])) {
            return;
        }

        if (isset($settings['Console']['StartHistory'])) {
            $this->Console['StartHistory'] = (bool) $settings['Console']['StartHistory'];
        }

        if (isset($settings['Console']['AlwaysExpand'])) {
            $this->Console['AlwaysExpand'] = (bool) $settings['Console']['AlwaysExpand'];
        }

        if (isset($settings['Console']['CurrentQuery'])) {
            $this->Console['CurrentQuery'] = (bool) $settings['Console']['CurrentQuery'];
        }

        if (isset($settings['Console']['EnterExecutes'])) {
            $this->Console['EnterExecutes'] = (bool) $settings['Console']['EnterExecutes'];
        }

        if (isset($settings['Console']['DarkTheme'])) {
            $this->Console['DarkTheme'] = (bool) $settings['Console']['DarkTheme'];
        }

        if (
            isset($settings['Console']['Mode'])
            && in_array($settings['Console']['Mode'], ['info', 'show', 'collapse'], true)
        ) {
            $this->Console['Mode'] = $settings['Console']['Mode'];
        }

        if (isset($settings['Console']['Height'])) {
            $height = (int) $settings['Console']['Height'];
            if ($height > 0) {
                $this->Console['Height'] = $height;
            }
        }

        if (isset($settings['Console']['GroupQueries'])) {
            $this->Console['GroupQueries'] = (bool) $settings['Console']['GroupQueries'];
        }

        if (
            isset($settings['Console']['OrderBy'])
            && in_array($settings['Console']['OrderBy'], ['exec', 'time', 'count'], true)
        ) {
            $this->Console['OrderBy'] = $settings['Console']['OrderBy'];
        }

        if (
            ! isset($settings['Console']['Order'])
            || ! in_array($settings['Console']['Order'], ['asc', 'desc'], true)
        ) {
            return;
        }

        $this->Console['Order'] = $settings['Console']['Order'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setDefaultTransformations(array $settings): void
    {
        if (isset($settings['DefaultTransformations']) && is_array($settings['DefaultTransformations'])) {
            $this->DefaultTransformations = new Transformations($settings['DefaultTransformations']);
        } else {
            $this->DefaultTransformations = new Transformations();
        }
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setFirstDayOfCalendar(array $settings): void
    {
        if (! isset($settings['FirstDayOfCalendar'])) {
            return;
        }

        $firstDayOfCalendar = (int) $settings['FirstDayOfCalendar'];
        if ($firstDayOfCalendar < 0 || $firstDayOfCalendar > 7) {
            return;
        }

        $this->FirstDayOfCalendar = $firstDayOfCalendar;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setIsSetup(array $settings): void
    {
        if (! isset($settings['is_setup'])) {
            return;
        }

        $this->is_setup = (bool) $settings['is_setup'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setIsWindows(array $settings): void
    {
        if (! isset($settings['PMA_IS_WINDOWS'])) {
            return;
        }

        $this->PMA_IS_WINDOWS = (bool) $settings['PMA_IS_WINDOWS'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setIsIIS(array $settings): void
    {
        if (! isset($settings['PMA_IS_IIS'])) {
            return;
        }

        $this->PMA_IS_IIS = (int) (bool) $settings['PMA_IS_IIS'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setIsGD2(array $settings): void
    {
        if (! isset($settings['PMA_IS_GD2'])) {
            return;
        }

        $this->PMA_IS_GD2 = (int) (bool) $settings['PMA_IS_GD2'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setUserOperatingSystem(array $settings): void
    {
        if (! isset($settings['PMA_USR_OS'])) {
            return;
        }

        $this->PMA_USR_OS = (string) $settings['PMA_USR_OS'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setUserBrowserVersion(array $settings): void
    {
        if (! isset($settings['PMA_USR_BROWSER_VER'])) {
            return;
        }

        if (is_int($settings['PMA_USR_BROWSER_VER'])) {
            $this->PMA_USR_BROWSER_VER = $settings['PMA_USR_BROWSER_VER'];

            return;
        }

        $this->PMA_USR_BROWSER_VER = (string) $settings['PMA_USR_BROWSER_VER'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setUserBrowserAgent(array $settings): void
    {
        if (! isset($settings['PMA_USR_BROWSER_AGENT'])) {
            return;
        }

        $this->PMA_USR_BROWSER_AGENT = (string) $settings['PMA_USR_BROWSER_AGENT'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setEnableUpload(array $settings): void
    {
        if (! isset($settings['enable_upload'])) {
            return;
        }

        $this->enable_upload = (bool) $settings['enable_upload'];
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setMaxUploadSize(array $settings): void
    {
        if (! isset($settings['max_upload_size'])) {
            return;
        }

        $maxUploadSize = (int) $settings['max_upload_size'];
        if ($maxUploadSize <= 0) {
            return;
        }

        $this->max_upload_size = $maxUploadSize;
    }

    /**
     * @param array<int|string, mixed> $settings
     *
     * @psalm-external-mutation-free
     */
    private function setServer(array $settings): void
    {
        if (isset($settings['Server']) && is_array($settings['Server'])) {
            $this->Server = new Server($settings['Server']);
        } else {
            $this->Server = new Server();
        }
    }
}
