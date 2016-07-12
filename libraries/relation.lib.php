<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used with the relation and pdf feature
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Executes a query as controluser if possible, otherwise as normal user
 *
 * @param string  $sql        the query to execute
 * @param boolean $show_error whether to display SQL error messages or not
 * @param int     $options    query options
 *
 * @return integer   the result set, or false if no result set
 *
 * @access  public
 *
 */
function PMA_queryAsControlUser($sql, $show_error = true, $options = 0)
{
    // Avoid caching of the number of rows affected; for example, this function
    // is called for tracking purposes but we want to display the correct number
    // of rows affected by the original query, not by the query generated for
    // tracking.
    $cache_affected_rows = false;

    if ($show_error) {
        $result = $GLOBALS['dbi']->query(
            $sql,
            $GLOBALS['controllink'],
            $options,
            $cache_affected_rows
        );
    } else {
        $result = @$GLOBALS['dbi']->tryQuery(
            $sql,
            $GLOBALS['controllink'],
            $options,
            $cache_affected_rows
        );
    } // end if... else...

    if ($result) {
        return $result;
    } else {
        return false;
    }
} // end of the "PMA_queryAsControlUser()" function

/**
 * Returns current relation parameters
 *
 * @return array   $cfgRelation
 */
function PMA_getRelationsParam()
{
    if (empty($_SESSION['relation'][$GLOBALS['server']])) {
        $_SESSION['relation'][$GLOBALS['server']] = PMA_checkRelationsParam();
    }

    // just for BC but needs to be before PMA_getRelationsParamDiagnostic()
    // which uses it
    $GLOBALS['cfgRelation'] = $_SESSION['relation'][$GLOBALS['server']];

    return $_SESSION['relation'][$GLOBALS['server']];
}

/**
 * prints out diagnostic info for pma relation feature
 *
 * @param array $cfgRelation Relation configuration
 *
 * @return string
 */
function PMA_getRelationsParamDiagnostic($cfgRelation)
{
    $retval = '';

    $messages = array();
    $messages['error'] = '<font color="red"><strong>'
        . __('not OK')
        . '</strong></font>';

    $messages['ok'] = '<font color="green"><strong>'
        .  _pgettext('Correctly working', 'OK')
        . '</strong></font>';

    $messages['enabled']  = '<font color="green">' . __('Enabled') . '</font>';
    $messages['disabled'] = '<font color="red">'   . __('Disabled') . '</font>';

    if (empty($cfgRelation['db'])) {
        $retval .= __('Configuration of pmadb… ')
             . $messages['error']
             . PMA_Util::showDocu('setup', 'linked-tables')
             . '<br />' . "\n"
             . __('General relation features')
             . ' <font color="green">' . __('Disabled')
             . '</font>' . "\n";
        if (! empty($GLOBALS['db']) && $GLOBALS['cfg']['ZeroConf']) {
            $retval .= PMA_getHtmlFixPMATables(true);
        }
    } else {
        $retval .= '<table>' . "\n";

        if (! $cfgRelation['allworks'] && $GLOBALS['cfg']['ZeroConf']) {
            $retval .= PMA_getHtmlFixPMATables(false);
            $retval .= '<br />';
        }

        $retval .= PMA_getDiagMessageForParameter(
            'pmadb',
            $cfgRelation['db'],
            $messages,
            'pmadb'
        );
        $retval .= PMA_getDiagMessageForParameter(
            'relation',
            isset($cfgRelation['relation']),
            $messages,
            'relation'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('General relation features'),
            'relwork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'table_info',
            isset($cfgRelation['table_info']),
            $messages,
            'table_info'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Display Features'),
            'displaywork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'table_coords',
            isset($cfgRelation['table_coords']),
            $messages,
            'table_coords'
        );
        $retval .= PMA_getDiagMessageForParameter(
            'pdf_pages',
            isset($cfgRelation['pdf_pages']),
            $messages,
            'pdf_pages'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Designer and creation of PDFs'),
            'pdfwork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'column_info',
            isset($cfgRelation['column_info']),
            $messages,
            'column_info'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Displaying Column Comments'),
            'commwork',
            $messages,
            false
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Browser transformation'),
            'mimework',
            $messages
        );
        if ($cfgRelation['commwork'] && ! $cfgRelation['mimework']) {
            $retval .= '<tr><td colspan=2 class="left error">';
            $retval .=  __(
                'Please see the documentation on how to'
                . ' update your column_info table. '
            );
            $retval .= PMA_Util::showDocu('config', 'cfg_Servers_column_info');
            $retval .= '</td></tr>';
        }
        $retval .= PMA_getDiagMessageForParameter(
            'bookmarktable',
            isset($cfgRelation['bookmark']),
            $messages,
            'bookmark'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Bookmarked SQL query'),
            'bookmarkwork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'history',
            isset($cfgRelation['history']),
            $messages,
            'history'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('SQL history'),
            'historywork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'recent',
            isset($cfgRelation['recent']),
            $messages,
            'recent'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Persistent recently used tables'),
            'recentwork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'favorite',
            isset($cfgRelation['favorite']),
            $messages,
            'favorite'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Persistent favorite tables'),
            'favoritework',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'table_uiprefs',
            isset($cfgRelation['table_uiprefs']),
            $messages,
            'table_uiprefs'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Persistent tables\' UI preferences'),
            'uiprefswork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'tracking',
            isset($cfgRelation['tracking']),
            $messages,
            'tracking'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Tracking'),
            'trackingwork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'userconfig',
            isset($cfgRelation['userconfig']),
            $messages,
            'userconfig'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('User preferences'),
            'userconfigwork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'users',
            isset($cfgRelation['users']),
            $messages,
            'users'
        );
        $retval .= PMA_getDiagMessageForParameter(
            'usergroups',
            isset($cfgRelation['usergroups']),
            $messages,
            'usergroups'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Configurable menus'),
            'menuswork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'navigationhiding',
            isset($cfgRelation['navigationhiding']),
            $messages,
            'navigationhiding'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Hide/show navigation items'),
            'navwork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'savedsearches',
            isset($cfgRelation['savedsearches']),
            $messages,
            'savedsearches'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Saving Query-By-Example searches'),
            'savedsearcheswork',
            $messages
        );
        $retval .= PMA_getDiagMessageForParameter(
            'central_columns',
            isset($cfgRelation['central_columns']),
            $messages,
            'central_columns'
        );
        $retval .= PMA_getDiagMessageForFeature(
            __('Managing Central list of columns'),
            'central_columnswork',
            $messages
        );
        $retval .= '</table>' . "\n";

        if (! $cfgRelation['allworks']) {

            $retval .= '<p>' . __('Quick steps to setup advanced features:')
                . '</p>';
            $retval .= '<ul>';
            $retval .= '<li>';
            $retval .= sprintf(
                __(
                    'Create the needed tables with the '
                    . '<code>%screate_tables.sql</code>.'
                ),
                htmlspecialchars(SQL_DIR)
            );
            $retval .= ' ' . PMA_Util::showDocu('setup', 'linked-tables');
            $retval .= '</li>';
            $retval .= '<li>';
            $retval .= __('Create a pma user and give access to these tables.');
            $retval .= ' ' . PMA_Util::showDocu('config', 'cfg_Servers_controluser');
            $retval .= '</li>';
            $retval .= '<li>';
            $retval .= __(
                'Enable advanced features in configuration file '
                . '(<code>config.inc.php</code>), for example by '
                . 'starting from <code>config.sample.inc.php</code>.'
            );
            $retval .= ' ' . PMA_Util::showDocu('setup', 'quick-install');
            $retval .= '</li>';
            $retval .= '<li>';
            $retval .= __(
                'Re-login to phpMyAdmin to load the updated configuration file.'
            );
            $retval .= '</li>';
            $retval .= '</ul>';
        }
    }

    return $retval;
}

