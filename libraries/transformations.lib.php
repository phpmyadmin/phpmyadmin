<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used with the relation and pdf feature
 *
 * @version $Id$
 */

/**
 * returns array of options from string with options separated by comma, removes quotes
 *
 * <code>
 * PMA_transformation_getOptions("'option ,, quoted',abd,'2,3',");
 * // array {
 * //     'option ,, quoted',
 * //     'abc',
 * //     '2,3',
 * //     '',
 * // }
 * </code>
 *
 * @uses    preg_split()
 * @uses    array_shift()
 * @uses    trim()
 * @uses    rtrim()
 * @uses    ltrim()
 * @uses    strlen()
 * @uses    substr()
 * @uses    stripslashes()
 * @param   string  $option_string  comma separated options
 * @return  array   options
 */
function PMA_transformation_getOptions($option_string)
{
    $result = array();

    if (! strlen($option_string)
     || ! $transform_options = preg_split('/,/', $option_string)) {
        return $result;
    }

    while (($option = array_shift($transform_options)) !== null) {
        $trimmed = trim($option);
        if (strlen($trimmed) > 1
         && $trimmed[0] == "'"
         && $trimmed[strlen($trimmed) - 1] == "'") {
            // '...'
            $option = substr($trimmed, 1, -1);
        } elseif (isset($trimmed[0]) && $trimmed[0] == "'") {
            // '...,
            $trimmed = ltrim($option);
            while (($option = array_shift($transform_options)) !== null) {
                // ...,
                $trimmed .= ',' . $option;
                $rtrimmed = rtrim($trimmed);
                if ($rtrimmed[strlen($rtrimmed) - 1] == "'") {
                    // ,...'
                    break;
                }
            }
            $option = substr($rtrimmed, 1, -1);
        }
        $result[] = stripslashes($option);
    }

    return $result;
}

/**
 * Gets all available MIME-types
 *
 * @access  public
 * @author  Garvin Hicking <me@supergarv.de>
 * @uses    opendir()
 * @uses    readdir()
 * @uses    closedir()
 * @uses    sort()
 * @uses    preg_match()
 * @uses    explode()
 * @uses    str_replace()
 * @staticvar   array   mimetypes
 * @return  array    array[mimetype], array[transformation]
 */
function PMA_getAvailableMIMEtypes()
{
    static $stack = null;

    if (null !== $stack) {
        return $stack;
    }

    $stack = array();
    $filestack = array();

    $handle = opendir('./libraries/transformations');

    if (! $handle) {
        return $stack;
    }

    while ($file = readdir($handle)) {
        $filestack[] = $file;
    }

    closedir($handle);
    sort($filestack);

    foreach ($filestack as $file) {
        if (preg_match('|^.*__.*\.inc\.php$|', $file)) {
            // File contains transformation functions.
            $base = explode('__', str_replace('.inc.php', '', $file));
            $mimetype = str_replace('_', '/', $base[0]);
            $stack['mimetype'][$mimetype] = $mimetype;

            $stack['transformation'][] = $mimetype . ': ' . $base[1];
            $stack['transformation_file'][] = $file;

        } elseif (preg_match('|^.*\.inc\.php$|', $file)) {
            // File is a plain mimetype, no functions.
            $base = str_replace('.inc.php', '', $file);

            if ($base != 'global') {
                $mimetype = str_replace('_', '/', $base);
                $stack['mimetype'][$mimetype] = $mimetype;
                $stack['empty_mimetype'][$mimetype] = $mimetype;
            }
        }
    }

    return $stack;
}

/**
 * Gets the mimetypes for all rows of a table
 *
 * @uses    $GLOBALS['controllink']
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_DBI_fetch_result()
 * @author  Mike Beck <mikebeck@users.sourceforge.net>
 * @author  Garvin Hicking <me@supergarv.de>
 * @access  public
 * @param   string   $db        the name of the db to check for
 * @param   string   $table     the name of the table to check for
 * @param   string   $strict    whether to include only results having a mimetype set
 * @return  array    [field_name][field_key] = field_value
 */
