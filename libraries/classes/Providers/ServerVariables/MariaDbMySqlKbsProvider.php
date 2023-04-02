<?php

declare(strict_types=1);

namespace PhpMyAdmin\Providers\ServerVariables;

use Williamdes\MariaDBMySQLKBS\KBException;
use Williamdes\MariaDBMySQLKBS\Search as KBSearch;

class MariaDbMySqlKbsProvider implements ServerVariablesProviderInterface
{
    public function getVariableType(string $name): string|null
    {
        try {
            return KBSearch::getVariableType($name);
        } catch (KBException) {
            return null;
        }
    }

    /** @return mixed[] */
    public function getStaticVariables(): array
    {
        return [];
    }

    public function getDocLinkByNameMariaDb(string $name): string|null
    {
        try {
            return KBSearch::getByName($name, KBSearch::MARIADB);
        } catch (KBException) {
            return null;
        }
    }

    public function getDocLinkByNameMysql(string $name): string|null
    {
        try {
            return KBSearch::getByName($name, KBSearch::MYSQL);
        } catch (KBException) {
            return null;
        }
    }
}
