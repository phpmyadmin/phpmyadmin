<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used with the relation and pdf feature
 *
 * @package PhpMyAdmin
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
 * @param string $option_string comma separated options
 *
 * @return array options
 */
function PMA_transformation_getOptions($option_string)
{
    $result = array();

    if (! strlen($option_string)
        || ! $transform_options = preg_split('/,/', $option_string)
    ) {
        return $result;
    }

    while (($option = array_shift($transform_options)) !== null) {
        $trimmed = trim($option);
        if (strlen($trimmed) > 1
            && $trimmed[0] == "'"
            && $trimmed[strlen($trimmed) - 1] == "'"
        ) {
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
 * Returns the description of the transformation
 *
 * @param string $file           transformation file
 * @param string $html_formatted whether the description should be formatted as HTML
 *
 * @return the description of the transformation
 */
function PMA_getTransformationDescription($file, $html_formatted = true)
{
    include_once './libraries/transformations/' . $file;
    $func = strtolower(str_replace('.inc.php', '', $file));
    $funcname = 'PMA_transformation_' . $func . '_info';

    $desc = sprintf(__('No description is available for this transformation.<br />Please ask the author what %s does.'), 'PMA_transformation_' . $func . '()');
    if ($html_formatted) {
        $desc = '<i>' . $desc . '</i>';
    } else {
        $desc = str_replace('<br />', ' ', $desc);
    }
    if (function_exists($funcname)) {
        $desc_arr = $funcname();
        if (isset($desc_arr['info'])) {
            $desc = $desc_arr['info'];
        }
    }
    return $desc;
}

/**
 * Gets the mimetypes for all columns of a table
 *
 * @param string $db     the name of the db to check for
 * @param string $table  the name of the table to check for
 * @param string $strict whether to include only results having a mimetype set
 *
 * @access  public
 *
 * @return array [field_name][field_key] = field_value
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
         WHERE `db_name`    = \'' . PMA_sqlAddSlashes($db) . '\'
           AND `table_name` = \'' . PMA_sqlAddSlashes($table) . '\'
           AND ( `mimetype` != \'\'' . (!$strict ? '
              OR `transformation` != \'\'
              OR `transformation_options` != \'\'' : '') . ')';
    return PMA_DBI_fetch_result($com_qry, 'column_name', null, $GLOBALS['controllink']);
} // end of the 'PMA_getMIME()' function

/**
 * Set a single mimetype to a certain value.
 *
 * @param string $db                     the name of the db
 * @param string $table                  the name of the table
 * @param string $key                    the name of the column
 * @param string $mimetype               the mimetype of the column
 * @param string $transformation         the transformation of the column
 * @param string $transformation_options the transformation options of the column
 * @param string $forcedelete            force delete, will erase any existing
 *                                       comments for this column
 *
 * @access  public
 *
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
          WHERE `db_name`     = \'' . PMA_sqlAddSlashes($db) . '\'
            AND `table_name`  = \'' . PMA_sqlAddSlashes($table) . '\'
            AND `column_name` = \'' . PMA_sqlAddSlashes($key) . '\'';
    $test_rs   = PMA_query_as_controluser($test_qry, true, PMA_DBI_QUERY_STORE);

    if ($test_rs && PMA_DBI_num_rows($test_rs) > 0) {
        $row = @PMA_DBI_fetch_assoc($test_rs);
        PMA_DBI_free_result($test_rs);

        if (! $forcedelete
            && (strlen($mimetype) || strlen($transformation)
            || strlen($transformation_options) || strlen($row['comment']))
        ) {
            $upd_query = '
                UPDATE ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']) . '
                   SET `mimetype`               = \'' . PMA_sqlAddSlashes($mimetype) . '\',
                       `transformation`         = \'' . PMA_sqlAddSlashes($transformation) . '\',
                       `transformation_options` = \'' . PMA_sqlAddSlashes($transformation_options) . '\'';
        } else {
            $upd_query = 'DELETE FROM ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info']);
        }
        $upd_query .= '
            WHERE `db_name`     = \'' . PMA_sqlAddSlashes($db) . '\'
              AND `table_name`  = \'' . PMA_sqlAddSlashes($table) . '\'
              AND `column_name` = \'' . PMA_sqlAddSlashes($key) . '\'';
    } elseif (strlen($mimetype) || strlen($transformation)
     || strlen($transformation_options)) {
        $upd_query = 'INSERT INTO ' . PMA_backquote($cfgRelation['db']) . '.' . PMA_backquote($cfgRelation['column_info'])
                   . ' (db_name, table_name, column_name, mimetype, transformation, transformation_options) '
                   . ' VALUES('
                   . '\'' . PMA_sqlAddSlashes($db) . '\','
                   . '\'' . PMA_sqlAddSlashes($table) . '\','
                   . '\'' . PMA_sqlAddSlashes($key) . '\','
                   . '\'' . PMA_sqlAddSlashes($mimetype) . '\','
                   . '\'' . PMA_sqlAddSlashes($transformation) . '\','
                   . '\'' . PMA_sqlAddSlashes($transformation_options) . '\')';
    }

    if (isset($upd_query)) {
        return PMA_query_as_controluser($upd_query);
    } else {
        return false;
    }
} // end of 'PMA_setMIME()' function
?>
