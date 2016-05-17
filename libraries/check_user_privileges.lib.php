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


function PMA_getItemsFromShowGrantsRow($row)
{
    $db_name_offset = mb_strpos($row, ' ON ') + 4;
    $show_grants_dbname = mb_substr(
        $row, $db_name_offset,
        mb_strpos($row, '.', $db_name_offset) - $db_name_offset
    );

    $show_grants_dbname = PMA\libraries\Util::unQuote($show_grants_dbname, '`');

    $show_grants_str    = mb_substr(
        $row,
        6,
        (mb_strpos($row, ' ON ') - 6)
    );

    // extrac table from GRANT sytax
    $tblname_start_offset = mb_strpos($row, '.') + 1;
    $tblname_end_offset = mb_strpos($row, ' TO ');

    $show_grants_tblname = mb_substr(
        $row, $tblname_start_offset,
        $tblname_end_offset - $tblname_start_offset
    );
    $show_grants_tblname = PMA\libraries\Util::unQuote($show_grants_tblname, '`');

    return array(
        $show_grants_str,
        $show_grants_dbname,
        $show_grants_tblname
    );
}

/**
 * Check if user has required privileges for
 * performing 'Adjust privileges' operations
 *
 * @param string $show_grants_str     string containing grants for user
 * @param string $show_grants_dbname  name of db extracted from grant string
 * @param string $show_grants_tblname name of table extracted from grant string
 *
 * @return void
 */
