<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions with the insert/edit features in pma
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\FileListing;
use PhpMyAdmin\Message;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Response;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Template;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use PhpMyAdmin\Core;

/**
 * PhpMyAdmin\InsertEdit class
 *
 * @package PhpMyAdmin
 */
class InsertEdit
{
    /**
     * DatabaseInterface instance
     *
     * @var DatabaseInterface
     */
    private $dbi;

    /**
     * @var Relation $relation
     */
    private $relation;

    /**
     * Constructor
     *
     * @param DatabaseInterface $dbi DatabaseInterface instance
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
        $this->relation = new Relation();
    }

    /**
     * Retrieve form parameters for insert/edit form
     *
     * @param string     $db                 name of the database
     * @param string     $table              name of the table
     * @param array|null $where_clauses      where clauses
     * @param array      $where_clause_array array of where clauses
     * @param string     $err_url            error url
     *
     * @return array $form_params array of insert/edit form parameters
     */
    public function getFormParametersForInsertForm(
        $db,
        $table,
        $where_clauses,
        array $where_clause_array,
        $err_url
    ) {
        $_form_params = array(
            'db'        => $db,
            'table'     => $table,
            'goto'      => $GLOBALS['goto'],
            'err_url'   => $err_url,
            'sql_query' => $_POST['sql_query'],
        );
        if (isset($where_clauses)) {
            foreach ($where_clause_array as $key_id => $where_clause) {
                $_form_params['where_clause[' . $key_id . ']'] = trim($where_clause);
            }
        }
        if (isset($_POST['clause_is_unique'])) {
            $_form_params['clause_is_unique'] = $_POST['clause_is_unique'];
        }
        return $_form_params;
    }

    /**
     * Creates array of where clauses
     *
     * @param array|string|null $where_clause where clause
     *
     * @return array whereClauseArray array of where clauses
     */
    private function getWhereClauseArray($where_clause)
    {
        if (!isset($where_clause)) {
            return array();
        }

        if (is_array($where_clause)) {
            return $where_clause;
        }

        return array(0 => $where_clause);
    }

    /**
     * Analysing where clauses array
     *
     * @param array  $where_clause_array array of where clauses
     * @param string $table              name of the table
     * @param string $db                 name of the database
     *
     * @return array $where_clauses, $result, $rows
     */
    private function analyzeWhereClauses(
        array $where_clause_array,
        $table,
        $db
    ) {
        $rows               = array();
        $result             = array();
        $where_clauses      = array();
        $found_unique_key   = false;
        foreach ($where_clause_array as $key_id => $where_clause) {
            $local_query     = 'SELECT * FROM '
                . Util::backquote($db) . '.'
                . Util::backquote($table)
                . ' WHERE ' . $where_clause . ';';
            $result[$key_id] = $this->dbi->query(
                $local_query,
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            );
            $rows[$key_id] = $this->dbi->fetchAssoc($result[$key_id]);

            $where_clauses[$key_id] = str_replace('\\', '\\\\', $where_clause);
            $has_unique_condition = $this->showEmptyResultMessageOrSetUniqueCondition(
                $rows,
                $key_id,
                $where_clause_array,
                $local_query,
                $result
            );
            if ($has_unique_condition) {
                $found_unique_key = true;
            }
        }
        return array($where_clauses, $result, $rows, $found_unique_key);
    }

    /**
     * Show message for empty result or set the unique_condition
     *
     * @param array  $rows               MySQL returned rows
     * @param string $key_id             ID in current key
     * @param array  $where_clause_array array of where clauses
     * @param string $local_query        query performed
     * @param array  $result             MySQL result handle
     *
     * @return boolean $has_unique_condition
     */
    private function showEmptyResultMessageOrSetUniqueCondition(
        array $rows,
        $key_id,
        array $where_clause_array,
        $local_query,
        array $result
    ) {
        $has_unique_condition = false;

        // No row returned
        if (! $rows[$key_id]) {
            unset($rows[$key_id], $where_clause_array[$key_id]);
            Response::getInstance()->addHtml(
                Util::getMessage(
                    __('MySQL returned an empty result set (i.e. zero rows).'),
                    $local_query
                )
            );
            /**
             * @todo not sure what should be done at this point, but we must not
             * exit if we want the message to be displayed
             */
        } else {// end if (no row returned)
            $meta = $this->dbi->getFieldsMeta($result[$key_id]);

            list($unique_condition, $tmp_clause_is_unique)
                = Util::getUniqueCondition(
                    $result[$key_id], // handle
                    count($meta), // fields_cnt
                    $meta, // fields_meta
                    $rows[$key_id], // row
                    true, // force_unique
                    false, // restrict_to_table
                    null // analyzed_sql_results
                );

            if (! empty($unique_condition)) {
                $has_unique_condition = true;
            }
            unset($unique_condition, $tmp_clause_is_unique);
        }
        return $has_unique_condition;
    }

    /**
     * No primary key given, just load first row
     *
     * @param string $table name of the table
     * @param string $db    name of the database
     *
     * @return array                containing $result and $rows arrays
     */
    private function loadFirstRow($table, $db)
    {
        $result = $this->dbi->query(
            'SELECT * FROM ' . Util::backquote($db)
            . '.' . Util::backquote($table) . ' LIMIT 1;',
            DatabaseInterface::CONNECT_USER,
            DatabaseInterface::QUERY_STORE
        );
        $rows = array_fill(0, $GLOBALS['cfg']['InsertRows'], false);
        return array($result, $rows);
    }

    /**
     * Add some url parameters
     *
     * @param array $url_params         containing $db and $table as url parameters
     * @param array $where_clause_array where clauses array
     *
     * @return array Add some url parameters to $url_params array and return it
     */
    public function urlParamsInEditMode(
        array $url_params,
        array $where_clause_array
    ) {
        if (isset($where_clause_array)) {
            foreach ($where_clause_array as $where_clause) {
                $url_params['where_clause'] = trim($where_clause);
            }
        }
        if (! empty($_POST['sql_query'])) {
            $url_params['sql_query'] = $_POST['sql_query'];
        }
        return $url_params;
    }

    /**
     * Show type information or function selectors in Insert/Edit
     *
     * @param string  $which      function|type
     * @param array   $url_params containing url parameters
     * @param boolean $is_show    whether to show the element in $which
     *
     * @return string an HTML snippet
     */
    public function showTypeOrFunction($which, array $url_params, $is_show)
    {
        $params = array();

        switch ($which) {
            case 'function':
                $params['ShowFunctionFields'] = ($is_show ? 0 : 1);
                $params['ShowFieldTypesInDataEditView']
                = $GLOBALS['cfg']['ShowFieldTypesInDataEditView'];
                break;
            case 'type':
                $params['ShowFieldTypesInDataEditView'] = ($is_show ? 0 : 1);
                $params['ShowFunctionFields']
                = $GLOBALS['cfg']['ShowFunctionFields'];
                break;
        }

        $params['goto'] = 'sql.php';
        $this_url_params = array_merge($url_params, $params);

        if (! $is_show) {
            return ' : <a href="tbl_change.php" data-post="'
                . Url::getCommon($this_url_params, '') . '">'
                . $this->showTypeOrFunctionLabel($which)
                . '</a>';
        }
        return '<th><a href="tbl_change.php" data-post="'
            . Url::getCommon($this_url_params, '')
            . '" title="' . __('Hide') . '">'
            . $this->showTypeOrFunctionLabel($which)
            . '</a></th>';
    }

    /**
     * Show type information or function selectors labels in Insert/Edit
     *
     * @param string $which function|type
     *
     * @return string an HTML snippet
     */
    private function showTypeOrFunctionLabel($which)
    {
        switch ($which) {
            case 'function':
                return __('Function');
            case 'type':
                return __('Type');
        }

        return null;
    }

     /**
      * Analyze the table column array
      *
      * @param array   $column         description of column in given table
      * @param array   $comments_map   comments for every column that has a comment
      * @param boolean $timestamp_seen whether a timestamp has been seen
      *
      * @return array                   description of column in given table
      */
    private function analyzeTableColumnsArray(
        array $column,
        array $comments_map,
        $timestamp_seen
    ) {
        $column['Field_html']    = htmlspecialchars($column['Field']);
        $column['Field_md5']     = md5($column['Field']);
        // True_Type contains only the type (stops at first bracket)
        $column['True_Type']     = preg_replace('@\(.*@s', '', $column['Type']);
        $column['len'] = preg_match('@float|double@', $column['Type']) ? 100 : -1;
        $column['Field_title']   = $this->getColumnTitle($column, $comments_map);
        $column['is_binary']     = $this->isColumn(
            $column,
            array('binary', 'varbinary')
        );
        $column['is_blob']       = $this->isColumn(
            $column,
            array('blob', 'tinyblob', 'mediumblob', 'longblob')
        );
        $column['is_char']       = $this->isColumn(
            $column,
            array('char', 'varchar')
        );

        list($column['pma_type'], $column['wrap'], $column['first_timestamp'])
            = $this->getEnumSetAndTimestampColumns($column, $timestamp_seen);

        return $column;
    }

     /**
      * Retrieve the column title
      *
      * @param array $column       description of column in given table
      * @param array $comments_map comments for every column that has a comment
      *
      * @return string              column title
      */
    private function getColumnTitle(array $column, array $comments_map)
    {
        if (isset($comments_map[$column['Field']])) {
            return '<span style="border-bottom: 1px dashed black;" title="'
                . htmlspecialchars($comments_map[$column['Field']]) . '">'
                . $column['Field_html'] . '</span>';
        }

        return $column['Field_html'];
    }

