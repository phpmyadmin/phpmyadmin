<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/** @psalm-immutable */
final class SavedQueryByExampleSearchesFeature
{
    public DatabaseName $database;

    public TableName $savedSearches;

    public function __construct(DatabaseName $database, TableName $savedSearches)
    {
        $this->database = $database;
        $this->savedSearches = $savedSearches;
    }
}