/**
 * prints out one diagnostic message for a feature
 *
 * @param string  $feature_name       feature name in a message string
 * @param string  $relation_parameter the $GLOBALS['cfgRelation'] parameter to check
 * @param array   $messages           utility messages
 * @param boolean $skip_line          whether to skip a line after the message
 *
 * @return string
 */
function PMA_getDiagMessageForFeature($feature_name,
    $relation_parameter, $messages, $skip_line = true
) {
    $retval = '    <tr><td colspan=2 class="right">' . $feature_name . ': ';
    if (isset($GLOBALS['cfgRelation'][$relation_parameter])
        && $GLOBALS['cfgRelation'][$relation_parameter]
    ) {
        $retval .= $messages['enabled'];
    } else {
        $retval .= $messages['disabled'];
    }
    $retval .= '</td></tr>';
    if ($skip_line) {
        $retval .= '<tr><td>&nbsp;</td></tr>';
    }
    return $retval;
}

/**
 * prints out one diagnostic message for a configuration parameter
 *
 * @param string  $parameter            config parameter name to display
 * @param boolean $relationParameterSet whether this parameter is set
 * @param array   $messages             utility messages
 * @param string  $docAnchor            anchor in documentation
 *
 * @return string
 */
function PMA_getDiagMessageForParameter($parameter,
    $relationParameterSet, $messages, $docAnchor
) {
    $retval = '<tr><th class="left">';
    $retval .= '$cfg[\'Servers\'][$i][\'' . $parameter . '\']  ... ';
    $retval .= '</th><td class="right">';
    if ($relationParameterSet) {
        $retval .= $messages['ok'];
    } else {
        $retval .= sprintf(
            $messages['error'],
            PMA_Util::getDocuLink('config', 'cfg_Servers_' . $docAnchor)
        );
    }
    $retval .= '</td></tr>' . "\n";
    return $retval;
}


/**
 * Defines the relation parameters for the current user
 * just a copy of the functions used for relations ;-)
 * but added some stuff to check what will work
 *
 * @access  protected
 * @return array    the relation parameters for the current user
 */
function PMA_checkRelationsParam()
{
    $cfgRelation                   = array();
    $cfgRelation['relwork']        = false;
    $cfgRelation['displaywork']    = false;
    $cfgRelation['bookmarkwork']   = false;
    $cfgRelation['pdfwork']        = false;
    $cfgRelation['commwork']       = false;
    $cfgRelation['mimework']       = false;
    $cfgRelation['historywork']    = false;
    $cfgRelation['recentwork']     = false;
    $cfgRelation['favoritework']   = false;
    $cfgRelation['uiprefswork']    = false;
    $cfgRelation['trackingwork']   = false;
    $cfgRelation['userconfigwork'] = false;
    $cfgRelation['menuswork']      = false;
    $cfgRelation['navwork']        = false;
    $cfgRelation['allworks']       = false;
    $cfgRelation['savedsearcheswork'] = false;
    $cfgRelation['central_columnswork'] = false;
    $cfgRelation['user']           = null;
    $cfgRelation['db']             = null;

    if ($GLOBALS['server'] == 0
        || empty($GLOBALS['cfg']['Server']['pmadb'])
        || ! $GLOBALS['dbi']->selectDb(
            $GLOBALS['cfg']['Server']['pmadb'], $GLOBALS['controllink']
        )
    ) {
        // No server selected -> no bookmark table
        // we return the array with the falses in it,
        // to avoid some 'Uninitialized string offset' errors later
        $GLOBALS['cfg']['Server']['pmadb'] = false;
        return $cfgRelation;
    }

    $cfgRelation['user']  = $GLOBALS['cfg']['Server']['user'];
    $cfgRelation['db']    = $GLOBALS['cfg']['Server']['pmadb'];

    //  Now I just check if all tables that i need are present so I can for
    //  example enable relations but not pdf...
    //  I was thinking of checking if they have all required columns but I
    //  fear it might be too slow

    $tab_query = 'SHOW TABLES FROM '
        . PMA_Util::backquote(
            $GLOBALS['cfg']['Server']['pmadb']
        );
    $tab_rs    = PMA_queryAsControlUser(
        $tab_query, false, PMA_DatabaseInterface::QUERY_STORE
    );

    if (! $tab_rs) {
        // query failed ... ?
        //$GLOBALS['cfg']['Server']['pmadb'] = false;
        return $cfgRelation;
    }

    while ($curr_table = @$GLOBALS['dbi']->fetchRow($tab_rs)) {
        if ($curr_table[0] == $GLOBALS['cfg']['Server']['bookmarktable']) {
            $cfgRelation['bookmark']        = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['relation']) {
            $cfgRelation['relation']        = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['table_info']) {
            $cfgRelation['table_info']      = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['table_coords']) {
            $cfgRelation['table_coords']    = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['column_info']) {
            $cfgRelation['column_info']     = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['pdf_pages']) {
            $cfgRelation['pdf_pages']       = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['history']) {
            $cfgRelation['history']         = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['recent']) {
            $cfgRelation['recent']          = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['favorite']) {
            $cfgRelation['favorite']        = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['table_uiprefs']) {
            $cfgRelation['table_uiprefs']   = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['tracking']) {
            $cfgRelation['tracking']        = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['userconfig']) {
            $cfgRelation['userconfig']      = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['users']) {
            $cfgRelation['users']           = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['usergroups']) {
            $cfgRelation['usergroups']      = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['navigationhiding']) {
            $cfgRelation['navigationhiding']      = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['savedsearches']) {
            $cfgRelation['savedsearches']    = $curr_table[0];
        } elseif ($curr_table[0] == $GLOBALS['cfg']['Server']['central_columns']) {
            $cfgRelation['central_columns']    = $curr_table[0];
        }
    } // end while
    $GLOBALS['dbi']->freeResult($tab_rs);

    if (isset($cfgRelation['relation'])) {
        $cfgRelation['relwork']         = true;
        if (isset($cfgRelation['table_info'])) {
            $cfgRelation['displaywork'] = true;
        }
    }

    if (isset($cfgRelation['table_coords']) && isset($cfgRelation['pdf_pages'])) {
        $cfgRelation['pdfwork']     = true;
    }

    if (isset($cfgRelation['column_info'])) {
        $cfgRelation['commwork']    = true;
        // phpMyAdmin 4.3+
        // Check for input transformations upgrade.
        $cfgRelation['mimework'] = PMA_tryUpgradeTransformations();
    }

    if (isset($cfgRelation['history'])) {
        $cfgRelation['historywork']     = true;
    }

    if (isset($cfgRelation['recent'])) {
        $cfgRelation['recentwork']      = true;
    }

    if (isset($cfgRelation['favorite'])) {
        $cfgRelation['favoritework']    = true;
    }

    if (isset($cfgRelation['table_uiprefs'])) {
        $cfgRelation['uiprefswork']     = true;
    }

    if (isset($cfgRelation['tracking'])) {
        $cfgRelation['trackingwork']     = true;
    }

    if (isset($cfgRelation['userconfig'])) {
        $cfgRelation['userconfigwork']   = true;
    }

    if (isset($cfgRelation['bookmark'])) {
        $cfgRelation['bookmarkwork']     = true;
    }

    if (isset($cfgRelation['users']) && isset($cfgRelation['usergroups'])) {
        $cfgRelation['menuswork']        = true;
    }

    if (isset($cfgRelation['navigationhiding'])) {
        $cfgRelation['navwork']          = true;
    }

    if (isset($cfgRelation['savedsearches'])) {
        $cfgRelation['savedsearcheswork']      = true;
    }

    if (isset($cfgRelation['central_columns'])) {
        $cfgRelation['central_columnswork']      = true;
    }

    if ($cfgRelation['relwork'] && $cfgRelation['displaywork']
        && $cfgRelation['pdfwork'] && $cfgRelation['commwork']
        && $cfgRelation['mimework'] && $cfgRelation['historywork']
        && $cfgRelation['recentwork'] && $cfgRelation['uiprefswork']
        && $cfgRelation['trackingwork'] && $cfgRelation['userconfigwork']
        && $cfgRelation['bookmarkwork'] && $cfgRelation['central_columnswork']
        && $cfgRelation['menuswork'] && $cfgRelation['navwork']
        && $cfgRelation['savedsearcheswork'] && $cfgRelation['favoritework']
    ) {
        $cfgRelation['allworks'] = true;
    }

    return $cfgRelation;
} // end of the 'PMA_checkRelationsParam()' function

