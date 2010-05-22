<?php
/**
 * Enter description here...
 * @package phpMyAdmin
 *
 */

/**
 * Database listing.
 */
require_once './libraries/List_Database.class.php';

/**
 * phpMyAdmin main Controller
 *
 *
 *
 * @package phpMyAdmin
 */
class PMA
{
    /**
     * Holds database list
     *
     * @var PMA_List_Database
     */
    protected $databases = null;

    /**
     * DBMS user link
     *
     * @var resource
     */
    protected $userlink = null;

    /**
     * DBMS control link
     *
     * @var resource
     */
    protected $controllink = null;

    /**
     * magic access to protected/inaccessible members/properties
     *
     * @see http://php.net/language.oop5.overloading
     */
    public function __get($param)
    {
        switch ($param) {
            case 'databases' :
                return $this->getDatabaseList();
                break;
            case 'userlink' :
                return $this->userlink;
                break;
            case 'controllink' :
                return $this->controllink;
                break;
        }

        return null;
    }

    /**
     * magic access to protected/inaccessible members/properties
     *
     * @see http://php.net/language.oop5.overloading
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
     * @uses    PMA::$databases
     * @uses    PMA::$userlink
     * @uses    PMA::$controllink
     * @uses    PMA_List_Database
     * @return PMA_List_Databases
     */
    public function getDatabaseList()
    {
        if (null === $this->databases) {
            $this->databases = new PMA_List_Database($this->userlink, $this->controllink);
        }

        return $this->databases;
    }
}
?>
