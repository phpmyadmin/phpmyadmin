<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used to build SQL dumps of tables
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
if (isset($plugin_list)) {
    $hide_sql       = false;
    $hide_structure = false;
    if ($plugin_param['export_type'] == 'table' && !$plugin_param['single_table']) {
        $hide_structure = true;
        $hide_sql       = true;
    }
    if (!$hide_sql) {
        $plugin_list['sql'] = array(
            'text' => 'strSQL',
            'extension' => 'sql',
            'mime_type' => 'text/x-sql',
            'options' => array(
                array('type' => 'text', 'name' => 'header_comment', 'text' => 'strAddHeaderComment'),
                array('type' => 'bool', 'name' => 'use_transaction', 'text' => 'strEncloseInTransaction'),
                array('type' => 'bool', 'name' => 'disable_fk', 'text' => 'strDisableForeignChecks'),
                ),
            'options_text' => 'strOptions',
            );
        $compats = PMA_DBI_getCompatibilities();
        if (count($compats) > 0) {
            $values = array();
            foreach($compats as $val) {
                $values[$val] = $val;
            }
            $plugin_list['sql']['options'][] =
                array('type' => 'select', 'name' => 'compatibility', 'text' => 'strSQLCompatibility', 'values' => $values, 'doc' => array('manual_MySQL_Database_Administration', 'Server_SQL_mode'));
            unset($values);
        }

        /* Server export options */
        if ($plugin_param['export_type'] == 'server') {
            $plugin_list['sql']['options'][] =
                array('type' => 'bgroup', 'text' => 'strDatabaseExportOptions');
            $plugin_list['sql']['options'][] =
                array('type' => 'bool', 'name' => 'drop_database', 'text' => sprintf($GLOBALS['strAddClause'], 'DROP DATABASE'));
            $plugin_list['sql']['options'][] =
                array('type' => 'egroup');
        }

        /* Structure options */
        if (!$hide_structure) {
            $plugin_list['sql']['options'][] =
                array('type' => 'bgroup', 'name' => 'structure', 'text' => 'strStructure', 'force' => 'data');
            if ($plugin_param['export_type'] == 'table') {
                if (PMA_Table::_isView($GLOBALS['db'], $GLOBALS['table'])) {
                    $drop_clause = 'DROP VIEW';
                } else {
                    $drop_clause = 'DROP TABLE';
                }
            } elseif (PMA_MYSQL_INT_VERSION >= 50000) {
                $drop_clause = 'DROP TABLE / VIEW / PROCEDURE / FUNCTION';
            } else {
                $drop_clause = 'DROP TABLE';
            }
            $plugin_list['sql']['options'][] =
                array('type' => 'bool', 'name' => 'drop_table', 'text' => sprintf($GLOBALS['strAddClause'], $drop_clause));
            $plugin_list['sql']['options'][] =
                array('type' => 'bool', 'name' => 'if_not_exists', 'text' => sprintf($GLOBALS['strAddClause'], 'IF NOT EXISTS'));
            $plugin_list['sql']['options'][] =
                array('type' => 'bool', 'name' => 'auto_increment', 'text' => 'strAddAutoIncrement');
            $plugin_list['sql']['options'][] =
                array('type' => 'bool', 'name' => 'backquotes', 'text' => 'strUseBackquotes');
            $plugin_list['sql']['options'][] =
                array('type' => 'bool', 'name' => 'procedure_function', 'text' => sprintf($GLOBALS['strAddClause'], 'CREATE PROCEDURE / FUNCTION'));

            /* MIME stuff etc. */
            $plugin_list['sql']['options'][] =
                array('type' => 'bgroup', 'text' => 'strAddIntoComments');
            $plugin_list['sql']['options'][] =
                array('type' => 'bool', 'name' => 'dates', 'text' => 'strCreationDates');
            if (!empty($GLOBALS['cfgRelation']['relation'])) {
                $plugin_list['sql']['options'][] =
                    array('type' => 'bool', 'name' => 'relation', 'text' => 'strRelations');
            }
            if (!empty($GLOBALS['cfgRelation']['commwork']) && PMA_MYSQL_INT_VERSION < 40100) {
                $plugin_list['sql']['options'][] =
                    array('type' => 'bool', 'name' => 'comments', 'text' => 'strComments');
            }
            if (!empty($GLOBALS['cfgRelation']['mimework'])) {
                $plugin_list['sql']['options'][] =
                    array('type' => 'bool', 'name' => 'mime', 'text' => 'strMIME_MIMEtype');
            }
            $plugin_list['sql']['options'][] =
                array('type' => 'egroup');

            $plugin_list['sql']['options'][] =
                array('type' => 'egroup');
        }

        /* Data */
        $plugin_list['sql']['options'][] =
            array('type' => 'bgroup', 'name' => 'data', 'text' => 'strData', 'force' => 'structure');
        $plugin_list['sql']['options'][] =
            array('type' => 'bool', 'name' => 'columns', 'text' => 'strCompleteInserts');
        $plugin_list['sql']['options'][] =
            array('type' => 'bool', 'name' => 'extended', 'text' => 'strExtendedInserts');
        $plugin_list['sql']['options'][] =
            array('type' => 'text', 'name' => 'max_query_size', 'text' => 'strMaximalQueryLength');
        $plugin_list['sql']['options'][] =
            array('type' => 'bool', 'name' => 'delayed', 'text' => 'strDelayedInserts');
        $plugin_list['sql']['options'][] =
            array('type' => 'bool', 'name' => 'ignore', 'text' => 'strIgnoreInserts');
        $plugin_list['sql']['options'][] =
            array('type' => 'bool', 'name' => 'hex_for_blob', 'text' => 'strHexForBLOB');
        $plugin_list['sql']['options'][] =
            array('type' => 'select', 'name' => 'type', 'text' => 'strSQLExportType', 'values' => array('INSERT' => 'INSERT', 'UPDATE' => 'UPDATE', 'REPLACE' => 'REPLACE'));
        $plugin_list['sql']['options'][] =
            array('type' => 'egroup');
    }
} else {

/**
 * Avoids undefined variables, use NULL so isset() returns false
 */
if (! isset($sql_backquotes)) {
    $sql_backquotes = null;
}

/**
 * Outputs comment
 *
 * @param   string      Text of comment
 *
 * @return  string      The formatted comment 
 */
function PMA_exportComment($text = '')
{
    // see http://dev.mysql.com/doc/refman/5.0/en/ansi-diff-comments.html
    return '--' . (empty($text) ? '' : ' ') . $text . $GLOBALS['crlf'];
}

/**
 * Outputs export footer
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportFooter()
{
    global $crlf;
    global $mysql_charset_map;

    $foot = '';

    if (isset($GLOBALS['sql_disable_fk'])) {
        $foot .=  $crlf . 'SET FOREIGN_KEY_CHECKS=1;' . $crlf;
    }

    if (isset($GLOBALS['sql_use_transaction'])) {
        $foot .=  $crlf . 'COMMIT;' . $crlf;
    }

    // restore connection settings
    // (not set if $cfg['AllowAnywhereRecoding'] is false)
    $charset_of_file = isset($GLOBALS['charset_of_file']) ? $GLOBALS['charset_of_file'] : '';
    if (!empty($GLOBALS['asfile']) && isset($mysql_charset_map[$charset_of_file])) {
        $foot .=  $crlf
               . '/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;' . $crlf
               . '/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;' . $crlf
               . '/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;' . $crlf;
    }

    return PMA_exportOutputHandler($foot);
}

/**
 * Outputs export header
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportHeader()
{
    global $crlf;
    global $cfg;
    global $mysql_charset_map;

    if (PMA_MYSQL_INT_VERSION >= 40100 && isset($GLOBALS['sql_compatibility']) && $GLOBALS['sql_compatibility'] != 'NONE') {
        PMA_DBI_try_query('SET SQL_MODE="' . $GLOBALS['sql_compatibility'] . '"');
    }
    $head  =  PMA_exportComment('phpMyAdmin SQL Dump')
           .  PMA_exportComment('version ' . PMA_VERSION)
           .  PMA_exportComment('http://www.phpmyadmin.net')
           .  PMA_exportComment();
    $head .= empty($cfg['Server']['port']) ? PMA_exportComment($GLOBALS['strHost'] . ': ' . $cfg['Server']['host']) : PMA_exportComment($GLOBALS['strHost'] . ': ' .  $cfg['Server']['host'] . ':' . $cfg['Server']['port']);
    $head .=  PMA_exportComment($GLOBALS['strGenTime']
           . ': ' .  PMA_localisedDate())
           .  PMA_exportComment($GLOBALS['strServerVersion'] . ': ' . substr(PMA_MYSQL_INT_VERSION, 0, 1) . '.' . (int) substr(PMA_MYSQL_INT_VERSION, 1, 2) . '.' . (int) substr(PMA_MYSQL_INT_VERSION, 3))
           .  PMA_exportComment($GLOBALS['strPHPVersion'] . ': ' . phpversion());

    if (isset($GLOBALS['sql_header_comment']) && !empty($GLOBALS['sql_header_comment'])) {
        // '\n' is not a newline (like "\n" would be), it's the characters
        // backslash and n, as explained on the export interface
        $lines = explode('\n', $GLOBALS['sql_header_comment']);
        $head .= PMA_exportComment();
        foreach($lines as $one_line) {
            $head .= PMA_exportComment($one_line);
        } 
        $head .= PMA_exportComment();
    }

    if (isset($GLOBALS['sql_disable_fk'])) {
        $head .=  $crlf . 'SET FOREIGN_KEY_CHECKS=0;' . $crlf;
    }

    /* We want exported AUTO_INCREMENT fields to have still same value, do this only for recent MySQL exports */
    if (!isset($GLOBALS['sql_compatibility']) || $GLOBALS['sql_compatibility'] == 'NONE') {
        $head .=  $crlf . (PMA_MYSQL_INT_VERSION >= 40101 ? 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";' . $crlf : ''); 
    }

    if (isset($GLOBALS['sql_use_transaction'])) {
        $head .=  $crlf .'SET AUTOCOMMIT=0;' . $crlf
                . 'START TRANSACTION;' . $crlf;
    }

    $head .= $crlf;

    if (! empty($GLOBALS['asfile'])) {
        // we are saving as file, therefore we provide charset information
        // so that a utility like the mysql client can interpret
        // the file correctly 
        if (isset($GLOBALS['charset_of_file']) && isset($mysql_charset_map[$GLOBALS['charset_of_file']])) {
            // $cfg['AllowAnywhereRecoding'] was true so we got a charset from 
            // the export dialog
            $set_names = $mysql_charset_map[$GLOBALS['charset_of_file']];
        } else {
            // by default we use the connection charset
            $set_names = $mysql_charset_map[$GLOBALS['charset']]; 
        } 
        $head .=  $crlf
               . '/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;' . $crlf
               . '/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;' . $crlf
               . '/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;' . $crlf
               . '/*!40101 SET NAMES ' . $set_names . ' */;' . $crlf . $crlf;
    }

    return PMA_exportOutputHandler($head);
}

/**
 * Outputs CREATE DATABASE database
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBCreate($db)
{
    global $crlf;
    if (isset($GLOBALS['sql_drop_database'])) {
        if (!PMA_exportOutputHandler('DROP DATABASE ' . (isset($GLOBALS['sql_backquotes']) ? PMA_backquote($db) : $db) . ';' . $crlf)) {
            return FALSE;
        }
    }
    $create_query = 'CREATE DATABASE ' . (isset($GLOBALS['sql_backquotes']) ? PMA_backquote($db) : $db);
    if (PMA_MYSQL_INT_VERSION >= 40101) {
        $collation = PMA_getDbCollation($db);
        if (strpos($collation, '_')) {
            $create_query .= ' DEFAULT CHARACTER SET ' . substr($collation, 0, strpos($collation, '_')) . ' COLLATE ' . $collation;
        } else {
            $create_query .= ' DEFAULT CHARACTER SET ' . $collation;
        }
    }
    $create_query .= ';' . $crlf;
    if (!PMA_exportOutputHandler($create_query)) {
        return FALSE;
    }
    if (isset($GLOBALS['sql_backquotes']) && PMA_MYSQL_INT_VERSION >= 40100 && isset($GLOBALS['sql_compatibility']) && $GLOBALS['sql_compatibility'] == 'NONE') {
        return PMA_exportOutputHandler('USE ' . PMA_backquote($db) . ';' . $crlf);
    }
    return PMA_exportOutputHandler('USE ' . $db . ';' . $crlf);
}

/**
 * Outputs database header
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBHeader($db)
{
    $head = PMA_exportComment() 
          . PMA_exportComment($GLOBALS['strDatabase'] . ': ' . (isset($GLOBALS['sql_backquotes']) ? PMA_backquote($db) : '\'' . $db . '\''))
          . PMA_exportComment();
    return PMA_exportOutputHandler($head);
}

/**
 * Outputs database footer
 *
 * @param   string      Database name
 *
 * @return  bool        Whether it suceeded
 *
 * @access  public
 */
function PMA_exportDBFooter($db)
{
    global $crlf;

    $result = TRUE;
    if (isset($GLOBALS['sql_constraints'])) {
        $result = PMA_exportOutputHandler($GLOBALS['sql_constraints']);
        unset($GLOBALS['sql_constraints']);
    }

    if (PMA_MYSQL_INT_VERSION >= 50000 && isset($GLOBALS['sql_structure']) && isset($GLOBALS['sql_procedure_function'])) {
        $procs_funcs = '';
        $delimiter = '$$';

        $procedure_names = PMA_DBI_get_procedures_or_functions($db, 'PROCEDURE');
        $function_names = PMA_DBI_get_procedures_or_functions($db, 'FUNCTION');

        if ($procedure_names || $function_names) {
            $procs_funcs .= $crlf
              . 'DELIMITER ' . $delimiter . $crlf;
        }

        if ($procedure_names) {
            $procs_funcs .= 
                PMA_exportComment() 
              . PMA_exportComment($GLOBALS['strProcedures'])
              . PMA_exportComment();

            foreach($procedure_names as $procedure_name) {
                if (! empty($GLOBALS['sql_drop_table'])) {
		    $procs_funcs .= 'DROP PROCEDURE IF EXISTS ' . PMA_backquote($procedure_name) . $delimiter . $crlf;
                }	
                $procs_funcs .= PMA_DBI_get_procedure_or_function_def($db, 'PROCEDURE', $procedure_name) . $delimiter . $crlf . $crlf;
            }
        }

        if ($function_names) {
            $procs_funcs .= 
                PMA_exportComment()
              . PMA_exportComment($GLOBALS['strFunctions'])
              . PMA_exportComment();

            foreach($function_names as $function_name) {
                if (! empty($GLOBALS['sql_drop_table'])) {
		    $procs_funcs .= 'DROP FUNCTION IF EXISTS ' . PMA_backquote($function_name) . $delimiter . $crlf;
                }	
                $procs_funcs .= PMA_DBI_get_procedure_or_function_def($db, 'FUNCTION', $function_name) . $delimiter . $crlf . $crlf;
            }
        }

        if ($procedure_names || $function_names) {
            $procs_funcs .= 'DELIMITER ;' . $crlf;
        }

        if (!empty($procs_funcs)) {
            $result = PMA_exportOutputHandler($procs_funcs);
        }
    }
    return $result;
}


/**
 * Returns a stand-in CREATE definition to resolve view dependencies
 *
 * @param   string   the database name
 * @param   string   the vew name
 * @param   string   the end of line sequence
 *
 * @return  string   resulting definition
 *
 * @access  public
 */
function PMA_getTableDefStandIn($db, $view, $crlf) {

    $create_query = '';
    if (! empty($GLOBALS['sql_drop_table'])) {
        $create_query .= 'DROP VIEW IF EXISTS ' . PMA_backquote($view) . ';' . $crlf;
    }

    $create_query .= 'CREATE TABLE ';
    if (isset($GLOBALS['sql_if_not_exists']) && $GLOBALS['sql_if_not_exists']) {
        $create_query .= 'IF NOT EXISTS ';
    }
    $create_query .= PMA_backquote($view) . ' (' . $crlf;
    $tmp = array();
    $columns = PMA_DBI_get_columns_full($db, $view);
    foreach($columns as $column_name => $definition) {
        $tmp[] = PMA_backquote($column_name) . ' ' . $definition['Type'] . $crlf;
    }
    $create_query .= implode(',', $tmp) . ');';
    return($create_query);
}

/**
 * Returns $table's CREATE definition
 *
 * @param   string   the database name
 * @param   string   the table name
 * @param   string   the end of line sequence
 * @param   string   the url to go back in case of error
 * @param   boolean  whether to include creation/update/check dates
 * @param   boolean  whether to add semicolon and end-of-line at the end
 *
 * @return  string   resulting schema
 *
 * @global  boolean  whether to add 'drop' statements or not
 * @global  boolean  whether to use backquotes to allow the use of special
 *                   characters in database, table and fields names or not
 *
 * @access  public
 */
function PMA_getTableDef($db, $table, $crlf, $error_url, $show_dates = false, $add_semicolon = true)
{
    global $sql_drop_table;
    global $sql_backquotes;
    global $cfgRelation;
    global $sql_constraints;
    global $sql_constraints_query; // just the text of the query

    $schema_create = '';
    $auto_increment = '';
    $new_crlf = $crlf;

    // need to use PMA_DBI_QUERY_STORE with PMA_DBI_num_rows() in mysqli
    $result = PMA_DBI_query('SHOW TABLE STATUS FROM ' . PMA_backquote($db) . ' LIKE \'' . PMA_sqlAddslashes($table) . '\'', null, PMA_DBI_QUERY_STORE);
    if ($result != FALSE) {
        if (PMA_DBI_num_rows($result) > 0) {
            $tmpres        = PMA_DBI_fetch_assoc($result);
            // Here we optionally add the AUTO_INCREMENT next value,
            // but starting with MySQL 5.0.24, the clause is already included
            // in SHOW CREATE TABLE so we'll remove it below
            if (isset($GLOBALS['sql_auto_increment']) && !empty($tmpres['Auto_increment'])) {
                $auto_increment .= ' AUTO_INCREMENT=' . $tmpres['Auto_increment'] . ' ';
            }

            if ($show_dates && isset($tmpres['Create_time']) && !empty($tmpres['Create_time'])) {
                $schema_create .= PMA_exportComment($GLOBALS['strStatCreateTime'] . ': ' . PMA_localisedDate(strtotime($tmpres['Create_time'])));
                $new_crlf = PMA_exportComment() . $crlf;
            }

            if ($show_dates && isset($tmpres['Update_time']) && !empty($tmpres['Update_time'])) {
                $schema_create .= PMA_exportComment($GLOBALS['strStatUpdateTime'] . ': ' . PMA_localisedDate(strtotime($tmpres['Update_time'])));
                $new_crlf = PMA_exportComment() . $crlf;
            }

            if ($show_dates && isset($tmpres['Check_time']) && !empty($tmpres['Check_time'])) {
                $schema_create .= PMA_exportComment($GLOBALS['strStatCheckTime'] . ': ' . PMA_localisedDate(strtotime($tmpres['Check_time'])));
                $new_crlf = PMA_exportComment() . $crlf;
            }
        }
        PMA_DBI_free_result($result);
    }

    $schema_create .= $new_crlf;

    // no need to generate a DROP VIEW here, it was done earlier
    if (! empty($sql_drop_table) && ! PMA_Table::isView($db,$table)) {
        $schema_create .= 'DROP TABLE IF EXISTS ' . PMA_backquote($table, $sql_backquotes) . ';' . $crlf;
    }

    // Steve Alberty's patch for complete table dump,
    // Whether to quote table and fields names or not
    if ($sql_backquotes) {
        PMA_DBI_query('SET SQL_QUOTE_SHOW_CREATE = 1');
    } else {
        PMA_DBI_query('SET SQL_QUOTE_SHOW_CREATE = 0');
    }

    // I don't see the reason why this unbuffered query could cause problems,
    // because SHOW CREATE TABLE returns only one row, and we free the
    // results below. Nonetheless, we got 2 user reports about this
    // (see bug 1562533) so I remove the unbuffered mode.
    //$result = PMA_DBI_query('SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table), null, PMA_DBI_QUERY_UNBUFFERED);
    //
    // Note: SHOW CREATE TABLE, at least in MySQL 5.1.23, does not
    // produce a displayable result for the default value of a BIT
    // field, nor does the mysqldump command. See MySQL bug 35796 
    $result = PMA_DBI_try_query('SHOW CREATE TABLE ' . PMA_backquote($db) . '.' . PMA_backquote($table));

    // an error can happen, for example the table is crashed
    $tmp_error = PMA_DBI_getError();
    if ($tmp_error) {
        return PMA_exportComment($GLOBALS['strInUse'] . '(' . $tmp_error . ')');
    }

    if ($result != FALSE && ($row = PMA_DBI_fetch_row($result))) {
        $create_query = $row[1];
        unset($row);

        // Convert end of line chars to one that we want (note that MySQL doesn't return query it will accept in all cases)
        if (strpos($create_query, "(\r\n ")) {
            $create_query = str_replace("\r\n", $crlf, $create_query);
        } elseif (strpos($create_query, "(\n ")) {
            $create_query = str_replace("\n", $crlf, $create_query);
        } elseif (strpos($create_query, "(\r ")) {
            $create_query = str_replace("\r", $crlf, $create_query);
        }

        // Should we use IF NOT EXISTS?
        if (isset($GLOBALS['sql_if_not_exists'])) {
            $create_query     = preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $create_query);
        }

        // are there any constraints to cut out?
        if (preg_match('@CONSTRAINT|FOREIGN[\s]+KEY@', $create_query)) {

            // Split the query into lines, so we can easily handle it. We know lines are separated by $crlf (done few lines above).
            $sql_lines = explode($crlf, $create_query);
            $sql_count = count($sql_lines);

            // lets find first line with constraints
            for ($i = 0; $i < $sql_count; $i++) {
                if (preg_match('@^[\s]*(CONSTRAINT|FOREIGN[\s]+KEY)@', $sql_lines[$i])) {
                    break;
                }
            }

            // If we really found a constraint
            if ($i != $sql_count) {

                // remove , from the end of create statement
                $sql_lines[$i - 1] = preg_replace('@,$@', '', $sql_lines[$i - 1]);

                // prepare variable for constraints
                if (!isset($sql_constraints)) {
                    if (isset($GLOBALS['no_constraints_comments'])) {
                        $sql_constraints = '';
                    } else {
                        $sql_constraints = $crlf
                                         . PMA_exportComment() 
                                         . PMA_exportComment($GLOBALS['strConstraintsForDumped'])
                                         . PMA_exportComment();
                    }
                }

                // comments for current table
                if (!isset($GLOBALS['no_constraints_comments'])) {
                    $sql_constraints .= $crlf
                                     . PMA_exportComment()
                                     . PMA_exportComment($GLOBALS['strConstraintsForTable'] . ' ' . PMA_backquote($table))
                                     . PMA_exportComment();
                }

                // let's do the work
                $sql_constraints_query .= 'ALTER TABLE ' . PMA_backquote($table) . $crlf;
                $sql_constraints .= 'ALTER TABLE ' . PMA_backquote($table) . $crlf;

                $first = TRUE;
                for ($j = $i; $j < $sql_count; $j++) {
                    if (preg_match('@CONSTRAINT|FOREIGN[\s]+KEY@', $sql_lines[$j])) {
                        if (!$first) {
                            $sql_constraints .= $crlf;
                        }
                        if (strpos($sql_lines[$j], 'CONSTRAINT') === FALSE) {
                            $str_tmp = preg_replace('/(FOREIGN[\s]+KEY)/', 'ADD \1', $sql_lines[$j]);
                            $sql_constraints_query .= $str_tmp;
                            $sql_constraints .= $str_tmp;
                        } else {
                            $str_tmp = preg_replace('/(CONSTRAINT)/', 'ADD \1', $sql_lines[$j]);
                            $sql_constraints_query .= $str_tmp;
                            $sql_constraints .= $str_tmp;
                        }
                        $first = FALSE;
                    } else {
                        break;
                    }
                }
                $sql_constraints .= ';' . $crlf;
                $sql_constraints_query .= ';';

                $create_query = implode($crlf, array_slice($sql_lines, 0, $i)) . $crlf . implode($crlf, array_slice($sql_lines, $j, $sql_count - 1));
                unset($sql_lines);
            }
        }
        $schema_create .= $create_query;
    }

    // remove a possible "AUTO_INCREMENT = value" clause
    // that could be there starting with MySQL 5.0.24
    $schema_create = preg_replace('/AUTO_INCREMENT\s*=\s*([0-9])+/', '', $schema_create);

    $schema_create .= $auto_increment;

    PMA_DBI_free_result($result);
    return $schema_create . ($add_semicolon ? ';' . $crlf : ''); 
} // end of the 'PMA_getTableDef()' function


