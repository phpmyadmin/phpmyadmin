<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles Database Search
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Class to handle database search
 *
 * @package PhpMyAdmin
 */
class PMA_DbSearch
{
    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $_db;
    /**
     * Table Names
     *
     * @access private
     * @var array
     */
    private $_tables_names_only;
    /**
     * Type of search
     *
     * @access private
     * @var array
     */
    private $_searchTypes;
    /**
     * Already set search type
     *
     * @access private
     * @var integer
     */
    private $_criteriaSearchType;
    /**
     * Already set search type's description
     *
     * @access private
     * @var string
     */
    private $_searchTypeDescription;
    /**
     * Search string/regexp
     *
     * @access private
     * @var string
     */
    private $_criteriaSearchString;
    /**
     * Criteria Tables to search in
     *
     * @access private
     * @var array
     */
    private $_criteriaTables;
    /**
     * Column names to search for
     *
     * @access private
     * @var string
     */
    private $_criteriaColumnName;

    /**
     * Public Constructor
     *
     * @param string $db         Database name
     *
     */
    public function __construct($db)
    {
        $this->_db = $db;
        // Sets criteria parameters
        $this->_setSearchParams();
    }

    /**
     * Set CommmonFunctions
     * 
     * @param PMA_CommonFunctions $commonFunctions
     * 
     * @return void
     */
    public function setCommonFunctions(PMA_CommonFunctions $commonFunctions)
    {
        $this->_common_functions = $commonFunctions;
    }
    
    
    /**
     * Get CommmonFunctions
     * 
     * @return CommonFunctions object
     */
    public function getCommonFunctions()
    {
        if (is_null($this->_common_functions)) {
            $this->_common_functions = PMA_CommonFunctions::getInstance();
        }
        return $this->_common_functions;
    }

    /**
     * Sets search parameters
     *
     */
    private function _setSearchParams()
    {
        $this->_tables_names_only = PMA_DBI_get_tables($this->_db);

        $this->_searchTypes = array(
            '1' => __('at least one of the words'),
            '2' => __('all words'),
            '3' => __('the exact phrase'),
            '4' => __('as regular expression'),
        );

        if (empty($_REQUEST['criteriaSearchType'])
            || ! is_string($_REQUEST['criteriaSearchType'])
            || ! array_key_exists($_REQUEST['criteriaSearchType'], $searchTypes)
        ) {
            $this->_criteriaSearchType = 1;
            unset($_REQUEST['submit_search']);
        } else {
            $this->_criteriaSearchType = (int) $_REQUEST['criteriaSearchType'];
            $this->_searchTypeDescription = $this->_searchTypes[$_REQUEST['criteriaSearchType']];
        }

        if (empty($_REQUEST['criteriaSearchString'])
            || ! is_string($_REQUEST['criteriaSearchString'])
        ) {
            $this->_criteriaSearchString = '';
            unset($_REQUEST['submit_search']);
        } else {
            $this->_criteriaSearchString = $_REQUEST['criteriaSearchString'];
        }

        $this->_criteriaTables = array();
        if (empty($_REQUEST['criteriaTables']) || ! is_array($_REQUEST['criteriaTables'])) {
            unset($_REQUEST['submit_search']);
        } elseif (! isset($_REQUEST['selectall']) && ! isset($_REQUEST['unselectall'])) {
            $this->_criteriaTables = array_intersect(
                $_REQUEST['criteriaTables'], $this->_tables_names_only
            );
        }

        if (isset($_REQUEST['selectall'])) {
            $this->_criteriaTables = $this->_tables_names_only;
        } elseif (isset($_REQUEST['unselectall'])) {
            $this->_criteriaTables = array();
        }

        if (empty($_REQUEST['criteriaColumnName'])
            || ! is_string($_REQUEST['criteriaColumnName'])
        ) {
            unset($this->_criteriaColumnName);
        } else {
            $this->_criteriaColumnName = $common_functions->sqlAddSlashes(
                $_REQUEST['criteriaColumnName'], true
            );
        }
    }
}
