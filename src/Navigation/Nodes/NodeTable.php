<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\Util;

use function __;
use function array_slice;

/**
 * Represents a columns node in the navigation tree
 */
class NodeTable extends NodeDatabaseChild
{
    /**
     * For the second IMG tag, used when rendering the node.
     */
    public Icon|null $secondIcon = null;

    /** @param string $name An identifier for the new node */
    public function __construct(Config $config, string $name)
    {
        parent::__construct($config, $name);

        $icon = $this->addIcon(
            $this->config->settings['NavigationTreeDefaultTabTable'],
            ['db' => null, 'table' => null],
        );
        if ($icon !== null) {
            $this->icon = $icon;
        }

        $this->secondIcon = $this->addIcon(
            $this->config->settings['NavigationTreeDefaultTabTable2'],
            ['db' => null, 'table' => null],
        );

        $this->link = new Link(
            Util::getTitleForTarget($this->config->config->DefaultTabTable),
            $this->config->config->DefaultTabTable,
            ['pos' => 0, 'db' => null, 'table' => null],
        );
        $this->classes = 'nav_node_table';
        $this->urlParamName = 'table';
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('columns' or 'indexes')
     * @param string $searchClause A string used to filter the results of the query
     */
    public function getPresence(UserPrivileges $userPrivileges, string $type = '', string $searchClause = ''): int
    {
        $retval = 0;
        $db = $this->getRealParent()->realName;
        $table = $this->realName;
        $dbi = DatabaseInterface::getInstance();
        switch ($type) {
            case 'columns':
                if (! $this->config->selectedServer['DisableIS']) {
                    $query = 'SELECT COUNT(*) ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`COLUMNS` ';
                    $query .= 'WHERE `TABLE_NAME`=' . $dbi->quoteString($table) . ' ';
                    $query .= 'AND `TABLE_SCHEMA`=' . $dbi->quoteString($db);
                    $retval = (int) $dbi->fetchValue($query);
                } else {
                    $db = Util::backquote($db);
                    $table = Util::backquote($table);
                    $query = 'SHOW COLUMNS FROM ' . $table . ' FROM ' . $db;
                    $retval = $this->queryAndGetNumRows($query);
                }

                break;
            case 'indexes':
                $db = Util::backquote($db);
                $table = Util::backquote($table);
                $query = 'SHOW INDEXES FROM ' . $table . ' FROM ' . $db;
                $retval = $this->queryAndGetNumRows($query);
                break;
            case 'triggers':
                if (! $this->config->selectedServer['DisableIS']) {
                    $query = 'SELECT COUNT(*) ';
                    $query .= 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` ';
                    $query .= 'WHERE `EVENT_OBJECT_SCHEMA` '
                    . Util::getCollateForIS() . '=' . $dbi->quoteString($db) . ' ';
                    $query .= 'AND `EVENT_OBJECT_TABLE` '
                    . Util::getCollateForIS() . '=' . $dbi->quoteString($table);
                    $retval = (int) $dbi->fetchValue($query);
                } else {
                    $db = Util::backquote($db);
                    $query = 'SHOW TRIGGERS FROM ' . $db . ' WHERE `Table` = ' . $dbi->quoteString($table);
                    $retval = $this->queryAndGetNumRows($query);
                }

                break;
            default:
                break;
        }

        return $retval;
    }

    /** @return NodeColumn[] */
    public function getColumns(DatabaseInterface $dbi, int $pos): array
    {
        $maxItems = $this->config->settings['MaxNavigationItems'];
        $db = $this->getRealParent()->realName;
        $table = $this->realName;
        if (! $this->config->selectedServer['DisableIS']) {
            $query = 'SELECT `COLUMN_NAME` AS `name` ';
            $query .= ',`COLUMN_KEY` AS `key` ';
            $query .= ',`DATA_TYPE` AS `type` ';
            $query .= ',`COLUMN_DEFAULT` AS `default` ';
            $query .= ",IF (`IS_NULLABLE` = 'NO', '', 'nullable') AS `nullable` ";
            $query .= 'FROM `INFORMATION_SCHEMA`.`COLUMNS` ';
            $query .= 'WHERE `TABLE_NAME`=' . $dbi->quoteString($table) . ' ';
            $query .= 'AND `TABLE_SCHEMA`=' . $dbi->quoteString($db) . ' ';
            $query .= 'ORDER BY `ORDINAL_POSITION` ASC ';
            $query .= 'LIMIT ' . $pos . ', ' . $maxItems;

            $columnNodes = [];
            foreach ($dbi->fetchResultSimple($query) as $row) {
                $columnNodes[] = new NodeColumn($this->config, $row);
            }

            return $columnNodes;
        }

        $query = 'SHOW COLUMNS FROM ' . Util::backquote($table) . ' FROM ' . Util::backquote($db);
        $handle = $dbi->tryQuery($query);
        if ($handle === false) {
            return [];
        }

        $columnNodes = [];
        foreach (array_slice($handle->fetchAllAssoc(), $pos, $maxItems) as $arr) {
            $columnNodes[] = new NodeColumn($this->config, [
                'name' => $arr['Field'],
                'key' => $arr['Key'] ?? '',
                'type' => Util::extractColumnSpec($arr['Type'])['type'],
                'default' => $arr['Default'],
                'nullable' => $arr['Null'] === 'NO' ? '' : 'nullable',
            ]);
        }

        return $columnNodes;
    }

    /** @return NodeIndex[] */
    public function getIndexes(DatabaseInterface $dbi, int $pos): array
    {
        $maxItems = $this->config->settings['MaxNavigationItems'];
        $db = $this->getRealParent()->realName;
        $table = $this->realName;
        $query = 'SHOW INDEXES FROM ' . Util::backquote($table) . ' FROM ' . Util::backquote($db);
        $handle = $dbi->tryQuery($query);
        if ($handle === false) {
            return [];
        }

        $indexNodes = [];
        /** @var string $indexName */
        foreach (array_slice($handle->fetchAllAssoc(), $pos, $maxItems) as ['Key_name' => $indexName]) {
            $indexNodes[] = new NodeIndex($this->config, $indexName);
        }

        return $indexNodes;
    }

    /** @return NodeTrigger[] */
    public function getTriggers(DatabaseInterface $dbi, int $pos): array
    {
        $maxItems = $this->config->settings['MaxNavigationItems'];
        $db = $this->getRealParent()->realName;
        $table = $this->realName;

        if (! $this->config->selectedServer['DisableIS']) {
            $query = 'SELECT `TRIGGER_NAME` AS `name` ';
            $query .= 'FROM `INFORMATION_SCHEMA`.`TRIGGERS` ';
            $query .= 'WHERE `EVENT_OBJECT_SCHEMA` ' . Util::getCollateForIS() . '=' . $dbi->quoteString($db) . ' ';
            $query .= 'AND `EVENT_OBJECT_TABLE` ' . Util::getCollateForIS() . '=' . $dbi->quoteString($table) . ' ';
            $query .= 'ORDER BY `TRIGGER_NAME` ASC ';
            $query .= 'LIMIT ' . $pos . ', ' . $maxItems;

            $triggerNodes = [];
            /** @var string $triggerName */
            foreach ($dbi->fetchSingleColumn($query) as $triggerName) {
                $triggerNodes[] = new NodeTrigger($this->config, $triggerName);
            }

            return $triggerNodes;
        }

        $query = 'SHOW TRIGGERS FROM ' . Util::backquote($db) . ' WHERE `Table` = ' . $dbi->quoteString($table);
        $handle = $dbi->tryQuery($query);
        if ($handle === false) {
            return [];
        }

        $triggerNodes = [];
        /** @var string $triggerName */
        foreach (array_slice($handle->fetchAllAssoc(), $pos, $maxItems) as ['Trigger' => $triggerName]) {
            $triggerNodes[] = new NodeTrigger($this->config, $triggerName);
        }

        return $triggerNodes;
    }

    /**
     * Returns the type of the item represented by the node.
     *
     * @return string type of the item
     */
    protected function getItemType(): string
    {
        return 'table';
    }

    /**
     * Add an icon to navigation tree
     *
     * @param '/table/sql'|'/table/search'|'/table/change'|'/sql'|'/table/structure'|'' $page   Page name to redirect
     * @param array<string, mixed>                                                      $params
     */
    private function addIcon(string $page, array $params): Icon|null
    {
        return match ($page) {
            '/table/structure' => new Icon('b_props', __('Structure'), $page, $params),
            '/table/search' => new Icon('b_search', __('Search'), $page, $params),
            '/table/change' => new Icon('b_insrow', __('Insert'), $page, $params),
            '/table/sql' => new Icon('b_sql', __('SQL'), $page, $params),
            '/sql' => new Icon('b_browse', __('Browse'), $page, $params),
            default => null,
        };
    }
}
