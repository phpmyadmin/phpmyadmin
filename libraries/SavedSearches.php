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
     * Id
     * @var int|null
     */
    private $_id = null;

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
     * Setter of id
     *
     * @param int|null $searchId Id of search
     *
     * @return static
     */
    public function setId($searchId)
    {
        $searchId = (int)$searchId;
        if (empty($searchId)) {
            $searchId = null;
        }

        $this->_id = $searchId;
        return $this;
    }

    /**
     * Getter of id
     *
     * @return int|null
     */
    public function getId()
    {
        return $this->_id;
    }

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
     * @param array $criterias Criterias of saved searches
     *
     * @return static
     */
    public function setCriterias($criterias)
    {
        $aListFieldsToGet = array(
            'criteriaColumn',
            'criteriaSort',
            'criteriaShow',
            'criteria',
            'criteriaAndOrRow',
            'criteriaAndOrColumn'
        );

        $data = array();

        $data['criteriaColumnCount'] = count($criterias['criteriaColumn']);
        $data['criteriaColumnAdd'] = count($criterias['criteriaAndOrRow']);

        foreach ($aListFieldsToGet as $field) {
            $data[$field] = $criterias[$field];
        }

        for ($i = 0; $i < $data['criteriaColumnAdd']; $i++) {
            $data['Or' . $i] = $criterias['Or' . $i];
        }

        $this->_criterias = json_encode($data);
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
     * Setter for DB name
     *
     * @param string $dbname DB name
     *
     * @return static
     */
    public function setDbname($dbname)
    {
        $this->_dbname = $dbname;
        return $this;
    }

    /**
     * Getter for DB name
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
    public function save()
    {
        if (null == $this->getUsername()
            || null == $this->getDbname()
            || null == $this->getSearchName()
            || null == $this->getCriterias()
        ) {
            PMA_Util::mysqlDie(__('Missing information to save the search.'));
        }

        $savedSearchesTbl
            = PMA_Util::backquote($this->_config['cfgRelation']['db']) . "."
            . PMA_Util::backquote($this->_config['cfgRelation']['savedsearches']);

        //If it's an insert.
        if (null === $this->getId()) {
            $wheres = array(
                "search_name = '" . PMA_Util::sqlAddSlashes($this->getSearchName())
                    . "'"
            );
            $existingSearches = $this->getList($wheres);

            if (!empty($existingSearches)) {
                PMA_Util::mysqlDie(__('An entry with this name already exists.'));
            }

            $sqlQuery = "INSERT INTO " . $savedSearchesTbl
                . "(`username`, `db_name`, `search_name`, `search_data`)"
                . " VALUES ("
                . "'" . PMA_Util::sqlAddSlashes($this->getUsername()) . "',"
                . "'" . PMA_Util::sqlAddSlashes($this->getDbname()) . "',"
                . "'" . PMA_Util::sqlAddSlashes($this->getSearchName()) . "',"
                . "'" . PMA_Util::sqlAddSlashes($this->getCriterias())
                . "')";

            $result = (bool)PMA_queryAsControlUser($sqlQuery);
            if (!$result) {
                return false;
            }

            $this->setId($GLOBALS['dbi']->insertId());

            return true;
        }

        //Else, it's an update.
        $wheres = array(
            "id != " . $this->getId(),
            "search_name = '" . PMA_Util::sqlAddSlashes($this->getSearchName()) . "'"
        );
        $existingSearches = $this->getList($wheres);

        if (!empty($existingSearches)) {
            PMA_Util::mysqlDie(__('An entry with this name already exists.'));
        }

        $sqlQuery = "UPDATE " . $savedSearchesTbl
            . "SET `search_name` = '"
            . PMA_Util::sqlAddSlashes($this->getSearchName()) . "', "
            . "`search_data` = '"
            . PMA_Util::sqlAddSlashes($this->getCriterias()) . "' "
            . "WHERE id = " . $this->getId();
        return (bool)PMA_queryAsControlUser($sqlQuery);
    }

    /**
     * Delete the search
     *
     * @return boolean
     */
    public function delete()
    {
        if (null == $this->getId()) {
            PMA_Util::mysqlDie(__('Missing information to delete the search.'));
        }

        $savedSearchesTbl
            = PMA_Util::backquote($this->_config['cfgRelation']['db']) . "."
            . PMA_Util::backquote($this->_config['cfgRelation']['savedsearches']);

        $sqlQuery = "DELETE FROM " . $savedSearchesTbl
            . "WHERE id = '" . PMA_Util::sqlAddSlashes($this->getId()) . "'";

        return (bool)PMA_queryAsControlUser($sqlQuery);
    }

    /**
     * Load the current search from an id.
     *
     * @return bool Success
     */
    public function load()
    {
        if (null == $this->getId()) {
            PMA_Util::mysqlDie(__('Missing information to load the search.'));
        }

        $savedSearchesTbl = PMA_Util::backquote($this->_config['cfgRelation']['db'])
            . "."
            . PMA_Util::backquote($this->_config['cfgRelation']['savedsearches']);
        $sqlQuery = "SELECT id, search_name, search_data "
            . "FROM " . $savedSearchesTbl . " "
            . "WHERE id = '" . PMA_Util::sqlAddSlashes($this->getId()) . "' ";

        $resList = PMA_queryAsControlUser($sqlQuery);

        if (false === ($oneResult = $GLOBALS['dbi']->fetchArray($resList))) {
            PMA_Util::mysqlDie(__('Error while loading the search.'));
        }

        $this->setSearchName($oneResult['search_name'])
            ->setCriterias($oneResult['search_data']);

        return true;
    }

    /**
     * Get the list of saved search of a user on a DB
     *
     * @param array $wheres List of filters
     *
     * @return array|bool List of saved search or false on failure
     */
    public function getList(array $wheres = array())
    {
        if (null == $this->getUsername()
            || null == $this->getDbname()
        ) {
            return false;
        }

        $savedSearchesTbl = PMA_Util::backquote($this->_config['cfgRelation']['db'])
            . "."
            . PMA_Util::backquote($this->_config['cfgRelation']['savedsearches']);
        $sqlQuery = "SELECT id, search_name "
            . "FROM " . $savedSearchesTbl . " "
            . "WHERE "
            . "username = '" . PMA_Util::sqlAddSlashes($this->getUsername()) . "' "
            . "AND db_name = '" . PMA_Util::sqlAddSlashes($this->getDbname()) . "' ";

        foreach ($wheres as $where) {
            $sqlQuery .= "AND " . $where . " ";
        }

        $sqlQuery .= "order by search_name ASC ";

        $resList = PMA_queryAsControlUser($sqlQuery);

        $list = array();
        while ($oneResult = $GLOBALS['dbi']->fetchArray($resList)) {
            $list[$oneResult['id']] = $oneResult['search_name'];
        }

        return $list;
    }
}
