<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Query\Utilities;

use function array_merge;
use function is_array;
use function is_string;
use function preg_match;
use function sort;
use function strlen;
use function strtr;
use function usort;

/**
 * handles database lists
 *
 * <code>
 * $ListDatabase = new ListDatabase();
 * </code>
 *
 * @todo this object should be attached to the PMA_Server object
 */
class ListDatabase extends ListAbstract
{
    public function __construct()
    {
        parent::__construct();

        $checkUserPrivileges = new CheckUserPrivileges(DatabaseInterface::getInstance());
        $checkUserPrivileges->getPrivileges();

        $this->build();
    }

    /** @return array<int, array<string, bool|string>> */
    public function getList(): array
    {
        $selected = $this->getDefault();

        $list = [];
        foreach ($this as $eachItem) {
            if (Utilities::isSystemSchema($eachItem)) {
                continue;
            }

            $list[] = ['name' => $eachItem, 'is_selected' => $selected === $eachItem];
        }

        return $list;
    }

    /**
     * checks if the configuration wants to hide some databases
     */
    protected function checkHideDatabase(): void
    {
        $config = Config::getInstance();
        if (empty($config->selectedServer['hide_db'])) {
            return;
        }

        foreach ($this->getArrayCopy() as $key => $db) {
            if (! preg_match('/' . $config->selectedServer['hide_db'] . '/', $db)) {
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
    protected function retrieve(string|null $likeDbName = null): array
    {
        $databaseList = [];
        $command = '';
        $config = Config::getInstance();
        if (! $config->selectedServer['DisableIS']) {
            $command .= 'SELECT `SCHEMA_NAME` FROM `INFORMATION_SCHEMA`.`SCHEMATA`';
            if ($likeDbName !== null) {
                $command .= " WHERE `SCHEMA_NAME` LIKE '" . $likeDbName . "'";
            }
        } elseif ($GLOBALS['dbs_to_test'] === false || $likeDbName !== null) {
            $command .= 'SHOW DATABASES';
            if ($likeDbName !== null) {
                $command .= " LIKE '" . $likeDbName . "'";
            }
        } else {
            foreach ($GLOBALS['dbs_to_test'] as $db) {
                $databaseList = array_merge(
                    $databaseList,
                    $this->retrieve($db),
                );
            }
        }

        if ($command !== '') {
            $databaseList = DatabaseInterface::getInstance()->fetchResult($command);
        }

        if ($config->settings['NaturalOrder']) {
            usort($databaseList, 'strnatcasecmp');
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
    public function build(): void
    {
        if (! $this->checkOnlyDatabase()) {
            $items = $this->retrieve();
            $this->exchangeArray($items);
        }

        $this->checkHideDatabase();
    }

    /**
     * checks the only_db configuration
     */
    protected function checkOnlyDatabase(): bool
    {
        $config = Config::getInstance();
        if (is_string($config->selectedServer['only_db']) && strlen($config->selectedServer['only_db']) > 0) {
            $config->selectedServer['only_db'] = [$config->selectedServer['only_db']];
        }

        if (! is_array($config->selectedServer['only_db'])) {
            return false;
        }

        $items = [];

        foreach ($config->selectedServer['only_db'] as $eachOnlyDb) {
            // check if the db name contains wildcard,
            // thus containing not escaped _ or %
            if (! preg_match('/(^|[^\\\\])(_|%)/', $eachOnlyDb)) {
                // ... not contains wildcard
                $items[] = strtr($eachOnlyDb, ['\\\\' => '\\', '\\_' => '_', '\\%' => '%']);
                continue;
            }

            $items = array_merge($items, $this->retrieve($eachOnlyDb));
        }

        $this->exchangeArray($items);

        return true;
    }

    /**
     * returns default item
     *
     * @return string default item
     */
    public function getDefault(): string
    {
        if (strlen($GLOBALS['db']) > 0) {
            return $GLOBALS['db'];
        }

        return parent::getDefault();
    }
}
