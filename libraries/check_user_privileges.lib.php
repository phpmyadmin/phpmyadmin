<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Get user's global privileges and some db-specific privileges
 *
 * @version $Id$
 */

/**
 *
 */
$is_create_db_priv  = false;
$is_process_priv    = true;
$is_reload_priv     = false;
$db_to_create       = '';
$dbs_where_create_table_allowed = array();

$is_superuser       = PMA_isSuperuser();

function PMA_analyseShowGrant($rs_usr, &$is_create_db_priv, &$db_to_create,
    &$is_reload_priv, &$dbs_where_create_table_allowed)
{
    $re0 = '(^|(\\\\\\\\)+|[^\])'; // non-escaped wildcards
    $re1 = '(^|[^\])(\\\)+'; // escaped wildcards
    while ($row = PMA_DBI_fetch_row($rs_usr)) {
        // extract db from GRANT ... ON *.* or GRANT ... ON db.*
        $db_name_offset = strpos($row[0], ' ON ') + 4;
        $show_grants_dbname = substr($row[0],
            $db_name_offset,
            strpos($row[0], '.', $db_name_offset) - $db_name_offset);
        $show_grants_dbname = PMA_unQuote($show_grants_dbname, '`');

        $show_grants_str    = substr($row[0], 6, (strpos($row[0], ' ON ') - 6));
        if ($show_grants_str == 'RELOAD') {
            $is_reload_priv = true;
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
                $is_create_db_priv = true;
                $is_reload_priv = true;
                $db_to_create   = '';
                $dbs_where_create_table_allowed[] = '*';
                // @todo we should not break here, cause GRANT ALL *.*
                // could be revoked by a later rule like GRANT SELECT ON db.*
                break;
            } else {
                // this array may contain wildcards
                $dbs_where_create_table_allowed[] = $show_grants_dbname;

                $dbname_to_test = PMA_backquote($show_grants_dbname);

                if ($is_create_db_priv) {
                    // no need for any more tests if we already know this
                    continue;
                }

                if ((ereg($re0 . '%|_', $show_grants_dbname)
                  && ! ereg('\\\\%|\\\\_', $show_grants_dbname))
                 // does this db exist?
                 || (! PMA_DBI_try_query('USE ' .  ereg_replace($re1 . '(%|_)', '\\1\\3', $dbname_to_test))
                  && substr(PMA_DBI_getError(), 1, 4) != 1044)
                ) {
                    $db_to_create = ereg_replace($re0 . '_',     '\\1?',   $show_grants_dbname);
                    $db_to_create = ereg_replace($re0 . '%',     '\\1...', $db_to_create);
                    $db_to_create = ereg_replace($re1 . '(%|_)', '\\1\\3', $db_to_create);
                    $is_create_db_priv = true;

                    /**
                     * @todo collect $db_to_create into an array, to display a
                     * drop-down in the "Create new database" dialog
                     */
                     // we don't break, we want all possible databases
                     //break;
                } // end if
            } // end elseif
        } // end if
    } // end while
} // end function

// Detection for some CREATE privilege.

// Since MySQL 4.1.2, we can easily detect current user's grants
// using $userlink (no control user needed)
// and we don't have to try any other method for detection
// @todo fix to get really all privileges, not only explicitly defined for this user
// from MySQL manual: (http://dev.mysql.com/doc/refman/5.0/en/show-grants.html)
// SHOW GRANTS displays only the privileges granted explicitly to the named
// account. Other privileges might be available to the account, but they are not
// displayed. For example, if an anonymous account exists, the named account
// might be able to use its privileges, but SHOW GRANTS will not display them.

if ($rs_usr = PMA_DBI_try_query('SHOW GRANTS')) {
    PMA_analyseShowGrant($rs_usr, $is_create_db_priv, $db_to_create, $is_reload_priv, $dbs_where_create_table_allowed);
    PMA_DBI_free_result($rs_usr);
    unset($rs_usr);
}

// If disabled, don't show it
if (!$cfg['SuggestDBName']) {
    $db_to_create = '';
}
?>
