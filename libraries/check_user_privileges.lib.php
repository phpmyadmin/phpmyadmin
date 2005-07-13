<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// Get user's global privileges ($dbh and $userlink are links to MySQL
// defined in the "common.lib.php" library)
// Note: if no controluser is defined, $dbh contains $userlink

$is_create_priv  = FALSE;
$is_process_priv = TRUE;
$is_reload_priv  = FALSE;
$db_to_create    = '';

// We were trying to find if user if superuser with 'USE mysql'
// but users with the global priv CREATE TEMPORARY TABLES or LOCK TABLES
// can do a 'USE mysql' (even if they cannot see the tables)
$is_superuser    = PMA_DBI_try_query('SELECT COUNT(*) FROM mysql.user', $userlink, PMA_DBI_QUERY_STORE);

function PMA_analyseShowGrant($rs_usr, &$is_create_priv, &$db_to_create, &$is_reload_priv) {

    $re0 = '(^|(\\\\\\\\)+|[^\])'; // non-escaped wildcards
    $re1 = '(^|[^\])(\\\)+'; // escaped wildcards
    while ($row = PMA_DBI_fetch_row($rs_usr)) {
        $show_grants_dbname = substr($row[0], strpos($row[0], ' ON ') + 4,(strpos($row[0], '.', strpos($row[0], ' ON ')) - strpos($row[0], ' ON ') - 4));
        $show_grants_dbname = ereg_replace('^`(.*)`','\\1',  $show_grants_dbname);
        $show_grants_str    = substr($row[0],6,(strpos($row[0],' ON ')-6));
        if (($show_grants_str == 'ALL') || ($show_grants_str == 'ALL PRIVILEGES') || ($show_grants_str == 'CREATE') || strpos($show_grants_str, 'CREATE')) {
            if ($show_grants_dbname == '*') {
                $is_create_priv = TRUE;
                $is_reload_priv = TRUE;
                $db_to_create   = '';
                break;
            } // end if
            else if ( (ereg($re0 . '%|_', $show_grants_dbname)
                    && !ereg('\\\\%|\\\\_', $show_grants_dbname))
                    || (!PMA_DBI_try_query('USE ' . ereg_replace($re1 .'(%|_)', '\\1\\3', $show_grants_dbname)) && substr(PMA_DBI_getError(), 1, 4) != 1044)
                    ) {
                     $db_to_create = ereg_replace($re0 . '%', '\\1...', ereg_replace($re0 . '_', '\\1?', $show_grants_dbname));
                     $db_to_create = ereg_replace($re1 . '(%|_)', '\\1\\3', $db_to_create);
                     $is_create_priv     = TRUE;
                     break;
            } // end elseif
        } // end if
    } // end while
} // end function

// Detection for some CREATE privilege.

// Since MySQL 4.1.2, we can easily detect current user's grants
// using $userlink (no control user needed)
// and we don't have to try any other method for detection

if (PMA_MYSQL_INT_VERSION >= 40102) {
    $rs_usr = PMA_DBI_try_query('SHOW GRANTS', $userlink, PMA_DBI_QUERY_STORE);
    if ($rs_usr) {
        PMA_analyseShowGrant($rs_usr,$is_create_priv, $db_to_create, $is_reload_priv);
        PMA_DBI_free_result($rs_usr);
        unset($rs_usr);
    }
} else {

// Before MySQL 4.1.2, we first try to find a priv in mysql.user. Hopefuly
// the controluser is correctly defined; but here, $dbh could contain
// $userlink so maybe the SELECT will fail

    if (!$is_create_priv) {
        $local_query = 'SELECT Create_priv, Reload_priv FROM mysql.user WHERE ' . PMA_convert_using('User') . ' = ' . PMA_convert_using(PMA_sqlAddslashes($mysql_cur_user), 'quoted') . ' OR ' . PMA_convert_using('User') . ' = ' . PMA_convert_using('', 'quoted') . ';';
        $rs_usr      = PMA_DBI_try_query($local_query, $dbh); // Debug: or PMA_mysqlDie('', $local_query, FALSE);
        if ($rs_usr) {
            while ($result_usr = PMA_DBI_fetch_assoc($rs_usr)) {
                if (!$is_create_priv) {
                    $is_create_priv  = ($result_usr['Create_priv'] == 'Y');
                }
                if (!$is_reload_priv) {
                    $is_reload_priv  = ($result_usr['Reload_priv'] == 'Y');
                }
            } // end while
            PMA_DBI_free_result($rs_usr);
            unset($rs_usr, $result_usr);
        } // end if
    } // end if

    // If the user has Create priv on a inexistant db, show him in the dialog
    // the first inexistant db name that we find, in most cases it's probably
    // the one he just dropped :)
    if (!$is_create_priv) {
        $local_query = 'SELECT DISTINCT Db FROM mysql.db WHERE ' . PMA_convert_using('Create_priv') . ' = ' . PMA_convert_using('Y', 'quoted') . ' AND (' . PMA_convert_using('User') . ' = ' .PMA_convert_using(PMA_sqlAddslashes($mysql_cur_user), 'quoted') . ' OR ' . PMA_convert_using('User') . ' = ' . PMA_convert_using('', 'quoted') . ');';
        $rs_usr      = PMA_DBI_try_query($local_query, $dbh, PMA_DBI_QUERY_STORE);
        if ($rs_usr) {
            $re0     = '(^|(\\\\\\\\)+|[^\])'; // non-escaped wildcards
            $re1     = '(^|[^\])(\\\)+';       // escaped wildcards
            while ($row = PMA_DBI_fetch_assoc($rs_usr)) {
                if (ereg($re0 . '(%|_)', $row['Db'])
                    || (!PMA_DBI_try_query('USE ' . ereg_replace($re1 . '(%|_)', '\\1\\3', $row['Db'])) && substr(PMA_DBI_getError(), 1, 4) != 1044)) {
                    $db_to_create   = ereg_replace($re0 . '%', '\\1...', ereg_replace($re0 . '_', '\\1?', $row['Db']));
                    $db_to_create   = ereg_replace($re1 . '(%|_)', '\\1\\3', $db_to_create);
                    $is_create_priv = TRUE;
                    break;
                } // end if
            } // end while
            PMA_DBI_free_result($rs_usr);
            unset($rs_usr, $row, $re0, $re1);
        } // end if
        else {
            // Finally, let's try to get the user's privileges by using SHOW
            // GRANTS...
            // Maybe we'll find a little CREATE priv there :)
            $rs_usr      = PMA_DBI_try_query('SHOW GRANTS FOR ' . $mysql_cur_user_and_host . ';', $dbh, PMA_DBI_QUERY_STORE);
            if (!$rs_usr) {
                // OK, now we'd have to guess the user's hostname, but we
                // only try out the 'username'@'%' case.
                $rs_usr      = PMA_DBI_try_query('SHOW GRANTS FOR ' . $mysql_cur_user . ';', $dbh, PMA_DBI_QUERY_STORE);
            }
            unset($local_query);
            if ($rs_usr) {
                PMA_analyseShowGrant($rs_usr,$is_create_priv, $db_to_create, $is_reload_priv);
                PMA_DBI_free_result($rs_usr);
                unset($rs_usr);
            } // end if
        } // end elseif
    } // end if
} // end else (MySQL < 4.1.2)

// If disabled, don't show it
if (!$cfg['SuggestDBName']) {
    $db_to_create = '';
}

?>

