<?php
/**
 * Get user's global privileges and some db-specific privileges
 */

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Utils\SessionCache;

use function mb_strpos;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function str_contains;

/**
 * PhpMyAdmin\CheckUserPrivileges class
 */
class CheckUserPrivileges
{
    public function __construct(private DatabaseInterface $dbi)
    {
    }

    /**
     * Check if user has required privileges for
     * performing 'Adjust privileges' operations
     */
    public function checkRequiredPrivilegesForAdjust(
        ShowGrants $showGrants,
    ): void {
        // '... ALL PRIVILEGES ON *.* ...' OR '... ALL PRIVILEGES ON `mysql`.* ..'
        // OR
        // SELECT, INSERT, UPDATE, DELETE .... ON *.* OR `mysql`.*
        if (
            $showGrants->grants !== 'ALL'
            && $showGrants->grants !== 'ALL PRIVILEGES'
            && (mb_strpos($showGrants->grants, 'SELECT, INSERT, UPDATE, DELETE') === false)
        ) {
            return;
        }

        if ($showGrants->dbName === '*' && $showGrants->tableName === '*') {
            $GLOBALS['col_priv'] = true;
            $GLOBALS['db_priv'] = true;
            $GLOBALS['proc_priv'] = true;
            $GLOBALS['table_priv'] = true;

            if ($showGrants->grants === 'ALL PRIVILEGES' || $showGrants->grants === 'ALL') {
                $GLOBALS['is_reload_priv'] = true;
            }
        }

        // check for specific tables in `mysql` db
        // Ex. '... ALL PRIVILEGES on `mysql`.`columns_priv` .. '
        if ($showGrants->dbName !== 'mysql') {
            return;
        }

        switch ($showGrants->tableName) {
            case 'columns_priv':
                $GLOBALS['col_priv'] = true;
                break;
            case 'db':
                $GLOBALS['db_priv'] = true;
                break;
            case 'procs_priv':
                $GLOBALS['proc_priv'] = true;
                break;
            case 'tables_priv':
                $GLOBALS['table_priv'] = true;
                break;
            case '*':
                $GLOBALS['col_priv'] = true;
                $GLOBALS['db_priv'] = true;
                $GLOBALS['proc_priv'] = true;
                $GLOBALS['table_priv'] = true;
                break;
            default:
        }
    }

