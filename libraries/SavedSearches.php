<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Saved searches managing
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Saved searches managing
 *
 * @package PhpMyAdmin
 */
class PMA_SavedSearches
{
    /**
     * Global configuration
     * @var array
     */
    private $_config = null;

    /**
     * Username
     * @var string
     */
    private $_username = null;

    /**
     * DB name
     * @var string
     */
    private $_dbname = null;

    /**
     * JSON of saved search
     * @var string
     */
    private $_criterias = null;

    public function setConfig($config)
    {
        $this->_config = $config;
    }

    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Setter for criterias
     *
     * @param string $criterias JSON of saved searches
     *
     * @return void
     */
    public function setCriterias($criterias)
    {
        $this->_criterias = $criterias;
    }

    /**
     * Getter for criterias
     *
     * @return string
     */
    public function getCriterias()
    {
        return $this->_criterias;
    }

    /**
     * Setter for username
     *
     * @param string $username
     *
     * @return void
     */
    public function setUsername($username)
    {
        $this->_username = $username;
    }

    /**
     * Getter for username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->_username;
    }

    /**
     * Setter for DB name
     *
     * @param string $dbname
     *
     * @return void
     */
    public function setDbname($dbname)
    {
        $this->_dbname = $dbname;
    }

    /**
     * Getter for DB name
     *
     * @return string
     */
    public function getDbname()
    {
        return $this->_dbname;
    }

    /**
     * Public constructor
     *
     * @param array $config Global configuration
     */
    public function __construct($config)
    {
        $this->setConfig($config);
    }

    /**
     * Save the search
     *
     * @return boolean
     */
    public function saveSearch()
    {
        if (null == $this->getUsername()
            || null == $this->getDbname()
            || null == $this->getCriterias()
        ) {
            //@todo Send an error.
            return false;
        }

        $savedSearchesTable = PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
            . "." . PMA_Util::backquote($GLOBALS['cfgRelation']['savedsearches']);
        $sqlQuery = "INSERT INTO " . $savedSearchesTable
            . "(`username`, `db_name`, `config_data`)"
            . " VALUES ("
            . "'" . PMA_Util::sqlAddSlashes($this->getUsername()) . "',"
            . "'" . PMA_Util::sqlAddSlashes($this->getDbname()) . "',"
            . "'" . PMA_Util::sqlAddSlashes($this->getCriterias())
            . "')";
        return (bool)PMA_queryAsControlUser($sqlQuery, false);
    }
}