/**
 * Check whether column_info table input transformation
 * upgrade is required and try to upgrade silently
 *
 * @return bool false if upgrade failed
 *
 * @access  public
 */
function PMA_tryUpgradeTransformations()
{
    // From 4.3, new input oriented transformation feature was introduced.
    // Check whether column_info table has input transformation columns
    $new_cols = array(
        "input_transformation",
        "input_transformation_options"
    );
    $query = 'SHOW COLUMNS FROM '
        . PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb'])
        . '.' . PMA_Util::backquote($GLOBALS['cfg']['Server']['column_info'])
        . ' WHERE Field IN (\'' . implode('\', \'', $new_cols) . '\')';
    $result = PMA_queryAsControlUser(
        $query, false, PMA_DatabaseInterface::QUERY_STORE
    );
    if ($result) {
        $rows = $GLOBALS['dbi']->numRows($result);
        $GLOBALS['dbi']->freeResult($result);
        // input transformations are present
        // no need to upgrade
        if ($rows === 2) {
            return true;
            // try silent upgrade without disturbing the user
        } else {
            // read upgrade query file
            $query = @file_get_contents(SQL_DIR . 'upgrade_column_info_4_3_0+.sql');
            // replace database name from query to with set in config.inc.php
            $query = str_replace(
                '`phpmyadmin`',
                PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb']),
                $query
            );
            // replace pma__column_info table name from query
            // to with set in config.inc.php
            $query = str_replace(
                '`pma__column_info`',
                PMA_Util::backquote($GLOBALS['cfg']['Server']['column_info']),
                $query
            );
            $GLOBALS['dbi']->tryMultiQuery($query, $GLOBALS['controllink']);
            // skips result sets of query as we are not interested in it
            while ($GLOBALS['dbi']->moreResults($GLOBALS['controllink'])
                && $GLOBALS['dbi']->nextResult($GLOBALS['controllink'])
            ) {
            }
            $error = $GLOBALS['dbi']->getError($GLOBALS['controllink']);
            // return true if no error exists otherwise false
            return empty($error);
        }
    }
    // some failure, either in upgrading or something else
    // make some noise, time to wake up user.
    return false;
}

/**
 * Gets all Relations to foreign tables for a given table or
 * optionally a given column in a table
 *
 * @param string $db     the name of the db to check for
 * @param string $table  the name of the table to check for
 * @param string $column the name of the column to check for
 * @param string $source the source for foreign key information
 *
 * @return array    db,table,column
 *
 * @access  public
 */
function PMA_getForeigners($db, $table, $column = '', $source = 'both')
{
    $cfgRelation = PMA_getRelationsParam();
    $foreign = array();

    if ($cfgRelation['relwork'] && ($source == 'both' || $source == 'internal')) {
        $rel_query = '
             SELECT `master_field`,
                    `foreign_db`,
                    `foreign_table`,
                    `foreign_field`
               FROM ' . PMA_Util::backquote($cfgRelation['db'])
                . '.' . PMA_Util::backquote($cfgRelation['relation']) . '
              WHERE `master_db`    = \'' . PMA_Util::sqlAddSlashes($db) . '\'
                AND `master_table` = \'' . PMA_Util::sqlAddSlashes($table) . '\' ';
        if (/*overload*/mb_strlen($column)) {
            $rel_query .= ' AND `master_field` = '
                . '\'' . PMA_Util::sqlAddSlashes($column) . '\'';
        }
        $foreign = $GLOBALS['dbi']->fetchResult(
            $rel_query, 'master_field', null, $GLOBALS['controllink']
        );
    }

    if (($source == 'both' || $source == 'foreign') && /*overload*/mb_strlen($table)
    ) {

        $showCreateTableQuery = 'SHOW CREATE TABLE '
            . PMA_Util::backquote($db) . '.' . PMA_Util::backquote($table);
        $show_create_table = $GLOBALS['dbi']->fetchValue(
            $showCreateTableQuery, 0, 1
        );
        if ($show_create_table) {
            $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));
            $foreign['foreign_keys_data'] = $analyzed_sql[0]['foreign_keys'];
        }
    }

    /**
     * Emulating relations for some information_schema and data_dictionary tables
     */
    $isInformationSchema = /*overload*/mb_strtolower($db) == 'information_schema';
    $is_data_dictionary = PMA_DRIZZLE
        && /*overload*/mb_strtolower($db) == 'data_dictionary';
    $isMysql = /*overload*/mb_strtolower($db) == 'mysql';
    if (($isInformationSchema || $is_data_dictionary || $isMysql)
        && ($source == 'internal' || $source == 'both')
    ) {
        if ($isInformationSchema) {
            $relations_key = 'information_schema_relations';
            include_once './libraries/information_schema_relations.lib.php';
        } else if ($is_data_dictionary) {
            $relations_key = 'data_dictionary_relations';
            include_once './libraries/data_dictionary_relations.lib.php';
        } else {
            $relations_key = 'mysql_relations';
            include_once './libraries/mysql_relations.lib.php';
        }
        if (isset($GLOBALS[$relations_key][$table])) {
            foreach ($GLOBALS[$relations_key][$table] as $field => $relations) {
                if ((! /*overload*/mb_strlen($column) || $column == $field)
                    && (! isset($foreign[$field])
                    || ! /*overload*/mb_strlen($foreign[$field]))
                ) {
                    $foreign[$field] = $relations;
                }
            }
        }
    }

    return $foreign;
} // end of the 'PMA_getForeigners()' function

/**
 * Gets the display field of a table
 *
 * @param string $db    the name of the db to check for
 * @param string $table the name of the table to check for
 *
 * @return string   field name
 *
 * @access  public
 */
function PMA_getDisplayField($db, $table)
{
    $cfgRelation = PMA_getRelationsParam();

    /**
     * Try to fetch the display field from DB.
     */
    if ($cfgRelation['displaywork']) {
        $disp_query = '
             SELECT `display_field`
               FROM ' . PMA_Util::backquote($cfgRelation['db'])
                . '.' . PMA_Util::backquote($cfgRelation['table_info']) . '
              WHERE `db_name`    = \'' . PMA_Util::sqlAddSlashes($db) . '\'
                AND `table_name` = \'' . PMA_Util::sqlAddSlashes($table) . '\'';

        $row = $GLOBALS['dbi']->fetchSingleRow(
            $disp_query, 'ASSOC', $GLOBALS['controllink']
        );
        if (isset($row['display_field'])) {
            return $row['display_field'];
        }
    }

    /**
     * Emulating the display field for some information_schema tables.
     */
    if ($db == 'information_schema') {
        switch ($table) {
        case 'CHARACTER_SETS':
            return 'DESCRIPTION';
        case 'TABLES':
            return 'TABLE_COMMENT';
        }
    }

    /**
     * No Luck...
     */
    return false;

} // end of the 'PMA_getDisplayField()' function

/**
 * Gets the comments for all columns of a table or the db itself
 *
 * @param string $db    the name of the db to check for
 * @param string $table the name of the table to check for
 *
 * @return array    [column_name] = comment
 *
 * @access  public
 */
function PMA_getComments($db, $table = '')
{
    $comments = array();

    if ($table != '') {
        // MySQL native column comments
        $columns = $GLOBALS['dbi']->getColumns($db, $table, null, true);
        if ($columns) {
            foreach ($columns as $column) {
                if (! empty($column['Comment'])) {
                    $comments[$column['Field']] = $column['Comment'];
                }
            }
        }
    } else {
        $comments[] = PMA_getDbComment($db);
    }

    return $comments;
} // end of the 'PMA_getComments()' function

