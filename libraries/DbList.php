<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * holds the DbList class
 *
 * @package PhpMyAdmin
 *
 */
namespace PMA\libraries;

/**
 * holds the DbList class
 *
 * @package PhpMyAdmin
 *
 * @property object $userlink
 * @property object $controllink
 */
class DbList
{
    /**
     * Holds database list
     *
     * @var ListDatabase
     */
    protected $databases = null;

    /**
     * DBMS user link
     *
     * @var object
     */
    protected $userlink = null;

    /**
     * DBMS control link
     *
     * @var object
     */
    protected $controllink = null;

    /**
     * magic access to protected/inaccessible members/properties
     *
     * @param string $param parameter name
     *
     * @return mixed
     * @see https://php.net/language.oop5.overloading
     */
    public function __get($param)
    {
        switch ($param) {
        case 'databases' :
            return $this->getDatabaseList();
        case 'userlink' :
            return $this->userlink;
        case 'controllink' :
            return $this->controllink;
        }

        return null;
    }

    /**
     * magic access to protected/inaccessible members/properties
     *
     * @param string $param parameter name
     * @param mixed  $value value to set
     *
     * @return void
     * @see https://php.net/language.oop5.overloading
     */
    public function __set($param, $value)
    {
        switch ($param) {
        case 'userlink' :
            $this->userlink = $value;
            break;
        case 'controllink' :
            $this->controllink = $value;
            break;
        }
    }

    /**
     * Accessor to PMA::$databases
     *
     * @return ListDatabase
     */
    public function getDatabaseList()
    {
        if (null === $this->databases) {
            $this->databases = new ListDatabase(
                $this->userlink
            );
        }

        return $this->databases;
    }
}
