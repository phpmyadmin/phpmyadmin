<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Saved searches managing
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * Saved searches managing
 *
 * @package PhpMyAdmin
 */
class SavedSearches
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
     * Criterias
     * @var array
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
     * @param array|string $criterias Criterias of saved searches
     * @param bool         $json      Criterias are in JSON format
     *
     * @return static
     */
    public function setCriterias($criterias, $json = false)
    {
        if (true === $json && is_string($criterias)) {
            $this->_criterias = json_decode($criterias, true);
            return $this;
        }

        $aListFieldsToGet = array(
            'criteriaColumn',
            'criteriaSort',
            'criteriaShow',
            'criteria',
            'criteriaAndOrRow',
            'criteriaAndOrColumn',
            'rows',
            'TableList'
        );

        $data = array();

        $data['criteriaColumnCount'] = count($criterias['criteriaColumn']);

        foreach ($aListFieldsToGet as $field) {
            if (isset($criterias[$field])) {
                $data[$field] = $criterias[$field];
            }
        }

        for ($i = 0; $i <= $data['rows']; $i++) {
            $data['Or' . $i] = $criterias['Or' . $i];
        }

        $this->_criterias = $data;
        return $this;
    }

    /**
     * Getter for criterias
     *
     * @return array
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
        if (null == $this->getSearchName()) {
            $message = Message::error(
                __('Please provide a name for this bookmarked search.')
            );
            $response = Response::getInstance();
            $response->setRequestStatus($message->isSuccess());
            $response->addJSON('fieldWithError', 'searchName');
            $response->addJSON('message', $message);
            exit;
        }

        if (null == $this->getUsername()
            || null == $this->getDbname()
            || null == $this->getSearchName()
            || null == $this->getCriterias()
        ) {
            $message = Message::error(
                __('Missing information to save the bookmarked search.')
            );
            $response = Response::getInstance();
            $response->setRequestStatus($message->isSuccess());
            $response->addJSON('message', $message);
            exit;
        }

        $savedSearchesTbl
            = Util::backquote($this->_config['cfgRelation']['db']) . "."
            . Util::backquote($this->_config['cfgRelation']['savedsearches']);

        //If it's an insert.
        if (null === $this->getId()) {
            $wheres = array(
                "search_name = '" . Util::sqlAddSlashes($this->getSearchName())
                . "'"
            );
            $existingSearches = $this->getList($wheres);

            if (!empty($existingSearches)) {
                $message = Message::error(
                    __('An entry with this name already exists.')
                );
                $response = Response::getInstance();
                $response->setRequestStatus($message->isSuccess());
                $response->addJSON('fieldWithError', 'searchName');
                $response->addJSON('message', $message);
                exit;
            }

            $sqlQuery = "INSERT INTO " . $savedSearchesTbl
                . "(`username`, `db_name`, `search_name`, `search_data`)"
                . " VALUES ("
                . "'" . Util::sqlAddSlashes($this->getUsername()) . "',"
                . "'" . Util::sqlAddSlashes($this->getDbname()) . "',"
                . "'" . Util::sqlAddSlashes($this->getSearchName()) . "',"
                . "'" . Util::sqlAddSlashes(json_encode($this->getCriterias()))
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
            "search_name = '" . Util::sqlAddSlashes($this->getSearchName()) . "'"
        );
        $existingSearches = $this->getList($wheres);

        if (!empty($existingSearches)) {
            $message = Message::error(
                __('An entry with this name already exists.')
            );
            $response = Response::getInstance();
            $response->setRequestStatus($message->isSuccess());
            $response->addJSON('fieldWithError', 'searchName');
            $response->addJSON('message', $message);
            exit;
        }

        $sqlQuery = "UPDATE " . $savedSearchesTbl
            . "SET `search_name` = '"
            . Util::sqlAddSlashes($this->getSearchName()) . "', "
            . "`search_data` = '"
            . Util::sqlAddSlashes(json_encode($this->getCriterias())) . "' "
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
            $message = Message::error(
                __('Missing information to delete the search.')
            );
            $response = Response::getInstance();
            $response->setRequestStatus($message->isSuccess());
            $response->addJSON('fieldWithError', 'searchId');
            $response->addJSON('message', $message);
            exit;
        }

        $savedSearchesTbl
            = Util::backquote($this->_config['cfgRelation']['db']) . "."
            . Util::backquote($this->_config['cfgRelation']['savedsearches']);

        $sqlQuery = "DELETE FROM " . $savedSearchesTbl
            . "WHERE id = '" . Util::sqlAddSlashes($this->getId()) . "'";

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
            $message = Message::error(
                __('Missing information to load the search.')
            );
            $response = Response::getInstance();
            $response->setRequestStatus($message->isSuccess());
            $response->addJSON('fieldWithError', 'searchId');
            $response->addJSON('message', $message);
            exit;
        }

        $savedSearchesTbl = Util::backquote($this->_config['cfgRelation']['db'])
            . "."
            . Util::backquote($this->_config['cfgRelation']['savedsearches']);
        $sqlQuery = "SELECT id, search_name, search_data "
            . "FROM " . $savedSearchesTbl . " "
            . "WHERE id = '" . Util::sqlAddSlashes($this->getId()) . "' ";

        $resList = PMA_queryAsControlUser($sqlQuery);

        if (false === ($oneResult = $GLOBALS['dbi']->fetchArray($resList))) {
            $message = Message::error(__('Error while loading the search.'));
            $response = Response::getInstance();
            $response->setRequestStatus($message->isSuccess());
            $response->addJSON('fieldWithError', 'searchId');
            $response->addJSON('message', $message);
            exit;
        }

        $this->setSearchName($oneResult['search_name'])
            ->setCriterias($oneResult['search_data'], true);

        return true;
    }

    /**
     * Get the list of saved searches of a user on a DB
     *
     * @param string[] $wheres List of filters
     *
     * @return array List of saved searches or empty array on failure
     */
    public function getList(array $wheres = array())
    {
        if (null == $this->getUsername()
            || null == $this->getDbname()
        ) {
            return array();
        }

        $savedSearchesTbl = Util::backquote($this->_config['cfgRelation']['db'])
            . "."
            . Util::backquote($this->_config['cfgRelation']['savedsearches']);
        $sqlQuery = "SELECT id, search_name "
            . "FROM " . $savedSearchesTbl . " "
            . "WHERE "
            . "username = '" . Util::sqlAddSlashes($this->getUsername()) . "' "
            . "AND db_name = '" . Util::sqlAddSlashes($this->getDbname()) . "' ";

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
