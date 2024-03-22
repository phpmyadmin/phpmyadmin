<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\NavigationItemsHidingFeature;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\ConnectionType;
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

        $this->icon = ['image' => 's_db', 'title' => __('Database operations')];

        $this->links = [
            'text' => [
                'route' => Util::getUrlForOption($this->config->settings['DefaultTabDatabase'], 'database'),
                'params' => ['db' => null],
            ],
            'icon' => ['route' => '/database/operations', 'params' => ['db' => null]],
            'title' => __('Structure'),
        ];

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

    /**
     * Returns the names of children of type $type present inside this container
     * This method is overridden by the PhpMyAdmin\Navigation\Nodes\NodeDatabase
     * and PhpMyAdmin\Navigation\Nodes\NodeTable classes
     *
     * @param string $type         The type of item we are looking for
     *                             ('tables', 'views', etc)
     * @param int    $pos          The offset of the list within the results
     * @param string $searchClause A string used to filter the results of the query
     *
     * @return string[]
     */
    public function getData(
        UserPrivileges $userPrivileges,
        RelationParameters $relationParameters,
        string $type,
        int $pos,
        string $searchClause = '',
    ): array {
        $retval = match ($type) {
            'tables' => $this->objectFetcher->getTables($this->realName, $searchClause),
            'views' => $this->objectFetcher->getViews($this->realName, $searchClause),
            'procedures' => $this->objectFetcher->getProcedures($this->realName, $searchClause),
            'functions' => $this->objectFetcher->getFunctions($this->realName, $searchClause),
            'events' => $this->objectFetcher->getEvents($this->realName, $searchClause),
            default => [],
        };

        $maxItems = $this->config->settings['MaxNavigationItems'];

        if ($this->config->settings['NaturalOrder']) {
            usort($retval, strnatcasecmp(...));
        }

        $retval = array_slice($retval, $pos, $maxItems);

        // Remove hidden items so that they are not displayed in navigation tree
        if ($relationParameters->navigationItemsHidingFeature !== null) {
            $hiddenItems = $this->getHiddenItems($relationParameters, substr($type, 0, -1));
            foreach ($retval as $key => $item) {
                if (! in_array($item, $hiddenItems, true)) {
                    continue;
                }

                unset($retval[$key]);
            }
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