/**
 * Gets the comment for a db
 *
 * @param string $db the name of the db to check for
 *
 * @return string   comment
 *
 * @access  public
 */
function PMA_getDbComment($db)
{
    $cfgRelation = PMA_getRelationsParam();
    $comment = '';

    if ($cfgRelation['commwork']) {
        // pmadb internal db comment
        $com_qry = "
             SELECT `comment`
               FROM " . PMA_Util::backquote($cfgRelation['db'])
                . "." . PMA_Util::backquote($cfgRelation['column_info']) . "
              WHERE db_name     = '" . PMA_Util::sqlAddSlashes($db) . "'
                AND table_name  = ''
                AND column_name = '(db_comment)'";
        $com_rs = PMA_queryAsControlUser(
            $com_qry, true, PMA_DatabaseInterface::QUERY_STORE
        );

        if ($com_rs && $GLOBALS['dbi']->numRows($com_rs) > 0) {
            $row = $GLOBALS['dbi']->fetchAssoc($com_rs);
            $comment = $row['comment'];
        }
        $GLOBALS['dbi']->freeResult($com_rs);
    }

    return $comment;
} // end of the 'PMA_getDbComment()' function

/**
 * Gets the comment for a db
 *
 * @access  public
 *
 * @return string   comment
 */
function PMA_getDbComments()
{
    $cfgRelation = PMA_getRelationsParam();
    $comments = array();

    if ($cfgRelation['commwork']) {
        // pmadb internal db comment
        $com_qry = "
             SELECT `db_name`, `comment`
               FROM " . PMA_Util::backquote($cfgRelation['db'])
                . "." . PMA_Util::backquote($cfgRelation['column_info']) . "
              WHERE `column_name` = '(db_comment)'";
        $com_rs = PMA_queryAsControlUser(
            $com_qry, true, PMA_DatabaseInterface::QUERY_STORE
        );

        if ($com_rs && $GLOBALS['dbi']->numRows($com_rs) > 0) {
            while ($row = $GLOBALS['dbi']->fetchAssoc($com_rs)) {
                $comments[$row['db_name']] = $row['comment'];
            }
        }
        $GLOBALS['dbi']->freeResult($com_rs);
    }

    return $comments;
} // end of the 'PMA_getDbComments()' function

/**
 * Set a database comment to a certain value.
 *
 * @param string $db      the name of the db
 * @param string $comment the value of the column
 *
 * @return boolean  true, if comment-query was made.
 *
 * @access  public
 */
function PMA_setDbComment($db, $comment = '')
{
    $cfgRelation = PMA_getRelationsParam();

    if (! $cfgRelation['commwork']) {
        return false;
    }

    if (/*overload*/mb_strlen($comment)) {
        $upd_query = 'INSERT INTO '
            . PMA_Util::backquote($cfgRelation['db']) . '.'
            . PMA_Util::backquote($cfgRelation['column_info'])
            . ' (`db_name`, `table_name`, `column_name`, `comment`)'
            . ' VALUES (\''
            . PMA_Util::sqlAddSlashes($db)
            . "', '', '(db_comment)', '"
            . PMA_Util::sqlAddSlashes($comment)
            . "') "
            . ' ON DUPLICATE KEY UPDATE '
            . "`comment` = '" . PMA_Util::sqlAddSlashes($comment) . "'";
    } else {
        $upd_query = 'DELETE FROM '
            . PMA_Util::backquote($cfgRelation['db']) . '.'
            . PMA_Util::backquote($cfgRelation['column_info'])
            . ' WHERE `db_name`     = \'' . PMA_Util::sqlAddSlashes($db) . '\'
                AND `table_name`  = \'\'
                AND `column_name` = \'(db_comment)\'';
    }

    if (isset($upd_query)) {
        return PMA_queryAsControlUser($upd_query);
    }

    return false;
} // end of 'PMA_setDbComment()' function

/**
 * Set a SQL history entry
 *
 * @param string $db       the name of the db
 * @param string $table    the name of the table
 * @param string $username the username
 * @param string $sqlquery the sql query
 *
 * @return void
 *
 * @access  public
 */
function PMA_setHistory($db, $table, $username, $sqlquery)
{
    $maxCharactersInDisplayedSQL = $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'];
    // Prevent to run this automatically on Footer class destroying in testsuite
    if (defined('TESTSUITE')
        || /*overload*/mb_strlen($sqlquery) > $maxCharactersInDisplayedSQL
    ) {
        return;
    }

    $cfgRelation = PMA_getRelationsParam();

    if (! isset($_SESSION['sql_history'])) {
        $_SESSION['sql_history'] = array();
    }

    $_SESSION['sql_history'][] = array(
        'db' => $db,
        'table' => $table,
        'sqlquery' => $sqlquery,
    );

    if (count($_SESSION['sql_history']) > $GLOBALS['cfg']['QueryHistoryMax']) {
        // history should not exceed a maximum count
        array_shift($_SESSION['sql_history']);
    }

    if (! $cfgRelation['historywork'] || ! $GLOBALS['cfg']['QueryHistoryDB']) {
        return;
    }

    PMA_queryAsControlUser(
        'INSERT INTO '
        . PMA_Util::backquote($cfgRelation['db']) . '.'
        . PMA_Util::backquote($cfgRelation['history']) . '
              (`username`,
                `db`,
                `table`,
                `timevalue`,
                `sqlquery`)
        VALUES
              (\'' . PMA_Util::sqlAddSlashes($username) . '\',
               \'' . PMA_Util::sqlAddSlashes($db) . '\',
               \'' . PMA_Util::sqlAddSlashes($table) . '\',
               NOW(),
               \'' . PMA_Util::sqlAddSlashes($sqlquery) . '\')'
    );

    PMA_purgeHistory($username);

} // end of 'PMA_setHistory()' function

/**
 * Gets a SQL history entry
 *
 * @param string $username the username
 *
 * @return array    list of history items
 *
 * @access  public
 */
function PMA_getHistory($username)
{
    $cfgRelation = PMA_getRelationsParam();

    if (! $cfgRelation['historywork']) {
        return false;
    }

    /**
     * if db-based history is disabled but there exists a session-based
     * history, use it
     */
    if (! $GLOBALS['cfg']['QueryHistoryDB']) {
        if (isset($_SESSION['sql_history'])) {
            return array_reverse($_SESSION['sql_history']);
        }
        return false;
    }

    $hist_query = '
         SELECT `db`,
                `table`,
                `sqlquery`,
                `timevalue`
           FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['history']) . '
          WHERE `username` = \'' . PMA_Util::sqlAddSlashes($username) . '\'
       ORDER BY `id` DESC';

    return $GLOBALS['dbi']->fetchResult(
        $hist_query, null, null, $GLOBALS['controllink']
    );
} // end of 'PMA_getHistory()' function

/**
 * purges SQL history
 *
 * deletes entries that exceeds $cfg['QueryHistoryMax'], oldest first, for the
 * given user
 *
 * @param string $username the username
 *
 * @return void
 *
 * @access  public
 */
function PMA_purgeHistory($username)
{
    $cfgRelation = PMA_getRelationsParam();
    if (! $GLOBALS['cfg']['QueryHistoryDB'] || ! $cfgRelation['historywork']) {
        return;
    }

    if (! $cfgRelation['historywork']) {
        return;
    }

    $search_query = '
         SELECT `timevalue`
           FROM ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['history']) . '
          WHERE `username` = \'' . PMA_Util::sqlAddSlashes($username) . '\'
       ORDER BY `timevalue` DESC
          LIMIT ' . $GLOBALS['cfg']['QueryHistoryMax'] . ', 1';

    if ($max_time = $GLOBALS['dbi']->fetchValue(
        $search_query, 0, 0, $GLOBALS['controllink']
    )) {
        PMA_queryAsControlUser(
            'DELETE FROM '
            . PMA_Util::backquote($cfgRelation['db']) . '.'
            . PMA_Util::backquote($cfgRelation['history']) . '
              WHERE `username` = \'' . PMA_Util::sqlAddSlashes($username) . '\'
                AND `timevalue` <= \'' . $max_time . '\''
        );
    }
} // end of 'PMA_purgeHistory()' function