    /**
     * sets privilege information extracted from SHOW GRANTS result
     *
     * Detection for some CREATE privilege.
     *
     * Since MySQL 4.1.2, we can easily detect current user's grants using $userlink
     * (no control user needed) and we don't have to try any other method for
     * detection
     *
     * @todo fix to get really all privileges, not only explicitly defined for this user
     * from MySQL manual: (https://dev.mysql.com/doc/refman/5.0/en/show-grants.html)
     * SHOW GRANTS displays only the privileges granted explicitly to the named
     * account. Other privileges might be available to the account, but they are not
     * displayed. For example, if an anonymous account exists, the named account
     * might be able to use its privileges, but SHOW GRANTS will not display them.
     */
    private function analyseShowGrant(): void
    {
        if (SessionCache::has('is_create_db_priv')) {
            $GLOBALS['is_create_db_priv'] = SessionCache::get('is_create_db_priv');
            $GLOBALS['is_reload_priv'] = SessionCache::get('is_reload_priv');
            $GLOBALS['db_to_create'] = SessionCache::get('db_to_create');
            $GLOBALS['dbs_to_test'] = SessionCache::get('dbs_to_test');

            $GLOBALS['db_priv'] = SessionCache::get('db_priv');
            $GLOBALS['col_priv'] = SessionCache::get('col_priv');
            $GLOBALS['table_priv'] = SessionCache::get('table_priv');
            $GLOBALS['proc_priv'] = SessionCache::get('proc_priv');

            return;
        }

        // defaults
        $GLOBALS['is_create_db_priv'] = false;
        $GLOBALS['is_reload_priv'] = false;
        $GLOBALS['db_to_create'] = '';
        $GLOBALS['dbs_to_test'] = Utilities::getSystemSchemas();
        $GLOBALS['proc_priv'] = false;
        $GLOBALS['db_priv'] = false;
        $GLOBALS['col_priv'] = false;
        $GLOBALS['table_priv'] = false;

        $showGrantsResult = $this->dbi->tryQuery('SHOW GRANTS');

        if (! $showGrantsResult) {
            return;
        }

        $re0 = '(^|(\\\\\\\\)+|[^\\\\])'; // non-escaped wildcards
        $re1 = '(^|[^\\\\])(\\\)+'; // escaped wildcards

        while ($showGrants = $showGrantsResult->fetchValue()) {
            $showGrants = new ShowGrants($showGrants);

            if ($showGrants->dbName === '*') {
                if ($showGrants->grants !== 'USAGE') {
                    $GLOBALS['dbs_to_test'] = false;
                }
            } elseif ($GLOBALS['dbs_to_test'] !== false) {
                $GLOBALS['dbs_to_test'][] = $showGrants->dbName;
            }

            if (str_contains($showGrants->grants, 'RELOAD')) {
                $GLOBALS['is_reload_priv'] = true;
            }

            // check for the required privileges for adjust
            $this->checkRequiredPrivilegesForAdjust($showGrants);

            /**
             * @todo if we find CREATE VIEW but not CREATE, do not offer
             * the create database dialog box
             */
            if (
                $showGrants->grants !== 'ALL'
                && $showGrants->grants !== 'ALL PRIVILEGES'
                && $showGrants->grants !== 'CREATE'
                && ! str_contains($showGrants->grants, 'CREATE,')
            ) {
                continue;
            }

            if ($showGrants->dbName === '*') {
                // a global CREATE privilege
                $GLOBALS['is_create_db_priv'] = true;
                $GLOBALS['is_reload_priv'] = true;
                $GLOBALS['db_to_create'] = '';
                // @todo we should not break here, cause GRANT ALL *.*
                // could be revoked by a later rule like GRANT SELECT ON db.*
                break;
            }

            $dbNameToTest = Util::backquote($showGrants->dbName);

            if ($GLOBALS['is_create_db_priv']) {
                // no need for any more tests if we already know this
                continue;
            }

            // does this db exist?
            if (
                (! preg_match('/' . $re0 . '%|_/', $showGrants->dbName)
                || preg_match('/\\\\%|\\\\_/', $showGrants->dbName))
                && ($this->dbi->tryQuery(
                    'USE ' . preg_replace(
                        '/' . $re1 . '(%|_)/',
                        '\\1\\3',
                        $dbNameToTest,
                    ),
                )
                || mb_substr($this->dbi->getError(), 1, 4) == 1044)
            ) {
                continue;
            }

            /**
             * Do not handle the underscore wildcard
             * (this case must be rare anyway)
             */
            $GLOBALS['db_to_create'] = preg_replace('/' . $re0 . '%/', '\\1', $showGrants->dbName);
            $GLOBALS['db_to_create'] = preg_replace('/' . $re1 . '(%|_)/', '\\1\\3', $GLOBALS['db_to_create']);
            $GLOBALS['is_create_db_priv'] = true;

            /**
             * @todo collect $GLOBALS['db_to_create'] into an array,
             * to display a drop-down in the "Create database" dialog
             */
            // we don't break, we want all possible databases
            //break;
        }

        // must also cacheUnset() them in
        // PhpMyAdmin\Plugins\Auth\AuthenticationCookie
        SessionCache::set('is_create_db_priv', $GLOBALS['is_create_db_priv']);
        SessionCache::set('is_reload_priv', $GLOBALS['is_reload_priv']);
        SessionCache::set('db_to_create', $GLOBALS['db_to_create']);
        SessionCache::set('dbs_to_test', $GLOBALS['dbs_to_test']);

        SessionCache::set('proc_priv', $GLOBALS['proc_priv']);
        SessionCache::set('table_priv', $GLOBALS['table_priv']);
        SessionCache::set('col_priv', $GLOBALS['col_priv']);
        SessionCache::set('db_priv', $GLOBALS['db_priv']);
    }

    /**
     * Get user's global privileges and some db-specific privileges
     */
    public function getPrivileges(): void
    {
        $username = '';

        $current = $this->dbi->getCurrentUserAndHost();
        if ($current !== []) {
            [$username] = $current;
        }

        // If MySQL is started with --skip-grant-tables
        if ($username === '') {
            $GLOBALS['is_create_db_priv'] = true;
            $GLOBALS['is_reload_priv'] = true;
            $GLOBALS['db_to_create'] = '';
            $GLOBALS['dbs_to_test'] = false;
            $GLOBALS['db_priv'] = true;
            $GLOBALS['col_priv'] = true;
            $GLOBALS['table_priv'] = true;
            $GLOBALS['proc_priv'] = true;

            return;
        }

        $this->analyseShowGrant();
    }
}
