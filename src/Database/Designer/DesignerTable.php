<?php

declare(strict_types=1);

namespace PhpMyAdmin\Database\Designer;

use PhpMyAdmin\Utils\ForeignKey;

readonly class DesignerTable
{
    /**
     * @param string      $databaseName The database name
     * @param string      $tableName    The table name
     * @param string      $tableEngine  The table engine
     * @param string|null $displayField The display field if available
     */
    public function __construct(
        public string $databaseName,
        public string $tableName,
        private string $tableEngine,
        public string|null $displayField,
    ) {
    }

    public function supportsForeignkeys(): bool
    {
        return ForeignKey::isSupported($this->tableEngine);
    }

    /**
     * Get the db and table separated with a dot
     */
    public function getDbTableString(): string
    {
        return $this->databaseName . '.' . $this->tableName;
    }
}