/**
 * Returns $table's comments, relations etc.
 *
 * @param   string   the database name
 * @param   string   the table name
 * @param   string   the end of line sequence
 * @param   boolean  whether to include relation comments
 * @param   boolean  whether to include column comments
 * @param   boolean  whether to include mime comments
 *
 * @return  string   resulting comments
 *
 * @access  public
 */
function PMA_getTableComments($db, $table, $crlf, $do_relation = false, $do_comments = false, $do_mime = false)
{
    global $cfgRelation;
    global $sql_backquotes;
    global $sql_constraints;

    $schema_create = '';

    // triggered only for MySQL < 4.1.x (pmadb-style comments)
    if ($do_comments && $cfgRelation['commwork']) {
        if (!($comments_map = PMA_getComments($db, $table))) {
            unset($comments_map);
        }
    }

    // Check if we can use Relations (Mike Beck)
    if ($do_relation && !empty($cfgRelation['relation'])) {
        // Find which tables are related with the current one and write it in
        // an array
        $res_rel = PMA_getForeigners($db, $table);

        if ($res_rel && count($res_rel) > 0) {
            $have_rel = TRUE;
        } else {
            $have_rel = FALSE;
        }
    } else {
           $have_rel = FALSE;
    } // end if

    if ($do_mime && $cfgRelation['mimework']) {
        if (!($mime_map = PMA_getMIME($db, $table, true))) {
            unset($mime_map);
        }
    }

    if (isset($comments_map) && count($comments_map) > 0) {
        $schema_create .= $crlf
                       . PMA_exportComment() 
                       . PMA_exportComment($GLOBALS['strCommentsForTable']. ' ' . PMA_backquote($table, $sql_backquotes) . ':');
        foreach ($comments_map AS $comment_field => $comment) {
            $schema_create .= PMA_exportComment('  ' . PMA_backquote($comment_field, $sql_backquotes))
                            . PMA_exportComment('      ' . PMA_backquote($comment, $sql_backquotes));
        }
        $schema_create .= PMA_exportComment();
    }

    if (isset($mime_map) && count($mime_map) > 0) {
        $schema_create .= $crlf
                       . PMA_exportComment() 
                       . PMA_exportComment($GLOBALS['strMIMETypesForTable']. ' ' . PMA_backquote($table, $sql_backquotes) . ':');
        @reset($mime_map);
        foreach ($mime_map AS $mime_field => $mime) {
            $schema_create .= PMA_exportComment('  ' . PMA_backquote($mime_field, $sql_backquotes))
                            . PMA_exportComment('      ' . PMA_backquote($mime['mimetype'], $sql_backquotes));
        }
        $schema_create .= PMA_exportComment(); 
    }

    if ($have_rel) {
        $schema_create .= $crlf
                       . PMA_exportComment() 
                       . PMA_exportComment($GLOBALS['strRelationsForTable']. ' ' . PMA_backquote($table, $sql_backquotes) . ':');
        foreach ($res_rel AS $rel_field => $rel) {
            $schema_create .= PMA_exportComment('  ' . PMA_backquote($rel_field, $sql_backquotes))
                            . PMA_exportComment('      ' . PMA_backquote($rel['foreign_table'], $sql_backquotes)
                            . ' -> ' . PMA_backquote($rel['foreign_field'], $sql_backquotes));
        }
        $schema_create .= PMA_exportComment(); 
    }

    return $schema_create;

} // end of the 'PMA_getTableComments()' function