function PMA_getMIME($db, $table, $strict = false)
{
    $cfgRelation = PMA_getRelationsParam();

    if (! $cfgRelation['commwork']) {
        return false;
    }

    $com_qry  = '
         SELECT `column_name`,
                `mimetype`,
                `transformation`,
                `transformation_options`
          FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
         WHERE `db_name`    = \'' . PMA_sqlAddslashes($db) . '\'
           AND `table_name` = \'' . PMA_sqlAddslashes($table) . '\'
           AND ( `mimetype` != \'\'' . (!$strict ? '
              OR `transformation` != \'\'
              OR `transformation_options` != \'\'' : '') . ')';
    return PMA_DBI_fetch_result($com_qry, 'column_name', null, $GLOBALS['controllink']);
} // end of the 'PMA_getMIME()' function

/**
 * Set a single mimetype to a certain value.
 *
 * @uses    PMA_DBI_QUERY_STORE
 * @uses    PMA_getRelationsParam()
 * @uses    PMA_backquote()
 * @uses    PMA_sqlAddslashes()
 * @uses    PMA_query_as_cu()
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_DBI_fetch_assoc()
 * @uses    PMA_DBI_free_result()
 * @uses    strlen()
 * @access  public
 * @param   string   $db        the name of the db
 * @param   string   $table     the name of the table
 * @param   string   $key       the name of the column
 * @param   string   $mimetype  the mimetype of the column
 * @param   string   $transformation    the transformation of the column
 * @param   string   $transformation_options    the transformation options of the column
 * @param   string   $forcedelete   force delete, will erase any existing comments for this column
 * @return  boolean  true, if comment-query was made.
 */
function PMA_setMIME($db, $table, $key, $mimetype, $transformation,
    $transformation_options, $forcedelete = false)
{
    $cfgRelation = PMA_getRelationsParam();

    if (! $cfgRelation['commwork']) {
        return false;
    }

    $test_qry  = '
         SELECT `mimetype`,
                `comment`
           FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
          WHERE `db_name`     = \'' . PMA_sqlAddslashes($db) . '\'
            AND `table_name`  = \'' . PMA_sqlAddslashes($table) . '\'
            AND `column_name` = \'' . PMA_sqlAddslashes($key) . '\'';
    $test_rs   = PMA_query_as_cu($test_qry, true, PMA_DBI_QUERY_STORE);

    if ($test_rs && PMA_DBI_num_rows($test_rs) > 0) {
        $row = @PMA_DBI_fetch_assoc($test_rs);
        PMA_DBI_free_result($test_rs);

        if (! $forcedelete
         && (strlen($mimetype) || strlen($transformation)
          || strlen($transformation_options) || strlen($row['comment']))) {
            $upd_query = '
                UPDATE ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
                   SET `mimetype`               = \'' . PMA_sqlAddslashes($mimetype) . '\',
                       `transformation`         = \'' . PMA_sqlAddslashes($transformation) . '\',
                       `transformation_options` = \'' . PMA_sqlAddslashes($transformation_options) . '\'';
        } else {
            $upd_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']);
        }
        $upd_query .= '
            WHERE `db_name`     = \'' . PMA_sqlAddslashes($db) . '\'
              AND `table_name`  = \'' . PMA_sqlAddslashes($table) . '\'
              AND `column_name` = \'' . PMA_sqlAddslashes($key) . '\'';
    } elseif (strlen($mimetype) || strlen($transformation)
     || strlen($transformation_options)) {
        $upd_query = 'INSERT INTO ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info'])
                   . ' (db_name, table_name, column_name, mimetype, transformation, transformation_options) '
                   . ' VALUES('
                   . '\'' . PMA_sqlAddslashes($db) . '\','
                   . '\'' . PMA_sqlAddslashes($table) . '\','
                   . '\'' . PMA_sqlAddslashes($key) . '\','
                   . '\'' . PMA_sqlAddslashes($mimetype) . '\','
                   . '\'' . PMA_sqlAddslashes($transformation) . '\','
                   . '\'' . PMA_sqlAddslashes($transformation_options) . '\')';
    }

    if (isset($upd_query)){
        return PMA_query_as_cu($upd_query);
    } else {
        return false;
    }
} // end of 'PMA_setMIME()' function

/**
 * Returns the real filename of a configured transformation
 *
 * in fact: it just replaces old php3 with php extension
 *
 * garvin: for security, never allow to break out from transformations directory
 *
 * @uses    PMA_securePath()
 * @uses    preg_replace()
 * @uses    strlen()
 * @uses    file_exists()
 * @access  public
 * @param   string   $filename   the current filename
 * @return  string   the new filename
 */
function PMA_sanitizeTransformationFile(&$filename)
{
    $include_file = PMA_securePath($filename);

    // This value can also contain a 'php3' value, in which case we map this filename to our new 'php' variant
    $testfile = preg_replace('@\.inc\.php3$@', '.inc.php', $include_file);
    if ($include_file{strlen($include_file)-1} == '3'
     && file_exists('./libraries/transformations/' . $testfile)) {
        $include_file = $testfile;
        $filename     = $testfile; // Corrects the referenced variable for further actions on the filename;
    }

    return $include_file;
} // end of 'PMA_sanitizeTransformationFile()' function
?>
