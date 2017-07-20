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

    if (strlen($option_string) === 0
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
            $option = mb_substr($trimmed, 1, -1);
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
            $option = mb_substr($rtrimmed, 1, -1);
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
        $handle = opendir('libraries/plugins/transformations/' . $sd);

        if (! $handle) {
            $stack[$prefix . 'transformation'] = array();
            $stack[$prefix . 'transformation_file'] = array();
            continue;
        }

        $filestack = array();
        while ($file = readdir($handle)) {
            // Ignore hidden files
            if ($file[0] == '.') {
                continue;
            }
            // Ignore old plugins (.class in filename)
            if (strpos($file, '.class') !== false) {
                continue;
            }
            $filestack[] = $file;
        }

        closedir($handle);
        sort($filestack);

        foreach ($filestack as $file) {
            if (preg_match('|^[^.].*_.*_.*\.php$|', $file)) {
                // File contains transformation functions.
                $parts = explode('_', str_replace('.php', '', $file));
                $mimetype = $parts[0] . "/" . $parts[1];
                $stack['mimetype'][$mimetype] = $mimetype;

                $stack[$prefix . 'transformation'][] = $mimetype . ': ' . $parts[2];
                $stack[$prefix . 'transformation_file'][] = $sd . $file;
                if ($sd === '') {
                    $stack['input_transformation'][] = $mimetype . ': ' . $parts[2];
                    $stack['input_transformation_file'][] = $sd . $file;
                }

            } elseif (preg_match('|^[^.].*\.php$|', $file)) {
                // File is a plain mimetype, no functions.
                $base = str_replace('.php', '', $file);

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
    $class_name = explode(".php", $filename);
    $class_name = 'PMA\\' . str_replace('/', '\\', $class_name[0]);

    return $class_name;
}

/**
 * Returns the description of the transformation
 *
 * @param string $file transformation file
 *
 * @return String the description of the transformation
 */
function PMA_getTransformationDescription($file)
{
    $include_file = 'libraries/plugins/transformations/' . $file;
    /* @var $class_name PMA\libraries\plugins\TransformationsInterface */
    $class_name = PMA_getTransformationClassName($include_file);
    // include and instantiate the class
    include_once $include_file;
    return $class_name::getInfo();
}

/**
 * Returns the name of the transformation
 *
 * @param string $file transformation file
 *
 * @return String the name of the transformation
 */
function PMA_getTransformationName($file)
{
    $include_file = 'libraries/plugins/transformations/' . $file;
    /* @var $class_name PMA\libraries\plugins\TransformationsInterface */
    $class_name = PMA_getTransformationClassName($include_file);
    // include and instantiate the class
    include_once $include_file;
    return $class_name::getName();
}

/**
 * Fixups old MIME or tranformation name to new one
 *
 * - applies some hardcoded fixups
 * - adds spaces after _ and numbers
 * - capitalizes words
 * - removes back spaces
 *
 * @param string $value Value to fixup
 *
 * @return string
 */
function PMA_fixupMIME($value)
{
    $value = str_replace(
        array("jpeg", "png"), array("JPEG", "PNG"), $value
    );
    return str_replace(
        ' ',
        '',
        ucwords(
            preg_replace('/([0-9_]+)/', '$1 ', $value)
        )
    );
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
         FROM ' . PMA\libraries\Util::backquote($cfgRelation['db']) . '.'
        . PMA\libraries\Util::backquote($cfgRelation['column_info']) . '
         WHERE `db_name`    = \'' . $GLOBALS['dbi']->escapeString($db) . '\'
           AND `table_name` = \'' . $GLOBALS['dbi']->escapeString($table) . '\'
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
        $values['mimetype'] = PMA_fixupMIME($values['mimetype']);

        // For transformation of form
        // output/image_jpeg__inline.inc.php
        // extract dir part.
        $dir = explode('/', $values['transformation']);
        $subdir = '';
        if (count($dir) === 2) {
            $subdir = $dir[0] . '/';
            $values['transformation'] = $dir[1];
        }

        $values['transformation'] = PMA_fixupMIME($values['transformation']);
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
    $mimetype = mb_strtolower($mimetype);
    $transformation = mb_strtolower($transformation);

    $test_qry = '
         SELECT `mimetype`,
                `comment`
           FROM ' . PMA\libraries\Util::backquote($cfgRelation['db']) . '.'
        . PMA\libraries\Util::backquote($cfgRelation['column_info']) . '
          WHERE `db_name`     = \'' . $GLOBALS['dbi']->escapeString($db) . '\'
            AND `table_name`  = \'' . $GLOBALS['dbi']->escapeString($table) . '\'
            AND `column_name` = \'' . $GLOBALS['dbi']->escapeString($key) . '\'';

    $test_rs   = PMA_queryAsControlUser(
        $test_qry, true, PMA\libraries\DatabaseInterface::QUERY_STORE
    );

    if ($test_rs && $GLOBALS['dbi']->numRows($test_rs) > 0) {
        $row = @$GLOBALS['dbi']->fetchAssoc($test_rs);
        $GLOBALS['dbi']->freeResult($test_rs);

        if (! $forcedelete
            && (strlen($mimetype) > 0
            || strlen($transformation) > 0
            || strlen($transformationOpts) > 0
            || strlen($row['comment']) > 0)
        ) {
            $upd_query = 'UPDATE '
                . PMA\libraries\Util::backquote($cfgRelation['db']) . '.'
                . PMA\libraries\Util::backquote($cfgRelation['column_info'])
                . ' SET '
                . '`mimetype` = \''
                . $GLOBALS['dbi']->escapeString($mimetype) . '\', '
                . '`transformation` = \''
                . $GLOBALS['dbi']->escapeString($transformation) . '\', '
                . '`transformation_options` = \''
                . $GLOBALS['dbi']->escapeString($transformationOpts) . '\', '
                . '`input_transformation` = \''
                . $GLOBALS['dbi']->escapeString($inputTransform) . '\', '
                . '`input_transformation_options` = \''
                . $GLOBALS['dbi']->escapeString($inputTransformOpts) . '\'';
        } else {
            $upd_query = 'DELETE FROM '
                . PMA\libraries\Util::backquote($cfgRelation['db'])
                . '.' . PMA\libraries\Util::backquote($cfgRelation['column_info']);
        }
        $upd_query .= '
            WHERE `db_name`     = \'' . $GLOBALS['dbi']->escapeString($db) . '\'
              AND `table_name`  = \'' . $GLOBALS['dbi']->escapeString($table)
                . '\'
              AND `column_name` = \'' . $GLOBALS['dbi']->escapeString($key)
                . '\'';
    } elseif (strlen($mimetype) > 0
        || strlen($transformation) > 0
        || strlen($transformationOpts) > 0
    ) {

        $upd_query = 'INSERT INTO '
            . PMA\libraries\Util::backquote($cfgRelation['db'])
            . '.' . PMA\libraries\Util::backquote($cfgRelation['column_info'])
            . ' (db_name, table_name, column_name, mimetype, '
            . 'transformation, transformation_options, '
            . 'input_transformation, input_transformation_options) '
            . ' VALUES('
            . '\'' . $GLOBALS['dbi']->escapeString($db) . '\','
            . '\'' . $GLOBALS['dbi']->escapeString($table) . '\','
            . '\'' . $GLOBALS['dbi']->escapeString($key) . '\','
            . '\'' . $GLOBALS['dbi']->escapeString($mimetype) . '\','
            . '\'' . $GLOBALS['dbi']->escapeString($transformation) . '\','
            . '\'' . $GLOBALS['dbi']->escapeString($transformationOpts) . '\','
            . '\'' . $GLOBALS['dbi']->escapeString($inputTransform) . '\','
            . '\'' . $GLOBALS['dbi']->escapeString($inputTransformOpts) . '\')';
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
        . PMA\libraries\Util::backquote($cfgRelation['db']) . '.'
        . PMA\libraries\Util::backquote($cfgRelation['column_info'])
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

