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
$GLOBALS['is_superuser']       = PMA_isSuperuser();

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
 */
function PMA_analyseShowGrant()
{
    if (PMA_cacheExists('is_create_db_priv', true)) {
        $GLOBALS['is_create_db_priv']  = PMA_cacheGet('is_create_db_priv', true);
        $GLOBALS['is_process_priv']    = PMA_cacheGet('is_process_priv', true);
        $GLOBALS['is_reload_priv']     = PMA_cacheGet('is_reload_priv', true);
        $GLOBALS['db_to_create']       = PMA_cacheGet('db_to_create', true);
        $GLOBALS['dbs_where_create_table_allowed']
            = PMA_cacheGet('dbs_where_create_table_allowed', true);
        return;
    }

    // defaults
    $GLOBALS['is_create_db_priv']  = false;
    $GLOBALS['is_process_priv']    = true;
    $GLOBALS['is_reload_priv']     = false;
    $GLOBALS['db_to_create']       = '';
    $GLOBALS['dbs_where_create_table_allowed'] = array();

    $rs_usr = PMA_DBI_try_query('SHOW GRANTS');

    if (! $rs_usr) {
        return;
    }

    $re0 = '(^|(\\\\\\\\)+|[^\\\\])'; // non-escaped wildcards
    $re1 = '(^|[^\\\\])(\\\)+'; // escaped wildcards

    while ($row = PMA_DBI_fetch_row($rs_usr)) {
        // extract db from GRANT ... ON *.* or GRANT ... ON db.*
        $db_name_offset = strpos($row[0], ' ON ') + 4;
        $show_grants_dbname = substr($row[0],
            $db_name_offset,
            strpos($row[0], '.', $db_name_offset) - $db_name_offset);
        $show_grants_dbname = PMA_unQuote($show_grants_dbname, '`');

        $show_grants_str    = substr($row[0], 6, (strpos($row[0], ' ON ') - 6));
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
         || strpos($show_grants_str, 'CREATE,') !== false) {
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

                $dbname_to_test = PMA_backquote($show_grants_dbname);

                if ($GLOBALS['is_create_db_priv']) {
                    // no need for any more tests if we already know this
                    continue;
                }

                if ((preg_match('/' . $re0 . '%|_/', $show_grants_dbname)
                  && ! preg_match('/\\\\%|\\\\_/', $show_grants_dbname))
                 // does this db exist?
                 || (! PMA_DBI_try_query('USE ' .  preg_replace('/' . $re1 . '(%|_)/', '\\1\\3', $dbname_to_test))
                  && substr(PMA_DBI_getError(), 1, 4) != 1044)
                ) {
                    if ($GLOBALS['cfg']['SuggestDBName']) {
                        /**
                         * Do not handle the underscore wildcard 
                         * (this case must be rare anyway)
                         */
                        $GLOBALS['db_to_create'] = preg_replace('/' . $re0 . '%/',     '\\1...', $show_grants_dbname);
                        $GLOBALS['db_to_create'] = preg_replace('/' . $re1 . '(%|_)/', '\\1\\3', $GLOBALS['db_to_create']);
                    }
                    $GLOBALS['is_create_db_priv'] = true;

                    /**
                     * @todo collect $GLOBALS['db_to_create'] into an array, to display a
                     * drop-down in the "Create database" dialog
                     */
                     // we don't break, we want all possible databases
                     //break;
                } // end if
            } // end elseif
        } // end if
    } // end while

    PMA_DBI_free_result($rs_usr);

    // must also PMA_cacheUnset() them in libraries/auth/cookie.auth.lib.php
    PMA_cacheSet('is_create_db_priv', $GLOBALS['is_create_db_priv'], true);
    PMA_cacheSet('is_process_priv', $GLOBALS['is_process_priv'], true);
    PMA_cacheSet('is_reload_priv', $GLOBALS['is_reload_priv'], true);
    PMA_cacheSet('db_to_create', $GLOBALS['db_to_create'], true);
    PMA_cacheSet('dbs_where_create_table_allowed', $GLOBALS['dbs_where_create_table_allowed'], true);
} // end function

if (!PMA_DRIZZLE) {
    PMA_analyseShowGrant();
} else {
    // todo: for simple_user_policy only database with user's login can be created (unless logged in as root)
    $GLOBALS['is_create_db_priv'] = $GLOBALS['is_superuser'];
    $GLOBALS['is_process_priv']   = false;
    $GLOBALS['is_reload_priv']    = false;
    $GLOBALS['db_to_create']      = '';
    $GLOBALS['dbs_where_create_table_allowed'] = array('*');
}

?>
