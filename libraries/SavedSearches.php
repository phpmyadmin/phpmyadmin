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
     * Saved search name
     * @var string
     */
    private $_searchName = null;

    /**
     * Setter of searchName
     *
     * @param string $searchName Saved search name
     *
     * @return static
     */
    public function setSearchName($searchName)
    {
        $this->_searchName = $searchName;
        return $this;
    }

    /**
     * Getter of searchName
     *
     * @return string
     */
    public function getSearchName()
    {
        return $this->_searchName;
    }

    /**
     * JSON of saved search
     * @var string
     */
    private $_criterias = null;

    /**
     * Setter of config
     *
     * @param array $config Global configuration
     *
     * @return static
     */
    public function setConfig($config)
    {
        $this->_config = $config;
        return $this;
    }

    /**
     * Getter of config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Setter for criterias
     *
     * @param string $criterias JSON of saved searches
     *
     * @return static
     */
    public function setCriterias($criterias)
    {
        $this->_criterias = $criterias;
        return $this;
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
     * @param string $username Username
     *
     * @return static
     */
    public function setUsername($username)
    {
        $this->_username = $username;
        return $this;
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
     * @param string $dbname DB name
     *
     * @return static
     */
    public function setDbname($dbname)
    {
        $this->_dbname = $dbname;
        return $this;
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
            || null == $this->getSearchName()
            || null == $this->getCriterias()
        ) {
            //@todo Send an error.
            PMA_Util::mysqlDie(__('Missing information.'));
        }

        $savedSearchesTbl = PMA_Util::backquote($this->_config['cfgRelation']['db'])
            . "."
            . PMA_Util::backquote($this->_config['cfgRelation']['savedsearches']);
        $sqlQuery = "INSERT INTO " . $savedSearchesTbl
            . "(`username`, `db_name`, `search_name`, `search_data`)"
            . " VALUES ("
            . "'" . PMA_Util::sqlAddSlashes($this->getUsername()) . "',"
            . "'" . PMA_Util::sqlAddSlashes($this->getDbname()) . "',"
            . "'" . PMA_Util::sqlAddSlashes($this->getSearchName()) . "',"
            . "'" . PMA_Util::sqlAddSlashes($this->getCriterias())
            . "')";
        return (bool)PMA_queryAsControlUser($sqlQuery);
    }
}