function PMA_checkRequiredPrivilegesForAdjust(
    $show_grants_str,
    $show_grants_dbname,
    $show_grants_tblname
) {
    // '... ALL PRIVILEGES ON *.* ...' OR '... ALL PRIVILEGES ON `mysql`.* ..'
    // OR
    // SELECT, INSERT, UPDATE, DELETE .... ON *.* OR `mysql`.*
    if ($show_grants_str == 'ALL'
        || $show_grants_str == 'ALL PRIVILEGES'
        || (mb_strpos(
                $show_grants_str, 'SELECT, INSERT, UPDATE, DELETE'
            ) !== false)
    ) {
        if ($show_grants_dbname == '*'
            && $show_grants_tblname == '*'
        ) {
            $GLOBALS['col_priv'] = true;
            $GLOBALS['db_priv'] = true;
            $GLOBALS['proc_priv'] = true;
            $GLOBALS['table_priv'] = true;

            if ($show_grants_str == 'ALL PRIVILEGES'
                || $show_grants_str == 'ALL'
            ) {
                $GLOBALS['is_reload_priv'] = true;
            }
        }

        // check for specific tables in `mysql` db
        // Ex. '... ALL PRIVILEGES on `mysql`.`columns_priv` .. '
        if ($show_grants_dbname == 'mysql') {
            switch ($show_grants_tblname) {
                case "columns_priv":
                    $GLOBALS['col_priv'] = true;
                    break;
                case "db":
                    $GLOBALS['db_priv'] = true;
                    break;
                case "procs_priv":
                    $GLOBALS['proc_priv'] = true;
                    break;
                case "tables_priv":
                    $GLOBALS['table_priv'] = true;
                    break;
                case "*":
                    $GLOBALS['col_priv'] = true;
                    $GLOBALS['db_priv'] = true;
                    $GLOBALS['proc_priv'] = true;
                    $GLOBALS['table_priv'] = true;
                    break;
                default:
            }
        }
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
 *
 * @return void
 */
function PMA_analyseShowGrant()
{
    if (PMA\libraries\Util::cacheExists('is_create_db_priv')) {
        $GLOBALS['is_create_db_priv'] = PMA\libraries\Util::cacheGet(
            'is_create_db_priv'
        );
        $GLOBALS['is_reload_priv'] = PMA\libraries\Util::cacheGet(
            'is_reload_priv'
        );
        $GLOBALS['db_to_create'] = PMA\libraries\Util::cacheGet(
            'db_to_create'
        );
        $GLOBALS['dbs_where_create_table_allowed'] = PMA\libraries\Util::cacheGet(
            'dbs_where_create_table_allowed'
        );
        $GLOBALS['dbs_to_test'] = PMA\libraries\Util::cacheGet(
            'dbs_to_test'
        );

        $GLOBALS['db_priv'] = PMA\libraries\Util::cacheGet(
            'db_priv'
        );
        $GLOBALS['col_priv'] = PMA\libraries\Util::cacheGet(
            'col_priv'
        );
        $GLOBALS['table_priv'] = PMA\libraries\Util::cacheGet(
            'table_priv'
        );
        $GLOBALS['proc_priv'] = PMA\libraries\Util::cacheGet(
            'proc_priv'
        );

        return;
    }

    // defaults
    $GLOBALS['is_create_db_priv']  = false;
    $GLOBALS['is_reload_priv']     = false;
    $GLOBALS['db_to_create']       = '';
    $GLOBALS['dbs_where_create_table_allowed'] = array();
    $GLOBALS['dbs_to_test']        = $GLOBALS['dbi']->getSystemSchemas();
    $GLOBALS['proc_priv'] = false;
    $GLOBALS['db_priv'] = false;
    $GLOBALS['col_priv'] = false;
    $GLOBALS['table_priv'] = false;

    $rs_usr = $GLOBALS['dbi']->tryQuery('SHOW GRANTS');

    if (! $rs_usr) {
        return;
    }

    $re0 = '(^|(\\\\\\\\)+|[^\\\\])'; // non-escaped wildcards
    $re1 = '(^|[^\\\\])(\\\)+'; // escaped wildcards

    while ($row = $GLOBALS['dbi']->fetchRow($rs_usr)) {
        list(
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname
        ) = PMA_getItemsFromShowGrantsRow($row[0]);

        if ($show_grants_dbname == '*') {
            if ($show_grants_str != 'USAGE') {
                $GLOBALS['dbs_to_test'] = false;
            }
        } elseif ($GLOBALS['dbs_to_test'] !== false) {
            $GLOBALS['dbs_to_test'][] = $show_grants_dbname;
        }

        if (
            mb_strpos($show_grants_str,'RELOAD') !== false
        ) {
            $GLOBALS['is_reload_priv'] = true;
        }

        // check for the required privileges for adjust
        PMA_checkRequiredPrivilegesForAdjust(
            $show_grants_str,
            $show_grants_dbname,
            $show_grants_tblname
        );

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

                $dbname_to_test = PMA\libraries\Util::backquote($show_grants_dbname);

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
                    && mb_substr($GLOBALS['dbi']->getError(), 1, 4) != 1044)
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
    // libraries/plugins/auth/AuthenticationCookie.php
    PMA\libraries\Util::cacheSet('is_create_db_priv', $GLOBALS['is_create_db_priv']);
    PMA\libraries\Util::cacheSet('is_reload_priv', $GLOBALS['is_reload_priv']);
    PMA\libraries\Util::cacheSet('db_to_create', $GLOBALS['db_to_create']);
    PMA\libraries\Util::cacheSet(
        'dbs_where_create_table_allowed',
        $GLOBALS['dbs_where_create_table_allowed']
    );
    PMA\libraries\Util::cacheSet('dbs_to_test', $GLOBALS['dbs_to_test']);

    PMA\libraries\Util::cacheSet('proc_priv', $GLOBALS['proc_priv']);
    PMA\libraries\Util::cacheSet('table_priv', $GLOBALS['table_priv']);
    PMA\libraries\Util::cacheSet('col_priv', $GLOBALS['col_priv']);
    PMA\libraries\Util::cacheSet('db_priv', $GLOBALS['db_priv']);
} // end function

$user = $GLOBALS['dbi']->fetchValue("SELECT CURRENT_USER();");
if ($user == '@') { // MySQL is started with --skip-grant-tables
    $GLOBALS['is_create_db_priv'] = true;
    $GLOBALS['is_reload_priv']    = true;
    $GLOBALS['db_to_create']      = '';
    $GLOBALS['dbs_where_create_table_allowed'] = array('*');
    $GLOBALS['dbs_to_test']       = false;
    $GLOBALS['db_priv'] = true;
    $GLOBALS['col_priv'] = true;
    $GLOBALS['table_priv'] = true;
    $GLOBALS['proc_priv'] = true;
} else {
    PMA_analyseShowGrant();
}