/**
 * Outputs table's structure
 *
 * @param   string   the database name
 * @param   string   the table name
 * @param   string   the end of line sequence
 * @param   string   the url to go back in case of error
 * @param   boolean  whether to include relation comments
 * @param   boolean  whether to include column comments
 * @param   boolean  whether to include mime comments
 * @param   string   'stand_in', 'create_table', 'create_view'
 * @param   string   'server', 'database', 'table' 
 *
 * @return  bool     Whether it suceeded
 *
 * @access  public
 */
function PMA_exportStructure($db, $table, $crlf, $error_url, $relation = FALSE, $comments = FALSE, $mime = FALSE, $dates = FALSE, $export_mode, $export_type)
{
    $formatted_table_name = (isset($GLOBALS['sql_backquotes']))
                          ? PMA_backquote($table)
                          : '\'' . $table . '\'';
    $dump = $crlf
          . PMA_exportComment(str_repeat('-', 56))
          . $crlf
          . PMA_exportComment(); 

    switch($export_mode) {
        case 'create_table':
            $dump .=  PMA_exportComment($GLOBALS['strTableStructure'] . ' ' . $formatted_table_name)
                  . PMA_exportComment(); 
            $dump .= PMA_getTableDef($db, $table, $crlf, $error_url, $dates);
            $triggers = PMA_DBI_get_triggers($db, $table);
            if ($triggers) {
                $dump .=  $crlf
                      . PMA_exportComment() 
                      . PMA_exportComment($GLOBALS['strTriggers'] . ' ' . $formatted_table_name)
                      . PMA_exportComment(); 
                $delimiter = '//';
                foreach ($triggers as $trigger) {
                    $dump .= $trigger['drop'] . ';' . $crlf;
                    $dump .= 'DELIMITER ' . $delimiter . $crlf;
                    $dump .= $trigger['create'];
                    $dump .= 'DELIMITER ;' . $crlf;
                }
            }
            break;
        case 'create_view':
            $dump .= PMA_exportComment($GLOBALS['strStructureForView'] . ' ' . $formatted_table_name)
                  .  PMA_exportComment();
            // delete the stand-in table previously created (if any)
            if ($export_type != 'table') {
                $dump .= 'DROP TABLE IF EXISTS ' . PMA_backquote($table) . ';' . $crlf;
            }
            $dump .= PMA_getTableDef($db, $table, $crlf, $error_url, $dates);
            break;
        case 'stand_in':
            $dump .=  PMA_exportComment($GLOBALS['strStandInStructureForView'] . ' ' . $formatted_table_name)
                  .  PMA_exportComment();
            // export a stand-in definition to resolve view dependencies
            $dump .= PMA_getTableDefStandIn($db, $table, $crlf);
    } // end switch

    $dump .= PMA_getTableComments($db, $table, $crlf, $relation, $comments, $mime);
    // this one is built by PMA_getTableDef() to use in table copy/move
    // but not in the case of export
    unset($GLOBALS['sql_constraints_query']);

    return PMA_exportOutputHandler($dump);
}

