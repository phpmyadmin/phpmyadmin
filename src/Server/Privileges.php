<?php
/**
 * set of functions with the Privileges section in pma
 */

declare(strict_types=1);

namespace PhpMyAdmin\Server;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\ConfigurableMenusFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Current;
use PhpMyAdmin\Database\Routines;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\Util;

use function __;
use function array_fill_keys;
use function array_filter;
use function array_intersect;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function ksort;
use function max;
use function mb_strpos;
use function mb_strrpos;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function range;
use function sprintf;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strnatcasecmp;
use function strtr;
use function trim;
use function uksort;

/**
 * Privileges class
 */
class Privileges
{
    public function __construct(
        public Template $template,
        public DatabaseInterface $dbi,
        public Relation $relation,
        private RelationCleanup $relationCleanup,
        private Plugins $plugins,
        private readonly Config $config,
    ) {
    }

    /**
     * Escapes wildcard in a database+table specification
     * before using it in a GRANT statement.
     *
     * Escaping a wildcard character in a GRANT is only accepted at the global
     * or database level, not at table level; this is why I remove
     * the escaping character. Internally, in mysql.tables_priv.Db there are
     * no escaping (for example test_db) but in mysql.db you'll see test\_db
     * for a db-specific privilege.
     *
     * @param string $dbname    Database name
     * @param string $tablename Table name
     *
     * @return string the escaped (if necessary) database.table
     */
    public function wildcardEscapeForGrant(string $dbname, string $tablename): string
    {
        if ($dbname === '') {
            return '*.*';
        }

        if ($tablename === '') {
            return Util::backquote($dbname) . '.*';
        }

        return Util::backquote(
            $this->unescapeGrantWildcards($dbname),
        ) . '.' . Util::backquote($tablename);
    }

    /**
     * Add slashes before "_" and "%" characters for using them in MySQL
     * database, table and field names.
     * Note: This function does not escape backslashes!
     *
     * @param string $name the string to escape
     *
     * @return string the escaped string
     */
    public function escapeGrantWildcards(string $name): string
    {
        return strtr($name, ['_' => '\\_', '%' => '\\%']);
    }

    /**
     * removes slashes before "_" and "%" characters
     * Note: This function does not unescape backslashes!
     *
     * @param string $name the string to escape
     *
     * @return string the escaped string
     */
    public function unescapeGrantWildcards(string $name): string
    {
        return strtr($name, ['\\_' => '_', '\\%' => '%']);
    }

    /**
     * Generates a condition on the user name
     *
     * @param string|null $initial the user's initial
     *
     * @return string   the generated condition
     */
    public function rangeOfUsers(string|null $initial = null): string
    {
        if ($initial === null) {
            return '';
        }

        if ($initial === '') {
            return "WHERE `User` = ''";
        }

        $like = $this->dbi->escapeMysqlWildcards($initial) . '%';

        // strtolower() is used because the User field
        // might be BINARY, so LIKE would be case sensitive
        return 'WHERE `User` LIKE '
            . $this->dbi->quoteString($like)
            . ' OR `User` LIKE '
            . $this->dbi->quoteString(mb_strtolower($like));
    }

    /**
     * Parses privileges into an array, it modifies the array
     *
     * @param mixed[] $row Results row from
     */
    public function fillInTablePrivileges(array &$row): void
    {
        $row1 = $this->dbi->fetchSingleRow('SHOW COLUMNS FROM `mysql`.`tables_priv` LIKE \'Table_priv\';');
        // note: in MySQL 5.0.3 we get "Create View', 'Show view';
        // the View for Create is spelled with uppercase V
        // the view for Show is spelled with lowercase v
        // and there is a space between the words

        $avGrants = explode(
            '\',\'',
            mb_substr(
                $row1['Type'],
                mb_strpos($row1['Type'], '(') + 2,
                mb_strpos($row1['Type'], ')')
                - mb_strpos($row1['Type'], '(') - 3,
            ),
        );

        $usersGrants = explode(',', $row['Table_priv']);

        foreach ($avGrants as $currentGrant) {
            $row[$currentGrant . '_priv'] = in_array($currentGrant, $usersGrants, true) ? 'Y' : 'N';
        }

        unset($row['Table_priv']);
    }

    /**
     * Extracts the privilege information of a priv table row
     *
     * @param mixed[]|null $row        the row
     * @param bool         $enableHTML add <dfn> tag with tooltips
     * @param bool         $tablePrivs whether row contains table privileges
     *
     * @return string[]
     *
     * @global resource $user_link the database connection
     */
    public function extractPrivInfo(array|null $row = null, bool $enableHTML = false, bool $tablePrivs = false): array
    {
        $grants = $tablePrivs ? $this->getTableGrantsArray() : $this->getGrantsArray();

        if ($row !== null && isset($row['Table_priv'])) {
            $this->fillInTablePrivileges($row);
        }

        $privs = [];
        $allPrivileges = true;
        foreach ($grants as $currentGrant) {
            if ($row !== null && isset($row[$currentGrant[0]])) {
                $grantValue = $row[$currentGrant[0]];
            } elseif ($row === null && isset($_POST[$currentGrant[0]])) {
                $grantValue = $_POST[$currentGrant[0]];
            } else {
                continue;
            }

            if (
                ($grantValue === 'Y')
                || ($row === null
                && is_array($grantValue)
                && count($grantValue) == $_REQUEST['column_count']
                && empty($_POST[$currentGrant[0] . '_none']))
            ) {
                if ($enableHTML) {
                    $privs[] = '<dfn title="' . $currentGrant[2] . '">'
                    . $currentGrant[1] . '</dfn>';
                } else {
                    $privs[] = $currentGrant[1];
                }
            } elseif (
                is_array($grantValue) && $grantValue !== []
                && empty($_POST[$currentGrant[0] . '_none'])
            ) {
                // Required for proper escaping of ` (backtick) in a column name
                $grantCols = array_map(
                    Util::backquote(...),
                    $grantValue,
                );

                if ($enableHTML) {
                    $privs[] = '<dfn title="' . $currentGrant[2] . '">'
                        . $currentGrant[1] . '</dfn>'
                        . ' (' . implode(', ', $grantCols) . ')';
                } else {
                    $privs[] = $currentGrant[1]
                        . ' (' . implode(', ', $grantCols) . ')';
                }
            } else {
                $allPrivileges = false;
            }
        }

        if ($privs === []) {
            if ($enableHTML) {
                $privs[] = '<dfn title="' . __('No privileges.') . '">USAGE</dfn>';
            } else {
                $privs[] = 'USAGE';
            }
        } elseif ($allPrivileges && (! isset($_POST['grant_count']) || count($privs) == $_POST['grant_count'])) {
            if ($enableHTML) {
                $privs = ['<dfn title="' . __('Includes all privileges except GRANT.') . '">ALL PRIVILEGES</dfn>'];
            } else {
                $privs = ['ALL PRIVILEGES'];
            }
        }

        return $privs;
    }

    /**
     * Returns an array of table grants and their descriptions
     *
     * @return string[][] array of table grants
     */
    public function getTableGrantsArray(): array
    {
        return [
            ['Delete', 'DELETE', __('Allows deleting data.')],
            ['Create', 'CREATE', __('Allows creating new tables.')],
            ['Drop', 'DROP', __('Allows dropping tables.')],
            ['Index', 'INDEX', __('Allows creating and dropping indexes.')],
            ['Alter', 'ALTER', __('Allows altering the structure of existing tables.')],
            ['Create View', 'CREATE_VIEW', __('Allows creating new views.')],
            ['Show view', 'SHOW_VIEW', __('Allows performing SHOW CREATE VIEW queries.')],
            ['Trigger', 'TRIGGER', __('Allows creating and dropping triggers.')],
        ];
    }

    /**
     * Get the grants array which contains all the privilege types
     * and relevant grant messages
     *
     * @return string[][]
     */
    public function getGrantsArray(): array
    {
        return [
            ['Select_priv', 'SELECT', __('Allows reading data.')],
            ['Insert_priv', 'INSERT', __('Allows inserting and replacing data.')],
            ['Update_priv', 'UPDATE', __('Allows changing data.')],
            ['Delete_priv', 'DELETE', __('Allows deleting data.')],
            ['Create_priv', 'CREATE', __('Allows creating new databases and tables.')],
            ['Drop_priv', 'DROP', __('Allows dropping databases and tables.')],
            ['Reload_priv', 'RELOAD', __('Allows reloading server settings and flushing the server\'s caches.')],
            ['Shutdown_priv', 'SHUTDOWN', __('Allows shutting down the server.')],
            ['Process_priv', 'PROCESS', __('Allows viewing processes of all users.')],
            ['File_priv', 'FILE', __('Allows importing data from and exporting data into files.')],
            ['References_priv', 'REFERENCES', __('Has no effect in this MySQL version.')],
            ['Index_priv', 'INDEX', __('Allows creating and dropping indexes.')],
            ['Alter_priv', 'ALTER', __('Allows altering the structure of existing tables.')],
            ['Show_db_priv', 'SHOW DATABASES', __('Gives access to the complete list of databases.')],
            [
                'Super_priv',
                'SUPER',
                __(
                    'Allows connecting, even if maximum number of connections '
                    . 'is reached; required for most administrative operations '
                    . 'like setting global variables or killing threads of other users.',
                ),
            ],
            ['Create_tmp_table_priv', 'CREATE TEMPORARY TABLES', __('Allows creating temporary tables.')],
            ['Lock_tables_priv', 'LOCK TABLES', __('Allows locking tables for the current thread.')],
            ['Repl_slave_priv', 'REPLICATION SLAVE', __('Needed for the replication replicas.')],
            [
                'Repl_client_priv',
                'REPLICATION CLIENT',
                __('Allows the user to ask where the replicas / primaries are.'),
            ],
            ['Create_view_priv', 'CREATE VIEW', __('Allows creating new views.')],
            ['Event_priv', 'EVENT', __('Allows to set up events for the event scheduler.')],
            ['Trigger_priv', 'TRIGGER', __('Allows creating and dropping triggers.')],
            // for table privs:
            ['Create View_priv', 'CREATE VIEW', __('Allows creating new views.')],
            ['Show_view_priv', 'SHOW VIEW', __('Allows performing SHOW CREATE VIEW queries.')],
            // for table privs:
            ['Show view_priv', 'SHOW VIEW', __('Allows performing SHOW CREATE VIEW queries.')],
            [
                'Delete_history_priv',
                'DELETE HISTORY',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                /* l10n: https://mariadb.com/kb/en/library/grant/#table-privileges "Remove historical rows from a table using the DELETE HISTORY statement" */
                __('Allows deleting historical rows.'),
            ],
            [
                // This was finally removed in the following MariaDB versions
                // @see https://jira.mariadb.org/browse/MDEV-20382
                'Delete versioning rows_priv',
                'DELETE HISTORY',
                // phpcs:ignore Generic.Files.LineLength.TooLong
                /* l10n: https://mariadb.com/kb/en/library/grant/#table-privileges "Remove historical rows from a table using the DELETE HISTORY statement" */
                __('Allows deleting historical rows.'),
            ],
            ['Create_routine_priv', 'CREATE ROUTINE', __('Allows creating stored routines.')],
            ['Alter_routine_priv', 'ALTER ROUTINE', __('Allows altering and dropping stored routines.')],
            ['Create_user_priv', 'CREATE USER', __('Allows creating, dropping and renaming user accounts.')],
            ['Execute_priv', 'EXECUTE', __('Allows executing stored routines.')],
        ];
    }

    /**
     * Get sql query for display privileges table
     *
     * @param string $db       the database
     * @param string $table    the table
     * @param string $username username for database connection
     * @param string $hostname hostname for database connection
     *
     * @return string sql query
     */
    public function getSqlQueryForDisplayPrivTable(
        string $db,
        string $table,
        string $username,
        string $hostname,
    ): string {
        if ($db === '*') {
            return 'SELECT * FROM `mysql`.`user`'
                . $this->getUserHostCondition($username, $hostname) . ';';
        }

        if ($table === '*') {
            return 'SELECT * FROM `mysql`.`db`'
                . $this->getUserHostCondition($username, $hostname)
                . ' AND `Db` = ' . $this->dbi->quoteString($db);
        }

        return 'SELECT `Table_priv`'
            . ' FROM `mysql`.`tables_priv`'
            . $this->getUserHostCondition($username, $hostname)
            . ' AND `Db` = ' . $this->dbi->quoteString($this->unescapeGrantWildcards($db))
            . ' AND `Table_name` = ' . $this->dbi->quoteString($table) . ';';
    }

    /**
     * Sets the user group from request values
     *
     * @param string $username  username
     * @param string $userGroup user group to set
     */
    public function setUserGroup(string $username, string $userGroup): void
    {
        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;
        if ($configurableMenusFeature === null) {
            return;
        }

        $userTable = Util::backquote($configurableMenusFeature->database)
            . '.' . Util::backquote($configurableMenusFeature->users);

        $sqlQuery = 'SELECT `usergroup` FROM ' . $userTable
            . ' WHERE `username` = ' . $this->dbi->quoteString($username, ConnectionType::ControlUser);
        $oldUserGroup = $this->dbi->fetchValue($sqlQuery, 0, ConnectionType::ControlUser);

        if ($oldUserGroup === false) {
            $updQuery = 'INSERT INTO ' . $userTable . '(`username`, `usergroup`)'
                . ' VALUES (' . $this->dbi->quoteString($username, ConnectionType::ControlUser) . ', '
                . $this->dbi->quoteString($userGroup, ConnectionType::ControlUser) . ')';
        } elseif ($userGroup === '') {
            $updQuery = 'DELETE FROM ' . $userTable
                . ' WHERE `username`=' . $this->dbi->quoteString($username, ConnectionType::ControlUser);
        } elseif ($oldUserGroup != $userGroup) {
            $updQuery = 'UPDATE ' . $userTable
                . ' SET `usergroup`=' . $this->dbi->quoteString($userGroup, ConnectionType::ControlUser)
                . ' WHERE `username`=' . $this->dbi->quoteString($username, ConnectionType::ControlUser);
        } else {
            return;
        }

        $this->dbi->queryAsControlUser($updQuery);
    }

