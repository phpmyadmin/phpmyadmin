<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Utils\SessionCache;

use function mb_substr;
use function preg_match;
use function preg_replace;
use function str_contains;

/**
 * Get user's global privileges and some db-specific privileges
 */
class UserPrivilegesFactory
{
    public function __construct(private DatabaseInterface $dbi)
    {
    }

    /**
     * Check if user has required privileges for
     * performing 'Adjust privileges' operations
     */
    public function checkRequiredPrivilegesForAdjust(UserPrivileges $userPrivileges, ShowGrants $showGrants): void
    {
        // '... ALL PRIVILEGES ON *.* ...' OR '... ALL PRIVILEGES ON `mysql`.* ..'
        // OR
        // SELECT, INSERT, UPDATE, DELETE .... ON *.* OR `mysql`.*
        if (
            $showGrants->grants !== 'ALL'
            && $showGrants->grants !== 'ALL PRIVILEGES'
            && ! str_contains($showGrants->grants, 'SELECT, INSERT, UPDATE, DELETE')
        ) {
            return;
        }

        if ($showGrants->dbName === '*' && $showGrants->tableName === '*') {
            $userPrivileges->column = true;
            $userPrivileges->database = true;
            $userPrivileges->routines = true;
            $userPrivileges->table = true;

            if ($showGrants->grants === 'ALL PRIVILEGES' || $showGrants->grants === 'ALL') {
                $userPrivileges->isReload = true;
            }
        }

        // check for specific tables in `mysql` db
        // Ex. '... ALL PRIVILEGES on `mysql`.`columns_priv` .. '
        if ($showGrants->dbName !== 'mysql') {
            return;
        }

        switch ($showGrants->tableName) {
            case 'columns_priv':
                $userPrivileges->column = true;
                break;
            case 'db':
                $userPrivileges->database = true;
                break;
            case 'procs_priv':
                $userPrivileges->routines = true;
                break;
            case 'tables_priv':
                $userPrivileges->table = true;
                break;
            case '*':
                $userPrivileges->column = true;
                $userPrivileges->database = true;
                $userPrivileges->routines = true;
                $userPrivileges->table = true;
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
    private function analyseShowGrant(): UserPrivileges
    {
        if (SessionCache::has('is_create_db_priv')) {
            return new UserPrivileges(
                SessionCache::get('db_priv'),
                SessionCache::get('table_priv'),
                SessionCache::get('col_priv'),
                SessionCache::get('proc_priv'),
                SessionCache::get('is_reload_priv'),
                SessionCache::get('is_create_db_priv'),
                SessionCache::get('db_to_create'),
                SessionCache::get('dbs_to_test'),
            );
        }

        $showGrantsResult = $this->dbi->tryQuery('SHOW GRANTS');
        if (! $showGrantsResult) {
            return new UserPrivileges(databasesToTest: Utilities::getSystemSchemas());
        }

        $userPrivileges = new UserPrivileges(databasesToTest: Utilities::getSystemSchemas());

        $re0 = '(^|(\\\\\\\\)+|[^\\\\])'; // non-escaped wildcards
        $re1 = '(^|[^\\\\])(\\\)+'; // escaped wildcards

        while ($showGrants = $showGrantsResult->fetchValue()) {
            $showGrants = new ShowGrants($showGrants);

            if ($showGrants->dbName === '*') {
                if ($showGrants->grants !== 'USAGE') {
                    $userPrivileges->databasesToTest = false;
                }
            } elseif ($userPrivileges->databasesToTest !== false) {
                $userPrivileges->databasesToTest[] = $showGrants->dbName;
            }

            if (str_contains($showGrants->grants, 'RELOAD')) {
                $userPrivileges->isReload = true;
            }

            // check for the required privileges for adjust
            $this->checkRequiredPrivilegesForAdjust($userPrivileges, $showGrants);

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
                $userPrivileges->isCreateDatabase = true;
                $userPrivileges->isReload = true;
                $userPrivileges->databaseToCreate = '';
                // @todo we should not break here, cause GRANT ALL *.*
                // could be revoked by a later rule like GRANT SELECT ON db.*
                break;
            }

            $dbNameToTest = Util::backquote($showGrants->dbName);

            if ($userPrivileges->isCreateDatabase) {
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
            $userPrivileges->databaseToCreate = preg_replace('/' . $re0 . '%/', '\\1', $showGrants->dbName);
            $userPrivileges->databaseToCreate = preg_replace(
                '/' . $re1 . '(%|_)/',
                '\\1\\3',
                $userPrivileges->databaseToCreate,
            );
            $userPrivileges->isCreateDatabase = true;

            /**
             * @todo collect {@see UserPrivileges::$databaseToCreate} into an array,
             * to display a drop-down in the "Create database" dialog
             */
            // we don't break, we want all possible databases
            //break;
        }

        // must also cacheUnset() them in
        // PhpMyAdmin\Plugins\Auth\AuthenticationCookie
        SessionCache::set('is_create_db_priv', $userPrivileges->isCreateDatabase);
        SessionCache::set('is_reload_priv', $userPrivileges->isReload);
        SessionCache::set('db_to_create', $userPrivileges->databaseToCreate);
        SessionCache::set('dbs_to_test', $userPrivileges->databasesToTest);

        SessionCache::set('proc_priv', $userPrivileges->routines);
        SessionCache::set('table_priv', $userPrivileges->table);
        SessionCache::set('col_priv', $userPrivileges->column);
        SessionCache::set('db_priv', $userPrivileges->database);

        return $userPrivileges;
    }

    /**
     * Get user's global privileges and some db-specific privileges
     */
    public function getPrivileges(): UserPrivileges
    {
        $username = '';
        $current = $this->dbi->getCurrentUserAndHost();
        if ($current !== []) {
            [$username] = $current;
        }

        // If MySQL is started with --skip-grant-tables
        if ($username === '') {
            return new UserPrivileges(
                database: true,
                table: true,
                column: true,
                routines: true,
                isReload: true,
                isCreateDatabase: true,
            );
        }

        return $this->analyseShowGrant();
    }
}