/**
 * Prepares the dropdown for one mode
 *
 * @param array  $foreign the keys and values for foreigns
 * @param string $data    the current data of the dropdown
 * @param string $mode    the needed mode
 *
 * @return array   the <option value=""><option>s
 *
 * @access  protected
 */
function PMA_buildForeignDropdown($foreign, $data, $mode)
{
    $reloptions = array();

    // id-only is a special mode used when no foreign display column
    // is available
    if ($mode == 'id-content' || $mode == 'id-only') {
        // sort for id-content
        if ($GLOBALS['cfg']['NaturalOrder']) {
            uksort($foreign, 'strnatcasecmp');
        } else {
            ksort($foreign);
        }
    } elseif ($mode == 'content-id') {
        // sort for content-id
        if ($GLOBALS['cfg']['NaturalOrder']) {
            natcasesort($foreign);
        } else {
            asort($foreign);
        }
    }

    foreach ($foreign as $key => $value) {
        if (/*overload*/mb_strlen($value) <= $GLOBALS['cfg']['LimitChars']
        ) {
            $vtitle = '';
            $value  = htmlspecialchars($value);
        } else {
            $vtitle  = htmlspecialchars($value);
            $value  = htmlspecialchars(
                /*overload*/mb_substr(
                    $value, 0, $GLOBALS['cfg']['LimitChars']
                ) . '...'
            );
        }

        $reloption = '<option value="' . htmlspecialchars($key) . '"';
        if ($vtitle != '') {
            $reloption .= ' title="' . $vtitle . '"';
        }

        if ((string) $key == (string) $data) {
            $reloption .= ' selected="selected"';
        }

        if ($mode == 'content-id') {
            $reloptions[] = $reloption . '>'
                . $value . '&nbsp;-&nbsp;' . htmlspecialchars($key) .  '</option>';
        } elseif ($mode == 'id-content') {
            $reloptions[] = $reloption . '>'
                . htmlspecialchars($key) .  '&nbsp;-&nbsp;' . $value . '</option>';
        } elseif ($mode == 'id-only') {
            $reloptions[] = $reloption . '>'
                . htmlspecialchars($key) . '</option>';
        }
    } // end foreach

    return $reloptions;
} // end of 'PMA_buildForeignDropdown' function

/**
 * Outputs dropdown with values of foreign fields
 *
 * @param array  $disp_row        array of the displayed row
 * @param string $foreign_field   the foreign field
 * @param string $foreign_display the foreign field to display
 * @param string $data            the current data of the dropdown (field in row)
 * @param int    $max             maximum number of items in the dropdown
 *
 * @return string   the <option value=""><option>s
 *
 * @access  public
 */
function PMA_foreignDropdown($disp_row, $foreign_field, $foreign_display, $data,
    $max = null
) {
    if (null === $max) {
        $max = $GLOBALS['cfg']['ForeignKeyMaxLimit'];
    }

    $foreign = array();

    // collect the data
    foreach ($disp_row as $relrow) {
        $key   = $relrow[$foreign_field];

        // if the display field has been defined for this foreign table
        if ($foreign_display) {
            $value  = $relrow[$foreign_display];
        } else {
            $value = '';
        } // end if ($foreign_display)

        $foreign[$key] = $value;
    } // end foreach

    // put the dropdown sections in correct order
    $top = array();
    $bottom = array();
    if ($foreign_display) {
        if (PMA_isValid($GLOBALS['cfg']['ForeignKeyDropdownOrder'], 'array')) {
            if (PMA_isValid($GLOBALS['cfg']['ForeignKeyDropdownOrder'][0])) {
                $top = PMA_buildForeignDropdown(
                    $foreign,
                    $data,
                    $GLOBALS['cfg']['ForeignKeyDropdownOrder'][0]
                );
            }
            if (PMA_isValid($GLOBALS['cfg']['ForeignKeyDropdownOrder'][1])) {
                $bottom = PMA_buildForeignDropdown(
                    $foreign,
                    $data,
                    $GLOBALS['cfg']['ForeignKeyDropdownOrder'][1]
                );
            }
        } else {
            $top = PMA_buildForeignDropdown($foreign, $data, 'id-content');
            $bottom = PMA_buildForeignDropdown($foreign, $data, 'content-id');
        }
    } else {
        $top = PMA_buildForeignDropdown($foreign, $data, 'id-only');
    }

    // beginning of dropdown
    $ret = '<option value="">&nbsp;</option>';
    $top_count = count($top);
    if ($max == -1 || $top_count < $max) {
        $ret .= implode('', $top);
        if ($foreign_display && $top_count > 0) {
            // this empty option is to visually mark the beginning of the
            // second series of values (bottom)
            $ret .= '<option value="">&nbsp;</option>';
        }
    }
    if ($foreign_display) {
        $ret .= implode('', $bottom);
    }

    return $ret;
} // end of 'PMA_foreignDropdown()' function

/**
 * Gets foreign keys in preparation for a drop-down selector
 *
 * @param array|boolean $foreigners     array of the foreign keys
 * @param string        $field          the foreign field name
 * @param bool          $override_total whether to override the total
 * @param string        $foreign_filter a possible filter
 * @param string        $foreign_limit  a possible LIMIT clause
 *
 * @return array    data about the foreign keys
 *
 * @access  public
 */

function PMA_getForeignData(
    $foreigners, $field, $override_total, $foreign_filter, $foreign_limit
) {
    // we always show the foreign field in the drop-down; if a display
    // field is defined, we show it besides the foreign field
    $foreign_link = false;
    do {
        if (! $foreigners) {
            break;
        }
        $foreigner = PMA_searchColumnInForeigners($foreigners, $field);
        if ($foreigner != false) {
            $foreign_db      = $foreigner['foreign_db'];
            $foreign_table   = $foreigner['foreign_table'];
            $foreign_field   = $foreigner['foreign_field'];
        } else {
            break;
        }

        // Count number of rows in the foreign table. Currently we do
        // not use a drop-down if more than ForeignKeyMaxLimit rows in the
        // foreign table,
        // for speed reasons and because we need a better interface for this.
        //
        // We could also do the SELECT anyway, with a LIMIT, and ensure that
        // the current value of the field is one of the choices.

        $the_total   = PMA_Table::countRecords($foreign_db, $foreign_table, true);

        if ($override_total == true
            || $the_total < $GLOBALS['cfg']['ForeignKeyMaxLimit']
        ) {
            // foreign_display can be false if no display field defined:
            $foreign_display = PMA_getDisplayField($foreign_db, $foreign_table);

            $f_query_main = 'SELECT ' . PMA_Util::backquote($foreign_field)
                . (
                    ($foreign_display == false)
                        ? ''
                        : ', ' . PMA_Util::backquote($foreign_display)
                );
            $f_query_from = ' FROM ' . PMA_Util::backquote($foreign_db)
                . '.' . PMA_Util::backquote($foreign_table);
            $f_query_filter = empty($foreign_filter) ? '' : ' WHERE '
                . PMA_Util::backquote($foreign_field)
                . ' LIKE "%' . PMA_Util::sqlAddSlashes($foreign_filter, true) . '%"'
                . (
                    ($foreign_display == false)
                    ? ''
                    : ' OR ' . PMA_Util::backquote($foreign_display)
                        . ' LIKE "%' . PMA_Util::sqlAddSlashes($foreign_filter, true)
                        . '%"'
                );
            $f_query_order = ($foreign_display == false) ? '' :' ORDER BY '
                . PMA_Util::backquote($foreign_table) . '.'
                . PMA_Util::backquote($foreign_display);
            $f_query_limit = isset($foreign_limit) ? $foreign_limit : '';

            if (!empty($foreign_filter)) {
                $the_total = $GLOBALS['dbi']->fetchValue(
                    'SELECT COUNT(*)' . $f_query_from . $f_query_filter
                );
                if ($the_total === false) {
                    $the_total = 0;
                }
            }

            $disp  = $GLOBALS['dbi']->tryQuery(
                $f_query_main . $f_query_from . $f_query_filter
                . $f_query_order . $f_query_limit
            );
            if ($disp && $GLOBALS['dbi']->numRows($disp) > 0) {
                // If a resultset has been created, pre-cache it in the $disp_row
                // array. This helps us from not needing to use mysql_data_seek by
                // accessing a pre-cached PHP array. Usually those resultsets are
                // not that big, so a performance hit should not be expected.
                $disp_row = array();
                while ($single_disp_row = @$GLOBALS['dbi']->fetchAssoc($disp)) {
                    $disp_row[] = $single_disp_row;
                }
                @$GLOBALS['dbi']->freeResult($disp);
            } else {
                // Either no data in the foreign table or
                // user does not have select permission to foreign table/field
                // Show an input field with a 'Browse foreign values' link
                $disp_row = null;
                $foreign_link = true;
            }
        } else {
            $disp_row = null;
            $foreign_link = true;
        }
    } while (false);

    $foreignData = array();
    $foreignData['foreign_link'] = $foreign_link;
    $foreignData['the_total'] = isset($the_total) ? $the_total : null;
    $foreignData['foreign_display'] = (
        isset($foreign_display) ? $foreign_display : null
    );
    $foreignData['disp_row'] = isset($disp_row) ? $disp_row : null;
    $foreignData['foreign_field'] = isset($foreign_field) ? $foreign_field : null;
    return $foreignData;
} // end of 'PMA_getForeignData()' function

