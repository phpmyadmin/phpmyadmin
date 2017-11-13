<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles DB Multi-table query
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Database;

use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

/**
 * Class to handle database Multi-table querying
 *
 * @package PhpMyAdmin
 */
class MultiTableQuery
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
        return Template::get('database/multi_table_query/columns')->render([
            'tables' => $tables,
            'dbi' => $GLOBALS['dbi'],
            'db' => $this->_db,
            'default_no_of_columns' => $this->_default_no_of_columns,
        ]);
    }

    public function getFormHTML()
    {
        $html_output = Util::getDivForSliderEffect('query_div', __('Query window'), 'open');
        $html_output .= '<form action="" id="query_form">';

        $html_output .= '<input type="hidden" id="db_name" value="' . $this->_db  . '">';
        $html_output .= $this->getColumnsHTML();

        $html_output .= '<fieldset class="tblFooters">';
        $html_output .= '<input type="button" id="update_query_button" value="' . __('Update query') . '">';
        $html_output .= '<input type="button" id="submit_query" value="' . __('Submit query') . '">';
        $html_output .= '</fieldset>';

        $html_output .= '</form>';
        $html_output .= '</div>';
        $html_output .= '<div id="sql_results"></div>';

        return $html_output;
    }
}
