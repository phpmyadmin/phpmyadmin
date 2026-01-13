<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\NavigationItemsHidingFeature;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\ConnectionType;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\Util;

use function __;
use function array_slice;
use function count;
use function in_array;
use function strnatcasecmp;
use function substr;
use function usort;

/**
 * Represents a database node in the navigation tree
 */
class NodeDatabase extends Node
{
    /**
     * The number of hidden items in this database
     *
     * @var int<0, max>
     */
    protected int $hiddenCount = 0;

    /** @var int[][] $presenceCounts */
    private array $presenceCounts = [];

    private ObjectFetcher $objectFetcher;

    /** @param string $name An identifier for the new node */
    public function __construct(Config $config, string $name)
    {
        parent::__construct($config, $name);

        $this->icon = new Icon(
            's_db',
            __('Database operations'),
            '/database/operations',
            ['db' => null],
        );

        $this->link = new Link(
            __('Structure'),
            $this->config->config->DefaultTabDatabase,
            ['db' => null],
        );

        $this->classes = 'database';
        $this->urlParamName = 'db';

        $this->objectFetcher = new ObjectFetcher(DatabaseInterface::getInstance(), $this->config);
    }

    /**
     * Returns the number of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param string $searchClause A string used to filter the results of
     *                             the query
     */
    public function getPresence(UserPrivileges $userPrivileges, string $type = '', string $searchClause = ''): int
    {
        return $this->presenceCounts[$type][$searchClause] ??= match ($type) {
            'tables' => count($this->objectFetcher->getTables($this->realName, $searchClause)),
            'views' => count($this->objectFetcher->getViews($this->realName, $searchClause)),
            'procedures' => count($this->objectFetcher->getProcedures($this->realName, $searchClause)),
            'functions' => count($this->objectFetcher->getFunctions($this->realName, $searchClause)),
            'events' => count($this->objectFetcher->getEvents($this->realName, $searchClause)),
            default => 0,
        };
    }

    /** @return NodeDatabaseChild[] */
    public function getDatabaseObjects(
        RelationParameters $relationParameters,
        string $type,
        int $pos,
        string $searchClause = '',
    ): array {
        if (! in_array($type, ['tables', 'views', 'procedures', 'functions', 'events'], true)) {
            return [];
        }

        $names = match ($type) {
            'tables' => $this->objectFetcher->getTables($this->realName, $searchClause),
            'views' => $this->objectFetcher->getViews($this->realName, $searchClause),
            'procedures' => $this->objectFetcher->getProcedures($this->realName, $searchClause),
            'functions' => $this->objectFetcher->getFunctions($this->realName, $searchClause),
            'events' => $this->objectFetcher->getEvents($this->realName, $searchClause),
        };

        $maxItems = $this->config->settings['MaxNavigationItems'];

        if ($this->config->settings['NaturalOrder']) {
            usort($names, strnatcasecmp(...));
        }

        $names = array_slice($names, $pos, $maxItems);

        // Remove hidden items so that they are not displayed in navigation tree
        if ($relationParameters->navigationItemsHidingFeature !== null) {
            $hiddenItems = $this->getHiddenItems($relationParameters, substr($type, 0, -1));
            foreach ($names as $key => $item) {
                if (! in_array($item, $hiddenItems, true)) {
                    continue;
                }

                unset($names[$key]);
            }
        }

        $retval = [];
        foreach ($names as $item) {
            $retval[] = match ($type) {
                'tables' => new NodeTable($this->config, $item),
                'views' => new NodeView($this->config, $item),
                'procedures' => new NodeProcedure($this->config, $item),
                'functions' => new NodeFunction($this->config, $item),
                'events' => new NodeEvent($this->config, $item),
            };
        }

        return $retval;
    }

    /**
     * Return list of hidden items of given type
     *
     * @param string $type The type of items we are looking for
     *                     ('table', 'function', 'group', etc.)
     *
     * @return list<string> Array containing hidden items of given type
     */
    public function getHiddenItems(RelationParameters $relationParameters, string $type): array
    {
        if ($relationParameters->navigationItemsHidingFeature === null || $relationParameters->user === null) {
            return [];
        }

        $navTable = Util::backquote($relationParameters->navigationItemsHidingFeature->database)
            . '.' . Util::backquote($relationParameters->navigationItemsHidingFeature->navigationHiding);
        $dbi = DatabaseInterface::getInstance();
        $sqlQuery = 'SELECT `item_name` FROM ' . $navTable
            . ' WHERE `username`='
            . $dbi->quoteString($relationParameters->user, ConnectionType::ControlUser)
            . ' AND `item_type`='
            . $dbi->quoteString($type, ConnectionType::ControlUser)
            . ' AND `db_name`='
            . $dbi->quoteString($this->realName, ConnectionType::ControlUser);
        $result = $dbi->tryQueryAsControlUser($sqlQuery);
        $hiddenItems = [];
        if ($result instanceof ResultInterface) {
            /** @var list<string> $hiddenItems */
            $hiddenItems = $result->fetchAllColumn();
        }

        return $hiddenItems;
    }

    /**
     * Returns HTML for control buttons displayed infront of a node
     *
     * @return string HTML for control buttons
     */
    public function getHtmlForControlButtons(NavigationItemsHidingFeature|null $navigationItemsHidingFeature): string
    {
        $ret = '';
        if ($navigationItemsHidingFeature !== null && $this->hiddenCount > 0) {
            $params = ['showUnhideDialog' => true, 'dbName' => $this->realName];
            $ret = '<span class="dbItemControls">'
                . '<a href="' . Url::getFromRoute('/navigation') . '" data-post="'
                . Url::getCommon($params, '', false) . '"'
                . ' class="showUnhide ajax">'
                . Generator::getImage(
                    'show',
                    __('Show hidden items'),
                )
                . '</a></span>';
        }

        return $ret;
    }

    /**
     * Sets the number of hidden items in this database
     */
    public function setHiddenCount(int $count): void
    {
        $this->hiddenCount = $count >= 1 ? $count : 0;
    }

    /**
     * Returns the number of hidden items in this database
     *
     * @return int<0, max>
     */
    public function getHiddenCount(): int
    {
        return $this->hiddenCount;
    }
}
