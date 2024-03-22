<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use ArrayObject;
use PhpMyAdmin\Query\Utilities;

use function array_merge;
use function in_array;
use function is_array;
use function is_string;
use function preg_match;
use function sort;
use function strnatcasecmp;
use function strtr;
use function usort;

/**
 * Handles database lists
 *
 * @extends ArrayObject<int, string>
 */
class ListDatabase extends ArrayObject
{
    public function __construct(
        private readonly DatabaseInterface $dbi,
        private readonly Config $config,
        private readonly UserPrivilegesFactory $userPrivilegesFactory,
    ) {
        parent::__construct();

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $this->build($userPrivileges);
    }

    /** @return array<int, array<string, bool|string>> */
    public function getList(): array
    {
        $list = [];
        foreach ($this as $eachItem) {
            if (Utilities::isSystemSchema($eachItem)) {
                continue;
            }

            $list[] = ['name' => $eachItem, 'is_selected' => $eachItem === Current::$database];
        }

        return $list;
    }

    /**
     * checks if the configuration wants to hide some databases
     */
    protected function checkHideDatabase(): void
    {
        if (empty($this->config->selectedServer['hide_db'])) {
            return;
        }

        foreach ($this->getArrayCopy() as $key => $db) {
            if (! preg_match('/' . $this->config->selectedServer['hide_db'] . '/', $db)) {
                continue;
            }

            $this->offsetUnset($key);
        }
    }

    /**
     * retrieves database list from server
     *
     * @param string|null $likeDbName usually a db_name containing wildcards
     *
     * @return mixed[]
     */
    protected function retrieve(UserPrivileges $userPrivileges, string|null $likeDbName = null): array
    {
        $databaseList = [];
        $command = '';
        if (! $this->config->selectedServer['DisableIS']) {
            $command .= 'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`';
            if ($likeDbName !== null) {
                $command .= " WHERE `SCHEMA_NAME` LIKE '" . $likeDbName . "'";
            }
        } elseif ($userPrivileges->databasesToTest === false || $likeDbName !== null) {
            $command .= 'SHOW DATABASES';
            if ($likeDbName !== null) {
                $command .= " LIKE '" . $likeDbName . "'";
            }
        } else {
            foreach ($userPrivileges->databasesToTest as $db) {
                $databaseList = array_merge(
                    $databaseList,
                    $this->retrieve($userPrivileges, $db),
                );
            }
        }

        if ($command !== '') {
            $databaseList = $this->dbi->fetchResult($command);
        }

        if ($this->config->settings['NaturalOrder']) {
            usort($databaseList, strnatcasecmp(...));
        } else {
            // need to sort anyway, otherwise information_schema
            // goes at the top
            sort($databaseList);
        }

        return $databaseList;
    }

    /**
     * builds up the list
     */
    public function build(UserPrivileges $userPrivileges): void
    {
        if (! $this->checkOnlyDatabase($userPrivileges)) {
            $items = $this->retrieve($userPrivileges);
            $this->exchangeArray($items);
        }

        $this->checkHideDatabase();
    }

    /**
     * checks the only_db configuration
     */
    protected function checkOnlyDatabase(UserPrivileges $userPrivileges): bool
    {
        if (
            is_string($this->config->selectedServer['only_db']) && $this->config->selectedServer['only_db'] !== ''
        ) {
            $this->config->selectedServer['only_db'] = [$this->config->selectedServer['only_db']];
        }

        if (! is_array($this->config->selectedServer['only_db'])) {
            return false;
        }

        $items = [];

        foreach ($this->config->selectedServer['only_db'] as $eachOnlyDb) {
            // check if the db name contains wildcard,
            // thus containing not escaped _ or %
            if (! preg_match('/(^|[^\\\\])(_|%)/', $eachOnlyDb)) {
                // ... not contains wildcard
                $items[] = strtr($eachOnlyDb, ['\\\\' => '\\', '\\_' => '_', '\\%' => '%']);
                continue;
            }

            $items = array_merge($items, $this->retrieve($userPrivileges, $eachOnlyDb));
        }

        $this->exchangeArray($items);

        return true;
    }

    /**
     * Checks if the given strings exists in the current list, if there is
     * missing at least one item it returns false otherwise true
     */
    public function exists(string ...$params): bool
    {
        $elements = $this->getArrayCopy();
        foreach ($params as $param) {
            if (! in_array($param, $elements, true)) {
                return false;
            }
        }

        return true;
    }
}