    /**
     * Displays the privileges form table
     *
     * @param string $db     the database
     * @param string $table  the table
     * @param bool   $submit whether to display the submit button or not
     *
     * @return string html snippet
     *
     * @global array     $cfg         the phpMyAdmin configuration
     * @global resource  $user_link   the database connection
     */
    public function getHtmlToDisplayPrivilegesTable(
        string $db = '*',
        string $table = '*',
        bool $submit = true,
    ): string {
        if ($db === '*') {
            $table = '*';
        }

        $username = '';
        $hostname = '';
        $row = [];
        if (isset($GLOBALS['username'])) {
            $username = $GLOBALS['username'];
            $hostname = $GLOBALS['hostname'];
            $sqlQuery = $this->getSqlQueryForDisplayPrivTable($db, $table, $username, $hostname);
            $row = $this->dbi->fetchSingleRow($sqlQuery);
        }

        if ($row === null || $row === []) {
            if ($table === '*' && $this->dbi->isSuperUser()) {
                $row = [];
                $sqlQuery = 'SHOW COLUMNS FROM `mysql`.' . ($db === '*' ? '`user`' : '`db`') . ';';

                $res = $this->dbi->query($sqlQuery);
                while ($row1 = $res->fetchRow()) {
                    if (str_starts_with($row1[0], 'max_')) {
                        $row[$row1[0]] = 0;
                    } elseif (str_starts_with($row1[0], 'x509_') || str_starts_with($row1[0], 'ssl_')) {
                        $row[$row1[0]] = '';
                    } else {
                        $row[$row1[0]] = 'N';
                    }
                }
            } elseif ($table === '*') {
                $row = [];
            } else {
                $row = ['Table_priv' => ''];
            }
        }

        $columns = [];
        if (isset($row['Table_priv'])) {
            $this->fillInTablePrivileges($row);

            // get columns
            $res = $this->dbi->tryQuery(
                'SHOW COLUMNS FROM '
                . Util::backquote(
                    $this->unescapeGrantWildcards($db),
                )
                . '.' . Util::backquote($table) . ';',
            );
            if ($res) {
                while ($row1 = $res->fetchRow()) {
                    $columns[$row1[0]] = [
                        'Select' => false,
                        'Insert' => false,
                        'Update' => false,
                        'References' => false,
                    ];
                }
            }
        }

        if ($columns !== []) {
            $res = $this->dbi->query(
                'SELECT `Column_name`, `Column_priv`'
                . ' FROM `mysql`.`columns_priv`'
                . $this->getUserHostCondition($username, $hostname)
                . ' AND `Db` = ' . $this->dbi->quoteString($this->unescapeGrantWildcards($db))
                . ' AND `Table_name` = ' . $this->dbi->quoteString($table) . ';',
            );

            while ($row1 = $res->fetchRow()) {
                $row1[1] = explode(',', $row1[1]);
                foreach ($row1[1] as $current) {
                    $columns[$row1[0]][$current] = true;
                }
            }
        }

        return $this->template->render('server/privileges/privileges_table', [
            'is_global' => $db === '*',
            'is_database' => $table === '*',
            'row' => $row,
            'columns' => $columns,
            'has_submit' => $submit,
            'supports_references_privilege' => Compatibility::supportsReferencesPrivilege($this->dbi),
            'is_mariadb' => $this->dbi->isMariaDB(),
        ]);
    }

    /**
     * Get the HTML snippet for routine specific privileges
     *
     * @param string $username  username for database connection
     * @param string $hostname  hostname for database connection
     * @param string $db        the database
     * @param string $routine   the routine
     * @param string $urlDbname url encoded db name
     */
    public function getHtmlForRoutineSpecificPrivileges(
        string $username,
        string $hostname,
        string $db,
        string $routine,
        string $urlDbname,
    ): string {
        $privileges = $this->getRoutinePrivileges($username, $hostname, $db, $routine);

        return $this->template->render('server/privileges/edit_routine_privileges', [
            'username' => $username,
            'hostname' => $hostname,
            'database' => $db,
            'routine' => $routine,
            'privileges' => $privileges,
            'dbname' => $urlDbname,
            'current_user' => $this->dbi->getCurrentUser(),
        ]);
    }

    /**
     * Displays the fields used by the "new user" form as well as the
     * "change login information / copy user" form.
     *
     * @param string|null $user User name
     * @param string|null $host Host name
     *
     * @return string  a HTML snippet
     */
    public function getHtmlForLoginInformationFields(
        string|null $user = null,
        string|null $host = null,
    ): string {
        $GLOBALS['pred_username'] ??= null;
        $GLOBALS['pred_hostname'] ??= null;
        $GLOBALS['username'] ??= null;
        $GLOBALS['hostname'] ??= null;
        $GLOBALS['new_username'] ??= null;

        [$usernameLength, $hostnameLength] = $this->getUsernameAndHostnameLength();

        if (isset($GLOBALS['username']) && $GLOBALS['username'] === '') {
            $GLOBALS['pred_username'] = 'any';
        }

        $currentUser = (string) $this->dbi->fetchValue('SELECT USER();');
        $thisHost = null;
        if ($currentUser !== '') {
            $thisHost = str_replace(
                '\'',
                '',
                mb_substr(
                    $currentUser,
                    mb_strrpos($currentUser, '@') + 1,
                ),
            );
        }

        if (! isset($GLOBALS['pred_hostname']) && isset($GLOBALS['hostname'])) {
            $GLOBALS['pred_hostname'] = match (mb_strtolower($GLOBALS['hostname'])) {
                'localhost', '127.0.0.1' => 'localhost',
                '%' => 'any',
                default => 'userdefined',
            };
        }

        $serverVersion = $this->dbi->getVersion();
        if ($user !== null && $host !== null) {
            $authPlugin = $this->getCurrentAuthenticationPlugin($user, $host);
        } else {
            $authPlugin = $this->getDefaultAuthenticationPlugin();
        }

        $isNew = (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50507)
            || (Compatibility::isMariaDb() && $serverVersion >= 50200);

        $activeAuthPlugins = ['mysql_native_password' => __('Native MySQL authentication')];
        if ($isNew) {
            $activeAuthPlugins = $this->plugins->getAuthentication();
            if (isset($activeAuthPlugins['mysql_old_password'])) {
                unset($activeAuthPlugins['mysql_old_password']);
            }
        }

        return $this->template->render('server/privileges/login_information_fields', [
            'pred_username' => $GLOBALS['pred_username'] ?? null,
            'pred_hostname' => $GLOBALS['pred_hostname'] ?? null,
            'username_length' => $usernameLength,
            'hostname_length' => $hostnameLength,
            'username' => $GLOBALS['username'] ?? null,
            'new_username' => $GLOBALS['new_username'] ?? null,
            'hostname' => $GLOBALS['hostname'] ?? null,
            'this_host' => $thisHost,
            'is_change' => $user !== null && $host !== null,
            'auth_plugin' => $authPlugin,
            'active_auth_plugins' => $activeAuthPlugins,
            'is_new' => $isNew,
        ]);
    }

    /**
     * Get username and hostname length
     *
     * @return mixed[] username length and hostname length
     */
    public function getUsernameAndHostnameLength(): array
    {
        /* Fallback values */
        $usernameLength = 16;
        $hostnameLength = 41;

        /* Try to get real lengths from the database */
        $fieldsInfo = $this->dbi->fetchResult(
            'SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH '
            . 'FROM information_schema.columns '
            . "WHERE table_schema = 'mysql' AND table_name = 'user' "
            . "AND COLUMN_NAME IN ('User', 'Host')",
        );
        foreach ($fieldsInfo as $val) {
            if ($val['COLUMN_NAME'] === 'User') {
                $usernameLength = $val['CHARACTER_MAXIMUM_LENGTH'];
            } elseif ($val['COLUMN_NAME'] === 'Host') {
                $hostnameLength = $val['CHARACTER_MAXIMUM_LENGTH'];
            }
        }

        return [$usernameLength, $hostnameLength];
    }

    /**
     * Get current authentication plugin in use for a user
     *
     * @param string $username User name
     * @param string $hostname Host name
     *
     * @return string authentication plugin in use
     */
    public function getCurrentAuthenticationPlugin(
        string $username,
        string $hostname,
    ): string {
        $plugin = $this->dbi->fetchValue(
            'SELECT `plugin` FROM `mysql`.`user`' . $this->getUserHostCondition($username, $hostname) . ' LIMIT 1',
        );

        // Table 'mysql'.'user' may not exist for some previous
        // versions of MySQL - in that case consider fallback value
        return is_string($plugin) ? $plugin : 'mysql_native_password';
    }

    /**
     * Get the default authentication plugin
     *
     * @return string|null authentication plugin
     */
    public function getDefaultAuthenticationPlugin(): string|null
    {
        if ($this->dbi->getVersion() >= 50702) {
            $plugin = $this->dbi->fetchValue('SELECT @@default_authentication_plugin');

            return is_string($plugin) ? $plugin : null;
        }

        /* Fallback (standard) value */
        return 'mysql_native_password';
    }

    /**
     * Returns all the grants for a certain user on a certain host
     * Used in the export privileges for all users section
     *
     * @param string $user User name
     * @param string $host Host name
     *
     * @return string containing all the grants text
     */
    public function getGrants(string $user, string $host): string
    {
        $grants = $this->dbi->fetchResult(
            'SHOW GRANTS FOR '
            . $this->dbi->quoteString($user) . '@'
            . $this->dbi->quoteString($host),
        );
        $response = '';
        foreach ($grants as $oneGrant) {
            $response .= $oneGrant . ";\n\n";
        }

        return $response;
    }

    /**
     * Update password and get message for password updating
     *
     * @param string $errorUrl error url
     * @param string $username username
     * @param string $hostname hostname
     *
     * @return Message success or error message after updating password
     */
    public function updatePassword(string $errorUrl, string $username, string $hostname): Message
    {
        // similar logic in /user-password
        $message = null;

        if (isset($_POST['pma_pw'], $_POST['pma_pw2']) && empty($_POST['nopass'])) {
            if ($_POST['pma_pw'] != $_POST['pma_pw2']) {
                $message = Message::error(__('The passwords aren\'t the same!'));
            } elseif (empty($_POST['pma_pw']) || empty($_POST['pma_pw2'])) {
                $message = Message::error(__('The password is empty!'));
            }
        }

        // here $nopass could be == 1
        if ($message === null) {
            $hashingFunction = 'PASSWORD';
            $serverVersion = $this->dbi->getVersion();
            $authenticationPlugin = $_POST['authentication_plugin'] ?? $this->getCurrentAuthenticationPlugin(
                $username,
                $hostname,
            );

            // Use 'ALTER USER ...' syntax for MySQL 5.7.6+
            if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50706) {
                if ($authenticationPlugin !== 'mysql_old_password') {
                    $queryPrefix = 'ALTER USER '
                        . $this->dbi->quoteString($username)
                        . '@' . $this->dbi->quoteString($hostname)
                        . ' IDENTIFIED WITH '
                        . $authenticationPlugin
                        . ' BY ';
                } else {
                    $queryPrefix = 'ALTER USER '
                        . $this->dbi->quoteString($username)
                        . '@' . $this->dbi->quoteString($hostname)
                        . ' IDENTIFIED BY ';
                }

                // in $sql_query which will be displayed, hide the password
                $sqlQuery = $queryPrefix . "'*'";

                $localQuery = $queryPrefix . $this->dbi->quoteString($_POST['pma_pw']);
            } elseif (Compatibility::isMariaDb() && $serverVersion >= 10000) {
                // MariaDB uses "SET PASSWORD" syntax to change user password.
                // On Galera cluster only DDL queries are replicated, since
                // users are stored in MyISAM storage engine.
                $sqlQuery = $localQuery = 'SET PASSWORD FOR '
                    . $this->dbi->quoteString($username)
                    . '@' . $this->dbi->quoteString($hostname)
                    . ' = PASSWORD (' . $this->dbi->quoteString($_POST['pma_pw']) . ')';
            } elseif (Compatibility::isMariaDb() && $serverVersion >= 50200 && $this->dbi->isSuperUser()) {
                // Use 'UPDATE `mysql`.`user` ...' Syntax for MariaDB 5.2+
                if ($authenticationPlugin === 'mysql_native_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'mysql_native_password' type
                    $this->dbi->tryQuery('SET old_passwords = 0;');
                } elseif ($authenticationPlugin === 'sha256_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'sha256_password' type
                    $this->dbi->tryQuery('SET `old_passwords` = 2;');
                }

                $hashedPassword = $this->getHashedPassword($_POST['pma_pw']);

                $sqlQuery = 'SET PASSWORD FOR '
                    . $this->dbi->quoteString($username)
                    . '@' . $this->dbi->quoteString($hostname) . ' = '
                    . ($_POST['pma_pw'] == ''
                        ? '\'\''
                        : $hashingFunction . '(\''
                        . preg_replace('@.@s', '*', $_POST['pma_pw']) . '\')');

                $localQuery = 'UPDATE `mysql`.`user` SET '
                    . " `authentication_string` = '" . $hashedPassword
                    . "', `Password` = '', "
                    . " `plugin` = '" . $authenticationPlugin . "'"
                    . $this->getUserHostCondition($username, $hostname) . ';';
            } else {
                // USE 'SET PASSWORD ...' syntax for rest of the versions
                // Backup the old value, to be reset later
                $row = $this->dbi->fetchSingleRow('SELECT @@old_passwords;');
                $origValue = $row['@@old_passwords'];
                $updatePluginQuery = 'UPDATE `mysql`.`user` SET'
                    . " `plugin` = '" . $authenticationPlugin . "'"
                    . $this->getUserHostCondition($username, $hostname) . ';';

                // Update the plugin for the user
                if (! $this->dbi->tryQuery($updatePluginQuery)) {
                    Generator::mysqlDie(
                        $this->dbi->getError(),
                        $updatePluginQuery,
                        false,
                        $errorUrl,
                    );
                }

                $this->dbi->tryQuery('FLUSH PRIVILEGES;');

                if ($authenticationPlugin === 'mysql_native_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'mysql_native_password' type
                    $this->dbi->tryQuery('SET old_passwords = 0;');
                } elseif ($authenticationPlugin === 'sha256_password') {
                    // Set the hashing method used by PASSWORD()
                    // to be 'sha256_password' type
                    $this->dbi->tryQuery('SET `old_passwords` = 2;');
                }

                $sqlQuery = 'SET PASSWORD FOR '
                    . $this->dbi->quoteString($username)
                    . '@' . $this->dbi->quoteString($hostname) . ' = '
                    . ($_POST['pma_pw'] == ''
                        ? '\'\''
                        : $hashingFunction . '(\''
                        . preg_replace('@.@s', '*', $_POST['pma_pw']) . '\')');

                $localQuery = 'SET PASSWORD FOR '
                    . $this->dbi->quoteString($username)
                    . '@' . $this->dbi->quoteString($hostname) . ' = '
                    . ($_POST['pma_pw'] == '' ? '\'\'' : $hashingFunction
                    . '(' . $this->dbi->quoteString($_POST['pma_pw']) . ')');
            }

            if (! $this->dbi->tryQuery($localQuery)) {
                Generator::mysqlDie(
                    $this->dbi->getError(),
                    $sqlQuery,
                    false,
                    $errorUrl,
                );
            }

            // Flush privileges after successful password change
            $this->dbi->tryQuery('FLUSH PRIVILEGES;');

            $message = Message::success(
                __('The password for %s was changed successfully.'),
            );
            $message->addParam('\'' . $username . '\'@\'' . $hostname . '\'');
            if (isset($origValue)) {
                $this->dbi->tryQuery('SET `old_passwords` = ' . $origValue . ';');
            }
        }

