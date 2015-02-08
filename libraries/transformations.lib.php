<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions used with the relation and pdf feature
 *
 * This file also provides basic functions to use in other plugins!
 * These are declared in the 'GLOBAL Plugin functions' section
 *
 * Please use short and expressive names.
 * For now, special characters which aren't allowed in
 * filenames or functions should not be used.
 *
 * Please provide a comment for your function,
 * what it does and what parameters are available.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Returns array of options from string with options separated by comma,
 * removes quotes
 *
 * <code>
 * PMA_Transformation_getOptions("'option ,, quoted',abd,'2,3',");
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
function PMA_Transformation_getOptions($option_string)
{
    $result = array();

    if (! /*overload*/mb_strlen($option_string)
        || ! $transform_options = preg_split('/,/', $option_string)
    ) {
        return $result;
    }

    while (($option = array_shift($transform_options)) !== null) {
        $trimmed = trim($option);
        if (/*overload*/mb_strlen($trimmed) > 1
            && $trimmed[0] == "'"
            && $trimmed[/*overload*/mb_strlen($trimmed) - 1] == "'"
        ) {
            // '...'
            $option = /*overload*/mb_substr($trimmed, 1, -1);
        } elseif (isset($trimmed[0]) && $trimmed[0] == "'") {
            // '...,
            $trimmed = ltrim($option);
            while (($option = array_shift($transform_options)) !== null) {
                // ...,
                $trimmed .= ',' . $option;
                $rtrimmed = rtrim($trimmed);
                if ($rtrimmed[/*overload*/mb_strlen($rtrimmed) - 1] == "'") {
                    // ,...'
                    break;
                }
            }
            $option = /*overload*/mb_substr($rtrimmed, 1, -1);
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
 * @return array    array[mimetype], array[transformation]
 */
function PMA_getAvailableMIMEtypes()
{
    static $stack = null;

    if (null !== $stack) {
        return $stack;
    }

    $stack = array();
    $sub_dirs = array(
        'input/' => 'input_',
        'output/' => '',
        '' => ''
    );
    foreach ($sub_dirs as $sd => $prefix) {
        $handle = opendir('./libraries/plugins/transformations/' . $sd);

        if (! $handle) {
            $stack[$prefix . 'transformation'] = array();
            $stack[$prefix . 'transformation_file'] = array();
            continue;
        }

        $filestack = array();
        while ($file = readdir($handle)) {
            $filestack[] = $file;
        }

        closedir($handle);
        sort($filestack);

        foreach ($filestack as $file) {
            if (preg_match('|^[^.].*_.*_.*\.class\.php$|', $file)) {
                // File contains transformation functions.
                $parts = explode('_', str_replace('.class.php', '', $file));
                $mimetype = $parts[0] . "/" . $parts[1];
                $stack['mimetype'][$mimetype] = $mimetype;

                $stack[$prefix . 'transformation'][] = $mimetype . ': ' . $parts[2];
                $stack[$prefix . 'transformation_file'][] = $sd . $file;
                if ($sd === '') {
                    $stack['input_transformation'][] = $mimetype . ': ' . $parts[2];
                    $stack['input_transformation_file'][] = $sd . $file;
                }

            } elseif (preg_match('|^[^.].*\.class.php$|', $file)) {
                // File is a plain mimetype, no functions.
                $base = str_replace('.class.php', '', $file);

                if ($base != 'global') {
                    $mimetype = str_replace('_', '/', $base);
                    $stack['mimetype'][$mimetype] = $mimetype;
                    $stack['empty_mimetype'][$mimetype] = $mimetype;
                }
            }
        }
    }
    return $stack;
}

/**
 * Returns the class name of the transformation
 *
 * @param string $filename transformation file name
 *
 * @return string the class name of transformation
 */
function PMA_getTransformationClassName($filename)
{
    // get the transformation class name
    $class_name = explode(".class.php", $filename);
    $class_name = explode("/", $class_name[0]);
    $class_name = count($class_name) === 1 ? $class_name[0] : $class_name[1];

    return $class_name;
}

/**
 * Returns the description of the transformation
 *
 * @param string  $file           transformation file
 * @param boolean $html_formatted whether the description should be formatted
 *                                as HTML
 *
 * @return String the description of the transformation
 */
function PMA_getTransformationDescription($file, $html_formatted = true)
{
    $class_name = PMA_getTransformationClassName($file);
    // include and instantiate the class
    include_once 'libraries/plugins/transformations/' . $file;
    return $class_name::getInfo();
}

/**
 * Gets the mimetypes for all columns of a table
 *
 * @param string  $db       the name of the db to check for
 * @param string  $table    the name of the table to check for
 * @param boolean $strict   whether to include only results having a mimetype set
 * @param boolean $fullName whether to use full column names as the key
 *
 * @access public
 *
 * @return array [field_name][field_key] = field_value
 */
function PMA_getMIME($db, $table, $strict = false, $fullName = false)
{
    $cfgRelation = PMA_getRelationsParam();

    if (! $cfgRelation['commwork']) {
        return false;
    }

    $com_qry = '';
    if ($fullName) {
        $com_qry .= "SELECT CONCAT("
            . "`db_name`, '.', `table_name`, '.', `column_name`"
            . ") AS column_name, ";
    } else {
        $com_qry  = "SELECT `column_name`, ";
    }
    $com_qry .= '`mimetype`,
                `transformation`,
                `transformation_options`,
                `input_transformation`,
                `input_transformation_options`
         FROM ' . PMA_Util::backquote($cfgRelation['db']) . '.'
        . PMA_Util::backquote($cfgRelation['column_info']) . '
         WHERE `db_name`    = \'' . PMA_Util::sqlAddSlashes($db) . '\'
           AND `table_name` = \'' . PMA_Util::sqlAddSlashes($table) . '\'
           AND ( `mimetype` != \'\'' . (!$strict ? '
              OR `transformation` != \'\'
              OR `transformation_options` != \'\'
              OR `input_transformation` != \'\'
              OR `input_transformation_options` != \'\'' : '') . ')';
    $result = $GLOBALS['dbi']->fetchResult(
        $com_qry, 'column_name', null, $GLOBALS['controllink']
    );

    foreach ($result as $column => $values) {
        // replacements in mimetype and transformation
        $values = str_replace("jpeg", "JPEG", $values);
        $values = str_replace("png", "PNG", $values);

        // convert mimetype to new format (f.e. Text_Plain, etc)
        $delimiter_space = '- ';
        $delimiter = "_";
        $values['mimetype'] = str_replace(
            $delimiter_space,
            $delimiter,
            ucwords(
                str_replace(
                    $delimiter,
                    $delimiter_space,
                    $values['mimetype']
                )
            )
        );

        // For transformation of form
        // output/image_jpeg__inline.inc.php
        // extract dir part.
        $dir = explode('/', $values['transformation']);
        $subdir = '';
        if (count($dir) === 2) {
            $subdir = $dir[0] . '/';
            $values['transformation'] = $dir[1];
        }

        $values['transformation'] = str_replace(
            $delimiter_space,
            $delimiter,
            ucwords(
                str_replace(
                    $delimiter,
                    $delimiter_space,
                    $values['transformation']
                )
            )
        );
        $values['transformation'] = $subdir . $values['transformation'];
        $result[$column] = $values;
    }

    return $result;
} // end of the 'PMA_getMIME()' function

/**
 * Set a single mimetype to a certain value.
 *
 * @param string  $db                 the name of the db
 * @param string  $table              the name of the table
 * @param string  $key                the name of the column
 * @param string  $mimetype           the mimetype of the column
 * @param string  $transformation     the transformation of the column
 * @param string  $transformationOpts the transformation options of the column
 * @param string  $inputTransform     the input transformation of the column
 * @param string  $inputTransformOpts the input transformation options of the column
 * @param boolean $forcedelete        force delete, will erase any existing
 *                                    comments for this column
 *
 * @access  public
 *
 * @return boolean  true, if comment-query was made.
 */
function PMA_setMIME($db, $table, $key, $mimetype, $transformation,
    $transformationOpts, $inputTransform, $inputTransformOpts, $forcedelete = false
) {
    $cfgRelation = PMA_getRelationsParam();

    if (! $cfgRelation['commwork']) {
        return false;
    }

    // lowercase mimetype & transformation
    $mimetype = /*overload*/mb_strtolower($mimetype);
    $transformation = /*overload*/mb_strtolower($transformation);

    $test_qry = '
         SELECT `mimetype`,
                `comment`
           FROM ' . PMA_Util::backquote($cfgRelation['db']) . '.'
        . PMA_Util::backquote($cfgRelation['column_info']) . '
          WHERE `db_name`     = \'' . PMA_Util::sqlAddSlashes($db) . '\'
            AND `table_name`  = \'' . PMA_Util::sqlAddSlashes($table) . '\'
            AND `column_name` = \'' . PMA_Util::sqlAddSlashes($key) . '\'';

    $test_rs   = PMA_queryAsControlUser(
        $test_qry, true, PMA_DatabaseInterface::QUERY_STORE
    );

    if ($test_rs && $GLOBALS['dbi']->numRows($test_rs) > 0) {
        $row = @$GLOBALS['dbi']->fetchAssoc($test_rs);
        $GLOBALS['dbi']->freeResult($test_rs);

        $transformationLength = /*overload*/mb_strlen($transformation);
        if (! $forcedelete
            && (/*overload*/mb_strlen($mimetype) || $transformationLength
            || /*overload*/mb_strlen($transformationOpts)
            || /*overload*/mb_strlen($row['comment']))
        ) {
            $upd_query = 'UPDATE ' . PMA_Util::backquote($cfgRelation['db']) . '.'
                . PMA_Util::backquote($cfgRelation['column_info'])
                . ' SET '
                . '`mimetype` = \''
                . PMA_Util::sqlAddSlashes($mimetype) . '\', '
                . '`transformation` = \''
                . PMA_Util::sqlAddSlashes($transformation) . '\', '
                . '`transformation_options` = \''
                . PMA_Util::sqlAddSlashes($transformationOpts) . '\', '
                . '`input_transformation` = \''
                . PMA_Util::sqlAddSlashes($inputTransform) . '\', '
                . '`input_transformation_options` = \''
                . PMA_Util::sqlAddSlashes($inputTransformOpts) . '\'';
        } else {
            $upd_query = 'DELETE FROM ' . PMA_Util::backquote($cfgRelation['db'])
                . '.' . PMA_Util::backquote($cfgRelation['column_info']);
        }
        $upd_query .= '
            WHERE `db_name`     = \'' . PMA_Util::sqlAddSlashes($db) . '\'
              AND `table_name`  = \'' . PMA_Util::sqlAddSlashes($table) . '\'
              AND `column_name` = \'' . PMA_Util::sqlAddSlashes($key) . '\'';
    } elseif (/*overload*/mb_strlen($mimetype)
        || /*overload*/mb_strlen($transformation)
        || /*overload*/mb_strlen($transformationOpts)
    ) {

        $upd_query = 'INSERT INTO ' . PMA_Util::backquote($cfgRelation['db'])
            . '.' . PMA_Util::backquote($cfgRelation['column_info'])
            . ' (db_name, table_name, column_name, mimetype, '
            . 'transformation, transformation_options, '
            . 'input_transformation, input_transformation_options) '
            . ' VALUES('
            . '\'' . PMA_Util::sqlAddSlashes($db) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($table) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($key) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($mimetype) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($transformation) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($transformationOpts) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($inputTransform) . '\','
            . '\'' . PMA_Util::sqlAddSlashes($inputTransformOpts) . '\')';
    }

    if (isset($upd_query)) {
        return PMA_queryAsControlUser($upd_query);
    } else {
        return false;
    }
} // end of 'PMA_setMIME()' function


/**
 * GLOBAL Plugin functions
 */


/**
 * Replaces "[__BUFFER__]" occurrences found in $options['string'] with the text
 * in $buffer, after performing a regular expression search and replace on
 * $buffer using $options['regex'] and $options['regex_replace'].
 *
 * @param string $buffer  text that will be replaced in $options['string'],
 *                        after being formatted
 * @param array  $options the options required to format $buffer
 *     = array (
 *         'string'        => 'string', // text containing "[__BUFFER__]"
 *         'regex'         => 'mixed',  // the pattern to search for
 *         'regex_replace' => 'mixed',  // string or array of strings to replace
 *                                      // with
 *     );
 *
 * @return string containing the text with all the replacements
 */
function PMA_Transformation_globalHtmlReplace($buffer, $options = array())
{
    if ( ! isset($options['string']) ) {
        $options['string'] = '';
    }

    if (isset($options['regex']) && isset($options['regex_replace'])) {
        $buffer = preg_replace(
            '@' . str_replace('@', '\@', $options['regex']) . '@si',
            $options['regex_replace'],
            $buffer
        );
    }

    // Replace occurrences of [__BUFFER__] with actual text
    $return = str_replace("[__BUFFER__]", $buffer, $options['string']);
    return $return;
}


/**
 * Delete related transformation details
 * after deleting database. table or column
 *
 * @param string $db     Database name
 * @param string $table  Table name
 * @param string $column Column name
 *
 * @return boolean State of the query execution
 */
function PMA_clearTransformations($db, $table = '', $column = '')
{
    $cfgRelation = PMA_getRelationsParam();

    if (! isset($cfgRelation['column_info'])) {
        return false;
    }

    $delete_sql = 'DELETE FROM '
        . PMA_Util::backquote($cfgRelation['db']) . '.'
        . PMA_Util::backquote($cfgRelation['column_info'])
        . ' WHERE ';

    if (($column != '') && ($table != '')) {

        $delete_sql .= '`db_name` = \'' . $db . '\' AND '
            . '`table_name` = \'' . $table . '\' AND '
            . '`column_name` = \'' . $column . '\' ';

    } else if ($table != '') {

        $delete_sql .= '`db_name` = \'' . $db . '\' AND '
            . '`table_name` = \'' . $table . '\' ';

    } else {
        $delete_sql .= '`db_name` = \'' . $db . '\' ';
    }

    return $GLOBALS['dbi']->tryQuery($delete_sql);

}

?>