/**
 * Finds all related tables
 *
 * @param array  $all_tables All the involved tables
 * @param string $master     The master table to form the LEFT JOIN clause
 *
 * @return string LEFT JOIN
 * @access  private
 */
function PMA_getRelatives($all_tables, $master)
{
    $fromclause = '';
    $left_joins = array();
    $emerg = '';
    $ignore_internal_relations = false;

    // The list of tables that we still couldn't connect
    $remaining_tables = $all_tables;
    unset($remaining_tables[$master]);
    // The list of already connected tables
    $known_tables = array();
    $known_tables[$master] = $master;
    $run = 0;
    while ($GLOBALS['cfgRelation']['relwork'] && count($remaining_tables) > 0) {
        // Whether to go from master to foreign or vice versa
        if ($run % 2 == 0) {
            $from = 'master';
            $to    = 'foreign';
        } else {
            $from = 'foreign';
            $to    = 'master';
        }
        $in_know = '(\'' . implode('\', \'', $known_tables) . '\')';
        $in_left = '(\'' . implode('\', \'', $remaining_tables) . '\')';
        $rel_query = 'SELECT *'
            . '  FROM ' . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
            .       '.' . PMA_Util::backquote($GLOBALS['cfgRelation']['relation'])
            . ' WHERE ' . $from . '_db = \''
            . PMA_Util::sqlAddSlashes($GLOBALS['db']) . '\''
            . '   AND ' . $to   . '_db = \''
            . PMA_Util::sqlAddSlashes($GLOBALS['db']) . '\''
            . '   AND ' . $from . '_table IN ' . $in_know
            . '   AND ' . $to   . '_table IN ' . $in_left;
        $relations = @$GLOBALS['dbi']->query($rel_query, $GLOBALS['controllink']);
        while ($row = $GLOBALS['dbi']->fetchAssoc($relations)) {
            $found_table                = $row[$to . '_table'];
            if (isset($remaining_tables[$found_table])) {
                $left_join_with = PMA_Util::backquote($GLOBALS['db']) . '.'
                        . PMA_Util::backquote($row[$to . '_table']);
                $on_condition = PMA_Util::backquote($row[$from . '_table']) . '.'
                    . PMA_Util::backquote($row[$from . '_field']) . ' = '
                    . PMA_Util::backquote($row[$to . '_table']) . '.'
                    . PMA_Util::backquote($row[$to . '_field']);

                $left_joins[$left_join_with] = array(
                    'left_join_with' => $left_join_with,
                    'on_condition' => array($on_condition)
                );

                $known_tables[$found_table] = $found_table;
                unset($remaining_tables[$found_table]);
            }
        } // end while
        $run++;
        if ($run > 5) {
            foreach ($remaining_tables as $table) {
                $emerg .= ', ' . PMA_Util::backquote($table);
                unset($remaining_tables[$table]);
            }
        }
    } // end while

    // Generate 'LEFT JOIN's for InnoDB foreign keys.
    $remaining_tables = $all_tables;
    foreach ($remaining_tables as $one_table) {
        $foreigners = PMA_getForeigners($GLOBALS['db'], $one_table, '', 'foreign');
        foreach ($foreigners['foreign_keys_data'] as $one_key) {
            if (in_array($one_key['ref_table_name'], $all_tables)
                && ! isset($one_key['ref_db_name'])
                && ($one_key['ref_table_name'] == $master
                || $one_key['ref_table_name'] == $one_table)
            ) {
                $left_join_with = PMA_Util::backquote($GLOBALS['db']) . '.'
                    . PMA_Util::backquote($one_table);
                if (! isset($left_joins[$left_join_with])) {
                    $left_joins[$left_join_with] = array(
                        'left_join_with' =>$left_join_with,
                        'on_condition' => array()
                    );
                }

                foreach ($one_key['ref_index_list'] as $key => $one_column) {
                    $on_condition = PMA_Util::backquote($one_key['ref_table_name'])
                    . '.' . PMA_Util::backquote($one_column) . ' = '
                    . PMA_Util::backquote($one_table) . '.'
                    . PMA_Util::backquote($one_key['index_list'][$key]);

                    if (! in_array($on_condition, $left_joins[$left_join_with]['on_condition'])) {
                        $left_joins[$left_join_with]['on_condition'][]
                            = $on_condition;
                    }
                }
                $ignore_internal_relations = true;
                unset($remaining_tables[$one_table]);
            }
        }
    }

    if ($ignore_internal_relations) {
        $emerg = '';
    }

    // Build the 'FROM' clause.
    foreach ($left_joins as $one_join) {
        $fromclause .= "\n" . ' LEFT JOIN '
            . $one_join['left_join_with']
            . ' ON '
            . implode(' AND ', $one_join['on_condition']);
    }

    $fromclause = $emerg . $fromclause;
    return $fromclause;
} // end of the "PMA_getRelatives()" function

/**
 * Rename a field in relation tables
 *
 * usually called after a column in a table was renamed
 *
 * @param string $db       database name
 * @param string $table    table name
 * @param string $field    old field name
 * @param string $new_name new field name
 *
 * @return void
 */
function PMA_REL_renameField($db, $table, $field, $new_name)
{
    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['displaywork']) {
        $table_query = 'UPDATE '
            . PMA_Util::backquote($cfgRelation['db']) . '.'
            . PMA_Util::backquote($cfgRelation['table_info'])
            . '   SET display_field = \'' . PMA_Util::sqlAddSlashes($new_name) . '\''
            . ' WHERE db_name       = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . '   AND table_name    = \'' . PMA_Util::sqlAddSlashes($table) . '\''
            . '   AND display_field = \'' . PMA_Util::sqlAddSlashes($field) . '\'';
        PMA_queryAsControlUser($table_query);
    }

    if ($cfgRelation['relwork']) {
        $table_query = 'UPDATE '
            . PMA_Util::backquote($cfgRelation['db']) . '.'
            . PMA_Util::backquote($cfgRelation['relation'])
            . '   SET master_field = \'' . PMA_Util::sqlAddSlashes($new_name) . '\''
            . ' WHERE master_db    = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . '   AND master_table = \'' . PMA_Util::sqlAddSlashes($table) . '\''
            . '   AND master_field = \'' . PMA_Util::sqlAddSlashes($field) . '\'';
        PMA_queryAsControlUser($table_query);

        $table_query = 'UPDATE '
            . PMA_Util::backquote($cfgRelation['db']) . '.'
            . PMA_Util::backquote($cfgRelation['relation'])
            . '   SET foreign_field = \'' . PMA_Util::sqlAddSlashes($new_name) . '\''
            . ' WHERE foreign_db    = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . '   AND foreign_table = \'' . PMA_Util::sqlAddSlashes($table) . '\''
            . '   AND foreign_field = \'' . PMA_Util::sqlAddSlashes($field) . '\'';
        PMA_queryAsControlUser($table_query);

    } // end if relwork
}