/**
 * Dispatches between the versions of 'getTableContent' to use depending
 * on the php version
 *
 * @param   string      the database name
 * @param   string      the table name
 * @param   string      the end of line sequence
 * @param   string      the url to go back in case of error
 * @param   string      SQL query for obtaining data
 *
 * @return  bool        Whether it suceeded
 *
 * @global  boolean  whether to use backquotes to allow the use of special
 *                   characters in database, table and fields names or not
 * @global  integer  the number of records
 * @global  integer  the current record position
 *
 * @access  public
 *
 * @see     PMA_getTableContentFast(), PMA_getTableContentOld()
 *
 * @author  staybyte
 */
function PMA_exportData($db, $table, $crlf, $error_url, $sql_query)
{
    global $sql_backquotes;
    global $rows_cnt;
    global $current_row;

    $formatted_table_name = (isset($GLOBALS['sql_backquotes']))
                          ? PMA_backquote($table)
                          : '\'' . $table . '\'';

    // Do not export data for a VIEW
    // (For a VIEW, this is called only when exporting a single VIEW)
    if (PMA_Table::_isView($db, $table)) {
        $head = $crlf
          . PMA_exportComment() 
          . PMA_exportComment('VIEW ' . ' ' . $formatted_table_name)
          . PMA_exportComment($GLOBALS['strData'] . ': ' . $GLOBALS['strNone']) 
          . PMA_exportComment() 
          . $crlf;

        if (! PMA_exportOutputHandler($head)) {
            return FALSE;
        }
        return true;
    }

    // it's not a VIEW
    $head = $crlf
          . PMA_exportComment() 
          . PMA_exportComment($GLOBALS['strDumpingData'] . ' ' . $formatted_table_name)
          . PMA_exportComment() 
          . $crlf;

    if (! PMA_exportOutputHandler($head)) {
        return FALSE;
    }

    $buffer = '';

    // analyze the query to get the true column names, not the aliases
    // (this fixes an undefined index, also if Complete inserts
    //  are used, we did not get the true column name in case of aliases)
    $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($sql_query));

    $result      = PMA_DBI_try_query($sql_query, null, PMA_DBI_QUERY_UNBUFFERED);
    // a possible error: the table has crashed
    $tmp_error = PMA_DBI_getError();
    if ($tmp_error) {
        return PMA_exportOutputHandler(PMA_exportComment($GLOBALS['strInUse'] . ' (' . $tmp_error . ')')); 
    }
    if ($result != FALSE) {
        $fields_cnt     = PMA_DBI_num_fields($result);

        // Get field information
        $fields_meta    = PMA_DBI_get_fields_meta($result);
        $field_flags    = array();
        for ($j = 0; $j < $fields_cnt; $j++) {
            $field_flags[$j] = PMA_DBI_field_flags($result, $j);
        }

        for ($j = 0; $j < $fields_cnt; $j++) {
            if (isset($analyzed_sql[0]['select_expr'][$j]['column'])) {
                $field_set[$j] = PMA_backquote($analyzed_sql[0]['select_expr'][$j]['column'], $sql_backquotes);
            } else {
                $field_set[$j] = PMA_backquote($fields_meta[$j]->name, $sql_backquotes);
            }
        }

        if (isset($GLOBALS['sql_type']) && $GLOBALS['sql_type'] == 'UPDATE') {
            // update
            $schema_insert  = 'UPDATE ';
            if (isset($GLOBALS['sql_ignore'])) {
                $schema_insert .= 'IGNORE ';
            }
            // avoid EOL blank
            $schema_insert .= PMA_backquote($table, $sql_backquotes) . ' SET';
        } else {
            // insert or replace
            if (isset($GLOBALS['sql_type']) && $GLOBALS['sql_type'] == 'REPLACE') {
                $sql_command    = 'REPLACE';
            } else {
                $sql_command    = 'INSERT';
            }

            // delayed inserts?
            if (isset($GLOBALS['sql_delayed'])) {
                $insert_delayed = ' DELAYED';
            } else {
                $insert_delayed = '';
            }

            // insert ignore?
            if (isset($GLOBALS['sql_type']) && $GLOBALS['sql_type'] == 'INSERT' && isset($GLOBALS['sql_ignore'])) {
                $insert_delayed .= ' IGNORE';
            }

            // scheme for inserting fields
            if (isset($GLOBALS['sql_columns'])) {
                $fields        = implode(', ', $field_set);
                $schema_insert = $sql_command . $insert_delayed .' INTO ' . PMA_backquote($table, $sql_backquotes)
            // avoid EOL blank
                               . ' (' . $fields . ') VALUES';
            } else {
                $schema_insert = $sql_command . $insert_delayed .' INTO ' . PMA_backquote($table, $sql_backquotes)
                               . ' VALUES';
            }
        }

        $search       = array("\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
        $replace      = array('\0', '\n', '\r', '\Z');
        $current_row  = 0;
        $query_size   = 0;
        if (isset($GLOBALS['sql_extended']) && (!isset($GLOBALS['sql_type']) || $GLOBALS['sql_type'] != 'UPDATE')) {
            $separator    = ',';
            $schema_insert .= $crlf;
        } else {
            $separator    = ';';
        }

        while ($row = PMA_DBI_fetch_row($result)) {
            $current_row++;
            for ($j = 0; $j < $fields_cnt; $j++) {
                // NULL
                if (!isset($row[$j]) || is_null($row[$j])) {
                    $values[]     = 'NULL';
                // a number
                // timestamp is numeric on some MySQL 4.1, BLOBs are sometimes numeric
                } elseif ($fields_meta[$j]->numeric && $fields_meta[$j]->type != 'timestamp'
                        && ! $fields_meta[$j]->blob) {
                    $values[] = $row[$j];
                // a true BLOB
                // - mysqldump only generates hex data when the --hex-blob
                //   option is used, for fields having the binary attribute
                //   no hex is generated
                // - a TEXT field returns type blob but a real blob
                //   returns also the 'binary' flag
                } elseif (stristr($field_flags[$j], 'BINARY')
                        && $fields_meta[$j]->blob
                        && isset($GLOBALS['sql_hex_for_blob'])) {
                    // empty blobs need to be different, but '0' is also empty :-(
                    if (empty($row[$j]) && $row[$j] != '0') {
                        $values[] = '\'\'';
                    } else {
                        $values[] = '0x' . bin2hex($row[$j]);
                    }
                // detection of 'bit' works only on mysqli extension
                } elseif ($fields_meta[$j]->type == 'bit') {
                    $values[] = "b'" . PMA_sqlAddslashes(PMA_printable_bit_value($row[$j], $fields_meta[$j]->length)) . "'";
                // something else -> treat as a string
                } else {
                    $values[] = '\'' . str_replace($search, $replace, PMA_sqlAddslashes($row[$j])) . '\'';
                } // end if
            } // end for

            // should we make update?
            if (isset($GLOBALS['sql_type']) && $GLOBALS['sql_type'] == 'UPDATE') {

                $insert_line = $schema_insert;
                for ($i = 0; $i < $fields_cnt; $i++) {
                    if (0 == $i) {
                        $insert_line .= ' ';
                    }
                    if ($i > 0) {
                        // avoid EOL blank
                        $insert_line .= ',';
                    }
                    $insert_line .= $field_set[$i] . ' = ' . $values[$i];
                }

                $insert_line .= ' WHERE ' . PMA_getUniqueCondition($result, $fields_cnt, $fields_meta, $row);

            } else {

                // Extended inserts case
                if (isset($GLOBALS['sql_extended'])) {
                    if ($current_row == 1) {
                        $insert_line  = $schema_insert . '(' . implode(', ', $values) . ')';
                    } else {
                        $insert_line  = '(' . implode(', ', $values) . ')';
                        if (isset($GLOBALS['sql_max_query_size']) && $GLOBALS['sql_max_query_size'] > 0 && $query_size + strlen($insert_line) > $GLOBALS['sql_max_query_size']) {
                            if (!PMA_exportOutputHandler(';' . $crlf)) {
                                return FALSE;
                            }
                            $query_size = 0;
                            $current_row = 1;
                            $insert_line = $schema_insert . $insert_line;
                        }
                    }
                    $query_size += strlen($insert_line);
                }
                // Other inserts case
                else {
                    $insert_line      = $schema_insert . '(' . implode(', ', $values) . ')';
                }
            }
            unset($values);

            if (!PMA_exportOutputHandler(($current_row == 1 ? '' : $separator . $crlf) . $insert_line)) {
                return FALSE;
            }

        } // end while
        if ($current_row > 0) {
            if (!PMA_exportOutputHandler(';' . $crlf)) {
                return FALSE;
            }
        }
    } // end if ($result != FALSE)
    PMA_DBI_free_result($result);

    return TRUE;
} // end of the 'PMA_exportData()' function
}
?>
