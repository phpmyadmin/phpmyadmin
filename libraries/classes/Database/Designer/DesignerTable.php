<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Database\Designer\DesignerTable class
 *
 * @package PhpMyAdmin-Designer
 */
namespace PhpMyAdmin\Database\Designer;

use PhpMyAdmin\Util;

/**
 * Common functions for Designer
 *
 * @package PhpMyAdmin-Designer
 */
class DesignerTable
{
    private $tableName;
    private $databaseName;
    private $tableEngine;
    private $displayField;

    /**
     * Create a new DesignerTable
     *
     * @param string $databaseName The database name
     * @param string $tableName The table name
     * @param string $tableEngine The table engine
     * @param string|null $displayField The display field if available
     */
    public function __construct(
        $databaseName,
        $tableName,
        $tableEngine,
        $displayField
    ) {
        $this->databaseName = $databaseName;
        $this->tableName = $tableName;
        $this->tableEngine = $tableEngine;
        $this->displayField = $displayField;
    }

    /**
     * The table engine supports or not foreign keys
     *
     * @return bool
     */
    public function supportsForeignkeys() {
        return Util::isForeignKeySupported($this->tableEngine);
    }

    /**
     * Get the database name
     *
     * @return string
     */
    public function getDatabaseName() {
        return $this->databaseName;
    }

    /**
     * Get the table name
     *
     * @return string
     */
    public function getTableName() {
        return $this->tableName;
    }

    /**
     * Get the table engine
     *
     * @return string
     */
    public function getTableEngine() {
        return $this->tableEngine;
    }

    /**
     * Get the db and table speparated with a dot
     *
     * @return string
     */
    public function getDbTableString() {
        return $this->databaseName . '.' . $this->tableName;
    }
}
