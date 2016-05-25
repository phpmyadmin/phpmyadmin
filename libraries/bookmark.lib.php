<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used with the bookmark feature
 *
 * @package PhpMyAdmin
 */

/**
 * Defines the bookmark parameters for the current user
 *
 * @return array    the bookmark parameters for the current user
 * @access  public
 */
function PMA_Bookmark_getParams()
{
    static $cfgBookmark = null;

    if (null !== $cfgBookmark) {
        return $cfgBookmark;
    }

    $cfgRelation = PMA_getRelationsParam();

    if ($cfgRelation['bookmarkwork']) {
        $cfgBookmark = array(
            'user'  => $GLOBALS['cfg']['Server']['user'],
            'db'    => $cfgRelation['db'],
            'table' => $cfgRelation['bookmark'],
        );
    } else {
        $cfgBookmark = false;
    }

    return $cfgBookmark;
} // end of the 'PMA_Bookmark_getParams()' function


/**
 * Gets the list of bookmarks defined for the current database
 *
 * @param string|bool $db the current database name or false
 *
 * @return array the bookmarks list (key as index, label as value),
 *               or if param is empty, function will give more information,
 *               array will be unindexed,
 *               each struct: [db, id, label, shared, query]
 *
 * @access public
 *
 * @global resource $controllink the controluser db connection handle
 */
function PMA_Bookmark_getList($db = false)
{
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return array();
    }

    if ($db !== false) {
        $query = 'SELECT query, label, id FROM ' . PMA\libraries\Util::backquote(
            $cfgBookmark['db']
        ) . '.' . PMA\libraries\Util::backquote($cfgBookmark['table'])
        . ' WHERE dbase = \'' . PMA\libraries\Util::sqlAddSlashes($db) . '\''
        . ' AND user = \'' . PMA\libraries\Util::sqlAddSlashes($cfgBookmark['user'])
            . '\''
        . ' ORDER BY label';
        $per_user = $GLOBALS['dbi']->fetchResult(
            $query,
            'id',
            null,
            $controllink,
            PMA\libraries\DatabaseInterface::QUERY_STORE
        );

        $query = 'SELECT query, label, id FROM ' . PMA\libraries\Util::backquote(
            $cfgBookmark['db']
        ) . '.' . PMA\libraries\Util::backquote($cfgBookmark['table'])
        . ' WHERE dbase = \'' . PMA\libraries\Util::sqlAddSlashes($db) . '\''
        . ' AND user = \'\''
        . ' ORDER BY label';
        $global = $GLOBALS['dbi']->fetchResult(
            $query,
            'id',
            null,
            $controllink,
            PMA\libraries\DatabaseInterface::QUERY_STORE
        );

        foreach ($global as $key => $val) {
            $global[$key]['label'] = $val['label'] . ' (' . __('shared') . ')';
        }

        $ret = $global + $per_user;

        asort($ret);
    } else {
        $query = "SELECT `label`, `id`, `query`, `dbase` AS `db`,"
            . " IF (`user` = '', true, false) AS `shared`"
            . " FROM " . PMA\libraries\Util::backquote($cfgBookmark['db'])
            . "." . PMA\libraries\Util::backquote($cfgBookmark['table'])
            . " WHERE `user` = '' OR"
            . " `user` = '" . PMA\libraries\Util::sqlAddSlashes($cfgBookmark['user'])
            . "'";

        $ret = $GLOBALS['dbi']->fetchResult(
            $query,
            null,
            null,
            $controllink,
            PMA\libraries\DatabaseInterface::QUERY_STORE
        );
    }

    return $ret;
} // end of the 'PMA_Bookmark_getList()' function


/**
 * Gets the sql command from a bookmark
 *
 * @param string  $db                  the current database name
 * @param mixed   $id                  the id of the bookmark to get
 * @param string  $id_field            which field to look up the $id
 * @param boolean $action_bookmark_all true: get all bookmarks regardless
 *                                     of the owning user
 * @param boolean $exact_user_match    whether to ignore bookmarks with no user
 *
 * @return string    the sql query
 *
 * @access  public
 *
 * @global  resource $controllink the controluser db connection handle
 *
 */