/**
 * Performs SQL query used for renaming table.
 *
 * @param string $table        Relation table to use
 * @param string $source_db    Source database name
 * @param string $target_db    Target database name
 * @param string $source_table Source table name
 * @param string $target_table Target table name
 * @param string $db_field     Name of database field
 * @param string $table_field  Name of table field
 *
 * @return void
 */
function PMA_REL_renameSingleTable($table,
    $source_db, $target_db,
    $source_table, $target_table,
    $db_field, $table_field
) {
    $query = 'UPDATE '
        . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
        . PMA_Util::backquote($GLOBALS['cfgRelation'][$table])
        . ' SET '
        . $db_field . ' = \'' . PMA_Util::sqlAddSlashes($target_db) . '\', '
        . $table_field . ' = \'' . PMA_Util::sqlAddSlashes($target_table) . '\''
        . ' WHERE '
        . $db_field . '  = \'' . PMA_Util::sqlAddSlashes($source_db) . '\''
        . ' AND '
        . $table_field . ' = \'' . PMA_Util::sqlAddSlashes($source_table) . '\'';
    PMA_queryAsControlUser($query);
}


/**
 * Rename a table in relation tables
 *
 * usually called after table has been moved
 *
 * @param string $source_db    Source database name
 * @param string $target_db    Target database name
 * @param string $source_table Source table name
 * @param string $target_table Target table name
 *
 * @return void
 */
function PMA_REL_renameTable($source_db, $target_db, $source_table, $target_table)
{
    // Move old entries from PMA-DBs to new table
    if ($GLOBALS['cfgRelation']['commwork']) {
        PMA_REL_renameSingleTable(
            'column_info',
            $source_db, $target_db,
            $source_table, $target_table,
            'db_name', 'table_name'
        );
    }

    // updating bookmarks is not possible since only a single table is
    // moved, and not the whole DB.

    if ($GLOBALS['cfgRelation']['displaywork']) {
        PMA_REL_renameSingleTable(
            'table_info',
            $source_db, $target_db,
            $source_table, $target_table,
            'db_name', 'table_name'
        );
    }

    if ($GLOBALS['cfgRelation']['relwork']) {
        PMA_REL_renameSingleTable(
            'relation',
            $source_db, $target_db,
            $source_table, $target_table,
            'foreign_db', 'foreign_table'
        );

        PMA_REL_renameSingleTable(
            'relation',
            $source_db, $target_db,
            $source_table, $target_table,
            'master_db', 'master_table'
        );
    }

    if ($GLOBALS['cfgRelation']['pdfwork']) {
        if ($source_db == $target_db) {
            // rename within the database can be handled
            PMA_REL_renameSingleTable(
                'table_coords',
                $source_db, $target_db,
                $source_table, $target_table,
                'db_name', 'table_name'
            );
        } else {
            // if the table is moved out of the database we can no loger keep the
            // record for table coordinate
            $remove_query = "DELETE FROM "
                . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . "."
                . PMA_Util::backquote($GLOBALS['cfgRelation']['table_coords'])
                . " WHERE db_name  = '" . PMA_Util::sqlAddSlashes($source_db) . "'"
                . " AND table_name = '" . PMA_Util::sqlAddSlashes($source_table)
                . "'";
            PMA_queryAsControlUser($remove_query);
        }
    }

    if ($GLOBALS['cfgRelation']['uiprefswork']) {
        PMA_REL_renameSingleTable(
            'table_uiprefs',
            $source_db, $target_db,
            $source_table, $target_table,
            'db_name', 'table_name'
        );
    }

    if ($GLOBALS['cfgRelation']['navwork']) {
        // update hidden items inside table
        PMA_REL_renameSingleTable(
            'navigationhiding',
            $source_db, $target_db,
            $source_table, $target_table,
            'db_name', 'table_name'
        );

        // update data for hidden table
        $query = "UPDATE "
            . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . "."
            . PMA_Util::backquote($GLOBALS['cfgRelation']['navigationhiding'])
            . " SET db_name = '" . PMA_Util::sqlAddSlashes($target_db) . "',"
            . " item_name = '" . PMA_Util::sqlAddSlashes($target_table) . "'"
            . " WHERE db_name  = '" . PMA_Util::sqlAddSlashes($source_db) . "'"
            . " AND item_name = '" . PMA_Util::sqlAddSlashes($source_table) . "'"
            . " AND item_type = 'table'";
        PMA_queryAsControlUser($query);
    }
}

/**
 * Create a PDF page
 *
 * @param string $newpage     name of the new PDF page
 * @param array  $cfgRelation Relation configuration
 * @param string $db          database name
 *
 * @return int $pdf_page_number
 */
function PMA_REL_createPage($newpage, $cfgRelation, $db)
{
    if (! isset($newpage) || $newpage == '') {
        $newpage = __('no description');
    }
    $ins_query   = 'INSERT INTO '
        . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
        . PMA_Util::backquote($cfgRelation['pdf_pages'])
        . ' (db_name, page_descr)'
        . ' VALUES (\''
        . PMA_Util::sqlAddSlashes($db) . '\', \''
        . PMA_Util::sqlAddSlashes($newpage) . '\')';
    PMA_queryAsControlUser($ins_query, false);

    return $GLOBALS['dbi']->insertId(
        isset($GLOBALS['controllink']) ? $GLOBALS['controllink'] : ''
    );
}

/**
 * Get child table references for a table column.
 * This works only if 'DisableIS' is false. An empty array is returned otherwise.
 *
 * @param string $db     name of master table db.
 * @param string $table  name of master table.
 * @param string $column name of master table column.
 *
 * @return array $child_references
 */
function PMA_getChildReferences($db, $table, $column = '')
{
    $child_references = array();
    if (! $GLOBALS['cfg']['Server']['DisableIS']) {
        $rel_query = "SELECT `column_name`, `table_name`,"
            . " `table_schema`, `referenced_column_name`"
            . " FROM `information_schema`.`key_column_usage`"
            . " WHERE `referenced_table_name` = '"
            . PMA_Util::sqlAddSlashes($table) . "'"
            . " AND `referenced_table_schema` = '"
            . PMA_Util::sqlAddSlashes($db) . "'";
        if ($column) {
            $rel_query .= " AND `referenced_column_name` = '"
                . PMA_Util::sqlAddSlashes($column) . "'";
        }

        $child_references = $GLOBALS['dbi']->fetchResult(
            $rel_query, array('referenced_column_name', null)
        );
    }
    return $child_references;
}

/**
 * Check child table references and foreign key for a table column.
 *
 * @param string $db                    name of master table db.
 * @param string $table                 name of master table.
 * @param string $column                name of master table column.
 * @param array  $foreigners_full       foreiners array for the whole table.
 * @param array  $child_references_full child references for the whole table.
 *
 * @return array $column_status telling about references if foreign key.
 */