        return $message;
    }

    /**
     * Revokes privileges and get message and SQL query for privileges revokes
     *
     * @param string $dbname    database name
     * @param string $tablename table name
     * @param string $username  username
     * @param string $hostname  host name
     * @param string $itemType  item type
     *
     * @return array{Message, string} ($message, $sql_query)
     */
    public function getMessageAndSqlQueryForPrivilegesRevoke(
        string $dbname,
        string $tablename,
        string $username,
        string $hostname,
        string $itemType,
    ): array {
        $dbAndTable = $this->wildcardEscapeForGrant($dbname, $tablename);

        $sqlQuery0 = 'REVOKE ALL PRIVILEGES ON ' . $itemType . ' ' . $dbAndTable
            . ' FROM '
            . $this->dbi->quoteString($username) . '@'
            . $this->dbi->quoteString($hostname) . ';';

        $sqlQuery1 = 'REVOKE GRANT OPTION ON ' . $itemType . ' ' . $dbAndTable
            . ' FROM ' . $this->dbi->quoteString($username) . '@'
            . $this->dbi->quoteString($hostname) . ';';

        $this->dbi->query($sqlQuery0);
        if (! $this->dbi->tryQuery($sqlQuery1)) {
            // this one may fail, too...
            $sqlQuery1 = '';
        }

        $sqlQuery = $sqlQuery0 . ' ' . $sqlQuery1;
        $message = Message::success(
            __('You have revoked the privileges for %s.'),
        );
        $message->addParam('\'' . $username . '\'@\'' . $hostname . '\'');

        return [$message, $sqlQuery];
    }

    /**
     * Get REQUIRE clause
     *
     * @return string REQUIRE clause
     */
    public function getRequireClause(): string
    {
        /** @var string|null $sslType */
        $sslType = $_POST['ssl_type'] ?? $GLOBALS['ssl_type'] ?? null;
        /** @var string|null $sslCipher */
        $sslCipher = $_POST['ssl_cipher'] ?? $GLOBALS['ssl_cipher'] ?? null;
        /** @var string|null $x509Issuer */
        $x509Issuer = $_POST['x509_issuer'] ?? $GLOBALS['x509_issuer'] ?? null;
        /** @var string|null $x509Subject */
        $x509Subject = $_POST['x509_subject'] ?? $GLOBALS['x509_subject'] ?? null;

        if ($sslType === 'SPECIFIED') {
            $require = [];
            if (is_string($sslCipher) && $sslCipher !== '') {
                $require[] = 'CIPHER ' . $this->dbi->quoteString($sslCipher);
            }

            if (is_string($x509Issuer) && $x509Issuer !== '') {
                $require[] = 'ISSUER ' . $this->dbi->quoteString($x509Issuer);
            }

            if (is_string($x509Subject) && $x509Subject !== '') {
                $require[] = 'SUBJECT ' . $this->dbi->quoteString($x509Subject);
            }

            if ($require !== []) {
                $requireClause = ' REQUIRE ' . implode(' AND ', $require);
            } else {
                $requireClause = ' REQUIRE NONE';
            }
        } elseif ($sslType === 'X509') {
            $requireClause = ' REQUIRE X509';
        } elseif ($sslType === 'ANY') {
            $requireClause = ' REQUIRE SSL';
        } else {
            $requireClause = ' REQUIRE NONE';
        }

        return $requireClause;
    }

    /**
     * Get a WITH clause for 'update privileges' and 'add user'
     */
    public function getWithClauseForAddUserAndUpdatePrivs(): string
    {
        $sqlQuery = '';
        if (
            isset($_POST['Grant_priv']) && $_POST['Grant_priv'] === 'Y'
            && ! (Compatibility::isMySqlOrPerconaDb() && $this->dbi->getVersion() >= 80011)
        ) {
            $sqlQuery .= ' GRANT OPTION';
        }

        if (isset($_POST['max_questions'])) {
            $maxQuestions = (int) $_POST['max_questions'];
            $maxQuestions = max(0, $maxQuestions);
            $sqlQuery .= ' MAX_QUERIES_PER_HOUR ' . $maxQuestions;
        }

        if (isset($_POST['max_connections'])) {
            $maxConnections = (int) $_POST['max_connections'];
            $maxConnections = max(0, $maxConnections);
            $sqlQuery .= ' MAX_CONNECTIONS_PER_HOUR ' . $maxConnections;
        }

        if (isset($_POST['max_updates'])) {
            $maxUpdates = (int) $_POST['max_updates'];
            $maxUpdates = max(0, $maxUpdates);
            $sqlQuery .= ' MAX_UPDATES_PER_HOUR ' . $maxUpdates;
        }

        if (isset($_POST['max_user_connections'])) {
            $maxUserConnections = (int) $_POST['max_user_connections'];
            $maxUserConnections = max(0, $maxUserConnections);
            $sqlQuery .= ' MAX_USER_CONNECTIONS ' . $maxUserConnections;
        }

        return $sqlQuery !== '' ? ' WITH' . $sqlQuery : '';
    }

    /**
     * Get HTML for addUsersForm, This function call if isset($_GET['adduser'])
     *
     * @param string $dbname database name
     *
     * @return string HTML for addUserForm
     */
    public function getHtmlForAddUser(string $dbname): string
    {
        $isGrantUser = $this->dbi->isGrantUser();
        $loginInformationFieldsNew = $this->getHtmlForLoginInformationFields();
        $privilegesTable = '';
        if ($isGrantUser) {
            $privilegesTable = $this->getHtmlToDisplayPrivilegesTable('*', '*', false);
        }

        return $this->template->render('server/privileges/add_user', [
            'database' => $dbname,
            'login_information_fields_new' => $loginInformationFieldsNew,
            'is_grant_user' => $isGrantUser,
            'privileges_table' => $privilegesTable,
        ]);
    }

    /** @return mixed[] */
    public function getAllPrivileges(DatabaseName $db, TableName|null $table = null): array
    {
        $databasePrivileges = $this->getGlobalAndDatabasePrivileges($db);
        $tablePrivileges = [];
        if ($table !== null) {
            $tablePrivileges = $this->getTablePrivileges($db, $table);
        }

        $routinePrivileges = $this->getRoutinesPrivileges($db);
        $allPrivileges = array_merge($databasePrivileges, $tablePrivileges, $routinePrivileges);

        $privileges = [];
        foreach ($allPrivileges as $privilege) {
            $userHost = $privilege['User'] . '@' . $privilege['Host'];
            $privileges[$userHost] ??= [];
            $privileges[$userHost]['user'] = (string) $privilege['User'];
            $privileges[$userHost]['host'] = (string) $privilege['Host'];
            $privileges[$userHost]['privileges'] ??= [];
            $privileges[$userHost]['privileges'][] = $this->getSpecificPrivilege($privilege);
        }

        return $privileges;
    }

    /**
     * @param mixed[] $row Array with user privileges
     *
     * @return mixed[]
     */
    private function getSpecificPrivilege(array $row): array
    {
        $privilege = ['type' => $row['Type'], 'database' => $row['Db']];
        if ($row['Type'] === 'r') {
            $privilege['routine'] = $row['Routine_name'];
            $privilege['has_grant'] = str_contains($row['Proc_priv'], 'Grant');
            $privilege['privileges'] = explode(',', $row['Proc_priv']);
        } elseif ($row['Type'] === 't') {
            $privilege['table'] = $row['Table_name'];
            $privilege['has_grant'] = str_contains($row['Table_priv'], 'Grant');
            $tablePrivs = explode(',', $row['Table_priv']);
            $specificPrivileges = [];
            $grantsArr = $this->getTableGrantsArray();
            foreach ($grantsArr as $grant) {
                $specificPrivileges[$grant[0]] = 'N';
                foreach ($tablePrivs as $tablePriv) {
                    if ($grant[0] !== $tablePriv) {
                        continue;
                    }

                    $specificPrivileges[$grant[0]] = 'Y';
                }
            }

            $privilege['privileges'] = $this->extractPrivInfo($specificPrivileges, true, true);
        } else {
            $privilege['has_grant'] = $row['Grant_priv'] === 'Y';
            $privilege['privileges'] = $this->extractPrivInfo($row, true);
        }

        return $privilege;
    }

    /** @return array<int, array<string|null>> */
    private function getGlobalAndDatabasePrivileges(DatabaseName $db): array
    {
        $listOfPrivileges = '`Select_priv`,
            `Insert_priv`,
            `Update_priv`,
            `Delete_priv`,
            `Create_priv`,
            `Drop_priv`,
            `Grant_priv`,
            `Index_priv`,
            `Alter_priv`,
            `References_priv`,
            `Create_tmp_table_priv`,
            `Lock_tables_priv`,
            `Create_view_priv`,
            `Show_view_priv`,
            `Create_routine_priv`,
            `Alter_routine_priv`,
            `Execute_priv`,
            `Event_priv`,
            `Trigger_priv`,';

        $listOfComparedPrivileges = 'BINARY `Select_priv` = \'N\' AND
            BINARY `Insert_priv` = \'N\' AND
            BINARY `Update_priv` = \'N\' AND
            BINARY `Delete_priv` = \'N\' AND
            BINARY `Create_priv` = \'N\' AND
            BINARY `Drop_priv` = \'N\' AND
            BINARY `Grant_priv` = \'N\' AND
            BINARY `References_priv` = \'N\' AND
            BINARY `Create_tmp_table_priv` = \'N\' AND
            BINARY `Lock_tables_priv` = \'N\' AND
            BINARY `Create_view_priv` = \'N\' AND
            BINARY `Show_view_priv` = \'N\' AND
            BINARY `Create_routine_priv` = \'N\' AND
            BINARY `Alter_routine_priv` = \'N\' AND
            BINARY `Execute_priv` = \'N\' AND
            BINARY `Event_priv` = \'N\' AND
            BINARY `Trigger_priv` = \'N\'';

        $query = '
            (
                SELECT `User`, `Host`, ' . $listOfPrivileges . ' \'*\' AS `Db`, \'g\' AS `Type`
                FROM `mysql`.`user`
                WHERE NOT (' . $listOfComparedPrivileges . ')
            )
            UNION
            (
                SELECT `User`, `Host`, ' . $listOfPrivileges . ' `Db`, \'d\' AS `Type`
                FROM `mysql`.`db`
                WHERE ' . $this->dbi->quoteString($db->getName()) . ' LIKE `Db` AND NOT ('
                . $listOfComparedPrivileges . ')
            )
            ORDER BY `User` ASC, `Host` ASC, `Db` ASC;
        ';

        return $this->dbi->query($query)->fetchAllAssoc();
    }

    /** @return array<int, array<string|null>> */
    private function getTablePrivileges(DatabaseName $db, TableName $table): array
    {
        $query = '
            SELECT `User`, `Host`, `Db`, \'t\' AS `Type`, `Table_name`, `Table_priv`
            FROM `mysql`.`tables_priv`
            WHERE
                ? LIKE `Db` AND
                ? LIKE `Table_name` AND
                NOT (`Table_priv` = \'\' AND Column_priv = \'\')
            ORDER BY `User` ASC, `Host` ASC, `Db` ASC, `Table_priv` ASC;
        ';
        $statement = $this->dbi->prepare($query);
        if ($statement === null || ! $statement->execute([$db->getName(), $table->getName()])) {
            return [];
        }

        return $statement->getResult()->fetchAllAssoc();
    }

    /** @return array<int, array<string|null>> */
    private function getRoutinesPrivileges(DatabaseName $db): array
    {
        $query = '
            SELECT *, \'r\' AS `Type`
            FROM `mysql`.`procs_priv`
            WHERE Db = ' . $this->dbi->quoteString($db->getName()) . ';';

        return $this->dbi->query($query)->fetchAllAssoc();
    }

    /**
     * Get HTML error for View Users form
     * For non superusers such as grant/create users
     */
    private function getHtmlForViewUsersError(): string
    {
        return Message::error(
            __('Not enough privilege to view users.'),
        )->getDisplay();
    }

    /**
     * Returns edit, revoke or export link for a user.
     *
     * @param string $linktype    The link type (edit | revoke | export)
     * @param string $username    User name
     * @param string $hostname    Host name
     * @param string $dbname      Database name
     * @param string $tablename   Table name
     * @param string $routinename Routine name
     * @param string $initial     Initial value
     *
     * @return string HTML code with link
     */
    public function getUserLink(
        string $linktype,
        string $username,
        string $hostname,
        string $dbname = '',
        string $tablename = '',
        string $routinename = '',
        string $initial = '',
    ): string {
        $linkClass = '';
        if ($linktype === 'edit') {
            $linkClass = 'edit_user_anchor';
        } elseif ($linktype === 'export') {
            $linkClass = 'export_user_anchor ajax';
        }

        $params = ['username' => $username, 'hostname' => $hostname];
        switch ($linktype) {
            case 'edit':
                $params['dbname'] = $dbname;
                $params['tablename'] = $tablename;
                $params['routinename'] = $routinename;
                break;
            case 'revoke':
                $params['dbname'] = $dbname;
                $params['tablename'] = $tablename;
                $params['routinename'] = $routinename;
                $params['revokeall'] = 1;
                break;
            case 'export':
                $params['initial'] = $initial;
                $params['export'] = 1;
                break;
        }

        $action = [];
        switch ($linktype) {
            case 'edit':
                $action['icon'] = 'b_usredit';
                $action['text'] = __('Edit privileges');
                break;
            case 'revoke':
                $action['icon'] = 'b_usrdrop';
                $action['text'] = __('Revoke');
                break;
            case 'export':
                $action['icon'] = 'b_tblexport';
                $action['text'] = __('Export');
                break;
        }

        return $this->template->render('server/privileges/get_user_link', [
            'link_class' => $linkClass,
            'is_revoke' => $linktype === 'revoke',
            'url_params' => $params,
            'action' => $action,
        ]);
    }

    /**
     * Returns number of defined user groups
     */
    public function getUserGroupCount(ConfigurableMenusFeature $configurableMenusFeature): int
    {
        $userGroupTable = Util::backquote($configurableMenusFeature->database)
            . '.' . Util::backquote($configurableMenusFeature->userGroups);
        $sqlQuery = 'SELECT COUNT(*) FROM ' . $userGroupTable;

        return (int) $this->dbi->fetchValue($sqlQuery, 0, ConnectionType::ControlUser);
    }

    /**
     * Returns name of user group that user is part of
     *
     * @param string $username User name
     *
     * @return string|null usergroup if found or null if not found
     */
    public function getUserGroupForUser(string $username): string|null
    {
        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;
        if ($configurableMenusFeature === null) {
            return null;
        }

        $userTable = Util::backquote($configurableMenusFeature->database)
            . '.' . Util::backquote($configurableMenusFeature->users);
        $sqlQuery = 'SELECT `usergroup` FROM ' . $userTable
            . ' WHERE `username` = \'' . $username . '\''
            . ' LIMIT 1';

        $usergroup = $this->dbi->fetchValue($sqlQuery, 0, ConnectionType::ControlUser);

        if ($usergroup === false) {
            return null;
        }

        return $usergroup;
    }

    /**
     * This function return the extra data array for the ajax behavior
     *
     * @param string $password password
     * @param string $sqlQuery sql query
     * @param string $hostname hostname
     * @param string $username username
     *
     * @return (string|bool)[]
     */
    public function getExtraDataForAjaxBehavior(
        string $password,
        string $sqlQuery,
        string $hostname,
        string $username,
    ): array {
        if (isset($GLOBALS['dbname'])) {
            //if (preg_match('/\\\\(?:_|%)/i', $dbname)) {
            if (preg_match('/(?<!\\\\)(?:_|%)/', $GLOBALS['dbname']) === 1) {
                $dbnameIsWildcard = true;
            } else {
                $dbnameIsWildcard = false;
            }
        }

        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;

        $userGroupCount = 0;
        if ($configurableMenusFeature !== null) {
            $userGroupCount = $this->getUserGroupCount($configurableMenusFeature);
        }

        $extraData = [];
        if ($sqlQuery !== '') {
            $extraData['sql_query'] = Generator::getMessage('', $sqlQuery);
        }

        if (isset($_POST['change_copy'])) {
            $user = [
                'name' => $username,
                'host' => $hostname,
                'has_password' => $password !== '' || isset($_POST['pma_pw']),
                'privileges' => implode(', ', $this->extractPrivInfo(null, true)),
                'has_group' => $configurableMenusFeature !== null,
                'has_group_edit' => $configurableMenusFeature !== null && $userGroupCount > 0,
                'has_grant' => isset($_POST['Grant_priv']) && $_POST['Grant_priv'] === 'Y',
            ];
            $extraData['new_user_string'] = $this->template->render('server/privileges/new_user_ajax', [
                'user' => $user,
                'is_grantuser' => $this->dbi->isGrantUser(),
                'initial' => $_GET['initial'] ?? '',
            ]);

            /**
             * Generate the string for this alphabet's initial, to update the user
             * pagination
             */
            $newUserInitial = mb_strtoupper(
                mb_substr($username, 0, 1),
            );
            $newUserInitialString = '<a href="';
            $newUserInitialString .= Url::getFromRoute('/server/privileges', ['initial' => $newUserInitial]);
            $newUserInitialString .= '">' . $newUserInitial . '</a>';
            $extraData['new_user_initial'] = $newUserInitial;
            $extraData['new_user_initial_string'] = $newUserInitialString;
        }

        if (isset($_POST['update_privs'])) {
            $extraData['db_specific_privs'] = false;
            $extraData['db_wildcard_privs'] = false;
            if (isset($dbnameIsWildcard)) {
                $extraData['db_specific_privs'] = ! $dbnameIsWildcard;
                $extraData['db_wildcard_privs'] = $dbnameIsWildcard;
            }

            $newPrivileges = implode(', ', $this->extractPrivInfo(null, true));

            $extraData['new_privileges'] = $newPrivileges;
        }

        if (isset($_GET['validate_username'])) {
            $sqlQuery = 'SELECT * FROM `mysql`.`user` WHERE `User` = '
                . $this->dbi->quoteString($_GET['username']) . ';';
            $res = $this->dbi->query($sqlQuery);
            $extraData['user_exists'] = $res->fetchRow() !== [];
        }

        return $extraData;
    }

    /**
     * no db name given, so we want all privs for the given user
     * db name was given, so we want all user specific rights for this db
     * So this function returns user rights as an array
     *
     * @param string $username username
     * @param string $hostname host name
     * @param string $type     database or table
     * @param string $dbname   database name
     *
     * @return mixed[] database rights
     */
    public function getUserSpecificRights(string $username, string $hostname, string $type, string $dbname = ''): array
    {
        $userHostCondition = $this->getUserHostCondition($username, $hostname);

        if ($type === 'database') {
            $tablesToSearchForUsers = ['tables_priv', 'columns_priv', 'procs_priv'];
            $dbOrTableName = 'Db';
        } elseif ($type === 'table') {
            $userHostCondition .= ' AND `Db` LIKE ' . $this->dbi->quoteString($dbname);
            $tablesToSearchForUsers = ['columns_priv'];
            $dbOrTableName = 'Table_name';
        } else { // routine
            $userHostCondition .= ' AND `Db` LIKE ' . $this->dbi->quoteString($dbname);
            $tablesToSearchForUsers = ['procs_priv'];
            $dbOrTableName = 'Routine_name';
        }

        // we also want privileges for this user not in table `db` but in other table
        $tables = $this->dbi->fetchResult('SHOW TABLES FROM `mysql`;');

        $dbRightsSqls = [];
        foreach ($tablesToSearchForUsers as $tableSearchIn) {
            if (! in_array($tableSearchIn, $tables, true)) {
                continue;
            }

            $dbRightsSqls[] = 'SELECT DISTINCT `' . $dbOrTableName
                . '` FROM `mysql`.' . Util::backquote($tableSearchIn)
                . $userHostCondition;
        }

        $userDefaults = [$dbOrTableName => '', 'Grant_priv' => 'N', 'privs' => ['USAGE'], 'Column_priv' => true];

        // for the rights
        $dbRights = [];

        $dbRightsSql = '(' . implode(') UNION (', $dbRightsSqls) . ')'
            . ' ORDER BY `' . $dbOrTableName . '` ASC';

        $dbRightsResult = $this->dbi->query($dbRightsSql);

        while ($dbRightsRow = $dbRightsResult->fetchAssoc()) {
            $dbRightsRow = array_merge($userDefaults, $dbRightsRow);
            if ($type === 'database') {
                // only Db names in the table `mysql`.`db` uses wildcards
                // as we are in the db specific rights display we want
                // all db names escaped, also from other sources
                $dbRightsRow['Db'] = $this->escapeGrantWildcards($dbRightsRow['Db']);
            }

            $dbRights[$dbRightsRow[$dbOrTableName]] = $dbRightsRow;
        }

        $sqlQuery = match ($type) {
            'database' => 'SELECT * FROM `mysql`.`db`'
                . $userHostCondition . ' ORDER BY `Db` ASC',
            'table' => 'SELECT `Table_name`,'
                . ' `Table_priv`,'
                . ' IF(`Column_priv` = _latin1 \'\', 0, 1)'
                . ' AS \'Column_priv\''
                . ' FROM `mysql`.`tables_priv`'
                . $userHostCondition
                . ' ORDER BY `Table_name` ASC;',
            default => 'SELECT `Routine_name`, `Proc_priv`'
                . ' FROM `mysql`.`procs_priv`'
                . $userHostCondition
                . ' ORDER BY `Routine_name`',
        };

        $result = $this->dbi->query($sqlQuery);

        while ($row = $result->fetchAssoc()) {
            if (isset($dbRights[$row[$dbOrTableName]])) {
                $dbRights[$row[$dbOrTableName]] = array_merge($dbRights[$row[$dbOrTableName]], $row);
            } else {
                $dbRights[$row[$dbOrTableName]] = $row;
            }

            if ($type !== 'database') {
                continue;
            }

            // there are db specific rights for this user
            // so we can drop this db rights
            $dbRights[$row['Db']]['can_delete'] = true;
        }

        return $dbRights;
    }

    /**
     * Parses Proc_priv data
     *
     * @param string $privs Proc_priv
     *
     * @return array<string, string>
     */
    public function parseProcPriv(string $privs): array
    {
        $result = ['Alter_routine_priv' => 'N', 'Execute_priv' => 'N', 'Grant_priv' => 'N'];
        foreach (explode(',', $privs) as $priv) {
            if ($priv === 'Alter Routine') {
                $result['Alter_routine_priv'] = 'Y';
            } else {
                $result[$priv . '_priv'] = 'Y';
            }
        }

        return $result;
    }

    /**
     * Get a HTML table for display user's table specific or database specific rights
     *
     * @param string $username username
     * @param string $hostname host name
     * @param string $type     database, table or routine
     * @param string $dbname   database name
     */
    public function getHtmlForAllTableSpecificRights(
        string $username,
        string $hostname,
        string $type,
        string $dbname = '',
    ): string {
        $uiData = [
            'database' => [
                'form_id' => 'database_specific_priv',
                'sub_menu_label' => __('Database'),
                'legend' => __('Database-specific privileges'),
                'type_label' => __('Database'),
            ],
            'table' => [
                'form_id' => 'table_specific_priv',
                'sub_menu_label' => __('Table'),
                'legend' => __('Table-specific privileges'),
                'type_label' => __('Table'),
            ],
            'routine' => [
                'form_id' => 'routine_specific_priv',
                'sub_menu_label' => __('Routine'),
                'legend' => __('Routine-specific privileges'),
                'type_label' => __('Routine'),
            ],
        ];

        /**
         * no db name given, so we want all privs for the given user
         * db name was given, so we want all user specific rights for this db
         */
        $dbRights = $this->getUserSpecificRights($username, $hostname, $type, $dbname);
        ksort($dbRights);

        $foundRows = [];
        $privileges = [];
        foreach ($dbRights as $row) {
            $onePrivilege = [];

            $paramTableName = '';
            $paramRoutineName = '';

            if ($type === 'database') {
                $name = $row['Db'];
                $onePrivilege['grant'] = $row['Grant_priv'] === 'Y';
                $onePrivilege['table_privs'] = ! empty($row['Table_priv'])
                    || ! empty($row['Column_priv']);
                $onePrivilege['privileges'] = implode(',', $this->extractPrivInfo($row, true));

                $paramDbName = $row['Db'];
            } elseif ($type === 'table') {
                $name = $row['Table_name'];
                $onePrivilege['grant'] = in_array(
                    'Grant',
                    explode(',', $row['Table_priv']),
                    true,
                );
                $onePrivilege['column_privs'] = ! empty($row['Column_priv']);
                $onePrivilege['privileges'] = implode(',', $this->extractPrivInfo($row, true));

                $paramDbName = $this->escapeGrantWildcards($dbname);
                $paramTableName = $row['Table_name'];
            } else { // routine
                $name = $row['Routine_name'];
                $onePrivilege['grant'] = in_array(
                    'Grant',
                    explode(',', $row['Proc_priv']),
                    true,
                );

                $privs = $this->parseProcPriv($row['Proc_priv']);
                $onePrivilege['privileges'] = implode(
                    ',',
                    $this->extractPrivInfo($privs, true),
                );

                $paramDbName = $this->escapeGrantWildcards($dbname);
                $paramRoutineName = $row['Routine_name'];
            }

            $foundRows[] = $name;
            $onePrivilege['name'] = $name;

            $onePrivilege['edit_link'] = '';
            if ($this->dbi->isGrantUser()) {
                $onePrivilege['edit_link'] = $this->getUserLink(
                    'edit',
                    $username,
                    $hostname,
                    $paramDbName,
                    $paramTableName,
                    $paramRoutineName,
                );
            }

            $onePrivilege['revoke_link'] = '';
            if ($type !== 'database' || ! empty($row['can_delete'])) {
                $onePrivilege['revoke_link'] = $this->getUserLink(
                    'revoke',
                    $username,
                    $hostname,
                    $paramDbName,
                    $paramTableName,
                    $paramRoutineName,
                );
            }

            $privileges[] = $onePrivilege;
        }

        $data = $uiData[$type];
        $data['privileges'] = $privileges;
        $data['username'] = $username;
        $data['hostname'] = $hostname;
        $data['database'] = $dbname;
        $data['escaped_database'] = $this->escapeGrantWildcards($dbname);
        $data['type'] = $type;

        if ($type === 'database') {
            $predDbArray = $this->dbi->getDatabaseList();
            $databasesToSkip = ['information_schema', 'performance_schema'];

            $databases = [];
            $escapedDatabases = [];
            foreach ($predDbArray as $currentDb) {
                if (in_array($currentDb, $databasesToSkip, true)) {
                    continue;
                }

                $currentDbEscaped = $this->escapeGrantWildcards($currentDb);
                // cannot use array_diff() once, outside of the loop,
                // because the list of databases has special characters
                // already escaped in $foundRows,
                // contrary to the output of SHOW DATABASES
                if (in_array($currentDbEscaped, $foundRows, true)) {
                    continue;
                }

                $databases[] = $currentDb;
                $escapedDatabases[] = $currentDbEscaped;
            }

            $data['databases'] = $databases;
            $data['escaped_databases'] = $escapedDatabases;
        } elseif ($type === 'table') {
            $result = $this->dbi->tryQuery('SHOW TABLES FROM ' . Util::backquote($dbname));

            $tables = [];
            if ($result) {
                while ($row = $result->fetchRow()) {
                    if (in_array($row[0], $foundRows, true)) {
                        continue;
                    }

                    $tables[] = $row[0];
                }
            }

            $data['tables'] = $tables;
        } else { // routine
            $routineData = Routines::getDetails($this->dbi, $dbname);

            $routines = [];
            foreach ($routineData as $routine) {
                if (in_array($routine->name, $foundRows, true)) {
                    continue;
                }

                $routines[] = $routine->name;
            }

            $data['routines'] = $routines;
        }

        return $this->template->render('server/privileges/privileges_summary', $data);
    }

    /**
     * Get HTML for display the users overview
     * (if less than 50 users, display them immediately)
     *
     * @param ResultInterface $result   ran sql query
     * @param mixed[]         $dbRights user's database rights array
     *
     * @return string HTML snippet
     */
    public function getUsersOverview(ResultInterface $result, array $dbRights): string
    {
        $configurableMenusFeature = $this->relation->getRelationParameters()->configurableMenusFeature;

        while ($row = $result->fetchAssoc()) {
            $row['privs'] = $this->extractPrivInfo($row, true);
            $dbRights[$row['User']][$row['Host']] = $row;
        }

        unset($result);

        $userGroupCount = 0;
        if ($configurableMenusFeature !== null) {
            $sqlQuery = 'SELECT * FROM ' . Util::backquote($configurableMenusFeature->database)
                . '.' . Util::backquote($configurableMenusFeature->users);
            $result = $this->dbi->tryQueryAsControlUser($sqlQuery);
            $groupAssignment = [];
            if ($result) {
                while ($row = $result->fetchAssoc()) {
                    $groupAssignment[$row['username']] = $row['usergroup'];
                }
            }

            unset($result);

            $userGroupCount = $this->getUserGroupCount($configurableMenusFeature);
        }

        $hosts = [];
        $hasAccountLocking = Compatibility::hasAccountLocking($this->dbi->isMariaDB(), $this->dbi->getVersion());
        foreach ($dbRights as $user) {
            ksort($user);
            foreach ($user as $host) {
                $res = $this->getUserPrivileges((string) $host['User'], (string) $host['Host'], $hasAccountLocking);

                $hasPassword = false;
                if (
                    (isset($res['authentication_string'])
                    && $res['authentication_string'] !== '')
                    || (isset($res['Password'])
                    && $res['Password'] !== '')
                ) {
                    $hasPassword = true;
                }

                $hosts[] = [
                    'user' => $host['User'],
                    'host' => $host['Host'],
                    'has_password' => $hasPassword,
                    'has_select_priv' => isset($host['Select_priv']),
                    'privileges' => $host['privs'],
                    'group' => $groupAssignment[$host['User']] ?? '',
                    'has_grant' => $host['Grant_priv'] === 'Y',
                    'is_account_locked' => isset($res['account_locked']) && $res['account_locked'] === 'Y',
                ];
            }
        }

        return $this->template->render('server/privileges/users_overview', [
            'menus_work' => $configurableMenusFeature !== null,
            'user_group_count' => $userGroupCount,
            'initial' => $_GET['initial'] ?? '',
            'hosts' => $hosts,
            'is_grantuser' => $this->dbi->isGrantUser(),
            'is_createuser' => $this->dbi->isCreateUser(),
            'has_account_locking' => $hasAccountLocking,
        ]);
    }

    /**
     * Displays the initials if there are many privileges
     */
    public function getHtmlForInitials(): string
    {
        $usersCount = $this->dbi->fetchValue('SELECT COUNT(*) FROM `mysql`.`user`');
        if ($usersCount === false || $usersCount <= 20) {
            return '';
        }

        $result = $this->dbi->tryQuery('SELECT DISTINCT UPPER(LEFT(`User`, 1)) FROM `user`');
        if ($result === false) {
            return '';
        }

        $initials = $result->fetchAllColumn();
        // Display the initials, which can be any characters, not
        // just letters. For letters A-Z, we add the non-used letters
        // as greyed out.
        $initialsMap = array_fill_keys($initials, true) + array_fill_keys(range('A', 'Z'), false);
        uksort($initialsMap, strnatcasecmp(...));

        return $this->template->render('server/privileges/initials_row', [
            'array_initials' => $initialsMap,
            'selected_initial' => $_GET['initial'] ?? null,
        ]);
    }

    /**
     * Get the database rights array for Display user overview
     *
     * @return (string|string[])[][][]    database rights array
     */
    public function getDbRightsForUserOverview(string|null $initial): array
    {
        // we also want users not in table `user` but in other table
        $mysqlTables = $this->dbi->fetchResult('SHOW TABLES FROM `mysql`');
        $userTables = ['user', 'db', 'tables_priv', 'columns_priv', 'procs_priv'];
        $whereUser = $this->rangeOfUsers($initial);
        $sqls = [];
        foreach (array_intersect($userTables, $mysqlTables) as $table) {
            $sqls[] = '(SELECT DISTINCT `User`, `Host` FROM `mysql`.`' . $table . '` ' . $whereUser . ')';
        }

        $sql = implode(' UNION ', $sqls) . ' ORDER BY `User` ASC, `Host` ASC';
        $result = $this->dbi->query($sql);

        $userDefaults = ['User' => '', 'Host' => '%', 'Password' => '?', 'Grant_priv' => 'N', 'privs' => ['USAGE']];
        $dbRights = [];
        while ($row = $result->fetchAssoc()) {
            /** @psalm-var array{User: string, Host: string} $row */
            $dbRights[$row['User']][$row['Host']] = array_merge($userDefaults, $row);
        }

        ksort($dbRights);

        return $dbRights;
    }

    /**
     * Delete user and get message and sql query for delete user in privileges
     *
     * @param mixed[] $queries queries
     *
     * @return array{string, Message} Message
     */
    public function deleteUser(array $queries): array
    {
        $sqlQuery = '';
        if ($queries === []) {
            $message = Message::error(__('No users selected for deleting!'));
        } else {
            if ($_POST['mode'] == 3) {
                $queries[] = '# ' . __('Reloading the privileges') . ' …';
                $queries[] = 'FLUSH PRIVILEGES;';
            }

            $dropUserError = '';
            foreach ($queries as $sqlQuery) {
                if ($sqlQuery[0] === '#') {
                    continue;
                }

                if ($this->dbi->tryQuery($sqlQuery)) {
                    continue;
                }

                $dropUserError .= $this->dbi->getError() . "\n";
            }

            // tracking sets this, causing the deleted db to be shown in navi
            Current::$database = '';

            $sqlQuery = implode("\n", $queries);
            if ($dropUserError !== '') {
                $message = Message::rawError($dropUserError);
            } else {
                $message = Message::success(
                    __('The selected users have been deleted successfully.'),
                );
            }
        }

        return [$sqlQuery, $message];
    }

    /**
     * Update the privileges and return the success or error message
     *
     * @return array{string, Message} success message or error message for update
     */
    public function updatePrivileges(
        string $username,
        string $hostname,
        string $tablename,
        string $dbname,
        string $itemType,
    ): array {
        $dbAndTable = $this->wildcardEscapeForGrant($dbname, $tablename);

        $sqlQuery0 = 'REVOKE ALL PRIVILEGES ON ' . $itemType . ' ' . $dbAndTable
            . ' FROM ' . $this->dbi->quoteString($username)
            . '@' . $this->dbi->quoteString($hostname) . ';';

        if (! isset($_POST['Grant_priv']) || $_POST['Grant_priv'] !== 'Y') {
            $sqlQuery1 = 'REVOKE GRANT OPTION ON ' . $itemType . ' ' . $dbAndTable
                . ' FROM ' . $this->dbi->quoteString($username) . '@'
                . $this->dbi->quoteString($hostname) . ';';
        } else {
            $sqlQuery1 = '';
        }

        $grantBackQuery = null;
        $alterUserQuery = null;

        // Should not do a GRANT USAGE for a table-specific privilege, it
        // causes problems later (cannot revoke it)
        if (! ($tablename !== '' && implode('', $this->extractPrivInfo()) === 'USAGE')) {
            [$grantBackQuery, $alterUserQuery] = $this->generateQueriesForUpdatePrivileges(
                $itemType,
                $dbAndTable,
                $username,
                $hostname,
                $dbname,
            );
        }

        if (! $this->dbi->tryQuery($sqlQuery0)) {
            // This might fail when the executing user does not have
            // ALL PRIVILEGES themselves.
            // See https://github.com/phpmyadmin/phpmyadmin/issues/9673
            $sqlQuery0 = '';
        }

        if ($sqlQuery1 !== '' && ! $this->dbi->tryQuery($sqlQuery1)) {
            // this one may fail, too...
            $sqlQuery1 = '';
        }

        if ($grantBackQuery !== null) {
            $this->dbi->query($grantBackQuery);
        } else {
            $grantBackQuery = '';
        }

        if ($alterUserQuery !== null) {
            $this->dbi->query($alterUserQuery);
        } else {
            $alterUserQuery = '';
        }

        $sqlQuery = $sqlQuery0 . ' ' . $sqlQuery1 . ' ' . $grantBackQuery . ' ' . $alterUserQuery;
        $message = Message::success(__('You have updated the privileges for %s.'));
        $message->addParam('\'' . $username . '\'@\'' . $hostname . '\'');

        return [$sqlQuery, $message];
    }

    /**
     * Generate the query for the GRANTS and requirements + limits
     *
     * @return array<int,string|null>
     */
    private function generateQueriesForUpdatePrivileges(
        string $itemType,
        string $dbAndTable,
        string $username,
        string $hostname,
        string $dbname,
    ): array {
        $alterUserQuery = null;

        $grantBackQuery = 'GRANT ' . implode(', ', $this->extractPrivInfo())
            . ' ON ' . $itemType . ' ' . $dbAndTable
            . ' TO ' . $this->dbi->quoteString($username) . '@'
            . $this->dbi->quoteString($hostname);

        $isMySqlOrPercona = Compatibility::isMySqlOrPerconaDb();
        $needsToUseAlter = $isMySqlOrPercona && $this->dbi->getVersion() >= 80011;

        if ($needsToUseAlter) {
            $alterUserQuery = 'ALTER USER ' . $this->dbi->quoteString($username) . '@'
            . $this->dbi->quoteString($hostname) . ' ';
        }

        if ($dbname === '') {
            // add REQUIRE clause
            if ($needsToUseAlter) {
                $alterUserQuery .= $this->getRequireClause();
            } else {
                $grantBackQuery .= $this->getRequireClause();
            }
        }

        if (
            (isset($_POST['Grant_priv']) && $_POST['Grant_priv'] === 'Y')
            || ($dbname === ''
            && (isset($_POST['max_questions']) || isset($_POST['max_connections'])
            || isset($_POST['max_updates'])
            || isset($_POST['max_user_connections'])))
        ) {
            if ($needsToUseAlter) {
                $alterUserQuery .= $this->getWithClauseForAddUserAndUpdatePrivs();
            } else {
                $grantBackQuery .= $this->getWithClauseForAddUserAndUpdatePrivs();
            }
        }

        $grantBackQuery .= ';';

        if ($needsToUseAlter) {
            $alterUserQuery .= ';';
        }

        return [$grantBackQuery, $alterUserQuery];
    }

    /**
     * Get List of information: Changes / copies a user
     */
    public function getDataForChangeOrCopyUser(string $oldUsername, string $oldHostname): string|null
    {
        if (isset($_POST['change_copy'])) {
            $userHostCondition = $this->getUserHostCondition($oldUsername, $oldHostname);
            $row = $this->dbi->fetchSingleRow('SELECT * FROM `mysql`.`user` ' . $userHostCondition . ';');
            if ($row === null || $row === []) {
                $response = ResponseRenderer::getInstance();
                $response->addHTML(
                    Message::notice(__('No user found.'))->getDisplay(),
                );
                unset($_POST['change_copy']);
            } else {
                foreach ($row as $key => $value) {
                    $GLOBALS[$key] = $value;
                }

                $serverVersion = $this->dbi->getVersion();
                // Recent MySQL versions have the field "Password" in mysql.user,
                // so the previous extract creates $row['Password'] but this script
                // uses $password
                if (! isset($row['password']) && isset($row['Password'])) {
                    $row['password'] = $row['Password'];
                }

                if (
                    Compatibility::isMySqlOrPerconaDb()
                    && $serverVersion >= 50606
                    && $serverVersion < 50706
                    && ((isset($row['authentication_string'])
                    && empty($row['password']))
                    || (isset($row['plugin'])
                    && $row['plugin'] === 'sha256_password'))
                ) {
                    $row['password'] = $row['authentication_string'];
                }

                if (
                    Compatibility::isMariaDb()
                    && $serverVersion >= 50500
                    && isset($row['authentication_string'])
                    && empty($row['password'])
                ) {
                    $row['password'] = $row['authentication_string'];
                }

                // Always use 'authentication_string' column
                // for MySQL 5.7.6+ since it does not have
                // the 'password' column at all
                if (
                    Compatibility::isMySqlOrPerconaDb()
                    && $serverVersion >= 50706
                    && isset($row['authentication_string'])
                ) {
                    $row['password'] = $row['authentication_string'];
                }

                return $row['password'];
            }
        }

        return null;
    }

    /**
     * Update Data for information: Deletes users
     *
     * @param mixed[] $queries queries array
     *
     * @return mixed[]
     */
    public function getDataForDeleteUsers(array $queries): array
    {
        if (isset($_POST['change_copy'])) {
            $selectedUsr = [$_POST['old_username'] . '&amp;#27;' . $_POST['old_hostname']];
        } else {
            // null happens when no user was selected
            $selectedUsr = $_POST['selected_usr'] ?? null;
            $queries = [];
        }

        // this happens, was seen in https://reports.phpmyadmin.net/reports/view/17146
        if (! is_array($selectedUsr)) {
            return [];
        }

        foreach ($selectedUsr as $eachUser) {
            [$thisUser, $thisHost] = explode('&amp;#27;', $eachUser);
            $queries[] = '# '
                . sprintf(
                    __('Deleting %s'),
                    '\'' . $thisUser . '\'@\'' . $thisHost . '\'',
                )
                . ' ...';
            $queries[] = 'DROP USER '
                . $this->dbi->quoteString($thisUser)
                . '@' . $this->dbi->quoteString($thisHost) . ';';
            $this->relationCleanup->user($thisUser);

            if (! isset($_POST['drop_users_db'])) {
                continue;
            }

            $queries[] = 'DROP DATABASE IF EXISTS '
                . Util::backquote($thisUser) . ';';
            $GLOBALS['reload'] = true;
        }

        return $queries;
    }

    /**
     * update Message For Reload
     */
    public function updateMessageForReload(): Message|null
    {
        $message = null;
        if (isset($_GET['flush_privileges'])) {
            $this->dbi->tryQuery('FLUSH PRIVILEGES;');
            $message = Message::success(
                __('The privileges were reloaded successfully.'),
            );
        }

        if (isset($_GET['validate_username'])) {
            return Message::success();
        }

        return $message;
    }

    /**
     * update Data For Queries from queries_for_display
     *
     * @param mixed[]      $queries           queries array
     * @param mixed[]|null $queriesForDisplay queries array for display
     *
     * @return mixed[]
     */
    public function getDataForQueries(array $queries, array|null $queriesForDisplay): array
    {
        $tmpCount = 0;
        foreach ($queries as $sqlQuery) {
            if ($sqlQuery[0] !== '#') {
                $this->dbi->query($sqlQuery);
            }

            // when there is a query containing a hidden password, take it
            // instead of the real query sent
            if (isset($queriesForDisplay[$tmpCount])) {
                $queries[$tmpCount] = $queriesForDisplay[$tmpCount];
            }

            $tmpCount++;
        }

        return $queries;
    }

    /**
     * update Data for information: Adds a user
     *
     * @param string|mixed[]|null $dbname     db name
     * @param string              $username   user name
     * @param string              $hostname   host name
     * @param string|null         $password   password
     * @param bool                $isMenuwork is_menuwork set?
     *
     * @return array{Message|null, string[], string[]|null, string, bool}
     */
    public function addUser(
        string|array|null $dbname,
        string $username,
        string $hostname,
        string|null $password,
        bool $isMenuwork,
    ): array {
        // Some reports were sent to the error reporting server with phpMyAdmin 5.1.0
        // pred_username was reported to be not defined
        $predUsername = $_POST['pred_username'] ?? '';
        if ($predUsername === 'any') {
            $username = '';
        }

        switch ($_POST['pred_hostname']) {
            case 'any':
                $hostname = '%';
                break;
            case 'localhost':
                $hostname = 'localhost';
                break;
            case 'hosttable':
                $hostname = '';
                break;
            case 'thishost':
                $currentUserName = $this->dbi->fetchValue('SELECT USER()');
                if (is_string($currentUserName)) {
                    $hostname = mb_substr($currentUserName, mb_strrpos($currentUserName, '@') + 1);
                    unset($currentUserName);
                }

                break;
        }

        if ($this->userExists($username, $hostname)) {
            $message = Message::error(__('The user %s already exists!'));
            $message->addParam('[em]\'' . $username . '\'@\'' . $hostname . '\'[/em]');
            $_GET['adduser'] = true;

            return [
                $message,
                [],
                null,
                '',
                true, // Add user error
            ];
        }

        [
            $createUserReal,
            $createUserShow,
            $realSqlQuery,
            $sqlQuery,
            $passwordSetReal,
            $passwordSetShow,
            $alterRealSqlQuery,
            $alterSqlQuery,
        ] = $this->getSqlQueriesForDisplayAndAddUser($username, $hostname, $password ?? '');

        if (empty($_POST['change_copy'])) {
            $error = false;

            if (! $this->dbi->tryQuery($createUserReal)) {
                $error = true;
            }

            if (isset($_POST['authentication_plugin']) && $passwordSetReal !== '') {
                $this->setProperPasswordHashing($_POST['authentication_plugin']);
                if ($this->dbi->tryQuery($passwordSetReal)) {
                    $sqlQuery .= $passwordSetShow;
                }
            }

            $sqlQuery = $createUserShow . $sqlQuery;

            [$sqlQuery, $message] = $this->addUserAndCreateDatabase(
                $error,
                $realSqlQuery,
                $sqlQuery,
                $username,
                $hostname,
                is_string($dbname) ? $dbname : '',
                $alterRealSqlQuery,
                $alterSqlQuery,
                isset($_POST['createdb-1']),
                isset($_POST['createdb-2']),
                isset($_POST['createdb-3']),
            );
            if (! empty($_POST['userGroup']) && $isMenuwork) {
                $this->setUserGroup($GLOBALS['username'], $_POST['userGroup']);
            }

            return [
                $message,
                [],
                null,
                $sqlQuery,
                $error, // Add user error if the query fails
            ];
        }

        // Copy the user group while copying a user
        $oldUserGroup = $_POST['old_usergroup'] ?? '';
        $this->setUserGroup($_POST['username'], $oldUserGroup);

        $queries = [];
        $queries[] = $createUserReal;
        $queries[] = $realSqlQuery;

        if (isset($_POST['authentication_plugin']) && $passwordSetReal !== '') {
            $this->setProperPasswordHashing($_POST['authentication_plugin']);

            $queries[] = $passwordSetReal;
        }

        // we put the query containing the hidden password in
        // $queries_for_display, at the same position occupied
        // by the real query in $queries
        $tmpCount = count($queries);
        $queriesForDisplay = [];
        $queriesForDisplay[$tmpCount - 2] = $createUserShow;

        if ($passwordSetReal !== '') {
            $queriesForDisplay[$tmpCount - 3] = $createUserShow;
            $queriesForDisplay[$tmpCount - 2] = $sqlQuery;
            $queriesForDisplay[$tmpCount - 1] = $passwordSetShow;
        } else {
            $queriesForDisplay[$tmpCount - 1] = $sqlQuery;
        }

        return [
            null,
            $queries,
            $queriesForDisplay,
            $sqlQuery,
            false, // Add user error
        ];
    }

    /**
     * Sets proper value of `old_passwords` according to
     * the authentication plugin selected
     *
     * @param string $authPlugin authentication plugin selected
     */
    public function setProperPasswordHashing(string $authPlugin): void
    {
        // Set the hashing method used by PASSWORD()
        // to be of type depending upon $authentication_plugin
        if ($authPlugin === 'sha256_password') {
            $this->dbi->tryQuery('SET `old_passwords` = 2;');
        } elseif ($authPlugin === 'mysql_old_password') {
            $this->dbi->tryQuery('SET `old_passwords` = 1;');
        } else {
            $this->dbi->tryQuery('SET `old_passwords` = 0;');
        }
    }

    /**
     * Update DB information: DB, Table, isWildcard
     *
     * @return mixed[]
     * @psalm-return array{?string, ?string, array|string|null, ?string, ?string, bool}
     */
    public function getDataForDBInfo(): array
    {
        $username = null;
        $hostname = null;
        $dbname = null;
        $tablename = null;
        $routinename = null;

        if (isset($_REQUEST['username'])) {
            $username = (string) $_REQUEST['username'];
        }

        if (isset($_REQUEST['hostname'])) {
            $hostname = (string) $_REQUEST['hostname'];
        }

        /**
         * Checks if a dropdown box has been used for selecting a database / table
         */
        if (
            isset($_POST['pred_tablename'])
            && is_string($_POST['pred_tablename'])
            && $_POST['pred_tablename'] !== ''
        ) {
            $tablename = $_POST['pred_tablename'];
        } elseif (
            isset($_REQUEST['tablename'])
            && is_string($_REQUEST['tablename'])
            && $_REQUEST['tablename'] !== ''
        ) {
            $tablename = $_REQUEST['tablename'];
        }

        if (
            isset($_POST['pred_routinename'])
            && is_string($_POST['pred_routinename'])
            && $_POST['pred_routinename'] !== ''
        ) {
            $routinename = $_POST['pred_routinename'];
        } elseif (
            isset($_REQUEST['routinename'])
            && is_string($_REQUEST['routinename'])
            && $_REQUEST['routinename'] !== ''
        ) {
            $routinename = $_REQUEST['routinename'];
        }

        // Accept only array of non-empty strings
        if (
            isset($_POST['pred_dbname'])
            && is_array($_POST['pred_dbname'])
            && $_POST['pred_dbname'] === array_filter($_POST['pred_dbname'])
        ) {
            $dbname = $_POST['pred_dbname'];
            // If dbname contains only one database.
            if (count($dbname) === 1) {
                $dbname = (string) $dbname[0];
            }
        }

        if ($dbname === null && isset($_REQUEST['dbname'])) {
            if (is_array($_REQUEST['dbname'])) {
                // Accept only array of non-empty strings
                if ($_REQUEST['dbname'] === array_filter($_REQUEST['dbname'])) {
                    $dbname = $_REQUEST['dbname'];
                }
            } elseif (
                is_string($_REQUEST['dbname'])
                && $_REQUEST['dbname'] !== ''
            ) {
                $dbname = $_REQUEST['dbname'];
            }
        }

        // check if given $dbname is a wildcard or not
        $databaseNameIsWildcard = is_string($dbname) && preg_match('/(?<!\\\\)(?:_|%)/', $dbname) === 1;

        return [$username, $hostname, $dbname, $tablename, $routinename, $databaseNameIsWildcard];
    }

    /**
     * Get title and textarea for export user definition in Privileges
     *
     * @param string        $username      username
     * @param string        $hostname      host name
     * @param string[]|null $selectedUsers
     */
    public function getExportUserDefinitionTextarea(
        string $username,
        string $hostname,
        array|null $selectedUsers,
    ): string {
        $export = '<textarea class="export" cols="60" rows="15">';

        if ($selectedUsers !== null) {
            //For removing duplicate entries of users
            $selectedUsers = array_unique($selectedUsers);

            foreach ($selectedUsers as $exportUser) {
                $exportUsername = mb_substr(
                    $exportUser,
                    0,
                    (int) mb_strpos($exportUser, '&'),
                );
                $exportHostname = mb_substr(
                    $exportUser,
                    mb_strrpos($exportUser, ';') + 1,
                );
                $export .= '# '
                    . sprintf(
                        __('Privileges for %s'),
                        '`' . htmlspecialchars($exportUsername)
                        . '`@`' . htmlspecialchars($exportHostname) . '`',
                    )
                    . "\n\n";
                $export .= $this->getGrants($exportUsername, $exportHostname) . "\n";
            }
        } else {
            // export privileges for a single user
            $export .= $this->getGrants($username, $hostname);
        }

        // remove trailing whitespace
        $export = trim($export);

        return $export . '</textarea>';
    }

    /**
     * Get HTML for display Add userfieldset
     *
     * @return string html output
     */
    private function getAddUserHtmlFieldset(): string
    {
        if (! $this->dbi->isCreateUser()) {
            return '';
        }

        return $this->template->render('server/privileges/add_user_fieldset', ['url_params' => ['adduser' => 1]]);
    }

    private function checkStructureOfPrivilegeTable(): string
    {
        // the query failed! This may have two reasons:
        // - the user does not have enough privileges
        // - the privilege tables use a structure of an earlier version.
        // so let's try a more simple query
        if (! $this->dbi->tryQuery('SELECT 1 FROM `mysql`.`user`')) {
            return $this->getHtmlForViewUsersError() . $this->getAddUserHtmlFieldset();
        }

        // This message is hardcoded because I will replace it by
        // a automatic repair feature soon.
        $raw = 'Your privilege table structure seems to be older than'
            . ' this MySQL version!<br>'
            . 'Please run the <code>mysql_upgrade</code> command'
            . ' that should be included in your MySQL server distribution'
            . ' to solve this problem!';

        return Message::rawError($raw)->getDisplay();
    }

    /**
     * Get HTML snippet for display user overview page
     */
    public function getHtmlForUserOverview(UserPrivileges $userPrivileges, string|null $initial): string
    {
        $serverVersion = $this->dbi->getVersion();
        $passwordColumn = Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50706
            ? 'authentication_string'
            : 'Password';

        // $sql is for the initial-filtered
        $sql = 'SELECT *, IF(`' . $passwordColumn . "` = _latin1 '', 'N', 'Y') AS `Password`" .
            ' FROM `mysql`.`user` ' . $this->rangeOfUsers($initial) . ' ORDER BY `User` ASC, `Host` ASC';

        $res = $this->dbi->tryQuery($sql);
        if ($res === false) {
            $errorMessages = $this->checkStructureOfPrivilegeTable();
        } else {
            $dbRights = $this->getDbRightsForUserOverview($initial);
            $emptyUserNotice = $this->getEmptyUserNotice($dbRights);
            $initialsHtml = $this->getHtmlForInitials();

            // Display the user overview (if less than 50 users, display them immediately)
            if (isset($_GET['initial']) || isset($_GET['showall']) || $res->numRows() < 50) {
                $usersOverview = $this->getUsersOverview($res, $dbRights) .
                    $this->template->render('export_modal');
            }

            $response = ResponseRenderer::getInstance();
            if (! $response->isAjax() || ! empty($_REQUEST['ajax_page_request'])) {
                if ($userPrivileges->isReload) {
                    $flushnote = new Message(
                        __(
                            'Note: phpMyAdmin gets the users’ privileges directly '
                            . 'from MySQL’s privilege tables. The content of these '
                            . 'tables may differ from the privileges the server uses, '
                            . 'if they have been changed manually. In this case, '
                            . 'you should %sreload the privileges%s before you continue.',
                        ),
                        MessageType::Notice,
                    );
                    $flushnote->addParamHtml(
                        '<a href="' . Url::getFromRoute('/server/privileges', ['flush_privileges' => 1])
                        . '" id="reload_privileges_anchor">',
                    );
                    $flushnote->addParamHtml('</a>');
                } else {
                    $flushnote = new Message(
                        __(
                            'Note: phpMyAdmin gets the users’ privileges directly '
                            . 'from MySQL’s privilege tables. The content of these '
                            . 'tables may differ from the privileges the server uses, '
                            . 'if they have been changed manually. In this case, '
                            . 'the privileges have to be reloaded but currently, you '
                            . 'don\'t have the RELOAD privilege.',
                        )
                        . MySQLDocumentation::show(
                            'privileges-provided',
                            false,
                            null,
                            null,
                            'priv_reload',
                        ),
                        MessageType::Notice,
                    );
                }

                $flushNotice = $flushnote->getDisplay();
            }
        }

        return $this->template->render('server/privileges/user_overview', [
            'error_messages' => $errorMessages ?? '',
            'empty_user_notice' => $emptyUserNotice ?? '',
            'initials' => $initialsHtml ?? '',
            'users_overview' => $usersOverview ?? '',
            'is_createuser' => $this->dbi->isCreateUser(),
            'flush_notice' => $flushNotice ?? '',
        ]);
    }

    /**
     * Get HTML snippet for display user properties
     *
     * @param bool           $dbnameIsWildcard whether database name is wildcard or not
     * @param string         $urlDbname        url database name that urlencode() string
     * @param string         $username         username
     * @param string         $hostname         host name
     * @param string|mixed[] $dbname           database name
     * @param string         $tablename        table name
     * @psalm-param non-empty-string $route
     */
    public function getHtmlForUserProperties(
        bool $dbnameIsWildcard,
        string $urlDbname,
        string $username,
        string $hostname,
        string|array $dbname,
        string $tablename,
        string $route,
    ): string {
        $userDoesNotExists = ! $this->userExists($username, $hostname);

        $loginInformationFields = '';
        if ($userDoesNotExists) {
            $loginInformationFields = $this->getHtmlForLoginInformationFields();
        }

        $params = ['username' => $username, 'hostname' => $hostname];
        $params['dbname'] = $dbname;
        if (! is_array($dbname) && $dbname !== '' && $tablename !== '') {
            $params['tablename'] = $tablename;
        }

        $privilegesTable = $this->getHtmlToDisplayPrivilegesTable(
            // If $dbname is an array, pass any one db as all have same privs.
            is_string($dbname) && $dbname !== ''
                ? $dbname
                : (is_array($dbname) ? (string) $dbname[0] : '*'),
            $tablename !== ''
                ? $tablename
                : '*',
        );

        $tableSpecificRights = '';
        if (! is_array($dbname) && $tablename === '' && $dbnameIsWildcard === false) {
            // no table name was given, display all table specific rights
            // but only if $dbname contains no wildcards
            if ($dbname === '') {
                $tableSpecificRights .= $this->getHtmlForAllTableSpecificRights($username, $hostname, 'database');
            } else {
                // unescape wildcards in dbname at table level
                $unescapedDb = $this->unescapeGrantWildcards($dbname);

                $tableSpecificRights .= $this->getHtmlForAllTableSpecificRights(
                    $username,
                    $hostname,
                    'table',
                    $unescapedDb,
                );
                $tableSpecificRights .= $this->getHtmlForAllTableSpecificRights(
                    $username,
                    $hostname,
                    'routine',
                    $unescapedDb,
                );
            }
        }

        $config = Config::getInstance();
        $databaseUrl = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
        $databaseUrlTitle = Util::getTitleForTarget($config->settings['DefaultTabDatabase']);
        $tableUrl = Util::getScriptNameForOption($config->settings['DefaultTabTable'], 'table');
        $tableUrlTitle = Util::getTitleForTarget($config->settings['DefaultTabTable']);

        $changePassword = '';
        $userGroup = '';
        $changeLoginInfoFields = '';
        if ($dbname === '' && ! $userDoesNotExists) {
            //change login information
            $changePassword = $this->getFormForChangePassword($username, $hostname, true, $route);
            $userGroup = $this->getUserGroupForUser($username);
            $changeLoginInfoFields = $this->getHtmlForLoginInformationFields($username, $hostname);
        }

        return $this->template->render('server/privileges/user_properties', [
            'user_does_not_exists' => $userDoesNotExists,
            'login_information_fields' => $loginInformationFields,
            'params' => $params,
            'privileges_table' => $privilegesTable,
            'table_specific_rights' => $tableSpecificRights,
            'change_password' => $changePassword,
            'database' => $dbname,
            'dbname' => $urlDbname,
            'username' => $username,
            'hostname' => $hostname,
            'is_databases' => $dbnameIsWildcard || is_array($dbname) && count($dbname) > 1,
            'is_wildcard' => $dbnameIsWildcard,
            'table' => $tablename,
            'current_user' => $this->dbi->getCurrentUser(),
            'user_group' => $userGroup,
            'change_login_info_fields' => $changeLoginInfoFields,
            'database_url' => $databaseUrl,
            'database_url_title' => $databaseUrlTitle,
            'table_url' => $tableUrl,
            'table_url_title' => $tableUrlTitle,
        ]);
    }

    /**
     * Get queries for Table privileges to change or copy user
     *
     * @param string  $userHostCondition user host condition to select relevant table privileges
     * @param mixed[] $queries           queries array
     * @param string  $username          username
     * @param string  $hostname          host name
     *
     * @return mixed[]
     */
    public function getTablePrivsQueriesForChangeOrCopyUser(
        string $userHostCondition,
        array $queries,
        string $username,
        string $hostname,
    ): array {
        $res = $this->dbi->query(
            'SELECT `Db`, `Table_name`, `Table_priv` FROM `mysql`.`tables_priv`' . $userHostCondition . ';',
        );
        while ($row = $res->fetchAssoc()) {
            $res2 = $this->dbi->query(
                'SELECT `Column_name`, `Column_priv`'
                . ' FROM `mysql`.`columns_priv`'
                . $userHostCondition
                . ' AND `Db` = ' . $this->dbi->quoteString($row['Db'])
                . ' AND `Table_name` = ' . $this->dbi->quoteString($row['Table_name'])
                . ';',
            );

            $tmpPrivs1 = $this->extractPrivInfo($row);
            $tmpPrivs2 = ['Select' => [], 'Insert' => [], 'Update' => [], 'References' => []];

            while ($row2 = $res2->fetchAssoc()) {
                $tmpArray = explode(',', $row2['Column_priv']);
                if (in_array('Select', $tmpArray, true)) {
                    $tmpPrivs2['Select'][] = $row2['Column_name'];
                }

                if (in_array('Insert', $tmpArray, true)) {
                    $tmpPrivs2['Insert'][] = $row2['Column_name'];
                }

                if (in_array('Update', $tmpArray, true)) {
                    $tmpPrivs2['Update'][] = $row2['Column_name'];
                }

                if (! in_array('References', $tmpArray, true)) {
                    continue;
                }

                $tmpPrivs2['References'][] = $row2['Column_name'];
            }

            if ($tmpPrivs2['Select'] !== [] && ! in_array('SELECT', $tmpPrivs1, true)) {
                $tmpPrivs1[] = 'SELECT (`' . implode('`, `', $tmpPrivs2['Select']) . '`)';
            }

            if ($tmpPrivs2['Insert'] !== [] && ! in_array('INSERT', $tmpPrivs1, true)) {
                $tmpPrivs1[] = 'INSERT (`' . implode('`, `', $tmpPrivs2['Insert']) . '`)';
            }

            if ($tmpPrivs2['Update'] !== [] && ! in_array('UPDATE', $tmpPrivs1, true)) {
                $tmpPrivs1[] = 'UPDATE (`' . implode('`, `', $tmpPrivs2['Update']) . '`)';
            }

            if ($tmpPrivs2['References'] !== [] && ! in_array('REFERENCES', $tmpPrivs1, true)) {
                $tmpPrivs1[] = 'REFERENCES (`' . implode('`, `', $tmpPrivs2['References']) . '`)';
            }

            $queries[] = 'GRANT ' . implode(', ', $tmpPrivs1)
                . ' ON ' . Util::backquote($row['Db']) . '.'
                . Util::backquote($row['Table_name'])
                . ' TO ' . $this->dbi->quoteString($username)
                . '@' . $this->dbi->quoteString($hostname)
                . (in_array('Grant', explode(',', $row['Table_priv']), true)
                ? ' WITH GRANT OPTION;'
                : ';');
        }

        return $queries;
    }

    /**
     * Get queries for database specific privileges for change or copy user
     *
     * @param mixed[] $queries  queries array with string
     * @param string  $username username
     * @param string  $hostname host name
     *
     * @return mixed[]
     */
    public function getDbSpecificPrivsQueriesForChangeOrCopyUser(
        array $queries,
        string $username,
        string $hostname,
        string $oldUsername,
        string $oldHostname,
    ): array {
        $userHostCondition = $this->getUserHostCondition($oldUsername, $oldHostname);

        $res = $this->dbi->query('SELECT * FROM `mysql`.`db`' . $userHostCondition . ';');

        foreach ($res as $row) {
            $queries[] = 'GRANT ' . implode(', ', $this->extractPrivInfo($row))
                . ' ON ' . Util::backquote($row['Db']) . '.*'
                . ' TO ' . $this->dbi->quoteString($username) . '@' . $this->dbi->quoteString($hostname)
                . ($row['Grant_priv'] === 'Y' ? ' WITH GRANT OPTION;' : ';');
        }

        return $this->getTablePrivsQueriesForChangeOrCopyUser($userHostCondition, $queries, $username, $hostname);
    }

    /**
     * Prepares queries for adding users and
     * also create database and return query and message
     *
     * @param bool   $error             whether user create or not
     * @param string $realSqlQuery      SQL query for add a user
     * @param string $sqlQuery          SQL query to be displayed
     * @param string $username          username
     * @param string $hostname          host name
     * @param string $dbname            database name
     * @param string $alterRealSqlQuery SQL query for ALTER USER
     * @param string $alterSqlQuery     SQL query for ALTER USER to be displayed
     *
     * @return array{string, Message}
     */
    public function addUserAndCreateDatabase(
        bool $error,
        string $realSqlQuery,
        string $sqlQuery,
        string $username,
        string $hostname,
        string $dbname,
        string $alterRealSqlQuery,
        string $alterSqlQuery,
        bool $createDb1,
        bool $createDb2,
        bool $createDb3,
    ): array {
        if ($error || ($realSqlQuery !== '' && ! $this->dbi->tryQuery($realSqlQuery))) {
            $createDb1 = $createDb2 = $createDb3 = false;
            $message = Message::rawError($this->dbi->getError());
        } elseif ($alterRealSqlQuery !== '' && ! $this->dbi->tryQuery($alterRealSqlQuery)) {
            $createDb1 = $createDb2 = $createDb3 = false;
            $message = Message::rawError($this->dbi->getError());
        } else {
            $sqlQuery .= $alterSqlQuery;
            $message = Message::success(__('You have added a new user.'));
        }

        if ($createDb1) {
            // Create database with same name and grant all privileges
            $query = 'CREATE DATABASE IF NOT EXISTS ' . Util::backquote($username) . ';';
            $sqlQuery .= $query;
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::rawError($this->dbi->getError());
            }

            /**
             * Reload the navigation
             */
            $GLOBALS['reload'] = true;
            Current::$database = $username;

            $query = 'GRANT ALL PRIVILEGES ON '
                . Util::backquote(
                    $this->escapeGrantWildcards($username),
                ) . '.* TO '
                . $this->dbi->quoteString($username) . '@' . $this->dbi->quoteString($hostname) . ';';
            $sqlQuery .= $query;
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::rawError($this->dbi->getError());
            }
        }

        if ($createDb2) {
            // Grant all privileges on wildcard name (username\_%)
            $query = 'GRANT ALL PRIVILEGES ON '
                . Util::backquote(
                    $this->escapeGrantWildcards($username) . '\_%',
                ) . '.* TO '
                . $this->dbi->quoteString($username) . '@' . $this->dbi->quoteString($hostname) . ';';
            $sqlQuery .= $query;
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::rawError($this->dbi->getError());
            }
        }

        if ($createDb3) {
            // Grant all privileges on the specified database to the new user
            $query = 'GRANT ALL PRIVILEGES ON '
            . Util::backquote($dbname) . '.* TO '
            . $this->dbi->quoteString($username) . '@' . $this->dbi->quoteString($hostname) . ';';
            $sqlQuery .= $query;
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::rawError($this->dbi->getError());
            }
        }

        return [$sqlQuery, $message];
    }

    /**
     * Get the hashed string for password
     *
     * @param string $password password
     */
    public function getHashedPassword(string $password): string
    {
        return (string) $this->dbi->fetchValue('SELECT PASSWORD(' . $this->dbi->quoteString($password) . ');');
    }

    /**
     * Check if MariaDB's 'simple_password_check'
     * OR 'cracklib_password_check' is ACTIVE
     */
    private function checkIfMariaDBPwdCheckPluginActive(): bool
    {
        $serverVersion = $this->dbi->getVersion();
        if (! (Compatibility::isMariaDb() && $serverVersion >= 100002)) {
            return false;
        }

        $result = $this->dbi->tryQuery('SHOW PLUGINS SONAME LIKE \'%_password_check%\'');

        /* Plugins are not working, for example directory does not exists */
        if ($result === false) {
            return false;
        }

        while ($row = $result->fetchAssoc()) {
            if ($row['Status'] === 'ACTIVE') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get SQL queries for Display and Add user
     *
     * @param string $username username
     * @param string $hostname host name
     * @param string $password password
     *
     * @return array{string, string, string, string, string, string, string, string}
     */
    public function getSqlQueriesForDisplayAndAddUser(string $username, string $hostname, string $password): array
    {
        $slashedUsername = $this->dbi->quoteString($username);
        $slashedHostname = $this->dbi->quoteString($hostname);
        $slashedPassword = $this->dbi->quoteString($password);
        $serverVersion = $this->dbi->getVersion();

        $createUserStmt = sprintf('CREATE USER %s@%s', $slashedUsername, $slashedHostname);
        $isMariaDBPwdPluginActive = $this->checkIfMariaDBPwdCheckPluginActive();

        // See https://github.com/phpmyadmin/phpmyadmin/pull/11560#issuecomment-147158219
        // for details regarding details of syntax usage for various versions

        // 'IDENTIFIED WITH auth_plugin'
        // is supported by MySQL 5.5.7+
        if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50507 && isset($_POST['authentication_plugin'])) {
            $createUserStmt .= ' IDENTIFIED WITH '
                . $_POST['authentication_plugin'];
        }

        // 'IDENTIFIED VIA auth_plugin'
        // is supported by MariaDB 5.2+
        if (
            Compatibility::isMariaDb()
            && $serverVersion >= 50200
            && isset($_POST['authentication_plugin'])
            && ! $isMariaDBPwdPluginActive
        ) {
            $createUserStmt .= ' IDENTIFIED VIA '
                . $_POST['authentication_plugin'];
        }

        $createUserReal = $createUserStmt;
        $createUserShow = $createUserStmt;

        $passwordSetStmt = 'SET PASSWORD FOR %s@%s = %s';
        $passwordSetShow = sprintf($passwordSetStmt, $slashedUsername, $slashedHostname, '\'***\'');

        $sqlQueryStmt = sprintf(
            'GRANT %s ON *.* TO %s@%s',
            implode(', ', $this->extractPrivInfo()),
            $slashedUsername,
            $slashedHostname,
        );
        $realSqlQuery = $sqlQuery = $sqlQueryStmt;

        // Set the proper hashing method
        if (isset($_POST['authentication_plugin'])) {
            $this->setProperPasswordHashing($_POST['authentication_plugin']);
        }

        // Use 'CREATE USER ... WITH ... AS ..' syntax for
        // newer MySQL versions
        // and 'CREATE USER ... VIA .. USING ..' syntax for
        // newer MariaDB versions
        if (
            (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50706)
            || (Compatibility::isMariaDb() && $serverVersion >= 50200)
        ) {
            $passwordSetReal = '';

            // Required for binding '%' with '%s'
            $createUserStmt = str_replace('%', '%%', $createUserStmt);

            // MariaDB uses 'USING' whereas MySQL uses 'AS'
            // but MariaDB with validation plugin needs cleartext password
            if (Compatibility::isMariaDb() && ! $isMariaDBPwdPluginActive && isset($_POST['authentication_plugin'])) {
                $createUserStmt .= ' USING %s';
            } elseif (Compatibility::isMariaDb()) {
                $createUserStmt .= ' IDENTIFIED BY %s';
            } elseif (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 80011) {
                if (! str_contains($createUserStmt, 'IDENTIFIED')) {
                    // Maybe the authentication_plugin was not posted and then a part is missing
                    $createUserStmt .= ' IDENTIFIED BY %s';
                } else {
                    $createUserStmt .= ' BY %s';
                }
            } else {
                $createUserStmt .= ' AS %s';
            }

            if ($_POST['pred_password'] === 'keep') {
                $createUserReal = sprintf($createUserStmt, $slashedPassword);
            } elseif ($_POST['pred_password'] === 'none') {
                $createUserReal = sprintf($createUserStmt, "''");
            } else {
                if (
                    ! ((Compatibility::isMariaDb() && $isMariaDBPwdPluginActive)
                    || Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 80011)
                ) {
                    $hashedPassword = $this->getHashedPassword($_POST['pma_pw']);
                } else {
                    // MariaDB with validation plugin needs cleartext password
                    $hashedPassword = $_POST['pma_pw'];
                }

                $createUserReal = sprintf($createUserStmt, $this->dbi->quoteString($hashedPassword));
            }

            $createUserShow = sprintf($createUserStmt, '\'***\'');
        } elseif ($_POST['pred_password'] === 'keep') {
            // Use 'SET PASSWORD' syntax for pre-5.7.6 MySQL versions
            // and pre-5.2.0 MariaDB versions
            $passwordSetReal = sprintf($passwordSetStmt, $slashedUsername, $slashedHostname, $slashedPassword);
        } elseif ($_POST['pred_password'] === 'none') {
            $passwordSetReal = sprintf($passwordSetStmt, $slashedUsername, $slashedHostname, "''");
        } else {
            $hashedPassword = $this->getHashedPassword($_POST['pma_pw']);
            $passwordSetReal = sprintf(
                $passwordSetStmt,
                $slashedUsername,
                $slashedHostname,
                $this->dbi->quoteString($hashedPassword),
            );
        }

        $alterRealSqlQuery = '';
        $alterSqlQuery = '';
        if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 80011) {
            $sqlQueryStmt = '';
            if (isset($_POST['Grant_priv']) && $_POST['Grant_priv'] === 'Y') {
                $sqlQueryStmt = ' WITH GRANT OPTION';
            }

            $realSqlQuery .= $sqlQueryStmt;
            $sqlQuery .= $sqlQueryStmt;

            $alterSqlQueryStmt = sprintf('ALTER USER %s@%s', $slashedUsername, $slashedHostname);
            $alterRealSqlQuery = $alterSqlQueryStmt;
            $alterSqlQuery = $alterSqlQueryStmt;
        }

        // add REQUIRE clause
        $requireClause = $this->getRequireClause();
        $withClause = $this->getWithClauseForAddUserAndUpdatePrivs();

        if (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 80011) {
            $alterRealSqlQuery .= $requireClause;
            $alterSqlQuery .= $requireClause;
            $alterRealSqlQuery .= $withClause;
            $alterSqlQuery .= $withClause;
        } else {
            $realSqlQuery .= $requireClause;
            $sqlQuery .= $requireClause;
            $realSqlQuery .= $withClause;
            $sqlQuery .= $withClause;
        }

        if ($alterRealSqlQuery !== '') {
            $alterRealSqlQuery .= ';';
            $alterSqlQuery .= ';';
        }

        $createUserReal .= ';';
        $createUserShow .= ';';
        $realSqlQuery .= ';';
        $sqlQuery .= ';';
        // No Global GRANT_OPTION privilege
        if (! $this->dbi->isGrantUser()) {
            $realSqlQuery = '';
            $sqlQuery = '';
        }

        // Use 'SET PASSWORD' for pre-5.7.6 MySQL versions
        // and pre-5.2.0 MariaDB
        if (
            (Compatibility::isMySqlOrPerconaDb()
            && $serverVersion >= 50706)
            || (Compatibility::isMariaDb()
            && $serverVersion >= 50200)
        ) {
            $passwordSetReal = '';
            $passwordSetShow = '';
        } else {
            if ($passwordSetReal !== '') {
                $passwordSetReal .= ';';
            }

            $passwordSetShow .= ';';
        }

        return [
            $createUserReal,
            $createUserShow,
            $realSqlQuery,
            $sqlQuery,
            $passwordSetReal,
            $passwordSetShow,
            $alterRealSqlQuery,
            $alterSqlQuery,
        ];
    }

    /**
     * Returns the type ('PROCEDURE' or 'FUNCTION') of the routine
     *
     * @param string $dbname      database
     * @param string $routineName routine
     *
     * @return string type
     */
    public function getRoutineType(string $dbname, string $routineName): string
    {
        $routineData = Routines::getDetails($this->dbi, $dbname);
        $routineName = mb_strtolower($routineName);

        foreach ($routineData as $routine) {
            if (mb_strtolower($routine->name) === $routineName) {
                return $routine->type;
            }
        }

        return '';
    }

    /**
     * @param string $username User name
     * @param string $hostname Host name
     * @param string $database Database name
     * @param string $routine  Routine name
     *
     * @return array<string, string>
     */
    private function getRoutinePrivileges(
        string $username,
        string $hostname,
        string $database,
        string $routine,
    ): array {
        $sql = 'SELECT `Proc_priv`'
            . ' FROM `mysql`.`procs_priv`'
            . $this->getUserHostCondition($username, $hostname)
            . ' AND `Db` = ' . $this->dbi->quoteString($this->unescapeGrantWildcards($database))
            . ' AND `Routine_name` LIKE ' . $this->dbi->quoteString($routine) . ';';
        $privileges = (string) $this->dbi->fetchValue($sql);

        return $this->parseProcPriv($privileges);
    }

    /** @psalm-param non-empty-string $route */
    public function getFormForChangePassword(
        string $username,
        string $hostname,
        bool $editOthers,
        string $route,
    ): string {
        $isPrivileges = $route === '/server/privileges';

        $serverVersion = $this->dbi->getVersion();
        $origAuthPlugin = $this->getCurrentAuthenticationPlugin($username, $hostname);

        $isNew = (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50507)
            || (Compatibility::isMariaDb() && $serverVersion >= 50200);
        $hasMoreAuthPlugins = (Compatibility::isMySqlOrPerconaDb() && $serverVersion >= 50706)
            || ($this->dbi->isSuperUser() && $editOthers);

        $activeAuthPlugins = ['mysql_native_password' => __('Native MySQL authentication')];

        if ($isNew && $hasMoreAuthPlugins) {
            $activeAuthPlugins = $this->plugins->getAuthentication();
            if (isset($activeAuthPlugins['mysql_old_password'])) {
                unset($activeAuthPlugins['mysql_old_password']);
            }
        }

        return $this->template->render('server/privileges/change_password', [
            'username' => $username,
            'hostname' => $hostname,
            'is_privileges' => $isPrivileges,
            'is_new' => $isNew,
            'has_more_auth_plugins' => $hasMoreAuthPlugins,
            'active_auth_plugins' => $activeAuthPlugins,
            'orig_auth_plugin' => $origAuthPlugin,
            'allow_no_password' => $this->config->selectedServer['AllowNoPassword'],
        ]);
    }

    /**
     * @see https://dev.mysql.com/doc/refman/en/account-locking.html
     * @see https://mariadb.com/kb/en/account-locking/
     *
     * @return array<string, string|null>|null
     */
    private function getUserPrivileges(string $user, string $host, bool $hasAccountLocking): array|null
    {
        $query = 'SELECT * FROM `mysql`.`user` WHERE `User` = ? AND `Host` = ?;';
        $statement = $this->dbi->prepare($query);
        if ($statement === null || ! $statement->execute([$user, $host])) {
            return null;
        }

        $result = $statement->getResult();
        /** @var array<string, string|null>|null $userPrivileges */
        $userPrivileges = $result->fetchAssoc();
        if ($userPrivileges === []) {
            return null;
        }

        if (! $hasAccountLocking || ! $this->dbi->isMariaDB()) {
            return $userPrivileges;
        }

        $userPrivileges['account_locked'] = 'N';

        $query = 'SELECT * FROM `mysql`.`global_priv` WHERE `User` = ? AND `Host` = ?;';
        $statement = $this->dbi->prepare($query);
        if ($statement === null || ! $statement->execute([$user, $host])) {
            return $userPrivileges;
        }

        $result = $statement->getResult();
        /** @var array<string, string|null>|null $globalPrivileges */
        $globalPrivileges = $result->fetchAssoc();
        if ($globalPrivileges === []) {
            return $userPrivileges;
        }

        $privileges = json_decode($globalPrivileges['Priv'] ?? '[]', true);
        if (! is_array($privileges)) {
            return $userPrivileges;
        }

        if (isset($privileges['account_locked']) && $privileges['account_locked']) {
            $userPrivileges['account_locked'] = 'Y';
        }

        return $userPrivileges;
    }

    private function getUserHostCondition(string $username, string $hostname): string
    {
        return ' WHERE `User` = ' . $this->dbi->quoteString($username)
            . ' AND `Host` = ' . $this->dbi->quoteString($hostname);
    }

    private function userExists(string $username, string $hostname): bool
    {
        $sql = "SELECT '1' FROM `mysql`.`user`" . $this->getUserHostCondition($username, $hostname) . ';';

        return (bool) $this->dbi->fetchValue($sql);
    }

    /** @param array<array<array<mixed>>> $dbRights */
    private function getEmptyUserNotice(array $dbRights): string
    {
        foreach ($dbRights as $user => $userRights) {
            foreach (array_keys($userRights) as $host) {
                if ($user === '' && $host === 'localhost') {
                    return Message::notice(
                        __(
                            'A user account allowing any user from localhost to '
                            . 'connect is present. This will prevent other users '
                            . 'from connecting if the host part of their account '
                            . 'allows a connection from any (%) host.',
                        )
                        . MySQLDocumentation::show('problems-connecting'),
                    )->getDisplay();
                }
            }
        }

        return '';
    }
}
