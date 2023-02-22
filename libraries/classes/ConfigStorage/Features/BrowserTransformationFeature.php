<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/** @psalm-immutable */
final class BrowserTransformationFeature
{
    public DatabaseName $database;

    public TableName $columnInfo;

    public function __construct(DatabaseName $database, TableName $columnInfo)
    {
        $this->database = $database;
        $this->columnInfo = $columnInfo;
    }
}
