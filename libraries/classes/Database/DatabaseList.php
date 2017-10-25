<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * holds the PhpMyAdmin\Database\DatabaseList class
 *
 * @package PhpMyAdmin
 *
 */
namespace PhpMyAdmin\Database;

use PhpMyAdmin\ListDatabase;

/**
 * holds the DatabaseList class
 *
 * @package PhpMyAdmin
 */
class DatabaseList
{
    /**
     * Holds database list
     *
     * @var ListDatabase
     */
    protected $databases = null;

    /**
     * magic access to protected/inaccessible members/properties
     *
     * @param string $param parameter name
     *
     * @return mixed
     * @see https://secure.php.net/language.oop5.overloading
     */
    public function __get($param)
    {
        switch ($param) {
        case 'databases' :
            return $this->getDatabaseList();
        }

        return null;
    }

    /**
     * Accessor to PMA::$databases
     *
     * @return ListDatabase
     */
    public function getDatabaseList()
    {
        if (null === $this->databases) {
            $this->databases = new ListDatabase();
        }

        return $this->databases;
    }
}