function PMA_checkChildForeignReferences(
    $db, $table, $column, $foreigners_full = null, $child_references_full = null
) {
    $column_status = array();
    $column_status['isEditable'] = false;
    $column_status['isReferenced'] = false;
    $column_status['isForeignKey'] = false;
    $column_status['references'] = array();

    $foreigners = array();
    if ($foreigners_full !== null) {
        if (isset($foreigners_full[$column])) {
            $foreigners[$column] = $foreigners_full[$column];
        }
        if (isset($foreigners_full['foreign_keys_data'])) {
            $foreigners['foreign_keys_data'] = $foreigners_full['foreign_keys_data'];
        }
    } else {
        $foreigners = PMA_getForeigners($db, $table, $column, 'foreign');
    }
    $foreigner = PMA_searchColumnInForeigners($foreigners, $column);

    $child_references = array();
    if ($child_references_full !== null) {
        if (isset($child_references_full[$column])) {
            $child_references = $child_references_full[$column];
        }
    } else {
        $child_references = PMA_getChildReferences($db, $table, $column);
    }

    if (sizeof($child_references, 0) > 0
        || $foreigner
    ) {
        if (sizeof($child_references, 0) > 0) {
            $column_status['isReferenced'] = true;
            foreach ($child_references as $row => $columns) {
                array_push(
                    $column_status['references'],
                    PMA_Util::backquote($columns['table_schema'])
                    . '.' . PMA_Util::backquote($columns['table_name'])
                );
            }
        }

        if ($foreigner) {
            $column_status['isForeignKey'] = true;
        }
    } else {
        $column_status['isEditable'] = true;
    }

    return $column_status;
}

/**
 * Search a table column in foreign data.
 *
 * @param array  $foreigners Table Foreign data
 * @param string $column     Column name
 *
 * @return bool|array
 */
function PMA_searchColumnInForeigners($foreigners, $column)
{
    if (isset($foreigners[$column])) {
        return $foreigners[$column];
    } else {
        $foreigner = array();
        foreach ($foreigners['foreign_keys_data'] as $key => $one_key) {
            $column_index = array_search($column, $one_key['index_list']);
            if ($column_index !== false) {
                $foreigner['foreign_field']
                    = $one_key['ref_index_list'][$column_index];
                $foreigner['foreign_db'] = isset($one_key['ref_db_name'])
                    ? $one_key['ref_db_name']
                    : $GLOBALS['db'];
                $foreigner['foreign_table'] = $one_key['ref_table_name'];
                $foreigner['constraint'] = $one_key['constraint'];
                $foreigner['on_update'] = isset($one_key['on_update'])
                    ? $one_key['on_update']
                    : 'RESTRICT';
                $foreigner['on_delete'] = isset($one_key['on_delete'])
                    ? $one_key['on_delete']
                    : 'RESTRICT';

                return $foreigner;
            }
        }
    }

    return false;
}

/**
 * Returns default PMA table names and their create queries.
 *
 * @return array table name, create query
 */
function PMA_getDefaultPMATableNames()
{
    $pma_tables = array();
    if (PMA_DRIZZLE) {
        $create_tables_file = file_get_contents(
            SQL_DIR . 'create_tables_drizzle.sql'
        );
    } else {
        $create_tables_file = file_get_contents(
            SQL_DIR . 'create_tables.sql'
        );
    }

    $queries = explode(';', $create_tables_file);

    foreach ($queries as $query) {
        if (preg_match(
            '/CREATE TABLE IF NOT EXISTS `(.*)` \(/',
            $query,
            $table
        )
        ) {
            $pma_tables[$table[1]] = $query . ';';
        }
    }

    return $pma_tables;
}

/**
 * Creates PMA tables in the given db, updates if already exists.
 *
 * @param string  $db     database
 * @param boolean $create whether to create tables if they don't exist.
 *
 * @return void
 */
function PMA_fixPMATables($db, $create = true)
{
    $tablesToFeatures = array(
        'pma__bookmark' => 'bookmarktable',
        'pma__relation' => 'relation',
        'pma__table_info' => 'table_info',
        'pma__table_coords' => 'table_coords',
        'pma__pdf_pages' => 'pdf_pages',
        'pma__column_info' => 'column_info',
        'pma__history' => 'history',
        'pma__recent' => 'recent',
        'pma__favorite' => 'favorite',
        'pma__table_uiprefs' => 'table_uiprefs',
        'pma__tracking' => 'tracking',
        'pma__userconfig' => 'userconfig',
        'pma__users' => 'users',
        'pma__usergroups' => 'usergroups',
        'pma__navigationhiding' => 'navigationhiding',
        'pma__savedsearches' => 'savedsearches',
        'pma__central_columns' => 'central_columns'
    );

    $existingTables = $GLOBALS['dbi']->getTables($db, $GLOBALS['controllink']);

    $createQueries = null;
    $foundOne = false;
    foreach ($tablesToFeatures as $table => $feature) {
        if (! in_array($table, $existingTables)) {
            if ($create) {
                if ($createQueries == null) { // first create
                    $createQueries = PMA_getDefaultPMATableNames();
                    $GLOBALS['dbi']->selectDb($db);
                }
                $GLOBALS['dbi']->tryQuery($createQueries[$table]);
                if ($error = $GLOBALS['dbi']->getError()) {
                    $GLOBALS['message'] = $error;
                    return;
                }
                $GLOBALS['cfg']['Server'][$feature] = $table;
            }
        } else {
            $foundOne = true;
            $GLOBALS['cfg']['Server'][$feature] = $table;
        }
    }

    if (! $foundOne) {
        return;
    }
    $GLOBALS['cfg']['Server']['pmadb'] = $db;
    $_SESSION['relation'][$GLOBALS['server']] = PMA_checkRelationsParam();

    $cfgRelation = PMA_getRelationsParam();
    if ($cfgRelation['recentwork'] || $cfgRelation['favoritework']) {
        // Since configuration storage is updated, we need to
        // re-initialize the favorite and recent tables stored in the
        // session from the current configuration storage.
        include_once 'libraries/RecentFavoriteTable.class.php';

        if ($cfgRelation['favoritework']) {
            $fav_tables = PMA_RecentFavoriteTable::getInstance('favorite');
            $_SESSION['tmpval']['favorite_tables'][$GLOBALS['server']]
                = $fav_tables->getFromDb();
        }

        if ($cfgRelation['recentwork']) {
            $recent_tables = PMA_RecentFavoriteTable::getInstance('recent');
            $_SESSION['tmpval']['recent_tables'][$GLOBALS['server']]
                = $recent_tables->getFromDb();
        }

        // Reload navi panel to update the recent/favorite lists.
        $GLOBALS['reload'] = true;
    }
}

/**
 * Get Html for PMA tables fixing anchor.
 *
 * @param boolean $allTables whether to create all tables
 *
 * @return string Html
 */
function PMA_getHtmlFixPMATables($allTables)
{
    $retval = '';

    $url_query = PMA_URL_getCommon(array('db' => $GLOBALS['db']));
    if ($allTables) {
        $url_query .= '&amp;goto=db_operations.php&amp;create_pmadb=1';
        $message = PMA_Message::notice(
            __(
                '%sCreate%s the phpMyAdmin configuration storage in the '
                . 'current database.'
            )
        );
    } else {
        $url_query .= '&amp;goto=db_operations.php&amp;fix_pmadb=1';
        $message = PMA_Message::notice(
            __('%sCreate%s missing phpMyAdmin configuration storage tables.')
        );
    }
    $message->addParam(
        '<a href="' . $GLOBALS['cfg']['PmaAbsoluteUri']
        . 'chk_rel.php' . $url_query . '">',
        false
    );
    $message->addParam('</a>', false);

    $retval .= $message->getDisplay();

    return $retval;
}

/**
 * Gets the relations info and status, depending on the condition
 *
 * @param boolean $condition whether to look for foreigners or not
 * @param string  $db        database name
 * @param string  $table     table name
 *
 * @return array ($res_rel, $have_rel)
 */
function PMA_getRelationsAndStatus($condition, $db, $table)
{
    if ($condition) {
        // Find which tables are related with the current one and write it in
        // an array
        $res_rel = PMA_getForeigners($db, $table);

        if (count($res_rel) > 0) {
            $have_rel = true;
        } else {
            $have_rel = false;
        }
    } else {
        $have_rel = false;
        $res_rel = array();
    } // end if
    return(array($res_rel, $have_rel));
}
?>
