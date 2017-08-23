<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles DB Multi-table query
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Template;
/**
 * Class to handle database Multi-table querying
 *
 * @package PhpMyAdmin
 */
class DbMultiTableQuery
{
    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $_db;

    /**
     * Default no. of columns
     *
     * @access private
     * @var integer
     */
    private $_default_no_of_columns;

    public function __construct ($db_name)
    {
        $this->_db = $db_name;
        $this->_default_no_of_columns = 3;
    }

    private function getColumnsHTML()
    {
        $tables = $GLOBALS['dbi']->getTables($this->_db);
        $html_output = '<fieldset>';
        for ($i = 0; $i < count($tables); $i++)
        {
            $html_output .= '<div style="display:none" id="' . md5($tables[$i]) . '">';
            $table_fields = $GLOBALS['dbi']->getColumns($this->_db, $tables[$i]);
            $html_output .= '<option value="*"> * </option>';
            foreach ($table_fields as $key => $value)
            {
                $html_output .= '<option value="' . $key  . '">' . $key . '</option>';
            }
            $html_output .= '</div>';
        }

        $html_output .= '<div style="display:none" id="new_column_layout">';
        $html_output .= Template::get('database/multi_table_query/new_column')->render(array(
            'id'        => 0,
            'tables'    => $tables
        ));
        $html_output .= '</div>';

        for ($i = 1; $i <= $this->_default_no_of_columns; $i++)
        {
            $html_output .= Template::get('database/multi_table_query/new_column')->render(array(
                'id'        => $i,
                'tables'    => $tables
            ));
        }
        $html_output .= '<fieldset style="display:inline">';
        $html_output .= '<input type="button" value="+ Add column" id="add_column_button">';
        $html_output .= '<br> &nbsp;';
        $html_output .= '</fieldset>';

        $html_output .= '<fieldset>';
        $html_output .= '<textarea cols="80" rows="4" style="float:left" name="sql_query" id="MultiSqlquery" dir="ltr"> </textarea>';
        $html_output .= '</fieldset>';

        $html_output .= '</fieldset>';

        return $html_output;
    }

    public function getFormHTML()
    {
        $html_output = Util::getDivForSliderEffect('query_div', __('Query window'), 'open');
        $html_output .= '<form action="" id="query_form">';

        $html_output .= '<input type="hidden" id="db_name" value="' . $this->_db  . '">';
        $html_output .= $this->getColumnsHTML();

        $html_output .= '<fieldset class="tblFooters">';
        $html_output .= '<input type="button" id="update_query_button" value="Update query">';
        $html_output .= '<input type="button" id="submit_query" value="Submit query">';
        $html_output .= '</fieldset>';

        $html_output .= '</form>';
        $html_output .= '</div>';
        $html_output .= '<div id="sql_results"></div>';

        return $html_output;
    }
}
