<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/** @psalm-immutable */
final class TrackingFeature
{
    public DatabaseName $database;

    public TableName $tracking;

    public function __construct(DatabaseName $database, TableName $tracking)
    {
        $this->database = $database;
        $this->tracking = $tracking;
    }
}
