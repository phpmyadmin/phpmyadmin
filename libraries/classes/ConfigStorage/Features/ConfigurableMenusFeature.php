<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/** @psalm-immutable */
final class ConfigurableMenusFeature
{
    public function __construct(public DatabaseName $database, public TableName $userGroups, public TableName $users)
    {
    }
}
