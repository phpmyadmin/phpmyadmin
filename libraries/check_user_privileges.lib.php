<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Get user's global privileges and some db-specific privileges
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
$GLOBALS['is_superuser'] = $GLOBALS['dbi']->isSuperuser();

/**
 * Check if user has required privileges for
 * performing 'FLUSH PRIVILEGES' operation
 *
 * @return void
 */
function PMA_checkRequiredPrivilegesForFlushing()
{

    $res = $GLOBALS['dbi']->tryQuery(
        'FLUSH PRIVILEGES'
    );

    // Save the value
    $GLOBALS['flush_priv'] = $res;
}

/**
 * Check if user has required privileges for
 * performing 'Adjust privileges' operations
 *
 * @return void
 */
function PMA_checkRequiredPrivilgesForAdjust()
{
    $privs_available = true;
    // FOR DB PRIVS
    $select_privs_available = $GLOBALS['dbi']->tryQuery(
        'SELECT * FROM `mysql`.`db` LIMIT 1'
    );

    $privs_available = $select_privs_available && $privs_available;

    if ($privs_available) {
        $delete_privs_available = $GLOBALS['dbi']->tryQuery(
            'DELETE FROM `mysql`.`db` WHERE `host` = "" AND '
            . '`Db` = "" AND `User` = "" LIMIT 1'
        );
        $privs_available = $delete_privs_available && $privs_available;
    }

    if ($privs_available) {
        $insert_privs_available = $GLOBALS['dbi']->tryQuery(
            'INSERT INTO `mysql`.`db`(`host`, `Db`, `User`) VALUES("pma_test_host", '
            . '"mysql", "pma_test_user")'
        );
        // If successful test insert, delete the test row
        if ($insert_privs_available) {
            $GLOBALS['dbi']->tryQuery(
                'DELETE FROM `mysql`.`db` WHERE host = "pma_test_host" AND '
                . 'Db = "mysql" AND User = "pma_test_user" LIMIT 1'
            );
        }
        $privs_available = $insert_privs_available && $privs_available;
    }

    if ($privs_available) {
        $update_privs_available = $GLOBALS['dbi']->tryQuery(
            'UPDATE `mysql`.`db` SET `host` = "" WHERE `host` = "" AND '
            . '`Db` = "" AND `User` = "" LIMIT 1'
        );
        $privs_available = $update_privs_available && $privs_available;
    }
    // save the value
    $GLOBALS['db_priv'] = $privs_available;
    // reset the value
    $privs_available = true;

    // FOR COLUMNS_PRIV
    $select_privs_available = $GLOBALS['dbi']->tryQuery(
        'SELECT * FROM `mysql`.`columns_priv` LIMIT 1'
    );

    $privs_available = $select_privs_available && $privs_available;

    if ($privs_available) {
        $delete_privs_available = $GLOBALS['dbi']->tryQuery(
            'DELETE FROM `mysql`.`columns_priv` WHERE `host` = "" AND '
            . '`Db` = "" AND `User` = "" LIMIT 1'
        );
        $privs_available = $delete_privs_available && $privs_available;
    }

    if ($privs_available) {
        $insert_privs_available = $GLOBALS['dbi']->tryQuery(
            'INSERT INTO `mysql`.`columns_priv`(`host`, `Db`, `User`, `Table_name`,'
            . ' `Column_name`) VALUES("pma_test_host", '
            . '"mysql", "pma_test_user", "", "")'
        );
        // If successful test insert, delete the test row
        if ($insert_privs_available) {
            $GLOBALS['dbi']->tryQuery(
                'DELETE FROM `mysql`.`columns_priv` WHERE host = "pma_test_host" AND '
                . 'Db = "mysql" AND User = "pma_test_user" AND Table_name = ""'
                . ' AND Column_name = "" LIMIT 1'
            );
        }
        $privs_available = $insert_privs_available && $privs_available;
    }

    if ($privs_available) {
        $update_privs_available = $GLOBALS['dbi']->tryQuery(
            'UPDATE `mysql`.`columns_priv` SET `host` = "" WHERE `host` = "" AND '
            . '`Db` = "" AND `User` = "" AND Column_name = "" AND Table_name = "" LIMIT 1'
        );
        $privs_available = $update_privs_available && $privs_available;

    }
    // Save the value
    $GLOBALS['col_priv'] = $privs_available;
    // Reset the value
    $privs_available = true;

    // FOR TABLES_PRIV
    $select_privs_available = $GLOBALS['dbi']->tryQuery(
        'SELECT * FROM `mysql`.`tables_priv` LIMIT 1'
    );

    $privs_available = $select_privs_available && $privs_available;

    if ($privs_available) {
        $delete_privs_available = $GLOBALS['dbi']->tryQuery(
            'DELETE FROM `mysql`.`tables_priv` WHERE `host` = "" AND '
            . '`Db` = "" AND `User` = "" AND Table_name = "" LIMIT 1'
        );
        $privs_available = $delete_privs_available && $privs_available;
    }

    if ($privs_available) {
        $insert_privs_available = $GLOBALS['dbi']->tryQuery(
            'INSERT INTO `mysql`.`tables_priv`(`host`, `Db`, `User`, `Table_name`'
            . ') VALUES("pma_test_host", '
            . '"mysql", "pma_test_user", "")'
        );
        // If successful test insert, delete the test row
        if ($insert_privs_available) {
            $GLOBALS['dbi']->tryQuery(
                'DELETE FROM `mysql`.`tables_priv` WHERE host = "pma_test_host" AND '
                . 'Db = "mysql" AND User = "pma_test_user" AND Table_name = "" LIMIT 1'
            );
        }
        $privs_available = $insert_privs_available && $privs_available;
    }

    if ($privs_available) {
        $update_privs_available = $GLOBALS['dbi']->tryQuery(
            'UPDATE `mysql`.`tables_priv` SET `host` = "" WHERE `host` = "" AND '
            . '`Db` = "" AND `User` = "" AND Table_name = "" LIMIT 1'
        );
        $privs_available = $update_privs_available && $privs_available;

    }
    // Save the value
    $GLOBALS['table_priv'] = $privs_available;
    // Reset the value
    $privs_available = true;

    // FOR PROCS_PRIV
    $select_privs_available = $GLOBALS['dbi']->tryQuery(
        'SELECT * FROM `mysql`.`procs_priv` LIMIT 1'
    );

    $privs_available = $select_privs_available && $privs_available;

    if ($privs_available) {
        $delete_privs_available = $GLOBALS['dbi']->tryQuery(
            'DELETE FROM `mysql`.`procs_priv` WHERE `host` = "" AND '
            . '`Db` = "" AND `User` = "" AND `Routine_name` = ""'
            . ' AND `Routine_type` = "" LIMIT 1'
        );
        $privs_available = $delete_privs_available && $privs_available;
    }

    if ($privs_available) {
        $insert_privs_available = $GLOBALS['dbi']->tryQuery(
            'INSERT INTO `mysql`.`procs_priv`(`host`, `Db`, `User`, `Routine_name`,'
            . ' `Routine_type`) VALUES("pma_test_host", '
            . '"mysql", "pma_test_user", "", "PROCEDURE")'
        );
        // If successful test insert, delete the test row
        if ($insert_privs_available) {
            $GLOBALS['dbi']->tryQuery(
                'DELETE FROM `mysql`.`procs_priv` WHERE `host` = "pma_test_host" AND '
                . '`Db` = "mysql" AND `User` = "pma_test_user" AND `Routine_name` = ""'
                . ' AND `Routine_type` = "PROCEDURE" LIMIT 1'
            );
        }
        $privs_available = $insert_privs_available && $privs_available;
    }

    if ($privs_available) {
        $update_privs_available = $GLOBALS['dbi']->tryQuery(
            'UPDATE `mysql`.`procs_priv` SET `host` = "" WHERE `host` = "" AND '
            . '`Db` = "" AND `User` = "" AND `Routine_name` = "" LIMIT 1'
        );
        $privs_available = $update_privs_available && $privs_available;
    }
    // Save the value
    $GLOBALS['proc_priv'] = $privs_available;

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
 * from MySQL manual: (http://dev.mysql.com/doc/refman/5.0/en/show-grants.html)
 * SHOW GRANTS displays only the privileges granted explicitly to the named
 * account. Other privileges might be available to the account, but they are not
 * displayed. For example, if an anonymous account exists, the named account
 * might be able to use its privileges, but SHOW GRANTS will not display them.
 *
 * @return void
 */
function PMA_analyseShowGrant()
{
    if (PMA_Util::cacheExists('is_create_db_priv')) {
        $GLOBALS['is_create_db_priv'] = PMA_Util::cacheGet(
            'is_create_db_priv'
        );
        $GLOBALS['is_reload_priv'] = PMA_Util::cacheGet(
            'is_reload_priv'
        );
        $GLOBALS['db_to_create'] = PMA_Util::cacheGet(
            'db_to_create'
        );
        $GLOBALS['dbs_where_create_table_allowed'] = PMA_Util::cacheGet(
            'dbs_where_create_table_allowed'
        );
        $GLOBALS['dbs_to_test'] = PMA_Util::cacheGet(
            'dbs_to_test'
        );
        return;
    }

    // defaults
    $GLOBALS['is_create_db_priv']  = false;
    $GLOBALS['is_reload_priv']     = false;
    $GLOBALS['db_to_create']       = '';
    $GLOBALS['dbs_where_create_table_allowed'] = array();
    $GLOBALS['dbs_to_test']        = $GLOBALS['dbi']->getSystemSchemas();

    $rs_usr = $GLOBALS['dbi']->tryQuery('SHOW GRANTS');

    if (! $rs_usr) {
        return;
    }

    $re0 = '(^|(\\\\\\\\)+|[^\\\\])'; // non-escaped wildcards
    $re1 = '(^|[^\\\\])(\\\)+'; // escaped wildcards

    while ($row = $GLOBALS['dbi']->fetchRow($rs_usr)) {
        // extract db from GRANT ... ON *.* or GRANT ... ON db.*
        $db_name_offset = /*overload*/mb_strpos($row[0], ' ON ') + 4;
        $show_grants_dbname = /*overload*/mb_substr(
            $row[0], $db_name_offset,
            /*overload*/mb_strpos($row[0], '.', $db_name_offset) - $db_name_offset
        );
        $show_grants_dbname = PMA_Util::unQuote($show_grants_dbname, '`');

        $show_grants_str    = /*overload*/mb_substr(
            $row[0],
            6,
            (/*overload*/mb_strpos($row[0], ' ON ') - 6)
        );

        if ($show_grants_dbname == '*') {
            if ($show_grants_str != 'USAGE') {
                $GLOBALS['dbs_to_test'] = false;
            }
        } elseif ($GLOBALS['dbs_to_test'] !== false) {
            $GLOBALS['dbs_to_test'][] = $show_grants_dbname;
        }

        if ($show_grants_str == 'RELOAD') {
            $GLOBALS['is_reload_priv'] = true;
        }

        /**
         * @todo if we find CREATE VIEW but not CREATE, do not offer
         * the create database dialog box
         */
        if ($show_grants_str == 'ALL'
            || $show_grants_str == 'ALL PRIVILEGES'
            || $show_grants_str == 'CREATE'
            || strpos($show_grants_str, 'CREATE,') !== false
        ) {
            if ($show_grants_dbname == '*') {
                // a global CREATE privilege
                $GLOBALS['is_create_db_priv'] = true;
                $GLOBALS['is_reload_priv'] = true;
                $GLOBALS['db_to_create']   = '';
                $GLOBALS['dbs_where_create_table_allowed'][] = '*';
                // @todo we should not break here, cause GRANT ALL *.*
                // could be revoked by a later rule like GRANT SELECT ON db.*
                break;
            } else {
                // this array may contain wildcards
                $GLOBALS['dbs_where_create_table_allowed'][] = $show_grants_dbname;

                $dbname_to_test = PMA_Util::backquote($show_grants_dbname);

                if ($GLOBALS['is_create_db_priv']) {
                    // no need for any more tests if we already know this
                    continue;
                }

                // does this db exist?
                if ((preg_match('/' . $re0 . '%|_/', $show_grants_dbname)
                    && ! preg_match('/\\\\%|\\\\_/', $show_grants_dbname))
                    || (! $GLOBALS['dbi']->tryQuery(
                        'USE ' .  preg_replace(
                            '/' . $re1 . '(%|_)/', '\\1\\3', $dbname_to_test
                        )
                    )
                    && /*overload*/mb_substr($GLOBALS['dbi']->getError(), 1, 4) != 1044)
                ) {
                    /**
                     * Do not handle the underscore wildcard
                     * (this case must be rare anyway)
                     */
                    $GLOBALS['db_to_create'] = preg_replace(
                        '/' . $re0 . '%/',     '\\1',
                        $show_grants_dbname
                    );
                    $GLOBALS['db_to_create'] = preg_replace(
                        '/' . $re1 . '(%|_)/', '\\1\\3',
                        $GLOBALS['db_to_create']
                    );
                    $GLOBALS['is_create_db_priv'] = true;

                    /**
                     * @todo collect $GLOBALS['db_to_create'] into an array,
                     * to display a drop-down in the "Create database" dialog
                     */
                     // we don't break, we want all possible databases
                     //break;
                } // end if
            } // end elseif
        } // end if
    } // end while

    $GLOBALS['dbi']->freeResult($rs_usr);

    // must also cacheUnset() them in
    // libraries/plugins/auth/AuthenticationCookie.class.php
    PMA_Util::cacheSet('is_create_db_priv', $GLOBALS['is_create_db_priv']);
    PMA_Util::cacheSet('is_reload_priv', $GLOBALS['is_reload_priv']);
    PMA_Util::cacheSet('db_to_create', $GLOBALS['db_to_create']);
    PMA_Util::cacheSet(
        'dbs_where_create_table_allowed',
        $GLOBALS['dbs_where_create_table_allowed']
    );
    PMA_Util::cacheSet('dbs_to_test', $GLOBALS['dbs_to_test']);
} // end function

if (!PMA_DRIZZLE) {
    $user = $GLOBALS['dbi']->fetchValue("SELECT CURRENT_USER();");
    if ($user == '@') { // MySQL is started with --skip-grant-tables
        $GLOBALS['is_create_db_priv'] = true;
        $GLOBALS['is_reload_priv']    = true;
        $GLOBALS['db_to_create']      = '';
        $GLOBALS['dbs_where_create_table_allowed'] = array('*');
        $GLOBALS['dbs_to_test']       = false;
    } else {
        PMA_analyseShowGrant();
    }

    // Check if privileges to 'mysql'.col_privs, 'mysql'.db,
    // 'mysql'.table_privs, 'mysql'.proc_privs and privileges for
    // flushing the privileges are available
    PMA_checkRequiredPrivilegesForFlushing();
    PMA_checkRequiredPrivilgesForAdjust();

} else {
    // todo: for simple_user_policy only database with user's login can be created
    // (unless logged in as root)
    $GLOBALS['is_create_db_priv'] = $GLOBALS['is_superuser'];
    $GLOBALS['is_reload_priv']    = false;
    $GLOBALS['db_to_create']      = '';
    $GLOBALS['dbs_where_create_table_allowed'] = array('*');
    $GLOBALS['dbs_to_test']       = false;
}

