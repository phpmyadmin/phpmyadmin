<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Handles Table search and Zoom search
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Class to handle normal-search
 * and zoom-search in a table
 *
 * @package PhpMyAdmin
 */
class PMA_TableSearch
{
    /**
     * Database name
     *
     * @access private
     * @var string
     */
    private $_db;
    /**
     * Table name
     *
     * @access private
     * @var string
     */
    private $_table;
    /**
     * Normal search or Zoom search
     *
     * @access private
     * @var string
     */
    private $_searchType;
    /**
     * Names of columns
     *
     * @access private
     * @var array
     */
    private $_columnNames;
    /**
     * Types of columns
     *
     * @access private
     * @var array
     */
    private $_columnTypes;
    /**
     * Collations of columns
     *
     * @access private
     * @var array
     */
    private $_columnCollations;
    /**
     * Null Flags of columns
     *
     * @access private
     * @var array
     */
    private $_columnNullFlags;
    /**
     * Whether a geometry column is present
     *
     * @access private
     * @var boolean
     */
    private $_geomColumnFlag;
    /**
     * Foreign Keys
     *
     * @access private
     * @var array
     */
    private $_foreigners;

    /**
     * Public Constructor
     *
     * @param string $db         Database name
     * @param string $table      Table name
     * @param string $searchType Whether normal or zoom search
     *
     * @return New PMA_TableSearch
     */
    public function __construct($db, $table, $searchType)
    {
        $this->_db = $db;
        $this->_table = $table;
        $this->_searchType = $searchType;
        $this->_columnNames = array();
        $this->_columnNullFlags = array();
        $this->_columnTypes = array();
        $this->_columnCollations = array();
        $this->_geomColumnFlag = false;
        $this->_foreigners = array();

        $this->_loadTableInfo($db, $table);
    }

    /**
     * Gets all the columns of a table along with their types, collations
     * and whether null or not.
     *
     * @return array Array containing the field list, field types, collations
     * and null constraint
     */
    private function _loadTableInfo()
    {
        // Gets the list and number of fields
        $columns = PMA_DBI_get_columns($this->_db, $this->_table, null, true);
        // Get details about the geometry fucntions
        $geom_types = PMA_getGISDatatypes();

        foreach ($columns as $key => $row) {
            // set column name
            $this->_columnNames[] = $row['Field'];

            $type = $row['Type'];
            // check whether table contains geometric columns
            if (in_array($type, $geom_types)) {
                $this->_geomColumnFlag = true;
            }
            // reformat mysql query output
            if (strncasecmp($type, 'set', 3) == 0
                || strncasecmp($type, 'enum', 4) == 0
            ) {
                $type = str_replace(',', ', ', $type);
            } else {
                // strip the "BINARY" attribute, except if we find "BINARY(" because
                // this would be a BINARY or VARBINARY field type
                if (! preg_match('@BINARY[\(]@i', $type)) {
                    $type = preg_replace('@BINARY@i', '', $type);
                }
                $type = preg_replace('@ZEROFILL@i', '', $type);
                $type = preg_replace('@UNSIGNED@i', '', $type);
                $type = strtolower($type);
            }
            if (empty($type)) {
                $type = '&nbsp;';
            }
            $this->_columnTypes[] = $type;
            $this->_columnNullFlags[] = $row['Null'];
            $this->_columnCollations[]
                = ! empty($row['Collation']) && $row['Collation'] != 'NULL'
                ? $row['Collation']
                : '';
        } // end for

        // Retrieve foreign keys
        $this->_foreigners = PMA_getForeigners($this->_db, $this->_table);
    }
}
?>