function PMA_Bookmark_get($db, $id, $id_field = 'id', $action_bookmark_all = false,
    $exact_user_match = false
) {
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return '';
    }

    $query = 'SELECT query FROM ' . PMA\libraries\Util::backquote($cfgBookmark['db'])
        . '.' . PMA\libraries\Util::backquote($cfgBookmark['table'])
        . ' WHERE dbase = \'' . PMA\libraries\Util::sqlAddSlashes($db) . '\'';

    if (! $action_bookmark_all) {
        $query .= ' AND (user = \''
            . PMA\libraries\Util::sqlAddSlashes($cfgBookmark['user']) . '\'';
        if (! $exact_user_match) {
            $query .= ' OR user = \'\'';
        }
        $query .= ')';
    }

    $query .= ' AND ' . PMA\libraries\Util::backquote($id_field) . ' = ' . $id;

    return $GLOBALS['dbi']->fetchValue($query, 0, 0, $controllink);
} // end of the 'PMA_Bookmark_get()' function

/**
 * Adds a bookmark
 *
 * @param array   $bkm_fields the properties of the bookmark to add; here,
 *                            $bkm_fields['bkm_sql_query'] is urlencoded
 * @param boolean $all_users  whether to make the bookmark available for all users
 *
 * @return boolean   whether the INSERT succeeds or not
 *
 * @access  public
 *
 * @global  resource $controllink the controluser db connection handle
 */
function PMA_Bookmark_save($bkm_fields, $all_users = false)
{
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (!(isset($bkm_fields['bkm_sql_query']) && isset($bkm_fields['bkm_label'])
        && mb_strlen($bkm_fields['bkm_sql_query']) > 0
        && mb_strlen($bkm_fields['bkm_label']) > 0)
    ) {
        return false;
    }

    $query = 'INSERT INTO ' . PMA\libraries\Util::backquote($cfgBookmark['db'])
        . '.' . PMA\libraries\Util::backquote($cfgBookmark['table'])
        . ' (id, dbase, user, query, label)'
        . ' VALUES (NULL, \''
        . PMA\libraries\Util::sqlAddSlashes($bkm_fields['bkm_database']) . '\', '
        . '\''
        . ($all_users
            ? ''
            : PMA\libraries\Util::sqlAddSlashes(
                $bkm_fields['bkm_user']
            ))
        . '\', '
        . '\''
        . PMA\libraries\Util::sqlAddSlashes($bkm_fields['bkm_sql_query'])
        . '\', '
        . '\'' . PMA\libraries\Util::sqlAddSlashes($bkm_fields['bkm_label']) . '\')';
    return $GLOBALS['dbi']->query($query, $controllink);
} // end of the 'PMA_Bookmark_save()' function


/**
 * Deletes a bookmark
 *
 * @param integer $id the id of the bookmark to delete
 *
 * @return bool true if successful
 *
 * @access  public
 *
 * @global  resource $controllink the controluser db connection handle
 */
function PMA_Bookmark_delete($id)
{
    global $controllink;

    $cfgBookmark = PMA_Bookmark_getParams();

    if (empty($cfgBookmark)) {
        return false;
    }

    $query  = 'DELETE FROM ' . PMA\libraries\Util::backquote($cfgBookmark['db'])
        . '.' . PMA\libraries\Util::backquote($cfgBookmark['table'])
        . ' WHERE (user = \''
        . PMA\libraries\Util::sqlAddSlashes($cfgBookmark['user']) . '\''
        . '        OR user = \'\')'
        . ' AND id = ' . $id;
    return $GLOBALS['dbi']->tryQuery($query, $controllink);
} // end of the 'PMA_Bookmark_delete()' function

/**
 * Returns the number of variables in a bookmark
 *
 * @param string $query bookmarked query
 *
 * @return number number of variables
 */
function PMA_Bookmark_getVariableCount($query)
{
    $matches = array();
    preg_match_all("/\[VARIABLE[0-9]*\]/", $query, $matches, PREG_SET_ORDER);
    return count($matches);
}

/**
 * Replace the placeholders in the bookmark query with variables
 *
 * @param string $query bookmarked query
 *
 * @return string query with variables applied
 */
function PMA_Bookmark_applyVariables($query)
{
    // remove comments that encloses a variable placeholder
    $query = preg_replace(
        '|/\*(.*\[VARIABLE[0-9]*\].*)\*/|imsU',
        '${1}',
        $query
    );
    // replace variable placeholders with values
    $number_of_variables = PMA_Bookmark_getVariableCount($query);
    for ($i = 1; $i <= $number_of_variables; $i++) {
        $var = '';
        if (! empty($_REQUEST['bookmark_variable'][$i])) {
            $var = PMA\libraries\Util::sqlAddSlashes(
                $_REQUEST['bookmark_variable'][$i]
            );
        }
        $query = str_replace('[VARIABLE' . $i . ']', $var, $query);
        // backward compatibility
        if ($i == 1) {
            $query = str_replace('[VARIABLE]', $var, $query);
        }
    }
    return $query;
}