     /**
      * check whether the column is of a certain type
      * the goal is to ensure that types such as "enum('one','two','binary',..)"
      * or "enum('one','two','varbinary',..)" are not categorized as binary
      *
      * @param array $column description of column in given table
      * @param array $types  the types to verify
      *
      * @return boolean whether the column's type if one of the $types
      */
    public function isColumn(array $column, array $types)
    {
        foreach ($types as $one_type) {
            if (mb_stripos($column['Type'], $one_type) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retrieve set, enum, timestamp table columns
     *
     * @param array   $column         description of column in given table
     * @param boolean $timestamp_seen whether a timestamp has been seen
     *
     * @return array $column['pma_type'], $column['wrap'], $column['first_timestamp']
     */
    private function getEnumSetAndTimestampColumns(array $column, $timestamp_seen)
    {
        $column['first_timestamp'] = false;
        switch ($column['True_Type']) {
            case 'set':
                $column['pma_type'] = 'set';
                $column['wrap']  = '';
                break;
            case 'enum':
                $column['pma_type'] = 'enum';
                $column['wrap']  = '';
                break;
            case 'timestamp':
                if (! $timestamp_seen) {   // can only occur once per table
                    $column['first_timestamp'] = true;
                }
                $column['pma_type'] = $column['Type'];
                $column['wrap']  = ' nowrap';
                break;

            default:
                $column['pma_type'] = $column['Type'];
                $column['wrap']  = ' nowrap';
                break;
        }
        return array($column['pma_type'], $column['wrap'], $column['first_timestamp']);
    }

    /**
     * The function column
     * We don't want binary data to be destroyed
     * Note: from the MySQL manual: "BINARY doesn't affect how the column is
     *       stored or retrieved" so it does not mean that the contents is binary
     *
     * @param array   $column                description of column in given table
     * @param boolean $is_upload             upload or no
     * @param string  $column_name_appendix  the name attribute
     * @param string  $onChangeClause        onchange clause for fields
     * @param array   $no_support_types      list of datatypes that are not (yet)
     *                                       handled by PMA
     * @param integer $tabindex_for_function +3000
     * @param integer $tabindex              tab index
     * @param integer $idindex               id index
     * @param boolean $insert_mode           insert mode or edit mode
     * @param boolean $readOnly              is column read only or not
     * @param array   $foreignData           foreign key data
     *
     * @return string                           an html snippet
     */
    private function getFunctionColumn(
        array $column,
        $is_upload,
        $column_name_appendix,
        $onChangeClause,
        array $no_support_types,
        $tabindex_for_function,
        $tabindex,
        $idindex,
        $insert_mode,
        $readOnly,
        array $foreignData
    ) {
        $html_output = '';
        if (($GLOBALS['cfg']['ProtectBinary'] === 'blob'
            && $column['is_blob'] && !$is_upload)
            || ($GLOBALS['cfg']['ProtectBinary'] === 'all'
            && $column['is_binary'])
            || ($GLOBALS['cfg']['ProtectBinary'] === 'noblob'
            && $column['is_binary'])
        ) {
            $html_output .= '<td class="center">' . __('Binary') . '</td>' . "\n";
        } elseif ($readOnly
            || mb_strstr($column['True_Type'], 'enum')
            || mb_strstr($column['True_Type'], 'set')
            || in_array($column['pma_type'], $no_support_types)
        ) {
            $html_output .= '<td class="center">--</td>' . "\n";
        } else {
            $html_output .= '<td>' . "\n";

            $html_output .= '<select name="funcs' . $column_name_appendix . '"'
                . ' ' . $onChangeClause
                . ' tabindex="' . ($tabindex + $tabindex_for_function) . '"'
                . ' id="field_' . $idindex . '_1">';
            $html_output .= Util::getFunctionsForField(
                $column,
                $insert_mode,
                $foreignData
            ) . "\n";

            $html_output .= '</select>' .  "\n";
            $html_output .= '</td>' .  "\n";
        }
        return $html_output;
    }

    /**
     * The null column
     *
     * @param array   $column               description of column in given table
     * @param string  $column_name_appendix the name attribute
     * @param boolean $real_null_value      is column value null or not null
     * @param integer $tabindex             tab index
     * @param integer $tabindex_for_null    +6000
     * @param integer $idindex              id index
     * @param string  $vkey                 [multi_edit]['row_id']
     * @param array   $foreigners           keys into foreign fields
     * @param array   $foreignData          data about the foreign keys
     * @param boolean $readOnly             is column read only or not
     *
     * @return string                       an html snippet
     */
    private function getNullColumn(
        array $column,
        $column_name_appendix,
        $real_null_value,
        $tabindex,
        $tabindex_for_null,
        $idindex,
        $vkey,
        array $foreigners,
        array $foreignData,
        $readOnly
    ) {
        if ($column['Null'] != 'YES' || $readOnly) {
            return "<td></td>\n";
        }
        $html_output = '';
        $html_output .= '<td>' . "\n";
        $html_output .= '<input type="hidden" name="fields_null_prev'
            . $column_name_appendix . '"';
        if ($real_null_value && !$column['first_timestamp']) {
            $html_output .= ' value="on"';
        }
        $html_output .= ' />' . "\n";

        $html_output .= '<input type="checkbox" class="checkbox_null" tabindex="'
            . ($tabindex + $tabindex_for_null) . '"'
            . ' name="fields_null' . $column_name_appendix . '"';
        if ($real_null_value) {
            $html_output .= ' checked="checked"';
        }
        $html_output .= ' id="field_' . ($idindex) . '_2" />';

        // nullify_code is needed by the js nullify() function
        $nullify_code = $this->getNullifyCodeForNullColumn(
            $column,
            $foreigners,
            $foreignData
        );
        // to be able to generate calls to nullify() in jQuery
        $html_output .= '<input type="hidden" class="nullify_code" name="nullify_code'
            . $column_name_appendix . '" value="' . $nullify_code . '" />';
        $html_output .= '<input type="hidden" class="hashed_field" name="hashed_field'
            . $column_name_appendix . '" value="' .  $column['Field_md5'] . '" />';
        $html_output .= '<input type="hidden" class="multi_edit" name="multi_edit'
            . $column_name_appendix . '" value="' . Sanitize::escapeJsString($vkey) . '" />';
        $html_output .= '</td>' . "\n";

        return $html_output;
    }

    /**
     * Retrieve the nullify code for the null column
     *
     * @param array $column      description of column in given table
     * @param array $foreigners  keys into foreign fields
     * @param array $foreignData data about the foreign keys
     *
     * @return integer              $nullify_code
     */
    private function getNullifyCodeForNullColumn(
        array $column,
        array $foreigners,
        array $foreignData
    ) {
        $foreigner = $this->relation->searchColumnInForeigners($foreigners, $column['Field']);
        if (mb_strstr($column['True_Type'], 'enum')) {
            if (mb_strlen($column['Type']) > 20) {
                $nullify_code = '1';
            } else {
                $nullify_code = '2';
            }
        } elseif (mb_strstr($column['True_Type'], 'set')) {
            $nullify_code = '3';
        } elseif (!empty($foreigners)
            && !empty($foreigner)
            && $foreignData['foreign_link'] == false
        ) {
            // foreign key in a drop-down
            $nullify_code = '4';
        } elseif (!empty($foreigners)
            && !empty($foreigner)
            && $foreignData['foreign_link'] == true
        ) {
            // foreign key with a browsing icon
            $nullify_code = '6';
        } else {
            $nullify_code = '5';
        }
        return $nullify_code;
    }

    /**
     * Get the HTML elements for value column in insert form
     * (here, "column" is used in the sense of HTML column in HTML table)
     *
     * @param array   $column                description of column in given table
     * @param string  $backup_field          hidden input field
     * @param string  $column_name_appendix  the name attribute
     * @param string  $onChangeClause        onchange clause for fields
     * @param integer $tabindex              tab index
     * @param integer $tabindex_for_value    offset for the values tabindex
     * @param integer $idindex               id index
     * @param string  $data                  description of the column field
     * @param string  $special_chars         special characters
     * @param array   $foreignData           data about the foreign keys
     * @param array   $paramTableDbArray     array containing $table and $db
     * @param integer $rownumber             the row number
     * @param array   $titles                An HTML IMG tag for a particular icon from
     *                                       a theme, which may be an actual file or
     *                                       an icon from a sprite
     * @param string  $text_dir              text direction
     * @param string  $special_chars_encoded replaced char if the string starts
     *                                       with a \r\n pair (0x0d0a) add an extra \n
     * @param string  $vkey                  [multi_edit]['row_id']
     * @param boolean $is_upload             is upload or not
     * @param integer $biggest_max_file_size 0 integer
     * @param string  $default_char_editing  default char editing mode which is stored
     *                                       in the config.inc.php script
     * @param array   $no_support_types      list of datatypes that are not (yet)
     *                                       handled by PMA
     * @param array   $gis_data_types        list of GIS data types
     * @param array   $extracted_columnspec  associative array containing type,
     *                                       spec_in_brackets and possibly
     *                                       enum_set_values (another array)
     * @param boolean $readOnly              is column read only or not
     *
     * @return string an html snippet
     */
    private function getValueColumn(
        array $column,
        $backup_field,
        $column_name_appendix,
        $onChangeClause,
        $tabindex,
        $tabindex_for_value,
        $idindex,
        $data,
        $special_chars,
        array $foreignData,
        array $paramTableDbArray,
        $rownumber,
        array $titles,
        $text_dir,
        $special_chars_encoded,
        $vkey,
        $is_upload,
        $biggest_max_file_size,
        $default_char_editing,
        array $no_support_types,
        array $gis_data_types,
        array $extracted_columnspec,
        $readOnly
    ) {
        // HTML5 data-* attribute data-type
        $data_type = $this->dbi->types->getTypeClass($column['True_Type']);
        $html_output = '';

        if ($foreignData['foreign_link'] == true) {
            $html_output .= $this->getForeignLink(
                $column,
                $backup_field,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $data,
                $paramTableDbArray,
                $rownumber,
                $titles,
                $readOnly
            );
        } elseif (is_array($foreignData['disp_row'])) {
            $html_output .= $this->dispRowForeignData(
                $backup_field,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $data,
                $foreignData,
                $readOnly
            );
        } elseif ($GLOBALS['cfg']['LongtextDoubleTextarea']
            && mb_strstr($column['pma_type'], 'longtext')
        ) {
            $html_output .= $this->getTextarea(
                $column,
                $backup_field,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $text_dir,
                $special_chars_encoded,
                $data_type,
                $readOnly
            );
        } elseif (mb_strstr($column['pma_type'], 'text')) {
            $html_output .= $this->getTextarea(
                $column,
                $backup_field,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $text_dir,
                $special_chars_encoded,
                $data_type,
                $readOnly
            );
            $html_output .= "\n";
            if (mb_strlen($special_chars) > 32000) {
                $html_output .= "</td>\n";
                $html_output .= '<td>' . __(
                    'Because of its length,<br /> this column might not be editable.'
                );
            }
        } elseif ($column['pma_type'] == 'enum') {
            $html_output .= $this->getPmaTypeEnum(
                $column,
                $backup_field,
                $column_name_appendix,
                $extracted_columnspec,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $data,
                $readOnly
            );
        } elseif ($column['pma_type'] == 'set') {
            $html_output .= $this->getPmaTypeSet(
                $column,
                $extracted_columnspec,
                $backup_field,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $data,
                $readOnly
            );
        } elseif ($column['is_binary'] || $column['is_blob']) {
            $html_output .= $this->getBinaryAndBlobColumn(
                $column,
                $data,
                $special_chars,
                $biggest_max_file_size,
                $backup_field,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $text_dir,
                $special_chars_encoded,
                $vkey,
                $is_upload,
                $readOnly
            );
        } elseif (! in_array($column['pma_type'], $no_support_types)) {
            $html_output .= $this->getValueColumnForOtherDatatypes(
                $column,
                $default_char_editing,
                $backup_field,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $special_chars,
                $tabindex_for_value,
                $idindex,
                $text_dir,
                $special_chars_encoded,
                $data,
                $extracted_columnspec,
                $readOnly
            );
        }

        if (in_array($column['pma_type'], $gis_data_types)) {
            $html_output .= $this->getHtmlForGisDataTypes();
        }

        return $html_output;
    }

    /**
     * Get HTML for foreign link in insert form
     *
     * @param array   $column               description of column in given table
     * @param string  $backup_field         hidden input field
     * @param string  $column_name_appendix the name attribute
     * @param string  $onChangeClause       onchange clause for fields
     * @param integer $tabindex             tab index
     * @param integer $tabindex_for_value   offset for the values tabindex
     * @param integer $idindex              id index
     * @param string  $data                 data to edit
     * @param array   $paramTableDbArray    array containing $table and $db
     * @param integer $rownumber            the row number
     * @param array   $titles               An HTML IMG tag for a particular icon from
     *                                      a theme, which may be an actual file or
     *                                      an icon from a sprite
     * @param boolean $readOnly             is column read only or not
     *
     * @return string                       an html snippet
     */
    private function getForeignLink(
        array $column,
        $backup_field,
        $column_name_appendix,
        $onChangeClause,
        $tabindex,
        $tabindex_for_value,
        $idindex,
        $data,
        array $paramTableDbArray,
        $rownumber,
        array $titles,
        $readOnly
    ) {
        list($table, $db) = $paramTableDbArray;
        $html_output = '';
        $html_output .= $backup_field . "\n";

        $html_output .= '<input type="hidden" name="fields_type'
            . $column_name_appendix . '" value="foreign" />';

        $html_output .= '<input type="text" name="fields' . $column_name_appendix . '" '
            . 'class="textfield" '
            . $onChangeClause . ' '
            . ($readOnly ? 'readonly="readonly" ' : '')
            . 'tabindex="' . ($tabindex + $tabindex_for_value) . '" '
            . 'id="field_' . ($idindex) . '_3" '
            . 'value="' . htmlspecialchars($data) . '" />';

        $html_output .= '<a class="ajax browse_foreign" href="browse_foreigners.php" data-post="'
            . Url::getCommon(
                array(
                    'db' => $db,
                    'table' => $table,
                    'field' => $column['Field'],
                    'rownumber' => $rownumber,
                    'data'      => $data
                ),
                ''
            ) . '">'
            . str_replace("'", "\'", $titles['Browse']) . '</a>';
        return $html_output;
    }

    /**
     * Get HTML to display foreign data
     *
     * @param string  $backup_field         hidden input field
     * @param string  $column_name_appendix the name attribute
     * @param string  $onChangeClause       onchange clause for fields
     * @param integer $tabindex             tab index
     * @param integer $tabindex_for_value   offset for the values tabindex
     * @param integer $idindex              id index
     * @param string  $data                 data to edit
     * @param array   $foreignData          data about the foreign keys
     * @param boolean $readOnly             is display read only or not
     *
     * @return string                       an html snippet
     */
    private function dispRowForeignData(
        $backup_field,
        $column_name_appendix,
        $onChangeClause,
        $tabindex,
        $tabindex_for_value,
        $idindex,
        $data,
        array $foreignData,
        $readOnly
    ) {
        $html_output = '';
        $html_output .= $backup_field . "\n";
        $html_output .= '<input type="hidden"'
            . ' name="fields_type' . $column_name_appendix . '"'
            . ' value="foreign" />';

        $html_output .= '<select name="fields' . $column_name_appendix . '"'
            . ' ' . $onChangeClause
            . ' class="textfield"'
            . ($readOnly ? ' disabled' : '')
            . ' tabindex="' . ($tabindex + $tabindex_for_value) . '"'
            . ' id="field_' . $idindex . '_3">';
        $html_output .= $this->relation->foreignDropdown(
            $foreignData['disp_row'],
            $foreignData['foreign_field'],
            $foreignData['foreign_display'],
            $data,
            $GLOBALS['cfg']['ForeignKeyMaxLimit']
        );
        $html_output .= '</select>';

        //Add hidden input, as disabled <select> input does not included in POST.
        if ($readOnly) {
            $html_output .= '<input name="fields' . $column_name_appendix . '"'
                . ' type="hidden" value="' . htmlspecialchars($data) . '">';
        }

        return $html_output;
    }

    /**
     * Get HTML textarea for insert form
     *
     * @param array   $column                column information
     * @param string  $backup_field          hidden input field
     * @param string  $column_name_appendix  the name attribute
     * @param string  $onChangeClause        onchange clause for fields
     * @param integer $tabindex              tab index
     * @param integer $tabindex_for_value    offset for the values tabindex
     * @param integer $idindex               id index
     * @param string  $text_dir              text direction
     * @param string  $special_chars_encoded replaced char if the string starts
     *                                       with a \r\n pair (0x0d0a) add an extra \n
     * @param string  $data_type             the html5 data-* attribute type
     * @param boolean $readOnly              is column read only or not
     *
     * @return string                       an html snippet
     */
    private function getTextarea(
        array $column,
        $backup_field,
        $column_name_appendix,
        $onChangeClause,
        $tabindex,
        $tabindex_for_value,
        $idindex,
        $text_dir,
        $special_chars_encoded,
        $data_type,
        $readOnly
    ) {
        $the_class = '';
        $textAreaRows = $GLOBALS['cfg']['TextareaRows'];
        $textareaCols = $GLOBALS['cfg']['TextareaCols'];

        if ($column['is_char']) {
            /**
             * @todo clarify the meaning of the "textfield" class and explain
             *       why character columns have the "char" class instead
             */
            $the_class = 'char';
            $textAreaRows = $GLOBALS['cfg']['CharTextareaRows'];
            $textareaCols = $GLOBALS['cfg']['CharTextareaCols'];
            $extracted_columnspec = Util::extractColumnSpec(
                $column['Type']
            );
            $maxlength = $extracted_columnspec['spec_in_brackets'];
        } elseif ($GLOBALS['cfg']['LongtextDoubleTextarea']
            && mb_strstr($column['pma_type'], 'longtext')
        ) {
            $textAreaRows = $GLOBALS['cfg']['TextareaRows'] * 2;
            $textareaCols = $GLOBALS['cfg']['TextareaCols'] * 2;
        }
        $html_output = $backup_field . "\n"
            . '<textarea name="fields' . $column_name_appendix . '"'
            . ' class="' . $the_class . '"'
            . ($readOnly ? ' readonly="readonly"' : '')
            . (isset($maxlength) ? ' data-maxlength="' . $maxlength . '"' : '')
            . ' rows="' . $textAreaRows . '"'
            . ' cols="' . $textareaCols . '"'
            . ' dir="' . $text_dir . '"'
            . ' id="field_' . ($idindex) . '_3"'
            . (! empty($onChangeClause) ? ' ' . $onChangeClause : '')
            . ' tabindex="' . ($tabindex + $tabindex_for_value) . '"'
            . ' data-type="' . $data_type . '">'
            . $special_chars_encoded
            . '</textarea>';

        return $html_output;
    }

    /**
     * Get HTML for enum type
     *
     * @param array   $column               description of column in given table
     * @param string  $backup_field         hidden input field
     * @param string  $column_name_appendix the name attribute
     * @param array   $extracted_columnspec associative array containing type,
     *                                      spec_in_brackets and possibly
     *                                      enum_set_values (another array)
     * @param string  $onChangeClause       onchange clause for fields
     * @param integer $tabindex             tab index
     * @param integer $tabindex_for_value   offset for the values tabindex
     * @param integer $idindex              id index
     * @param mixed   $data                 data to edit
     * @param boolean $readOnly             is column read only or not
     *
     * @return string an html snippet
     */
    private function getPmaTypeEnum(
        array $column,
        $backup_field,
        $column_name_appendix,
        array $extracted_columnspec,
        $onChangeClause,
        $tabindex,
        $tabindex_for_value,
        $idindex,
        $data,
        $readOnly
    ) {
        $html_output = '';
        if (! isset($column['values'])) {
            $column['values'] = $this->getColumnEnumValues(
                $column,
                $extracted_columnspec
            );
        }
        $column_enum_values = $column['values'];
        $html_output .= '<input type="hidden" name="fields_type'
            . $column_name_appendix . '" value="enum" />';
        $html_output .= "\n" . '            ' . $backup_field . "\n";
        if (mb_strlen($column['Type']) > 20) {
            $html_output .= $this->getDropDownDependingOnLength(
                $column,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $data,
                $column_enum_values,
                $readOnly
            );
        } else {
            $html_output .= $this->getRadioButtonDependingOnLength(
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $column,
                $tabindex_for_value,
                $idindex,
                $data,
                $column_enum_values,
                $readOnly
            );
        }
        return $html_output;
    }

    /**
     * Get column values
     *
     * @param array $column               description of column in given table
     * @param array $extracted_columnspec associative array containing type,
     *                                    spec_in_brackets and possibly enum_set_values
     *                                    (another array)
     *
     * @return array column values as an associative array
     */
    private function getColumnEnumValues(array $column, array $extracted_columnspec)
    {
        $column['values'] = array();
        foreach ($extracted_columnspec['enum_set_values'] as $val) {
            $column['values'][] = array(
                'plain' => $val,
                'html'  => htmlspecialchars($val),
            );
        }
        return $column['values'];
    }

    /**
     * Get HTML drop down for more than 20 string length
     *
     * @param array   $column               description of column in given table
     * @param string  $column_name_appendix the name attribute
     * @param string  $onChangeClause       onchange clause for fields
     * @param integer $tabindex             tab index
     * @param integer $tabindex_for_value   offset for the values tabindex
     * @param integer $idindex              id index
     * @param string  $data                 data to edit
     * @param array   $column_enum_values   $column['values']
     * @param boolean $readOnly             is column read only or not
     *
     * @return string                       an html snippet
     */
    private function getDropDownDependingOnLength(
        array $column,
        $column_name_appendix,
        $onChangeClause,
        $tabindex,
        $tabindex_for_value,
        $idindex,
        $data,
        array $column_enum_values,
        $readOnly
    ) {
        $html_output = '<select name="fields' . $column_name_appendix . '"'
            . ' ' . $onChangeClause
            . ' class="textfield"'
            . ' tabindex="' . ($tabindex + $tabindex_for_value) . '"'
            . ($readOnly ? ' disabled' : '')
            . ' id="field_' . ($idindex) . '_3">';
        $html_output .= '<option value="">&nbsp;</option>' . "\n";

        $selected_html = '';
        foreach ($column_enum_values as $enum_value) {
            $html_output .= '<option value="' . $enum_value['html'] . '"';
            if ($data == $enum_value['plain']
                || ($data == ''
                && (! isset($_POST['where_clause']) || $column['Null'] != 'YES')
                && isset($column['Default'])
                && $enum_value['plain'] == $column['Default'])
            ) {
                $html_output .= ' selected="selected"';
                $selected_html = $enum_value['html'];
            }
            $html_output .= '>' . $enum_value['html'] . '</option>' . "\n";
        }
        $html_output .= '</select>';

        //Add hidden input, as disabled <select> input does not included in POST.
        if ($readOnly) {
            $html_output .= '<input name="fields' . $column_name_appendix . '"'
                . ' type="hidden" value="' . $selected_html . '">';
        }
        return $html_output;
    }

    /**
     * Get HTML radio button for less than 20 string length
     *
     * @param string  $column_name_appendix the name attribute
     * @param string  $onChangeClause       onchange clause for fields
     * @param integer $tabindex             tab index
     * @param array   $column               description of column in given table
     * @param integer $tabindex_for_value   offset for the values tabindex
     * @param integer $idindex              id index
     * @param string  $data                 data to edit
     * @param array   $column_enum_values   $column['values']
     * @param boolean $readOnly             is column read only or not
     *
     * @return string                       an html snippet
     */
    private function getRadioButtonDependingOnLength(
        $column_name_appendix,
        $onChangeClause,
        $tabindex,
        array $column,
        $tabindex_for_value,
        $idindex,
        $data,
        array $column_enum_values,
        $readOnly
    ) {
        $j = 0;
        $html_output = '';
        foreach ($column_enum_values as $enum_value) {
            $html_output .= '            '
                . '<input type="radio" name="fields' . $column_name_appendix . '"'
                . ' class="textfield"'
                . ' value="' . $enum_value['html'] . '"'
                . ' id="field_' . ($idindex) . '_3_'  . $j . '"'
                . ' ' . $onChangeClause;
            if ($data == $enum_value['plain']
                || ($data == ''
                && (! isset($_POST['where_clause']) || $column['Null'] != 'YES')
                && isset($column['Default'])
                && $enum_value['plain'] == $column['Default'])
            ) {
                $html_output .= ' checked="checked"';
            } elseif ($readOnly) {
                $html_output .= ' disabled';
            }
            $html_output .= ' tabindex="' . ($tabindex + $tabindex_for_value) . '" />';
            $html_output .= '<label for="field_' . $idindex . '_3_' . $j . '">'
                . $enum_value['html'] . '</label>' . "\n";
            $j++;
        }
        return $html_output;
    }

    /**
     * Get the HTML for 'set' pma type
     *
     * @param array   $column               description of column in given table
     * @param array   $extracted_columnspec associative array containing type,
     *                                      spec_in_brackets and possibly
     *                                      enum_set_values (another array)
     * @param string  $backup_field         hidden input field
     * @param string  $column_name_appendix the name attribute
     * @param string  $onChangeClause       onchange clause for fields
     * @param integer $tabindex             tab index
     * @param integer $tabindex_for_value   offset for the values tabindex
     * @param integer $idindex              id index
     * @param string  $data                 description of the column field
     * @param boolean $readOnly             is column read only or not
     *
     * @return string                       an html snippet
     */
    private function getPmaTypeSet(
        array $column,
        array $extracted_columnspec,
        $backup_field,
        $column_name_appendix,
        $onChangeClause,
        $tabindex,
        $tabindex_for_value,
        $idindex,
        $data,
        $readOnly
    ) {
        list($column_set_values, $select_size) = $this->getColumnSetValueAndSelectSize(
            $column,
            $extracted_columnspec
        );
        $vset = array_flip(explode(',', $data));
        $html_output = $backup_field . "\n";
        $html_output .= '<input type="hidden" name="fields_type'
            . $column_name_appendix . '" value="set" />';
        $html_output .= '<select name="fields' . $column_name_appendix . '[]' . '"'
            . ' class="textfield"'
            . ($readOnly ? ' disabled' : '')
            . ' size="' . $select_size . '"'
            . ' multiple="multiple"'
            . ' ' . $onChangeClause
            . ' tabindex="' . ($tabindex + $tabindex_for_value) . '"'
            . ' id="field_' . ($idindex) . '_3">';

        $selected_html = '';
        foreach ($column_set_values as $column_set_value) {
            $html_output .= '<option value="' . $column_set_value['html'] . '"';
            if (isset($vset[$column_set_value['plain']])) {
                $html_output .= ' selected="selected"';
                $selected_html = $column_set_value['html'];
            }
            $html_output .= '>' . $column_set_value['html'] . '</option>' . "\n";
        }
        $html_output .= '</select>';

        //Add hidden input, as disabled <select> input does not included in POST.
        if ($readOnly) {
            $html_output .= '<input name="fields' . $column_name_appendix . '[]' . '"'
                . ' type="hidden" value="' . $selected_html . '">';
        }
        return $html_output;
    }

    /**
     * Retrieve column 'set' value and select size
     *
     * @param array $column               description of column in given table
     * @param array $extracted_columnspec associative array containing type,
     *                                    spec_in_brackets and possibly enum_set_values
     *                                    (another array)
     *
     * @return array $column['values'], $column['select_size']
     */
    private function getColumnSetValueAndSelectSize(
        array $column,
        array $extracted_columnspec
    ) {
        if (! isset($column['values'])) {
            $column['values'] = array();
            foreach ($extracted_columnspec['enum_set_values'] as $val) {
                $column['values'][] = array(
                    'plain' => $val,
                    'html'  => htmlspecialchars($val),
                );
            }
            $column['select_size'] = min(4, count($column['values']));
        }
        return array($column['values'], $column['select_size']);
    }

    /**
     * Get HTML for binary and blob column
     *
     * @param array   $column                description of column in given table
     * @param string  $data                  data to edit
     * @param string  $special_chars         special characters
     * @param integer $biggest_max_file_size biggest max file size for uploading
     * @param string  $backup_field          hidden input field
     * @param string  $column_name_appendix  the name attribute
     * @param string  $onChangeClause        onchange clause for fields
     * @param integer $tabindex              tab index
     * @param integer $tabindex_for_value    offset for the values tabindex
     * @param integer $idindex               id index
     * @param string  $text_dir              text direction
     * @param string  $special_chars_encoded replaced char if the string starts
     *                                       with a \r\n pair (0x0d0a) add an extra \n
     * @param string  $vkey                  [multi_edit]['row_id']
     * @param boolean $is_upload             is upload or not
     * @param boolean $readOnly              is column read only or not
     *
     * @return string                           an html snippet
     */
    private function getBinaryAndBlobColumn(
        array $column,
        $data,
        $special_chars,
        $biggest_max_file_size,
        $backup_field,
        $column_name_appendix,
        $onChangeClause,
        $tabindex,
        $tabindex_for_value,
        $idindex,
        $text_dir,
        $special_chars_encoded,
        $vkey,
        $is_upload,
        $readOnly
    ) {
        $html_output = '';
        // Add field type : Protected or Hexadecimal
        $fields_type_html = '<input type="hidden" name="fields_type'
            . $column_name_appendix . '" value="%s" />';
        // Default value : hex
        $fields_type_val = 'hex';
        if (($GLOBALS['cfg']['ProtectBinary'] === 'blob' && $column['is_blob'])
            || ($GLOBALS['cfg']['ProtectBinary'] === 'all')
            || ($GLOBALS['cfg']['ProtectBinary'] === 'noblob' && !$column['is_blob'])
        ) {
            $html_output .= __('Binary - do not edit');
            if (isset($data)) {
                $data_size = Util::formatByteDown(
                    mb_strlen(stripslashes($data)),
                    3,
                    1
                );
                $html_output .= ' (' . $data_size[0] . ' ' . $data_size[1] . ')';
                unset($data_size);
            }
            $fields_type_val = 'protected';
            $html_output .= '<input type="hidden" name="fields'
                . $column_name_appendix . '" value="" />';
        } elseif ($column['is_blob']
            || ($column['len'] > $GLOBALS['cfg']['LimitChars'])
        ) {
            $html_output .= "\n" . $this->getTextarea(
                $column,
                $backup_field,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $text_dir,
                $special_chars_encoded,
                'HEX',
                $readOnly
            );
        } else {
            // field size should be at least 4 and max $GLOBALS['cfg']['LimitChars']
            $fieldsize = min(max($column['len'], 4), $GLOBALS['cfg']['LimitChars']);
            $html_output .= "\n" . $backup_field . "\n" . $this->getHtmlInput(
                $column,
                $column_name_appendix,
                $special_chars,
                $fieldsize,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                'HEX',
                $readOnly
            );
        }
        $html_output .= sprintf($fields_type_html, $fields_type_val);

        if ($is_upload && $column['is_blob'] && !$readOnly) {
            // We don't want to prevent users from using
            // browser's default drag-drop feature on some page(s),
            // so we add noDragDrop class to the input
            $html_output .= '<br />'
                . '<input type="file"'
                . ' name="fields_upload' . $vkey . '[' . $column['Field_md5'] . ']"'
                . ' class="textfield noDragDrop" id="field_' . $idindex . '_3" size="10"'
                . ' ' . $onChangeClause . '/>&nbsp;';
            list($html_out,) = $this->getMaxUploadSize(
                $column,
                $biggest_max_file_size
            );
            $html_output .= $html_out;
        }

        if (!empty($GLOBALS['cfg']['UploadDir']) && !$readOnly) {
            $html_output .= $this->getSelectOptionForUpload($vkey, $column);
        }

        return $html_output;
    }

    /**
     * Get HTML input type
     *
     * @param array   $column               description of column in given table
     * @param string  $column_name_appendix the name attribute
     * @param string  $special_chars        special characters
     * @param integer $fieldsize            html field size
     * @param string  $onChangeClause       onchange clause for fields
     * @param integer $tabindex             tab index
     * @param integer $tabindex_for_value   offset for the values tabindex
     * @param integer $idindex              id index
     * @param string  $data_type            the html5 data-* attribute type
     * @param boolean $readOnly             is column read only or not
     *
     * @return string                       an html snippet
     */
    private function getHtmlInput(
        array $column,
        $column_name_appendix,
        $special_chars,
        $fieldsize,
        $onChangeClause,
        $tabindex,
        $tabindex_for_value,
        $idindex,
        $data_type,
        $readOnly
    ) {
        $input_type = 'text';
        // do not use the 'date' or 'time' types here; they have no effect on some
        // browsers and create side effects (see bug #4218)

        $the_class = 'textfield';
        // verify True_Type which does not contain the parentheses and length
        if ($readOnly) {
            //NOOP. Disable date/timepicker
        } elseif ($column['True_Type'] === 'date') {
            $the_class .= ' datefield';
        } elseif ($column['True_Type'] === 'time') {
            $the_class .= ' timefield';
        } elseif ($column['True_Type'] === 'datetime'
            || $column['True_Type'] === 'timestamp'
        ) {
            $the_class .= ' datetimefield';
        }
        $input_min_max = false;
        if (in_array($column['True_Type'], $this->dbi->types->getIntegerTypes())) {
            $extracted_columnspec = Util::extractColumnSpec(
                $column['Type']
            );
            $is_unsigned = $extracted_columnspec['unsigned'];
            $min_max_values = $this->dbi->types->getIntegerRange(
                $column['True_Type'],
                ! $is_unsigned
            );
            $input_min_max = 'min="' . $min_max_values[0] . '" '
                . 'max="' . $min_max_values[1] . '"';
            $data_type = 'INT';
        }
        return '<input type="' . $input_type . '"'
            . ' name="fields' . $column_name_appendix . '"'
            . ' value="' . $special_chars . '" size="' . $fieldsize . '"'
            . ((isset($column['is_char']) && $column['is_char'])
            ? ' data-maxlength="' . $fieldsize . '"'
            : '')
            . ($readOnly ? ' readonly="readonly"' : '')
            . ($input_min_max !== false ? ' ' . $input_min_max : '')
            . ' data-type="' . $data_type . '"'
            . ($input_type === 'time' ? ' step="1"' : '')
            . ' class="' . $the_class . '" ' . $onChangeClause
            . ' tabindex="' . ($tabindex + $tabindex_for_value) . '"'
            . ' id="field_' . ($idindex) . '_3" />';
    }

    /**
     * Get HTML select option for upload
     *
     * @param string $vkey   [multi_edit]['row_id']
     * @param array  $column description of column in given table
     *
     * @return string|void an html snippet
     */
    private function getSelectOptionForUpload($vkey, array $column)
    {
        $files = FileListing::getFileSelectOptions(
            Util::userDir($GLOBALS['cfg']['UploadDir'])
        );

        if ($files === false) {
            return '<span style="color:red">' . __('Error') . '</span><br />' . "\n"
                .  __('The directory you set for upload work cannot be reached.') . "\n";
        } elseif (!empty($files)) {
            return "<br />\n"
                . '<i>' . __('Or') . '</i>' . ' '
                . __('web server upload directory:') . '<br />' . "\n"
                . '<select size="1" name="fields_uploadlocal'
                . $vkey . '[' . $column['Field_md5'] . ']">' . "\n"
                . '<option value="" selected="selected"></option>' . "\n"
                . $files
                . '</select>' . "\n";
        }

        return null;
    }

    /**
     * Retrieve the maximum upload file size
     *
     * @param array   $column                description of column in given table
     * @param integer $biggest_max_file_size biggest max file size for uploading
     *
     * @return array an html snippet and $biggest_max_file_size
     */
    private function getMaxUploadSize(array $column, $biggest_max_file_size)
    {
        // find maximum upload size, based on field type
        /**
         * @todo with functions this is not so easy, as you can basically
         * process any data with function like MD5
         */
        global $max_upload_size;
        $max_field_sizes = array(
            'tinyblob'   =>        '256',
            'blob'       =>      '65536',
            'mediumblob' =>   '16777216',
            'longblob'   => '4294967296' // yeah, really
        );

        $this_field_max_size = $max_upload_size; // from PHP max
        if ($this_field_max_size > $max_field_sizes[$column['pma_type']]) {
            $this_field_max_size = $max_field_sizes[$column['pma_type']];
        }
        $html_output
            = Util::getFormattedMaximumUploadSize(
                $this_field_max_size
            ) . "\n";
        // do not generate here the MAX_FILE_SIZE, because we should
        // put only one in the form to accommodate the biggest field
        if ($this_field_max_size > $biggest_max_file_size) {
            $biggest_max_file_size = $this_field_max_size;
        }
        return array($html_output, $biggest_max_file_size);
    }

    /**
     * Get HTML for the Value column of other datatypes
     * (here, "column" is used in the sense of HTML column in HTML table)
     *
     * @param array   $column                description of column in given table
     * @param string  $default_char_editing  default char editing mode which is stored
     *                                       in the config.inc.php script
     * @param string  $backup_field          hidden input field
     * @param string  $column_name_appendix  the name attribute
     * @param string  $onChangeClause        onchange clause for fields
     * @param integer $tabindex              tab index
     * @param string  $special_chars         special characters
     * @param integer $tabindex_for_value    offset for the values tabindex
     * @param integer $idindex               id index
     * @param string  $text_dir              text direction
     * @param string  $special_chars_encoded replaced char if the string starts
     *                                       with a \r\n pair (0x0d0a) add an extra \n
     * @param string  $data                  data to edit
     * @param array   $extracted_columnspec  associative array containing type,
     *                                       spec_in_brackets and possibly
     *                                       enum_set_values (another array)
     * @param boolean $readOnly              is column read only or not
     *
     * @return string an html snippet
     */
    private function getValueColumnForOtherDatatypes(
        array $column,
        $default_char_editing,
        $backup_field,
        $column_name_appendix,
        $onChangeClause,
        $tabindex,
        $special_chars,
        $tabindex_for_value,
        $idindex,
        $text_dir,
        $special_chars_encoded,
        $data,
        array $extracted_columnspec,
        $readOnly
    ) {
        // HTML5 data-* attribute data-type
        $data_type = $this->dbi->types->getTypeClass($column['True_Type']);
        $fieldsize = $this->getColumnSize($column, $extracted_columnspec);
        $html_output = $backup_field . "\n";
        if ($column['is_char']
            && ($GLOBALS['cfg']['CharEditing'] == 'textarea'
            || mb_strpos($data, "\n") !== false)
        ) {
            $html_output .= "\n";
            $GLOBALS['cfg']['CharEditing'] = $default_char_editing;
            $html_output .= $this->getTextarea(
                $column,
                $backup_field,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $text_dir,
                $special_chars_encoded,
                $data_type,
                $readOnly
            );
        } else {
            $html_output .= $this->getHtmlInput(
                $column,
                $column_name_appendix,
                $special_chars,
                $fieldsize,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $data_type,
                $readOnly
            );

            if (preg_match('/(VIRTUAL|PERSISTENT|GENERATED)/', $column['Extra']) && $column['Extra'] !== 'DEFAULT_GENERATED') {
                $html_output .= '<input type="hidden" name="virtual'
                    . $column_name_appendix . '" value="1" />';
            }
            if ($column['Extra'] == 'auto_increment') {
                $html_output .= '<input type="hidden" name="auto_increment'
                    . $column_name_appendix . '" value="1" />';
            }
            if (substr($column['pma_type'], 0, 9) == 'timestamp') {
                $html_output .= '<input type="hidden" name="fields_type'
                    . $column_name_appendix . '" value="timestamp" />';
            }
            if (substr($column['pma_type'], 0, 8) == 'datetime') {
                $html_output .= '<input type="hidden" name="fields_type'
                    . $column_name_appendix . '" value="datetime" />';
            }
            if ($column['True_Type'] == 'bit') {
                $html_output .= '<input type="hidden" name="fields_type'
                    . $column_name_appendix . '" value="bit" />';
            }
            if ($column['pma_type'] == 'date'
                || $column['pma_type'] == 'datetime'
                || substr($column['pma_type'], 0, 9) == 'timestamp'
            ) {
                // the _3 suffix points to the date field
                // the _2 suffix points to the corresponding NULL checkbox
                // in dateFormat, 'yy' means the year with 4 digits
            }
        }
        return $html_output;
    }

    /**
     * Get the field size
     *
     * @param array $column               description of column in given table
     * @param array $extracted_columnspec associative array containing type,
     *                                    spec_in_brackets and possibly enum_set_values
     *                                    (another array)
     *
     * @return integer      field size
     */
    private function getColumnSize(array $column, array $extracted_columnspec)
    {
        if ($column['is_char']) {
            $fieldsize = $extracted_columnspec['spec_in_brackets'];
            if ($fieldsize > $GLOBALS['cfg']['MaxSizeForInputField']) {
                /**
                 * This case happens for CHAR or VARCHAR columns which have
                 * a size larger than the maximum size for input field.
                 */
                $GLOBALS['cfg']['CharEditing'] = 'textarea';
            }
        } else {
            /**
             * This case happens for example for INT or DATE columns;
             * in these situations, the value returned in $column['len']
             * seems appropriate.
             */
            $fieldsize = $column['len'];
        }
        return min(
            max($fieldsize, $GLOBALS['cfg']['MinSizeForInputField']),
            $GLOBALS['cfg']['MaxSizeForInputField']
        );
    }

    /**
     * Get HTML for gis data types
     *
     * @return string an html snippet
     */
    private function getHtmlForGisDataTypes()
    {
        $edit_str = Util::getIcon('b_edit', __('Edit/Insert'));
        return '<span class="open_gis_editor">'
            . Util::linkOrButton(
                '#',
                $edit_str,
                array(),
                '_blank'
            )
            . '</span>';
    }

    /**
     * get html for continue insertion form
     *
     * @param string $table              name of the table
     * @param string $db                 name of the database
     * @param array  $where_clause_array array of where clauses
     * @param string $err_url            error url
     *
     * @return string                   an html snippet
     */
    public function getContinueInsertionForm(
        $table,
        $db,
        array $where_clause_array,
        $err_url
    ) {
        return Template::get('table/insert/continue_insertion_form')->render([
            'db' => $db,
            'table' => $table,
            'where_clause_array' => $where_clause_array,
            'err_url' => $err_url,
            'goto' => $GLOBALS['goto'],
            'sql_query' => isset($_POST['sql_query']) ? $_POST['sql_query'] : null,
            'has_where_clause' => isset($_POST['where_clause']),
            'insert_rows_default' => $GLOBALS['cfg']['InsertRows'],
        ]);
    }

    /**
     * Get action panel
     *
     * @param array|null $where_clause       where clause
     * @param string     $after_insert       insert mode, e.g. new_insert, same_insert
     * @param integer    $tabindex           tab index
     * @param integer    $tabindex_for_value offset for the values tabindex
     * @param boolean    $found_unique_key   boolean variable for unique key
     *
     * @return string an html snippet
     */
    public function getActionsPanel(
        $where_clause,
        $after_insert,
        $tabindex,
        $tabindex_for_value,
        $found_unique_key
    ) {
        $html_output = '<fieldset id="actions_panel">'
            . '<table cellpadding="5" cellspacing="0" class="tdblock width100">'
            . '<tr>'
            . '<td class="nowrap vmiddle">'
            . $this->getSubmitTypeDropDown($where_clause, $tabindex, $tabindex_for_value)
            . "\n";

        $html_output .= '</td>'
            . '<td class="vmiddle">'
            . '&nbsp;&nbsp;&nbsp;<strong>'
            . __('and then') . '</strong>&nbsp;&nbsp;&nbsp;'
            . '</td>'
            . '<td class="nowrap vmiddle">'
            . $this->getAfterInsertDropDown(
                $where_clause,
                $after_insert,
                $found_unique_key
            )
            . '</td>'
            . '</tr>';
        $html_output .='<tr>'
            . $this->getSubmitAndResetButtonForActionsPanel($tabindex, $tabindex_for_value)
            . '</tr>'
            . '</table>'
            . '</fieldset>';
        return $html_output;
    }

    /**
     * Get a HTML drop down for submit types
     *
     * @param array|null $where_clause       where clause
     * @param integer    $tabindex           tab index
     * @param integer    $tabindex_for_value offset for the values tabindex
     *
     * @return string                       an html snippet
     */
    private function getSubmitTypeDropDown(
        $where_clause,
        $tabindex,
        $tabindex_for_value
    ) {
        $html_output = '<select name="submit_type" class="control_at_footer" tabindex="'
            . ($tabindex + $tabindex_for_value + 1) . '">';
        if (isset($where_clause)) {
            $html_output .= '<option value="save">' . __('Save') . '</option>';
        }
        $html_output .= '<option value="insert">'
            . __('Insert as new row')
            . '</option>'
            . '<option value="insertignore">'
            . __('Insert as new row and ignore errors')
            . '</option>'
            . '<option value="showinsert">'
            . __('Show insert query')
            . '</option>'
            . '</select>';
        return $html_output;
    }

    /**
     * Get HTML drop down for after insert
     *
     * @param array|null $where_clause     where clause
     * @param string     $after_insert     insert mode, e.g. new_insert, same_insert
     * @param boolean    $found_unique_key boolean variable for unique key
     *
     * @return string                   an html snippet
     */
    private function getAfterInsertDropDown($where_clause, $after_insert, $found_unique_key)
    {
        $html_output = '<select name="after_insert" class="control_at_footer">'
            . '<option value="back" '
            . ($after_insert == 'back' ? 'selected="selected"' : '') . '>'
            . __('Go back to previous page') . '</option>'
            . '<option value="new_insert" '
            . ($after_insert == 'new_insert' ? 'selected="selected"' : '') . '>'
            . __('Insert another new row') . '</option>';

        if (isset($where_clause)) {
            $html_output .= '<option value="same_insert" '
                . ($after_insert == 'same_insert' ? 'selected="selected"' : '') . '>'
                . __('Go back to this page') . '</option>';

            // If we have just numeric primary key, we can also edit next
            // in 2.8.2, we were looking for `field_name` = numeric_value
            //if (preg_match('@^[\s]*`[^`]*` = [0-9]+@', $where_clause)) {
            // in 2.9.0, we are looking for `table_name`.`field_name` = numeric_value
            $is_numeric = false;
            if (! is_array($where_clause)) {
                $where_clause = array($where_clause);
            }
            for ($i = 0, $nb = count($where_clause); $i < $nb; $i++) {
                // preg_match() returns 1 if there is a match
                $is_numeric = (preg_match(
                    '@^[\s]*`[^`]*`[\.]`[^`]*` = [0-9]+@',
                    $where_clause[$i]
                ) == 1);
                if ($is_numeric === true) {
                    break;
                }
            }
            if ($found_unique_key && $is_numeric) {
                $html_output .= '<option value="edit_next" '
                    . ($after_insert == 'edit_next' ? 'selected="selected"' : '') . '>'
                    . __('Edit next row') . '</option>';
            }
        }
        $html_output .= '</select>';
        return $html_output;
    }

    /**
     * get Submit button and Reset button for action panel
     *
     * @param integer $tabindex           tab index
     * @param integer $tabindex_for_value offset for the values tabindex
     *
     * @return string an html snippet
     */
    private function getSubmitAndResetButtonForActionsPanel($tabindex, $tabindex_for_value)
    {
        return '<td>'
        . Util::showHint(
            __(
                'Use TAB key to move from value to value,'
                . ' or CTRL+arrows to move anywhere.'
            )
        )
        . '</td>'
        . '<td colspan="3" class="right vmiddle">'
        . '<input type="submit" class="control_at_footer" value="' . __('Go') . '"'
        . ' tabindex="' . ($tabindex + $tabindex_for_value + 6) . '" id="buttonYes" />'
        . '<input type="button" class="preview_sql" value="' . __('Preview SQL') . '"'
        . ' tabindex="' . ($tabindex + $tabindex_for_value + 7) . '" />'
        . '<input type="reset" class="control_at_footer" value="' . __('Reset') . '"'
        . ' tabindex="' . ($tabindex + $tabindex_for_value + 8) . '" />'
        . '</td>';
    }

    /**
     * Get table head and table foot for insert row table
     *
     * @param array $url_params url parameters
     *
     * @return string           an html snippet
     */
    private function getHeadAndFootOfInsertRowTable(array $url_params)
    {
        $html_output = '<div class="responsivetable">'
            . '<table class="insertRowTable topmargin">'
            . '<thead>'
            . '<tr>'
            . '<th>' . __('Column') . '</th>';

        if ($GLOBALS['cfg']['ShowFieldTypesInDataEditView']) {
            $html_output .= $this->showTypeOrFunction('type', $url_params, true);
        }
        if ($GLOBALS['cfg']['ShowFunctionFields']) {
            $html_output .= $this->showTypeOrFunction('function', $url_params, true);
        }

        $html_output .= '<th>' . __('Null') . '</th>'
            . '<th>' . __('Value') . '</th>'
            . '</tr>'
            . '</thead>'
            . ' <tfoot>'
            . '<tr>'
            . '<th colspan="5" class="tblFooters right">'
            . '<input type="submit" value="' . __('Go') . '" />'
            . '</th>'
            . '</tr>'
            . '</tfoot>';
        return $html_output;
    }

    /**
     * Prepares the field value and retrieve special chars, backup field and data array
     *
     * @param array   $current_row          a row of the table
     * @param array   $column               description of column in given table
     * @param array   $extracted_columnspec associative array containing type,
     *                                      spec_in_brackets and possibly
     *                                      enum_set_values (another array)
     * @param boolean $real_null_value      whether column value null or not null
     * @param array   $gis_data_types       list of GIS data types
     * @param string  $column_name_appendix string to append to column name in input
     * @param bool    $as_is                use the data as is, used in repopulating
     *
     * @return array $real_null_value, $data, $special_chars, $backup_field,
     *               $special_chars_encoded
     */
    private function getSpecialCharsAndBackupFieldForExistingRow(
        array $current_row,
        array $column,
        array $extracted_columnspec,
        $real_null_value,
        array $gis_data_types,
        $column_name_appendix,
        $as_is
    ) {
        $special_chars_encoded = '';
        $data = null;
        // (we are editing)
        if (!isset($current_row[$column['Field']])) {
            $real_null_value = true;
            $current_row[$column['Field']] = '';
            $special_chars = '';
            $data = $current_row[$column['Field']];
        } elseif ($column['True_Type'] == 'bit') {
            $special_chars = $as_is
                ? $current_row[$column['Field']]
                : Util::printableBitValue(
                    $current_row[$column['Field']],
                    $extracted_columnspec['spec_in_brackets']
                );
        } elseif ((substr($column['True_Type'], 0, 9) == 'timestamp'
            || $column['True_Type'] == 'datetime'
            || $column['True_Type'] == 'time')
            && (mb_strpos($current_row[$column['Field']], ".") !== false)
        ) {
            $current_row[$column['Field']] = $as_is
                ? $current_row[$column['Field']]
                : Util::addMicroseconds(
                    $current_row[$column['Field']]
                );
            $special_chars = htmlspecialchars($current_row[$column['Field']]);
        } elseif (in_array($column['True_Type'], $gis_data_types)) {
            // Convert gis data to Well Know Text format
            $current_row[$column['Field']] = $as_is
                ? $current_row[$column['Field']]
                : Util::asWKT(
                    $current_row[$column['Field']],
                    true
                );
            $special_chars = htmlspecialchars($current_row[$column['Field']]);
        } else {
            // special binary "characters"
            if ($column['is_binary']
                || ($column['is_blob'] && $GLOBALS['cfg']['ProtectBinary'] !== 'all')
            ) {
                $current_row[$column['Field']] = $as_is
                    ? $current_row[$column['Field']]
                    : bin2hex(
                        $current_row[$column['Field']]
                    );
            } // end if
            $special_chars = htmlspecialchars($current_row[$column['Field']]);

            //We need to duplicate the first \n or otherwise we will lose
            //the first newline entered in a VARCHAR or TEXT column
            $special_chars_encoded
                = Util::duplicateFirstNewline($special_chars);

            $data = $current_row[$column['Field']];
        } // end if... else...

        //when copying row, it is useful to empty auto-increment column
        // to prevent duplicate key error
        if (isset($_POST['default_action'])
            && $_POST['default_action'] === 'insert'
        ) {
            if ($column['Key'] === 'PRI'
                && mb_strpos($column['Extra'], 'auto_increment') !== false
            ) {
                $data = $special_chars_encoded = $special_chars = null;
            }
        }
        // If a timestamp field value is not included in an update
        // statement MySQL auto-update it to the current timestamp;
        // however, things have changed since MySQL 4.1, so
        // it's better to set a fields_prev in this situation
        $backup_field = '<input type="hidden" name="fields_prev'
            . $column_name_appendix . '" value="'
            . htmlspecialchars($current_row[$column['Field']]) . '" />';

        return array(
            $real_null_value,
            $special_chars_encoded,
            $special_chars,
            $data,
            $backup_field
        );
    }

    /**
     * display default values
     *
     * @param array   $column          description of column in given table
     * @param boolean $real_null_value whether column value null or not null
     *
     * @return array $real_null_value, $data, $special_chars,
     *               $backup_field, $special_chars_encoded
     */
    private function getSpecialCharsAndBackupFieldForInsertingMode(
        array $column,
        $real_null_value
    ) {
        if (! isset($column['Default'])) {
            $column['Default']    = '';
            $real_null_value          = true;
            $data                     = '';
        } else {
            $data                     = $column['Default'];
        }

        $trueType = $column['True_Type'];

        if ($trueType == 'bit') {
            $special_chars = Util::convertBitDefaultValue(
                $column['Default']
            );
        } elseif (substr($trueType, 0, 9) == 'timestamp'
            || $trueType == 'datetime'
            || $trueType == 'time'
        ) {
            $special_chars = Util::addMicroseconds($column['Default']);
        } elseif ($trueType == 'binary' || $trueType == 'varbinary') {
            $special_chars = bin2hex($column['Default']);
        } elseif ('text' === substr($trueType, -4)) {
            $textDefault = substr($column['Default'], 1, -1);
            $special_chars = stripcslashes($textDefault !== false ? $textDefault : $column['Default']);
        } else {
            $special_chars = htmlspecialchars($column['Default']);
        }
        $backup_field = '';
        $special_chars_encoded = Util::duplicateFirstNewline(
            $special_chars
        );
        return array(
            $real_null_value, $data, $special_chars,
            $backup_field, $special_chars_encoded
        );
    }

    /**
     * Prepares the update/insert of a row
     *
     * @return array     $loop_array, $using_key, $is_insert, $is_insertignore
     */
    public function getParamsForUpdateOrInsert()
    {
        if (isset($_POST['where_clause'])) {
            // we were editing something => use the WHERE clause
            $loop_array = is_array($_POST['where_clause'])
                ? $_POST['where_clause']
                : array($_POST['where_clause']);
            $using_key  = true;
            $is_insert  = isset($_POST['submit_type'])
                          && ($_POST['submit_type'] == 'insert'
                          || $_POST['submit_type'] == 'showinsert'
                          || $_POST['submit_type'] == 'insertignore');
        } else {
            // new row => use indexes
            $loop_array = array();
            if (! empty($_POST['fields'])) {
                foreach ($_POST['fields']['multi_edit'] as $key => $dummy) {
                    $loop_array[] = $key;
                }
            }
            $using_key  = false;
            $is_insert  = true;
        }
        $is_insertignore  = isset($_POST['submit_type'])
            && $_POST['submit_type'] == 'insertignore';
        return array($loop_array, $using_key, $is_insert, $is_insertignore);
    }

    /**
     * Check wether insert row mode and if so include tbl_changen script and set
     * global variables.
     *
     * @return void
     */
    public function isInsertRow()
    {
        if (isset($_POST['insert_rows'])
            && is_numeric($_POST['insert_rows'])
            && $_POST['insert_rows'] != $GLOBALS['cfg']['InsertRows']
        ) {
            $GLOBALS['cfg']['InsertRows'] = $_POST['insert_rows'];
            $response = Response::getInstance();
            $header = $response->getHeader();
            $scripts = $header->getScripts();
            $scripts->addFile('vendor/jquery/additional-methods.js');
            $scripts->addFile('tbl_change.js');
            if (!defined('TESTSUITE')) {
                include 'tbl_change.php';
                exit;
            }
        }
    }

    /**
     * set $_SESSION for edit_next
     *
     * @param string $one_where_clause one where clause from where clauses array
     *
     * @return void
     */
    public function setSessionForEditNext($one_where_clause)
    {
        $local_query = 'SELECT * FROM ' . Util::backquote($GLOBALS['db'])
            . '.' . Util::backquote($GLOBALS['table']) . ' WHERE '
            . str_replace('` =', '` >', $one_where_clause) . ' LIMIT 1;';

        $res = $this->dbi->query($local_query);
        $row = $this->dbi->fetchRow($res);
        $meta = $this->dbi->getFieldsMeta($res);
        // must find a unique condition based on unique key,
        // not a combination of all fields
        list($unique_condition, $clause_is_unique)
            = Util::getUniqueCondition(
                $res, // handle
                count($meta), // fields_cnt
                $meta, // fields_meta
                $row, // row
                true, // force_unique
                false, // restrict_to_table
                null // analyzed_sql_results
            );
        if (! empty($unique_condition)) {
            $_SESSION['edit_next'] = $unique_condition;
        }
        unset($unique_condition, $clause_is_unique);
    }

    /**
     * set $goto_include variable for different cases and retrieve like,
     * if $GLOBALS['goto'] empty, if $goto_include previously not defined
     * and new_insert, same_insert, edit_next
     *
     * @param string $goto_include store some script for include, otherwise it is
     *                             boolean false
     *
     * @return string               $goto_include
     */
    public function getGotoInclude($goto_include)
    {
        $valid_options = array('new_insert', 'same_insert', 'edit_next');
        if (isset($_POST['after_insert'])
            && in_array($_POST['after_insert'], $valid_options)
        ) {
            $goto_include = 'tbl_change.php';
        } elseif (! empty($GLOBALS['goto'])) {
            if (! preg_match('@^[a-z_]+\.php$@', $GLOBALS['goto'])) {
                // this should NOT happen
                //$GLOBALS['goto'] = false;
                $goto_include = false;
            } else {
                $goto_include = $GLOBALS['goto'];
            }
            if ($GLOBALS['goto'] == 'db_sql.php' && strlen($GLOBALS['table']) > 0) {
                $GLOBALS['table'] = '';
            }
        }
        if (! $goto_include) {
            if (strlen($GLOBALS['table']) === 0) {
                $goto_include = 'db_sql.php';
            } else {
                $goto_include = 'tbl_sql.php';
            }
        }
        return $goto_include;
    }

    /**
     * Defines the url to return in case of failure of the query
     *
     * @param array $url_params url parameters
     *
     * @return string           error url for query failure
     */
    public function getErrorUrl(array $url_params)
    {
        if (isset($_POST['err_url'])) {
            return $_POST['err_url'];
        }

        return 'tbl_change.php' . Url::getCommon($url_params);
    }

    /**
     * Builds the sql query
     *
     * @param boolean $is_insertignore $_POST['submit_type'] == 'insertignore'
     * @param array   $query_fields    column names array
     * @param array   $value_sets      array of query values
     *
     * @return array of query
     */
    public function buildSqlQuery($is_insertignore, array $query_fields, array $value_sets)
    {
        if ($is_insertignore) {
            $insert_command = 'INSERT IGNORE ';
        } else {
            $insert_command = 'INSERT ';
        }
        $query = array(
            $insert_command . 'INTO '
            . Util::backquote($GLOBALS['table'])
            . ' (' . implode(', ', $query_fields) . ') VALUES ('
            . implode('), (', $value_sets) . ')'
        );
        unset($insert_command, $query_fields);
        return $query;
    }

    /**
     * Executes the sql query and get the result, then move back to the calling page
     *
     * @param array $url_params url parameters array
     * @param array $query      built query from buildSqlQuery()
     *
     * @return array            $url_params, $total_affected_rows, $last_messages
     *                          $warning_messages, $error_messages, $return_to_sql_query
     */
    public function executeSqlQuery(array $url_params, array $query)
    {
        $return_to_sql_query = '';
        if (! empty($GLOBALS['sql_query'])) {
            $url_params['sql_query'] = $GLOBALS['sql_query'];
            $return_to_sql_query = $GLOBALS['sql_query'];
        }
        $GLOBALS['sql_query'] = implode('; ', $query) . ';';
        // to ensure that the query is displayed in case of
        // "insert as new row" and then "insert another new row"
        $GLOBALS['display_query'] = $GLOBALS['sql_query'];

        $total_affected_rows = 0;
        $last_messages = array();
        $warning_messages = array();
        $error_messages = array();

        foreach ($query as $single_query) {
            if ($_POST['submit_type'] == 'showinsert') {
                $last_messages[] = Message::notice(__('Showing SQL query'));
                continue;
            }
            if ($GLOBALS['cfg']['IgnoreMultiSubmitErrors']) {
                $result = $this->dbi->tryQuery($single_query);
            } else {
                $result = $this->dbi->query($single_query);
            }
            if (! $result) {
                $error_messages[] = $this->dbi->getError();
            } else {
                // The next line contains a real assignment, it's not a typo
                if ($tmp = @$this->dbi->affectedRows()) {
                    $total_affected_rows += $tmp;
                }
                unset($tmp);

                $insert_id = $this->dbi->insertId();
                if ($insert_id != 0) {
                    // insert_id is id of FIRST record inserted in one insert, so if we
                    // inserted multiple rows, we had to increment this

                    if ($total_affected_rows > 0) {
                        $insert_id = $insert_id + $total_affected_rows - 1;
                    }
                    $last_message = Message::notice(__('Inserted row id: %1$d'));
                    $last_message->addParam($insert_id);
                    $last_messages[] = $last_message;
                }
                $this->dbi->freeResult($result);
            }
            $warning_messages = $this->getWarningMessages();
        }
        return array(
            $url_params,
            $total_affected_rows,
            $last_messages,
            $warning_messages,
            $error_messages,
            $return_to_sql_query
        );
    }

    /**
     * get the warning messages array
     *
     * @return array  $warning_essages
     */
    private function getWarningMessages()
    {
        $warning_essages = array();
        foreach ($this->dbi->getWarnings() as $warning) {
            $warning_essages[] = Message::sanitize(
                $warning['Level'] . ': #' . $warning['Code'] . ' ' . $warning['Message']
            );
        }
        return $warning_essages;
    }

    /**
     * Column to display from the foreign table?
     *
     * @param string $where_comparison string that contain relation field value
     * @param array  $map              all Relations to foreign tables for a given
     *                                 table or optionally a given column in a table
     * @param string $relation_field   relation field
     *
     * @return string $dispval display value from the foreign table
     */
    public function getDisplayValueForForeignTableColumn(
        $where_comparison,
        array $map,
        $relation_field
    ) {
        $foreigner = $this->relation->searchColumnInForeigners($map, $relation_field);
        $display_field = $this->relation->getDisplayField(
            $foreigner['foreign_db'],
            $foreigner['foreign_table']
        );
        // Field to display from the foreign table?
        if (isset($display_field) && strlen($display_field) > 0) {
            $dispsql = 'SELECT ' . Util::backquote($display_field)
                . ' FROM ' . Util::backquote($foreigner['foreign_db'])
                . '.' . Util::backquote($foreigner['foreign_table'])
                . ' WHERE ' . Util::backquote($foreigner['foreign_field'])
                . $where_comparison;
            $dispresult = $this->dbi->tryQuery(
                $dispsql,
                DatabaseInterface::CONNECT_USER,
                DatabaseInterface::QUERY_STORE
            );
            if ($dispresult && $this->dbi->numRows($dispresult) > 0) {
                list($dispval) = $this->dbi->fetchRow($dispresult, 0);
            } else {
                $dispval = '';
            }
            if ($dispresult) {
                $this->dbi->freeResult($dispresult);
            }
            return $dispval;
        }
        return '';
    }

    /**
     * Display option in the cell according to user choices
     *
     * @param array  $map                  all Relations to foreign tables for a given
     *                                     table or optionally a given column in a table
     * @param string $relation_field       relation field
     * @param string $where_comparison     string that contain relation field value
     * @param string $dispval              display value from the foreign table
     * @param string $relation_field_value relation field value
     *
     * @return string $output HTML <a> tag
     */
    public function getLinkForRelationalDisplayField(
        array $map,
        $relation_field,
        $where_comparison,
        $dispval,
        $relation_field_value
    ) {
        $foreigner = $this->relation->searchColumnInForeigners($map, $relation_field);
        if ('K' == $_SESSION['tmpval']['relational_display']) {
            // user chose "relational key" in the display options, so
            // the title contains the display field
            $title = (! empty($dispval))
                ? ' title="' . htmlspecialchars($dispval) . '"'
                : '';
        } else {
            $title = ' title="' . htmlspecialchars($relation_field_value) . '"';
        }
        $_url_params = array(
            'db'    => $foreigner['foreign_db'],
            'table' => $foreigner['foreign_table'],
            'pos'   => '0',
            'sql_query' => 'SELECT * FROM '
                . Util::backquote($foreigner['foreign_db'])
                . '.' . Util::backquote($foreigner['foreign_table'])
                . ' WHERE ' . Util::backquote($foreigner['foreign_field'])
                . $where_comparison
        );
        $output = '<a href="sql.php'
            . Url::getCommon($_url_params) . '"' . $title . '>';

        if ('D' == $_SESSION['tmpval']['relational_display']) {
            // user chose "relational display field" in the
            // display options, so show display field in the cell
            $output .= (!empty($dispval)) ? htmlspecialchars($dispval) : '';
        } else {
            // otherwise display data in the cell
            $output .= htmlspecialchars($relation_field_value);
        }
        $output .= '</a>';
        return $output;
    }

    /**
     * Transform edited values
     *
     * @param string $db             db name
     * @param string $table          table name
     * @param array  $transformation mimetypes for all columns of a table
     *                               [field_name][field_key]
     * @param array  &$edited_values transform columns list and new values
     * @param string $file           file containing the transformation plugin
     * @param string $column_name    column name
     * @param array  $extra_data     extra data array
     * @param string $type           the type of transformation
     *
     * @return array $extra_data
     */
    public function transformEditedValues(
        $db,
        $table,
        array $transformation,
        array &$edited_values,
        $file,
        $column_name,
        array $extra_data,
        $type
    ) {
        $include_file = 'libraries/classes/Plugins/Transformations/' . $file;
        if (is_file($include_file)) {
            $_url_params = array(
                'db'            => $db,
                'table'         => $table,
                'where_clause_sign' => Core::signSqlQuery($_POST['where_clause']),
                'where_clause'  => $_POST['where_clause'],
                'transform_key' => $column_name
            );
            $transform_options = Transformations::getOptions(
                isset($transformation[$type . '_options'])
                ? $transformation[$type . '_options']
                : ''
            );
            $transform_options['wrapper_link'] = Url::getCommon($_url_params);
            $class_name = Transformations::getClassName($include_file);
            if (class_exists($class_name)) {
                /** @var TransformationsPlugin $transformation_plugin */
                $transformation_plugin = new $class_name();

                foreach ($edited_values as $cell_index => $curr_cell_edited_values) {
                    if (isset($curr_cell_edited_values[$column_name])) {
                        $edited_values[$cell_index][$column_name]
                            = $extra_data['transformations'][$cell_index]
                                = $transformation_plugin->applyTransformation(
                                    $curr_cell_edited_values[$column_name],
                                    $transform_options,
                                    ''
                                );
                    }
                }   // end of loop for each transformation cell
            }
        }
        return $extra_data;
    }

    /**
     * Get current value in multi edit mode
     *
     * @param array  $multi_edit_funcs        multiple edit functions array
     * @param array  $multi_edit_salt         multiple edit array with encryption salt
     * @param array  $gis_from_text_functions array that contains gis from text functions
     * @param string $current_value           current value in the column
     * @param array  $gis_from_wkb_functions  initially $val is $multi_edit_columns[$key]
     * @param array  $func_optional_param     array('RAND','UNIX_TIMESTAMP')
     * @param array  $func_no_param           array of set of string
     * @param string $key                     an md5 of the column name
     *
     * @return array $cur_value
     */
    public function getCurrentValueAsAnArrayForMultipleEdit(
        $multi_edit_funcs,
        $multi_edit_salt,
        $gis_from_text_functions,
        $current_value,
        $gis_from_wkb_functions,
        $func_optional_param,
        $func_no_param,
        $key
    ) {
        if (empty($multi_edit_funcs[$key])) {
            return $current_value;
        } elseif ('UUID' === $multi_edit_funcs[$key]) {
            /* This way user will know what UUID new row has */
            $uuid = $this->dbi->fetchValue('SELECT UUID()');
            return "'" . $uuid . "'";
        } elseif ((in_array($multi_edit_funcs[$key], $gis_from_text_functions)
            && substr($current_value, 0, 3) == "'''")
            || in_array($multi_edit_funcs[$key], $gis_from_wkb_functions)
        ) {
            // Remove enclosing apostrophes
            $current_value = mb_substr($current_value, 1, -1);
            // Remove escaping apostrophes
            $current_value = str_replace("''", "'", $current_value);
            return $multi_edit_funcs[$key] . '(' . $current_value . ')';
        } elseif (! in_array($multi_edit_funcs[$key], $func_no_param)
            || ($current_value != "''"
            && in_array($multi_edit_funcs[$key], $func_optional_param))
        ) {
            if ((isset($multi_edit_salt[$key])
                && ($multi_edit_funcs[$key] == "AES_ENCRYPT"
                || $multi_edit_funcs[$key] == "AES_DECRYPT"))
                || (! empty($multi_edit_salt[$key])
                && ($multi_edit_funcs[$key] == "DES_ENCRYPT"
                || $multi_edit_funcs[$key] == "DES_DECRYPT"
                || $multi_edit_funcs[$key] == "ENCRYPT"))
            ) {
                return $multi_edit_funcs[$key] . '(' . $current_value . ",'"
                    . $this->dbi->escapeString($multi_edit_salt[$key]) . "')";
            }

            return $multi_edit_funcs[$key] . '(' . $current_value . ')';
        }

        return $multi_edit_funcs[$key] . '()';
    }

    /**
     * Get query values array and query fields array for insert and update in multi edit
     *
     * @param array   $multi_edit_columns_name      multiple edit columns name array
     * @param array   $multi_edit_columns_null      multiple edit columns null array
     * @param string  $current_value                current value in the column in loop
     * @param array   $multi_edit_columns_prev      multiple edit previous columns array
     * @param array   $multi_edit_funcs             multiple edit functions array
     * @param boolean $is_insert                    boolean value whether insert or not
     * @param array   $query_values                 SET part of the sql query
     * @param array   $query_fields                 array of query fields
     * @param string  $current_value_as_an_array    current value in the column
     *                                              as an array
     * @param array   $value_sets                   array of valu sets
     * @param string  $key                          an md5 of the column name
     * @param array   $multi_edit_columns_null_prev array of multiple edit columns
     *                                              null previous
     *
     * @return array ($query_values, $query_fields)
     */
    public function getQueryValuesForInsertAndUpdateInMultipleEdit(
        $multi_edit_columns_name,
        $multi_edit_columns_null,
        $current_value,
        $multi_edit_columns_prev,
        $multi_edit_funcs,
        $is_insert,
        $query_values,
        $query_fields,
        $current_value_as_an_array,
        $value_sets,
        $key,
        $multi_edit_columns_null_prev
    ) {
        //  i n s e r t
        if ($is_insert) {
            // no need to add column into the valuelist
            if (strlen($current_value_as_an_array) > 0) {
                $query_values[] = $current_value_as_an_array;
                // first inserted row so prepare the list of fields
                if (empty($value_sets)) {
                    $query_fields[] = Util::backquote(
                        $multi_edit_columns_name[$key]
                    );
                }
            }
        } elseif (! empty($multi_edit_columns_null_prev[$key])
            && ! isset($multi_edit_columns_null[$key])
        ) {
            //  u p d a t e

            // field had the null checkbox before the update
            // field no longer has the null checkbox
            $query_values[]
                = Util::backquote($multi_edit_columns_name[$key])
                . ' = ' . $current_value_as_an_array;
        } elseif (empty($multi_edit_funcs[$key])
            && isset($multi_edit_columns_prev[$key])
            && (("'" . $this->dbi->escapeString($multi_edit_columns_prev[$key]) . "'" === $current_value)
            || ('0x' . $multi_edit_columns_prev[$key] === $current_value))
        ) {
            // No change for this column and no MySQL function is used -> next column
        } elseif (! empty($current_value)) {
            // avoid setting a field to NULL when it's already NULL
            // (field had the null checkbox before the update
            //  field still has the null checkbox)
            if (empty($multi_edit_columns_null_prev[$key])
                || empty($multi_edit_columns_null[$key])
            ) {
                 $query_values[]
                     = Util::backquote($multi_edit_columns_name[$key])
                    . ' = ' . $current_value_as_an_array;
            }
        }
        return array($query_values, $query_fields);
    }

    /**
     * Get the current column value in the form for different data types
     *
     * @param string|false $possibly_uploaded_val        uploaded file content
     * @param string       $key                          an md5 of the column name
     * @param array        $multi_edit_columns_type      array of multi edit column types
     * @param string       $current_value                current column value in the form
     * @param array        $multi_edit_auto_increment    multi edit auto increment
     * @param integer      $rownumber                    index of where clause array
     * @param array        $multi_edit_columns_name      multi edit column names array
     * @param array        $multi_edit_columns_null      multi edit columns null array
     * @param array        $multi_edit_columns_null_prev multi edit columns previous null
     * @param boolean      $is_insert                    whether insert or not
     * @param boolean      $using_key                    whether editing or new row
     * @param string       $where_clause                 where clause
     * @param string       $table                        table name
     * @param array        $multi_edit_funcs             multiple edit functions array
     *
     * @return string $current_value  current column value in the form
     */
    public function getCurrentValueForDifferentTypes(
        $possibly_uploaded_val,
        $key,
        $multi_edit_columns_type,
        $current_value,
        $multi_edit_auto_increment,
        $rownumber,
        $multi_edit_columns_name,
        $multi_edit_columns_null,
        $multi_edit_columns_null_prev,
        $is_insert,
        $using_key,
        $where_clause,
        $table,
        $multi_edit_funcs
    ) {
        // Fetch the current values of a row to use in case we have a protected field
        if ($is_insert
            && $using_key && isset($multi_edit_columns_type)
            && is_array($multi_edit_columns_type) && !empty($where_clause)
        ) {
            $protected_row = $this->dbi->fetchSingleRow(
                'SELECT * FROM ' . Util::backquote($table)
                . ' WHERE ' . $where_clause . ';'
            );
        }

        if (false !== $possibly_uploaded_val) {
            $current_value = $possibly_uploaded_val;
        } elseif (! empty($multi_edit_funcs[$key])) {
            $current_value = "'" . $this->dbi->escapeString($current_value)
                . "'";
        } else {
            // c o l u m n    v a l u e    i n    t h e    f o r m
            if (isset($multi_edit_columns_type[$key])) {
                $type = $multi_edit_columns_type[$key];
            } else {
                $type = '';
            }

            if ($type != 'protected' && $type != 'set' && strlen($current_value) === 0) {
                // best way to avoid problems in strict mode
                // (works also in non-strict mode)
                if (isset($multi_edit_auto_increment)
                    && isset($multi_edit_auto_increment[$key])
                ) {
                    $current_value = 'NULL';
                } else {
                    $current_value = "''";
                }
            } elseif ($type == 'set') {
                if (! empty($_POST['fields']['multi_edit'][$rownumber][$key])) {
                    $current_value = implode(
                        ',',
                        $_POST['fields']['multi_edit'][$rownumber][$key]
                    );
                    $current_value = "'"
                        . $this->dbi->escapeString($current_value) . "'";
                } else {
                     $current_value = "''";
                }
            } elseif ($type == 'protected') {
                // here we are in protected mode (asked in the config)
                // so tbl_change has put this special value in the
                // columns array, so we do not change the column value
                // but we can still handle column upload

                // when in UPDATE mode, do not alter field's contents. When in INSERT
                // mode, insert empty field because no values were submitted.
                // If protected blobs where set, insert original fields content.
                if (! empty($protected_row[$multi_edit_columns_name[$key]])) {
                    $current_value = '0x'
                        . bin2hex($protected_row[$multi_edit_columns_name[$key]]);
                } else {
                    $current_value = '';
                }
            } elseif ($type === 'hex') {
                if (substr($current_value, 0, 2) != '0x') {
                    $current_value = '0x' . $current_value;
                }
            } elseif ($type == 'bit') {
                $current_value = preg_replace('/[^01]/', '0', $current_value);
                $current_value = "b'" . $this->dbi->escapeString($current_value)
                    . "'";
            } elseif (! ($type == 'datetime' || $type == 'timestamp')
                || ($current_value != 'CURRENT_TIMESTAMP'
                    && $current_value != 'current_timestamp()')
            ) {
                $current_value = "'" . $this->dbi->escapeString($current_value)
                    . "'";
            }

            // Was the Null checkbox checked for this field?
            // (if there is a value, we ignore the Null checkbox: this could
            // be possible if Javascript is disabled in the browser)
            if (! empty($multi_edit_columns_null[$key])
                && ($current_value == "''" || $current_value == '')
            ) {
                $current_value = 'NULL';
            }

            // The Null checkbox was unchecked for this field
            if (empty($current_value)
                && ! empty($multi_edit_columns_null_prev[$key])
                && ! isset($multi_edit_columns_null[$key])
            ) {
                $current_value = "''";
            }
        }  // end else (column value in the form)
        return $current_value;
    }

    /**
     * Check whether inline edited value can be truncated or not,
     * and add additional parameters for extra_data array  if needed
     *
     * @param string $db          Database name
     * @param string $table       Table name
     * @param string $column_name Column name
     * @param array  &$extra_data Extra data for ajax response
     *
     * @return void
     */
    public function verifyWhetherValueCanBeTruncatedAndAppendExtraData(
        $db,
        $table,
        $column_name,
        array &$extra_data
    ) {
        $extra_data['isNeedToRecheck'] = false;

        $sql_for_real_value = 'SELECT ' . Util::backquote($table) . '.'
            . Util::backquote($column_name)
            . ' FROM ' . Util::backquote($db) . '.'
            . Util::backquote($table)
            . ' WHERE ' . $_POST['where_clause'][0];

        $result = $this->dbi->tryQuery($sql_for_real_value);
        $fields_meta = $this->dbi->getFieldsMeta($result);
        $meta = $fields_meta[0];
        if ($row = $this->dbi->fetchRow($result)) {
            $new_value = $row[0];
            if ((substr($meta->type, 0, 9) == 'timestamp')
                || ($meta->type == 'datetime')
                || ($meta->type == 'time')
            ) {
                $new_value = Util::addMicroseconds($new_value);
            } elseif (mb_strpos($meta->flags, 'binary') !== false) {
                $new_value = '0x' . bin2hex($new_value);
            }
            $extra_data['isNeedToRecheck'] = true;
            $extra_data['truncatableFieldValue'] = $new_value;
        }
        $this->dbi->freeResult($result);
    }

    /**
     * Function to get the columns of a table
     *
     * @param string $db    current db
     * @param string $table current table
     *
     * @return array
     */
    public function getTableColumns($db, $table)
    {
        $this->dbi->selectDb($db);
        return array_values($this->dbi->getColumns($db, $table, null, true));
    }

    /**
     * Function to determine Insert/Edit rows
     *
     * @param string $where_clause where clause
     * @param string $db           current database
     * @param string $table        current table
     *
     * @return mixed
     */
    public function determineInsertOrEdit($where_clause, $db, $table)
    {
        if (isset($_POST['where_clause'])) {
            $where_clause = $_POST['where_clause'];
        }
        if (isset($_SESSION['edit_next'])) {
            $where_clause = $_SESSION['edit_next'];
            unset($_SESSION['edit_next']);
            $after_insert = 'edit_next';
        }
        if (isset($_POST['ShowFunctionFields'])) {
            $GLOBALS['cfg']['ShowFunctionFields'] = $_POST['ShowFunctionFields'];
        }
        if (isset($_POST['ShowFieldTypesInDataEditView'])) {
            $GLOBALS['cfg']['ShowFieldTypesInDataEditView']
                = $_POST['ShowFieldTypesInDataEditView'];
        }
        if (isset($_POST['after_insert'])) {
            $after_insert = $_POST['after_insert'];
        }

        if (isset($where_clause)) {
            // we are editing
            $insert_mode = false;
            $where_clause_array = $this->getWhereClauseArray($where_clause);
            list($where_clauses, $result, $rows, $found_unique_key)
                = $this->analyzeWhereClauses(
                    $where_clause_array,
                    $table,
                    $db
                );
        } else {
            // we are inserting
            $insert_mode = true;
            $where_clause = null;
            list($result, $rows) = $this->loadFirstRow($table, $db);
            $where_clauses = null;
            $where_clause_array = array();
            $found_unique_key = false;
        }

        // Copying a row - fetched data will be inserted as a new row,
        // therefore the where clause is needless.
        if (isset($_POST['default_action'])
            && $_POST['default_action'] === 'insert'
        ) {
            $where_clause = $where_clauses = null;
        }

        return array(
            $insert_mode, $where_clause, $where_clause_array, $where_clauses,
            $result, $rows, $found_unique_key,
            isset($after_insert) ? $after_insert : null
        );
    }

    /**
     * Function to get comments for the table columns
     *
     * @param string $db    current database
     * @param string $table current table
     *
     * @return array $comments_map comments for columns
     */
    public function getCommentsMap($db, $table)
    {
        $comments_map = array();

        if ($GLOBALS['cfg']['ShowPropertyComments']) {
            $comments_map = $this->relation->getComments($db, $table);
        }

        return $comments_map;
    }

    /**
     * Function to get URL parameters
     *
     * @param string $db    current database
     * @param string $table current table
     *
     * @return array $url_params url parameters
     */
    public function getUrlParameters($db, $table)
    {
        /**
         * @todo check if we could replace by "db_|tbl_" - please clarify!?
         */
        $url_params = array(
            'db' => $db,
            'sql_query' => $_POST['sql_query']
        );

        if (preg_match('@^tbl_@', $GLOBALS['goto'])) {
            $url_params['table'] = $table;
        }

        return $url_params;
    }

    /**
     * Function to get html for the gis editor div
     *
     * @return string
     */
    public function getHtmlForGisEditor()
    {
        return '<div id="gis_editor"></div>'
            . '<div id="popup_background"></div>'
            . '<br />';
    }

    /**
     * Function to get html for the ignore option in insert mode
     *
     * @param int  $row_id  row id
     * @param bool $checked ignore option is checked or not
     *
     * @return string
     */
    public function getHtmlForIgnoreOption($row_id, $checked = true)
    {
        return '<input type="checkbox"'
                . ($checked ? ' checked="checked"' : '')
                . ' name="insert_ignore_' . $row_id . '"'
                . ' id="insert_ignore_' . $row_id . '" />'
                . '<label for="insert_ignore_' . $row_id . '">'
                . __('Ignore')
                . '</label><br />' . "\n";
    }

    /**
     * Function to get html for the function option
     *
     * @param array  $column               column
     * @param string $column_name_appendix column name appendix
     *
     * @return String
     */
    private function getHtmlForFunctionOption(array $column, $column_name_appendix)
    {
        return '<tr class="noclick">'
            . '<td '
            . 'class="center">'
            . $column['Field_title']
            . '<input type="hidden" name="fields_name' . $column_name_appendix
            . '" value="' . $column['Field_html'] . '"/>'
            . '</td>';
    }

    /**
     * Function to get html for the column type
     *
     * @param array $column column
     *
     * @return string
     */
    private function getHtmlForInsertEditColumnType(array $column)
    {
        return '<td class="center' . $column['wrap'] . '">'
            . '<span class="column_type" dir="ltr">' . $column['pma_type'] . '</span>'
            . '</td>';
    }

    /**
     * Function to get html for the insert edit form header
     *
     * @param bool $has_blob_field whether has blob field
     * @param bool $is_upload      whether is upload
     *
     * @return string
     */
    public function getHtmlForInsertEditFormHeader($has_blob_field, $is_upload)
    {
        $html_output ='<form id="insertForm" class="lock-page ';
        if ($has_blob_field && $is_upload) {
            $html_output .='disableAjax';
        }
        $html_output .='" method="post" action="tbl_replace.php" name="insertForm" ';
        if ($is_upload) {
            $html_output .= ' enctype="multipart/form-data"';
        }
        $html_output .= '>';

        return $html_output;
    }

    /**
     * Function to get html for each insert/edit column
     *
     * @param array  $table_columns         table columns
     * @param int    $column_number         column index in table_columns
     * @param array  $comments_map          comments map
     * @param bool   $timestamp_seen        whether timestamp seen
     * @param array  $current_result        current result
     * @param string $chg_evt_handler       javascript change event handler
     * @param string $jsvkey                javascript validation key
     * @param string $vkey                  validation key
     * @param bool   $insert_mode           whether insert mode
     * @param array  $current_row           current row
     * @param int    &$o_rows               row offset
     * @param int    &$tabindex             tab index
     * @param int    $columns_cnt           columns count
     * @param bool   $is_upload             whether upload
     * @param int    $tabindex_for_function tab index offset for function
     * @param array  $foreigners            foreigners
     * @param int    $tabindex_for_null     tab index offset for null
     * @param int    $tabindex_for_value    tab index offset for value
     * @param string $table                 table
     * @param string $db                    database
     * @param int    $row_id                row id
     * @param array  $titles                titles
     * @param int    $biggest_max_file_size biggest max file size
     * @param string $default_char_editing  default char editing mode which is stored
     *                                      in the config.inc.php script
     * @param string $text_dir              text direction
     * @param array  $repopulate            the data to be repopulated
     * @param array  $column_mime           the mime information of column
     * @param string $where_clause          the where clause
     *
     * @return string
     */
    private function getHtmlForInsertEditFormColumn(
        array $table_columns,
        $column_number,
        array $comments_map,
        $timestamp_seen,
        $current_result,
        $chg_evt_handler,
        $jsvkey,
        $vkey,
        $insert_mode,
        array $current_row,
        &$o_rows,
        &$tabindex,
        $columns_cnt,
        $is_upload,
        $tabindex_for_function,
        array $foreigners,
        $tabindex_for_null,
        $tabindex_for_value,
        $table,
        $db,
        $row_id,
        array $titles,
        $biggest_max_file_size,
        $default_char_editing,
        $text_dir,
        array $repopulate,
        array $column_mime,
        $where_clause
    ) {
        $column = $table_columns[$column_number];
        $readOnly = false;

        if (! isset($column['processed'])) {
            $column = $this->analyzeTableColumnsArray(
                $column,
                $comments_map,
                $timestamp_seen
            );
        }
        $as_is = false;
        if (!empty($repopulate) && !empty($current_row)) {
            $current_row[$column['Field']] = $repopulate[$column['Field_md5']];
            $as_is = true;
        }

        $extracted_columnspec
            = Util::extractColumnSpec($column['Type']);

        if (-1 === $column['len']) {
            $column['len'] = $this->dbi->fieldLen(
                $current_result,
                $column_number
            );
            // length is unknown for geometry fields,
            // make enough space to edit very simple WKTs
            if (-1 === $column['len']) {
                $column['len'] = 30;
            }
        }
        //Call validation when the form submitted...
        $onChangeClause = $chg_evt_handler
            . "=\"return verificationsAfterFieldChange('"
            . Sanitize::escapeJsString($column['Field_md5']) . "', '"
            . Sanitize::escapeJsString($jsvkey) . "','" . $column['pma_type'] . "')\"";

        // Use an MD5 as an array index to avoid having special characters
        // in the name attribute (see bug #1746964 )
        $column_name_appendix = $vkey . '[' . $column['Field_md5'] . ']';

        if ($column['Type'] === 'datetime'
            && ! isset($column['Default'])
            && ! is_null($column['Default'])
            && $insert_mode
        ) {
            $column['Default'] = date('Y-m-d H:i:s', time());
        }

        $html_output = $this->getHtmlForFunctionOption(
            $column,
            $column_name_appendix
        );

        if ($GLOBALS['cfg']['ShowFieldTypesInDataEditView']) {
            $html_output .= $this->getHtmlForInsertEditColumnType($column);
        } //End if

        // Get a list of GIS data types.
        $gis_data_types = Util::getGISDatatypes();

        // Prepares the field value
        $real_null_value = false;
        $special_chars_encoded = '';
        if (!empty($current_row)) {
            // (we are editing)
            list(
                $real_null_value, $special_chars_encoded, $special_chars,
                $data, $backup_field
            )
                = $this->getSpecialCharsAndBackupFieldForExistingRow(
                    $current_row,
                    $column,
                    $extracted_columnspec,
                    $real_null_value,
                    $gis_data_types,
                    $column_name_appendix,
                    $as_is
                );
        } else {
            // (we are inserting)
            // display default values
            $tmp = $column;
            if (isset($repopulate[$column['Field_md5']])) {
                $tmp['Default'] = $repopulate[$column['Field_md5']];
            }
            list($real_null_value, $data, $special_chars, $backup_field,
                $special_chars_encoded
            )
                = $this->getSpecialCharsAndBackupFieldForInsertingMode(
                    $tmp,
                    $real_null_value
                );
            unset($tmp);
        }

        $idindex = ($o_rows * $columns_cnt) + $column_number + 1;
        $tabindex = $idindex;

        // Get a list of data types that are not yet supported.
        $no_support_types = Util::unsupportedDatatypes();

        // The function column
        // -------------------
        $foreignData = $this->relation->getForeignData(
            $foreigners,
            $column['Field'],
            false,
            '',
            ''
        );
        if ($GLOBALS['cfg']['ShowFunctionFields']) {
            $html_output .= $this->getFunctionColumn(
                $column,
                $is_upload,
                $column_name_appendix,
                $onChangeClause,
                $no_support_types,
                $tabindex_for_function,
                $tabindex,
                $idindex,
                $insert_mode,
                $readOnly,
                $foreignData
            );
        }

        // The null column
        // ---------------
        $html_output .= $this->getNullColumn(
            $column,
            $column_name_appendix,
            $real_null_value,
            $tabindex,
            $tabindex_for_null,
            $idindex,
            $vkey,
            $foreigners,
            $foreignData,
            $readOnly
        );

        // The value column (depends on type)
        // ----------------
        // See bug #1667887 for the reason why we don't use the maxlength
        // HTML attribute

        //add data attributes "no of decimals" and "data type"
        $no_decimals = 0;
        $type = current(explode("(", $column['pma_type']));
        if (preg_match('/\(([^()]+)\)/', $column['pma_type'], $match)) {
            $match[0] = trim($match[0], '()');
            $no_decimals = $match[0];
        }
        $html_output .= '<td' . ' data-type="' . $type . '"' . ' data-decimals="'
            . $no_decimals . '">' . "\n";
        // Will be used by js/tbl_change.js to set the default value
        // for the "Continue insertion" feature
        $html_output .= '<span class="default_value hide">'
            . $special_chars . '</span>';

        // Check input transformation of column
        $transformed_html = '';
        if (!empty($column_mime['input_transformation'])) {
            $file = $column_mime['input_transformation'];
            $include_file = 'libraries/classes/Plugins/Transformations/' . $file;
            if (is_file($include_file)) {
                $class_name = Transformations::getClassName($include_file);
                if (class_exists($class_name)) {
                    $transformation_plugin = new $class_name();
                    $transformation_options = Transformations::getOptions(
                        $column_mime['input_transformation_options']
                    );
                    $_url_params = array(
                        'db'            => $db,
                        'table'         => $table,
                        'transform_key' => $column['Field'],
                        'where_clause_sign' => Core::signSqlQuery($where_clause),
                        'where_clause'  => $where_clause
                    );
                    $transformation_options['wrapper_link']
                        = Url::getCommon($_url_params);
                    $current_value = '';
                    if (isset($current_row[$column['Field']])) {
                        $current_value = $current_row[$column['Field']];
                    }
                    if (method_exists($transformation_plugin, 'getInputHtml')) {
                        $transformed_html = $transformation_plugin->getInputHtml(
                            $column,
                            $row_id,
                            $column_name_appendix,
                            $transformation_options,
                            $current_value,
                            $text_dir,
                            $tabindex,
                            $tabindex_for_value,
                            $idindex
                        );
                    }
                    if (method_exists($transformation_plugin, 'getScripts')) {
                        $GLOBALS['plugin_scripts'] = array_merge(
                            $GLOBALS['plugin_scripts'],
                            $transformation_plugin->getScripts()
                        );
                    }
                }
            }
        }
        if (!empty($transformed_html)) {
            $html_output .= $transformed_html;
        } else {
            $html_output .= $this->getValueColumn(
                $column,
                $backup_field,
                $column_name_appendix,
                $onChangeClause,
                $tabindex,
                $tabindex_for_value,
                $idindex,
                $data,
                $special_chars,
                $foreignData,
                array($table, $db),
                $row_id,
                $titles,
                $text_dir,
                $special_chars_encoded,
                $vkey,
                $is_upload,
                $biggest_max_file_size,
                $default_char_editing,
                $no_support_types,
                $gis_data_types,
                $extracted_columnspec,
                $readOnly
            );
        }
        return $html_output;
    }

    /**
     * Function to get html for each insert/edit row
     *
     * @param array  $url_params            url parameters
     * @param array  $table_columns         table columns
     * @param array  $comments_map          comments map
     * @param bool   $timestamp_seen        whether timestamp seen
     * @param array  $current_result        current result
     * @param string $chg_evt_handler       javascript change event handler
     * @param string $jsvkey                javascript validation key
     * @param string $vkey                  validation key
     * @param bool   $insert_mode           whether insert mode
     * @param array  $current_row           current row
     * @param int    &$o_rows               row offset
     * @param int    &$tabindex             tab index
     * @param int    $columns_cnt           columns count
     * @param bool   $is_upload             whether upload
     * @param int    $tabindex_for_function tab index offset for function
     * @param array  $foreigners            foreigners
     * @param int    $tabindex_for_null     tab index offset for null
     * @param int    $tabindex_for_value    tab index offset for value
     * @param string $table                 table
     * @param string $db                    database
     * @param int    $row_id                row id
     * @param array  $titles                titles
     * @param int    $biggest_max_file_size biggest max file size
     * @param string $text_dir              text direction
     * @param array  $repopulate            the data to be repopulated
     * @param array  $where_clause_array    the array of where clauses
     *
     * @return string
     */
    public function getHtmlForInsertEditRow(
        array $url_params,
        array $table_columns,
        array $comments_map,
        $timestamp_seen,
        $current_result,
        $chg_evt_handler,
        $jsvkey,
        $vkey,
        $insert_mode,
        array $current_row,
        &$o_rows,
        &$tabindex,
        $columns_cnt,
        $is_upload,
        $tabindex_for_function,
        array $foreigners,
        $tabindex_for_null,
        $tabindex_for_value,
        $table,
        $db,
        $row_id,
        array $titles,
        $biggest_max_file_size,
        $text_dir,
        array $repopulate,
        array $where_clause_array
    ) {
        $html_output = $this->getHeadAndFootOfInsertRowTable($url_params)
            . '<tbody>';

        //store the default value for CharEditing
        $default_char_editing  = $GLOBALS['cfg']['CharEditing'];
        $mime_map = Transformations::getMIME($db, $table);
        $where_clause = '';
        if (isset($where_clause_array[$row_id])) {
            $where_clause = $where_clause_array[$row_id];
        }
        for ($column_number = 0; $column_number < $columns_cnt; $column_number++) {
            $table_column = $table_columns[$column_number];
            $column_mime = array();
            if (isset($mime_map[$table_column['Field']])) {
                $column_mime = $mime_map[$table_column['Field']];
            }

            $virtual = [
                'VIRTUAL',
                'PERSISTENT',
                'VIRTUAL GENERATED',
                'STORED GENERATED',
            ];
            if (! in_array($table_column['Extra'], $virtual)) {
                $html_output .= $this->getHtmlForInsertEditFormColumn(
                    $table_columns,
                    $column_number,
                    $comments_map,
                    $timestamp_seen,
                    $current_result,
                    $chg_evt_handler,
                    $jsvkey,
                    $vkey,
                    $insert_mode,
                    $current_row,
                    $o_rows,
                    $tabindex,
                    $columns_cnt,
                    $is_upload,
                    $tabindex_for_function,
                    $foreigners,
                    $tabindex_for_null,
                    $tabindex_for_value,
                    $table,
                    $db,
                    $row_id,
                    $titles,
                    $biggest_max_file_size,
                    $default_char_editing,
                    $text_dir,
                    $repopulate,
                    $column_mime,
                    $where_clause
                );
            }
        } // end for
        $o_rows++;
        $html_output .= '  </tbody>'
            . '</table></div><br />'
            . '<div class="clearfloat"></div>';

        return $html_output;
    }

    /**
     * Returns whether the user has necessary insert/update privileges for the column
     *
     * @param array $table_column array of column details
     * @param bool  $insert_mode  whether on insert mode
     *
     * @return boolean whether user has necessary privileges
     */
    private function userHasColumnPrivileges(array $table_column, $insert_mode)
    {
        $privileges = $table_column['Privileges'];
        return ($insert_mode && strstr($privileges, 'insert') !== false)
            || (! $insert_mode && strstr($privileges, 'update') !== false);
    }
}
