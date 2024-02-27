<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Favorites\RecentFavoriteTables;
use PhpMyAdmin\Favorites\TableType;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Navigation\Nodes\NodeColumn;
use PhpMyAdmin\Navigation\Nodes\NodeColumnContainer;
use PhpMyAdmin\Navigation\Nodes\NodeDatabase;
use PhpMyAdmin\Navigation\Nodes\NodeDatabaseContainer;
use PhpMyAdmin\Navigation\Nodes\NodeEvent;
use PhpMyAdmin\Navigation\Nodes\NodeEventContainer;
use PhpMyAdmin\Navigation\Nodes\NodeFunction;
use PhpMyAdmin\Navigation\Nodes\NodeFunctionContainer;
use PhpMyAdmin\Navigation\Nodes\NodeIndex;
use PhpMyAdmin\Navigation\Nodes\NodeIndexContainer;
use PhpMyAdmin\Navigation\Nodes\NodeProcedure;
use PhpMyAdmin\Navigation\Nodes\NodeProcedureContainer;
use PhpMyAdmin\Navigation\Nodes\NodeTable;
use PhpMyAdmin\Navigation\Nodes\NodeTableContainer;
use PhpMyAdmin\Navigation\Nodes\NodeTrigger;
use PhpMyAdmin\Navigation\Nodes\NodeTriggerContainer;
use PhpMyAdmin\Navigation\Nodes\NodeView;
use PhpMyAdmin\Navigation\Nodes\NodeViewContainer;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivileges;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Util;

use function __;
use function _ngettext;
use function array_intersect_key;
use function array_key_exists;
use function array_key_last;
use function array_keys;
use function array_map;
use function array_merge;
use function array_shift;
use function base64_decode;
use function count;
use function explode;
use function floor;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function is_bool;
use function is_object;
use function is_string;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function sort;
use function sprintf;
use function strcasecmp;
use function strlen;
use function strnatcasecmp;
use function strrpos;
use function strstr;
use function substr;
use function trigger_error;
use function trim;
use function usort;

use const E_USER_WARNING;

/**
 * Displays a collapsible of database objects in the navigation frame
 */
class NavigationTree
{
    private const SPECIAL_NODE_NAMES = ['tables', 'views', 'functions', 'procedures', 'events'];

    /** @var Node Reference to the root node of the tree */
    private Node $tree;
    /**
     * @var mixed[] The actual paths to all expanded nodes in the tree
     *            This does not include nodes created after the grouping
     *            of nodes has been performed
     */
    private array $aPath = [];
    /**
     * @var mixed[] The virtual paths to all expanded nodes in the tree
     *            This includes nodes created after the grouping of
     *            nodes has been performed
     */
    private array $vPath = [];
    /**
     * @var int Position in the list of databases,
     *          used for pagination
     */
    private int $pos = 0;
    /**
     * @var string[] The names of the type of items that are being paginated on
     *               the second level of the navigation tree. These may be
     *               tables, views, functions, procedures or events.
     */
    private array $pos2Name = [];
    /**
     * @var int[] The positions of nodes in the lists of tables, views,
     *            routines or events used for pagination
     */
    private array $pos2Value = [];
    /**
     * @var string[] The names of the type of items that are being paginated
     *               on the second level of the navigation tree.
     *               These may be columns or indexes
     */
    private array $pos3Name = [];
    /**
     * @var int[] The positions of nodes in the lists of columns or indexes
     *            used for pagination
     */
    private array $pos3Value = [];
    /**
     * @var string The search clause to use in SQL queries for
     *             fetching databases
     *             Used by the asynchronous fast filter
     */
    private string $searchClause = '';
    /**
     * @var string The search clause to use in SQL queries for
     *             fetching nodes
     *             Used by the asynchronous fast filter
     */
    private string $searchClause2 = '';
    /**
     * @var bool Whether a warning was raised for large item groups
     *           which can affect performance.
     */
    private bool $largeGroupWarning = false;

    private RelationParameters $relationParameters;

    public function __construct(
        private Template $template,
        private DatabaseInterface $dbi,
        Relation $relation,
        private readonly Config $config,
    ) {
        $this->relationParameters = $relation->getRelationParameters();
        $userPrivilegesFactory = new UserPrivilegesFactory($this->dbi);
        $userPrivileges = $userPrivilegesFactory->getPrivileges();

        // Save the position at which we are in the database list
        if (isset($_POST['pos'])) {
            $this->pos = (int) $_POST['pos'];
        } elseif (isset($_GET['pos'])) {
            $this->pos = (int) $_GET['pos'];
        } else {
            $this->pos = $this->getNavigationDbPos($userPrivileges);
        }

        // Get the active node
        if (isset($_POST['aPath'])) {
            $this->aPath[0] = $this->parsePath($_POST['aPath']);
            $this->pos2Name[0] = $_POST['pos2_name'] ?? '';
            $this->pos2Value[0] = (int) ($_POST['pos2_value'] ?? 0);
            if (isset($_POST['pos3_name'])) {
                $this->pos3Name[0] = $_POST['pos3_name'];
                $this->pos3Value[0] = (int) $_POST['pos3_value'];
            }
        } elseif (isset($_POST['n0_aPath'])) {
            $count = 0;
            while (isset($_POST['n' . $count . '_aPath'])) {
                $this->aPath[$count] = $this->parsePath($_POST['n' . $count . '_aPath']);
                if (isset($_POST['n' . $count . '_pos2_name'])) {
                    $this->pos2Name[$count] = $_POST['n' . $count . '_pos2_name'];
                    $this->pos2Value[$count] = (int) $_POST['n' . $count . '_pos2_value'];
                }

                if (isset($_POST['n' . $count . '_pos3_name'])) {
                    $this->pos3Name[$count] = $_POST['n' . $count . '_pos3_name'];
                    $this->pos3Value[$count] = (int) $_POST['n' . $count . '_pos3_value'];
                }

                $count++;
            }
        }

        if (isset($_POST['vPath'])) {
            $this->vPath[0] = $this->parsePath($_POST['vPath']);
        } elseif (isset($_POST['n0_vPath'])) {
            $count = 0;
            while (isset($_POST['n' . $count . '_vPath'])) {
                $this->vPath[$count] = $this->parsePath($_POST['n' . $count . '_vPath']);
                $count++;
            }
        }

        if (isset($_POST['searchClause'])) {
            $this->searchClause = $_POST['searchClause'];
        }

        if (isset($_POST['searchClause2'])) {
            $this->searchClause2 = $_POST['searchClause2'];
        }

        // Initialize the tree by creating a root node
        $this->tree = new NodeDatabaseContainer($this->config, 'root');
        if (
            ! $this->config->settings['NavigationTreeEnableGrouping']
            || ! $this->config->settings['ShowDatabasesNavigationAsTree']
        ) {
            return;
        }

        $this->tree->separator = $this->config->settings['NavigationTreeDbSeparator'];
        $this->tree->separatorDepth = 10000;
    }

    /**
     * Returns the database position for the page selector
     */
    private function getNavigationDbPos(UserPrivileges $userPrivileges): int
    {
        if (Current::$database === '') {
            return 0;
        }

        /** @todo describe a scenario where this code is executed */
        if (! $this->config->selectedServer['DisableIS']) {
            $query = 'SELECT (COUNT(DB_first_level) DIV %d) * %d ';
            $query .= 'from ( ';
            $query .= ' SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, ';
            $query .= ' %s, 1) ';
            $query .= ' DB_first_level ';
            $query .= ' FROM INFORMATION_SCHEMA.SCHEMATA ';
            $query .= ' WHERE `SCHEMA_NAME` < %s ';
            $query .= ') t ';

            return (int) $this->dbi->fetchValue(
                sprintf(
                    $query,
                    $this->config->settings['FirstLevelNavigationItems'],
                    $this->config->settings['FirstLevelNavigationItems'],
                    $this->dbi->quoteString($this->config->settings['NavigationTreeDbSeparator']),
                    $this->dbi->quoteString(Current::$database),
                ),
            );
        }

        $prefixMap = [];
        if ($userPrivileges->databasesToTest === false) {
            $handle = $this->dbi->tryQuery('SHOW DATABASES');
            if ($handle !== false) {
                while ($database = $handle->fetchValue()) {
                    if (strcasecmp($database, Current::$database) >= 0) {
                        break;
                    }

                    $prefix = strstr($database, $this->config->settings['NavigationTreeDbSeparator'], true);
                    if ($prefix === false) {
                        $prefix = $database;
                    }

                    $prefixMap[$prefix] = 1;
                }
            }
        } else {
            $databases = [];
            foreach ($userPrivileges->databasesToTest as $db) {
                $query = "SHOW DATABASES LIKE '" . $db . "'";
                $handle = $this->dbi->tryQuery($query);
                if ($handle === false) {
                    continue;
                }

                $databases = array_merge($databases, $handle->fetchAllColumn());
            }

            sort($databases);
            foreach ($databases as $database) {
                if (strcasecmp($database, Current::$database) >= 0) {
                    break;
                }

                $prefix = strstr($database, $this->config->settings['NavigationTreeDbSeparator'], true);
                if ($prefix === false) {
                    $prefix = $database;
                }

                $prefixMap[$prefix] = 1;
            }
        }

        $navItems = $this->config->settings['FirstLevelNavigationItems'];

        return (int) floor(count($prefixMap) / $navItems) * $navItems;
    }

    /**
     * Converts an encoded path to a node in string format to an array
     *
     * @param string $string The path to parse
     *
     * @return non-empty-list<string>
     */
    private function parsePath(string $string): array
    {
        return array_map(base64_decode(...), explode('.', $string));
    }

    /**
     * Generates the tree structure so that it can be rendered later
     *
     * @return Node|bool The active node or false in case of failure, or true: (@see buildPathPart())
     */
    private function buildPath(UserPrivileges $userPrivileges): Node|bool
    {
        $retval = $this->tree;

        // Add all databases unconditionally
        $data = $this->tree->getData(
            $userPrivileges,
            $this->relationParameters,
            'databases',
            $this->pos,
            $this->searchClause,
        );
        $hiddenCounts = $this->tree->getNavigationHidingData($this->relationParameters->navigationItemsHidingFeature);
        foreach ($data as $db) {
            $node = new NodeDatabase($this->config, $db);
            if (isset($hiddenCounts[$db])) {
                $node->setHiddenCount((int) $hiddenCounts[$db]);
            }

            $this->tree->addChild($node);
        }

        // Whether build other parts of the tree depends
        // on whether we have any paths in $this->aPath
        foreach ($this->aPath as $key => $path) {
            $retval = $this->buildPathPart(
                $userPrivileges,
                $path,
                $this->pos2Name[$key] ?? '',
                $this->pos2Value[$key] ?? 0,
                $this->pos3Name[$key] ?? '',
                $this->pos3Value[$key] ?? 0,
            );
        }

        return $retval;
    }

    /**
     * Builds a branch of the tree
     *
     * @param mixed[] $path  A paths pointing to the branch
     *                     of the tree that needs to be built
     * @param string  $type2 The type of item being paginated on
     *                       the second level of the tree
     * @param int     $pos2  The position for the pagination of
     *                       the branch at the second level of the tree
     * @param string  $type3 The type of item being paginated on
     *                       the third level of the tree
     * @param int     $pos3  The position for the pagination of
     *                       the branch at the third level of the tree
     *
     * @return Node|bool    The active node or false in case of failure, true if the path contains <= 1 items
     */
    private function buildPathPart(
        UserPrivileges $userPrivileges,
        array $path,
        string $type2,
        int $pos2,
        string $type3,
        int $pos3,
    ): Node|bool {
        if (count($path) <= 1) {
            return true;
        }

        array_shift($path); // remove 'root'
        /** @var NodeDatabase|null $db */
        $db = $this->tree->getChild($path[0]);

        if ($db === null) {
            return false;
        }

        $containers = $this->addDbContainers($userPrivileges, $db, $type2, $pos2);

        array_shift($path); // remove db

        if (($path === [] || ! array_key_exists($path[0], $containers)) && count($containers) != 1) {
            return $db;
        }

        if (count($containers) === 1) {
            $container = array_shift($containers);
        } else {
            $container = $db->getChild($path[0], true);
            if ($container === null) {
                return false;
            }
        }

        if (count($container->children) <= 1) {
            $dbData = $db->getData(
                $userPrivileges,
                $this->relationParameters,
                $container->realName,
                $pos2,
                $this->searchClause2,
            );
            foreach ($dbData as $item) {
                $node = match ($container->realName) {
                    'events' => new NodeEvent($this->config, $item),
                    'functions' => new NodeFunction($this->config, $item),
                    'procedures' => new NodeProcedure($this->config, $item),
                    'tables' => new NodeTable($this->config, $item),
                    'views' => new NodeView($this->config, $item),
                    default => null,
                };

                if ($node === null) {
                    continue;
                }

                if ($type2 === $container->realName) {
                    $node->pos2 = $pos2;
                }

                $container->addChild($node);
            }
        }

        if (count($path) > 1 && $path[0] !== 'tables') {
            return false;
        }

        array_shift($path); // remove container
        if ($path === []) {
            return $container;
        }

        /** @var NodeTable|null $table */
        $table = $container->getChild($path[0], true);
        if ($table === null) {
            if ($db->getPresence($userPrivileges, 'tables', $path[0]) === 0) {
                return false;
            }

            $node = new NodeTable($this->config, $path[0]);
            if ($type2 === $container->realName) {
                $node->pos2 = $pos2;
            }

            $container->addChild($node);
            $table = $container->getChild($path[0], true);
            if ($table === null) {
                return false;
            }
        }

        $containers = $this->addTableContainers($userPrivileges, $table, $pos2, $type3, $pos3);
        array_shift($path); // remove table
        if ($path === [] || ! array_key_exists($path[0], $containers)) {
            return $table;
        }

        $container = $table->getChild($path[0], true);
        if ($container === null) {
            return false;
        }

        $tableData = $table->getData($userPrivileges, $this->relationParameters, $container->realName, $pos3);
        foreach ($tableData as $item) {
            $node = match ($container->realName) {
                'indexes' => new NodeIndex($this->config, $item),
                'columns' => new NodeColumn($this->config, $item),
                'triggers' => new NodeTrigger($this->config, $item),
                default => null,
            };

            if ($node === null) {
                continue;
            }

            $node->pos2 = $container->parent->pos2;
            if ($type3 === $container->realName) {
                $node->pos3 = $pos3;
            }

            $container->addChild($node);
        }

        return $container;
    }

    /**
     * Adds containers to a node that is a table
     *
     * References to existing children are returned
     * if this function is called twice on the same node
     *
     * @param NodeTable $table The table node, new containers will be
     *                         attached to this node
     * @param int       $pos2  The position for the pagination of
     *                         the branch at the second level of the tree
     * @param string    $type3 The type of item being paginated on
     *                         the third level of the tree
     * @param int       $pos3  The position for the pagination of
     *                         the branch at the third level of the tree
     *
     * @return Node[] An array of new nodes
     */
    private function addTableContainers(
        UserPrivileges $userPrivileges,
        NodeTable $table,
        int $pos2,
        string $type3,
        int $pos3,
    ): array {
        $retval = [];
        if (! $table->hasChildren()) {
            if ($table->getPresence($userPrivileges, 'columns') !== 0) {
                $retval['columns'] = new NodeColumnContainer($this->config);
            }

            if ($table->getPresence($userPrivileges, 'indexes') !== 0) {
                $retval['indexes'] = new NodeIndexContainer($this->config);
            }

            if ($table->getPresence($userPrivileges, 'triggers') !== 0) {
                $retval['triggers'] = new NodeTriggerContainer($this->config);
            }

            // Add all new Nodes to the tree
            foreach ($retval as $node) {
                $node->pos2 = $pos2;
                if ($type3 === $node->realName) {
                    $node->pos3 = $pos3;
                }

                $table->addChild($node);
            }
        } else {
            foreach ($table->children as $node) {
                if ($type3 === $node->realName) {
                    $node->pos3 = $pos3;
                }

                $retval[$node->realName] = $node;
            }
        }

        return $retval;
    }

    /**
     * Adds containers to a node that is a database
     *
     * References to existing children are returned
     * if this function is called twice on the same node
     *
     * @param NodeDatabase $db   The database node, new containers will be
     *                           attached to this node
     * @param string       $type The type of item being paginated on
     *                           the second level of the tree
     * @param int          $pos2 The position for the pagination of
     *                           the branch at the second level of the tree
     *
     * @return Node[] An array of new nodes
     */
    private function addDbContainers(UserPrivileges $userPrivileges, NodeDatabase $db, string $type, int $pos2): array
    {
        // Get items to hide
        $hidden = $db->getHiddenItems($this->relationParameters, 'group');
        if (! $this->config->settings['NavigationTreeShowTables'] && ! in_array('tables', $hidden, true)) {
            $hidden[] = 'tables';
        }

        if (! $this->config->settings['NavigationTreeShowViews'] && ! in_array('views', $hidden, true)) {
            $hidden[] = 'views';
        }

        if (! $this->config->settings['NavigationTreeShowFunctions'] && ! in_array('functions', $hidden, true)) {
            $hidden[] = 'functions';
        }

        if (! $this->config->settings['NavigationTreeShowProcedures'] && ! in_array('procedures', $hidden, true)) {
            $hidden[] = 'procedures';
        }

        if (! $this->config->settings['NavigationTreeShowEvents'] && ! in_array('events', $hidden, true)) {
            $hidden[] = 'events';
        }

        $retval = [];
        if (! $db->hasChildren()) {
            if (! in_array('tables', $hidden, true) && $db->getPresence($userPrivileges, 'tables')) {
                $retval['tables'] = new NodeTableContainer($this->config);
            }

            if (! in_array('views', $hidden, true) && $db->getPresence($userPrivileges, 'views')) {
                $retval['views'] = new NodeViewContainer($this->config);
            }

            if (! in_array('functions', $hidden, true) && $db->getPresence($userPrivileges, 'functions')) {
                $retval['functions'] = new NodeFunctionContainer($this->config);
            }

            if (! in_array('procedures', $hidden, true) && $db->getPresence($userPrivileges, 'procedures')) {
                $retval['procedures'] = new NodeProcedureContainer($this->config);
            }

            if (! in_array('events', $hidden, true) && $db->getPresence($userPrivileges, 'events')) {
                $retval['events'] = new NodeEventContainer($this->config);
            }

            // Add all new Nodes to the tree
            foreach ($retval as $node) {
                if ($type === $node->realName) {
                    $node->pos2 = $pos2;
                }

                $db->addChild($node);
            }
        } else {
            foreach ($db->children as $node) {
                if ($type === $node->realName) {
                    $node->pos2 = $pos2;
                }

                $retval[$node->realName] = $node;
            }
        }

        return $retval;
    }

    /**
     * Recursively groups tree nodes given a separator
     *
     * @param Node|null $node The node to group or null
     *                   to group the whole tree. If
     *                   passed as an argument, $node
     *                   must be of type CONTAINER
     */
    public function groupTree(Node|null $node = null): void
    {
        if ($node === null) {
            $node = $this->tree;
        }

        $this->groupNode($node);
        foreach ($node->children as $child) {
            $this->groupTree($child);
        }
    }

    /**
     * Recursively groups tree nodes given a separator
     *
     * @param Node $node The node to group
     */
    public function groupNode(Node $node): void
    {
        if ($node->type !== NodeType::Container || ! $this->config->settings['NavigationTreeEnableExpansion']) {
            return;
        }

        $separators = [];
        if (is_array($node->separator)) {
            $separators = $node->separator;
        } elseif (is_string($node->separator) && $node->separator !== '') {
            $separators[] = $node->separator;
        }

        $prefixes = [];
        if ($node->separatorDepth > 0) {
            foreach ($node->children as $child) {
                $prefixPos = false;
                foreach ($separators as $separator) {
                    $sepPos = mb_strpos($child->name, $separator);
                    if (
                        $sepPos === false
                        || $sepPos === mb_strlen($child->name)
                        || $sepPos === 0
                        || ($prefixPos !== false && $sepPos >= $prefixPos)
                    ) {
                        continue;
                    }

                    $prefixPos = $sepPos;
                }

                if ($prefixPos !== false) {
                    $prefix = mb_substr($child->name, 0, $prefixPos);
                    if (! isset($prefixes[$prefix])) {
                        $prefixes[$prefix] = 1;
                    } else {
                        $prefixes[$prefix]++;
                    }
                }

                //Bug #4375: Check if prefix is the name of a DB, to create a group.
                foreach ($node->children as $otherChild) {
                    if (! array_key_exists($otherChild->name, $prefixes)) {
                        continue;
                    }

                    $prefixes[$otherChild->name]++;
                }
            }

            //Check if prefix is the name of a DB, to create a group.
            foreach ($node->children as $child) {
                if (! array_key_exists($child->name, $prefixes)) {
                    continue;
                }

                $prefixes[$child->name]++;
            }
        }

        // It is not a group if it has only one item
        foreach ($prefixes as $key => $value) {
            if ($value > 1) {
                continue;
            }

            unset($prefixes[$key]);
        }

        $numChildren = count($node->children);

        // rfe #1634 Don't group if there's only one group and no other items
        if (count($prefixes) === 1) {
            $keys = array_keys($prefixes);
            $key = $keys[0];
            if ($prefixes[$key] == $numChildren - 1) {
                unset($prefixes[$key]);
            }
        }

        if ($prefixes === []) {
            return;
        }

        /** @var Node[] $groups */
        $groups = [];
        foreach ($prefixes as $key => $value) {
            // warn about large groups
            if ($value > 500 && ! $this->largeGroupWarning) {
                trigger_error(
                    __(
                        'There are large item groups in navigation panel which '
                        . 'may affect the performance. Consider disabling item '
                        . 'grouping in the navigation panel.',
                    ),
                    E_USER_WARNING,
                );
                $this->largeGroupWarning = true;
            }

            $newChildren = [];
            foreach ($separators as $separator) {
                $separatorLength = strlen($separator);
                // FIXME: this could be more efficient
                foreach ($node->children as $child) {
                    $keySeparatorLength = mb_strlen((string) $key) + $separatorLength;
                    $nameSubstring = mb_substr($child->name, 0, $keySeparatorLength);
                    if (
                        ($nameSubstring !== $key . $separator && $child->name !== $key)
                        || $child->type !== NodeType::Object
                    ) {
                        continue;
                    }

                    $newChild = new $child($this->config, mb_substr($child->name, $keySeparatorLength));
                    if ($child instanceof NodeDatabase && $child->getHiddenCount() > 0) {
                        $newChild->setHiddenCount($child->getHiddenCount());
                    }

                    $newChild->realName = $child->realName;
                    $newChild->icon = $child->icon;
                    $newChild->links = $child->links;
                    $newChild->pos2 = $child->pos2;
                    $newChild->pos3 = $child->pos3;
                    foreach ($child->children as $elm) {
                        $newChild->addChild($elm);
                    }

                    $newChildren[] = ['node' => $newChild, 'replaces_name' => $child->name];
                }
            }

            if ($newChildren === []) {
                continue;
            }

            // If the current node is a standard group (not NodeTableContainer, etc.)
            // and the new group contains all of the current node's children, combine them
            $class = $node::class;
            if (count($newChildren) === $numChildren && substr($class, strrpos($class, '\\') + 1) === 'Node') {
                $node->name .= $separators[0] . htmlspecialchars((string) $key);
                $node->realName .= $separators[0] . htmlspecialchars((string) $key);
                $node->separatorDepth--;
                foreach ($newChildren as $newChild) {
                    $node->removeChild($newChild['replaces_name']);
                    $node->addChild($newChild['node']);
                }
            } else {
                $groups[$key] = new Node($this->config, (string) $key, NodeType::Container, true);
                $groups[$key]->separator = $node->separator;
                $groups[$key]->separatorDepth = $node->separatorDepth - 1;
                $groups[$key]->icon = ['image' => 'b_group', 'title' => __('Groups')];
                $groups[$key]->pos2 = $node->pos2;
                $groups[$key]->pos3 = $node->pos3;
                if ($node instanceof NodeTableContainer || $node instanceof NodeViewContainer) {
                    $groups[$key]->links = [
                        'text' => [
                            'route' => $node->links['text']['route'],
                            'params' => array_merge($node->links['text']['params'], ['tbl_group' => $key]),
                        ],
                        'icon' => [
                            'route' => $node->links['icon']['route'],
                            'params' => array_merge($node->links['icon']['params'], ['tbl_group' => $key]),
                        ],
                    ];
                }

                foreach ($newChildren as $newChild) {
                    $node->removeChild($newChild['replaces_name']);
                    $groups[$key]->addChild($newChild['node']);
                }
            }
        }

        foreach ($groups as $group) {
            if ($group->children === []) {
                continue;
            }

            $node->addChild($group);
            $this->groupNode($group);
            $group->classes = 'navGroup';
        }
    }

    /**
     * Renders a state of the tree, used in light mode when
     * either JavaScript and/or Ajax are disabled
     *
     * @return string HTML code for the navigation tree
     */
    public function renderState(UserPrivileges $userPrivileges): string
    {
        $this->buildPath($userPrivileges);

        $quickWarp = $this->quickWarp();
        $fastFilter = $this->fastFilterHtml($userPrivileges, $this->tree);
        $controls = '';
        if ($this->config->settings['NavigationTreeEnableExpansion']) {
            $controls = $this->controls();
        }

        $pageSelector = $this->getPageSelector($userPrivileges, $this->tree);

        $this->groupTree();
        $children = $this->tree->children;
        usort($children, $this->sortNode(...));
        $this->setVisibility();

        $nodes = $this->renderNodes($userPrivileges, $children);

        return $this->template->render('navigation/tree/state', [
            'quick_warp' => $quickWarp,
            'fast_filter' => $fastFilter,
            'controls' => $controls,
            'page_selector' => $pageSelector,
            'nodes' => $nodes,
        ]);
    }

    /**
     * Renders a part of the tree, used for Ajax requests in light mode
     *
     * @return string|false HTML code for the navigation tree
     */
    public function renderPath(UserPrivileges $userPrivileges): string|false
    {
        $node = $this->buildPath($userPrivileges);
        if (! is_bool($node)) {
            $this->groupTree();

            $listContent = $this->fastFilterHtml($userPrivileges, $node);
            $listContent .= $this->getPageSelector($userPrivileges, $node);
            $children = $node->children;
            usort($children, $this->sortNode(...));

            $listContent .= $this->renderNodes($userPrivileges, $children, false);

            if (! $this->config->settings['ShowDatabasesNavigationAsTree']) {
                $parents = $node->parents(true);
                $parentName = $parents[0]->realName;
            }
        }

        $hasSearchClause = $this->searchClause !== '' || $this->searchClause2 !== '';
        if ($hasSearchClause && ! is_bool($node)) {
            $results = 0;
            if ($this->searchClause2 !== '') {
                if (is_object($node->realParent())) {
                    $results = $node->realParent()
                        ->getPresence($userPrivileges, $node->realName, $this->searchClause2);
                }
            } else {
                $results = $this->tree->getPresence($userPrivileges, 'databases', $this->searchClause);
            }

            $results = sprintf(
                _ngettext(
                    '%s result found',
                    '%s results found',
                    $results,
                ),
                $results,
            );
            ResponseRenderer::getInstance()
                ->addJSON('results', $results);
        }

        if ($node !== false) {
            return $this->template->render('navigation/tree/path', [
                'has_search_results' => $this->searchClause !== '' || $this->searchClause2 !== '',
                'list_content' => $listContent ?? '',
                'is_tree' => $this->config->settings['ShowDatabasesNavigationAsTree'],
                'parent_name' => $parentName ?? '',
            ]);
        }

        return false;
    }

    /**
     * Renders the parameters that are required on the client
     * side to know which page(s) we will be requesting data from
     *
     * @param Node $node The node to create the pagination parameters for
     *
     * @return array<string, string>
     */
    private function getPaginationParamsHtml(Node $node): array
    {
        $renderDetails = [];
        $paths = $node->getPaths();
        if (isset($paths['aPath_clean'][2])) {
            $renderDetails['position'] = 'pos2_nav';
            $renderDetails['data_name'] = $paths['aPath_clean'][2];
            $renderDetails['data_value'] = (string) $node->pos2;
        }

        if (isset($paths['aPath_clean'][4])) {
            $renderDetails['position'] = 'pos3_nav';
            $renderDetails['data_name'] = $paths['aPath_clean'][4];
            $renderDetails['data_value'] = (string) $node->pos3;
        }

        return $renderDetails;
    }

    /**
     * Finds whether given tree matches this tree.
     *
     * @param mixed[] $tree  Tree to check
     * @param mixed[] $paths Paths to check
     */
    private function findTreeMatch(array $tree, array $paths): bool
    {
        $match = false;
        foreach ($tree as $path) {
            $match = true;
            foreach ($paths as $key => $part) {
                if (! isset($path[$key]) || $part != $path[$key]) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                break;
            }
        }

        return $match;
    }

    /** @param Node[] $children */
    private function renderNodes(UserPrivileges $userPrivileges, array $children, bool $hasFirstClass = true): string
    {
        $nodes = '';
        $lastKey = array_key_last($children);
        foreach ($children as $i => $child) {
            if ($i === 0) {
                $nodes .= $this->renderNode($userPrivileges, $child, $hasFirstClass ? 'first' : '');
            } elseif ($i !== $lastKey) {
                $nodes .= $this->renderNode($userPrivileges, $child);
            } else {
                $nodes .= $this->renderNode($userPrivileges, $child, 'last');
            }
        }

        return $nodes;
    }

    /**
     * Renders a single node or a branch of the tree
     *
     * @param Node   $node  The node to render
     * @param string $class An additional class for the list item
     *
     * @return string HTML code for the tree node or branch
     */
    private function renderNode(UserPrivileges $userPrivileges, Node $node, string $class = ''): string
    {
        $controlButtons = '';
        $paths = $node->getPaths();
        $nodeIsContainer = $node->type === NodeType::Container;
        $liClasses = '';

        // Whether to show the node in the tree (true for all nodes but root)
        // If false, the node's children will still be shown, but as children of the node's parent
        $showNode = $node->hasSiblings() || $node->parents(false, true) !== [];

        // Don't show the 'Tables' node under each database unless it has 'Views', etc. as a sibling
        if ($node instanceof NodeTableContainer && ! $node->hasSiblings()) {
            $showNode = false;
        }

        if ($showNode) {
            $response = ResponseRenderer::getInstance();
            if ($nodeIsContainer && $node->children === [] && ! $response->isAjax()) {
                return '';
            }

            $liClasses = trim($class . ' ' . $node->classes);
            $sterile = ['events', 'triggers', 'functions', 'procedures', 'views', 'columns', 'indexes'];
            $parentName = '';
            $parents = $node->parents(false, true);
            if ($parents !== []) {
                $parentName = $parents[0]->realName;
            }

            // if node name itself is in sterile, then allow
            $nodeIsGroup = $node->isGroup
                || (! in_array($parentName, $sterile, true) && ! $node->isNew)
                || (in_array($node->realName, $sterile, true) && $node->children !== []);
            if ($nodeIsGroup) {
                $match = $this->findTreeMatch($this->vPath, $paths['vPath_clean']);
                $linkClasses = $node->getCssClasses($match);
                if ($this->config->settings['ShowDatabasesNavigationAsTree'] || $parentName !== 'root') {
                    $nodeIcon = $node->getIcon($match);
                }
            }

            $paginationParams = $this->getPaginationParamsHtml($node);

            if (! $node->isGroup) {
                $args = [];
                $parents = $node->parents(true);
                foreach ($parents as $parent) {
                    if ($parent->urlParamName === null) {
                        continue;
                    }

                    $args[$parent->urlParamName] = $parent->realName;
                }

                $iconLinks = [];
                $iconLinks[] = [
                    'route' => $node->links['icon']['route'],
                    'params' => array_merge(
                        $node->links['icon']['params'],
                        array_intersect_key($args, $node->links['icon']['params']),
                    ),
                    'image' => $node->icon['image'],
                    'title' => $node->icon['title'],
                ];

                if (isset($node->links['second_icon'], $node->secondIcon)) {
                    $iconLinks[] = [
                        'route' => $node->links['second_icon']['route'],
                        'params' => array_merge(
                            $node->links['second_icon']['params'],
                            array_intersect_key($args, $node->links['second_icon']['params']),
                        ),
                        'image' => $node->secondIcon['image'],
                        'title' => $node->secondIcon['title'],
                    ];
                }

                $textLink = [
                    'route' => $node->links['text']['route'],
                    'params' => array_merge(
                        $node->links['text']['params'],
                        array_intersect_key($args, $node->links['text']['params']),
                    ),
                    'title' => $node->links['title'] ?? $node->title,
                ];
            }

            $controlButtons .= $node->getHtmlForControlButtons($this->relationParameters->navigationItemsHidingFeature);
            $wrap = true;
        } else {
            $node->visible = true;
            $wrap = false;
            $paginationParams = $this->getPaginationParamsHtml($node);
        }

        $children = $node->children;
        usort($children, $this->sortNode(...));
        $buffer = '';
        $extraClass = '';
        $lastKey = array_key_last($children);
        foreach ($children as $i => $child) {
            if ($i === $lastKey) {
                $extraClass = ' last';
            }

            $buffer .= $this->renderNode($userPrivileges, $child, $child->classes . $extraClass);
        }

        if ($buffer !== '') {
            $recursiveHtml = $this->fastFilterHtml($userPrivileges, $node);
            $recursiveHtml .= $this->getPageSelector($userPrivileges, $node);
            $recursiveHtml .= $buffer;
        }

        return $this->template->render('navigation/tree/node', [
            'node' => $node,
            'class' => $class,
            'show_node' => $showNode,
            'has_siblings' => $node->hasSiblings(),
            'li_classes' => $liClasses,
            'control_buttons' => $controlButtons,
            'node_is_container' => $nodeIsContainer,
            'has_second_icon' => isset($node->secondIcon),
            'recursive' => ['html' => $recursiveHtml ?? '', 'has_wrapper' => $wrap, 'is_hidden' => ! $node->visible],
            'icon_links' => $iconLinks ?? [],
            'text_link' => $textLink ?? [],
            'pagination_params' => $paginationParams,
            'node_is_group' => $nodeIsGroup ?? false,
            'link_classes' => $linkClasses ?? '',
            'paths' => ['a_path' => $paths['aPath'], 'v_path' => $paths['vPath'], 'pos' => $this->pos],
            'node_icon' => $nodeIcon ?? '',
        ]);
    }

    /**
     * Renders a database select box like the pre-4.0 navigation panel
     *
     * @return string HTML code
     */
    public function renderDbSelect(UserPrivileges $userPrivileges): string
    {
        $this->buildPath($userPrivileges);

        $quickWarp = $this->quickWarp();

        $this->tree->isGroup = false;

        // Provide for pagination in database select
        $listNavigator = Generator::getListNavigator(
            $this->tree->getPresence($userPrivileges, 'databases'),
            $this->pos,
            ['server' => Current::$server],
            Url::getFromRoute('/navigation'),
            'frame_navigation',
            $this->config->settings['FirstLevelNavigationItems'],
            'pos',
            ['dbselector'],
        );

        $children = $this->tree->children;
        $selected = Current::$database;
        $options = [];
        foreach ($children as $node) {
            if ($node->isNew) {
                continue;
            }

            $paths = $node->getPaths();
            if (! isset($node->links['text'])) {
                continue;
            }

            $title = $node->links['title'] ?? '';
            $options[] = [
                'title' => $title,
                'name' => $node->realName,
                'data' => ['apath' => $paths['aPath'], 'vpath' => $paths['vPath'], 'pos' => $this->pos],
                'isSelected' => $node->realName === $selected,
            ];
        }

        $children = $this->tree->children;
        usort($children, $this->sortNode(...));
        $this->setVisibility();

        $nodes = $this->renderNodes($userPrivileges, $children);

        $databaseUrl = Util::getScriptNameForOption($this->config->settings['DefaultTabDatabase'], 'database');

        return $this->template->render('navigation/tree/database_select', [
            'quick_warp' => $quickWarp,
            'list_navigator' => $listNavigator,
            'server' => Current::$server,
            'options' => $options,
            'nodes' => $nodes,
            'database_url' => $databaseUrl,
        ]);
    }

    /**
     * Makes some nodes visible based on the which node is active
     */
    private function setVisibility(): void
    {
        foreach ($this->vPath as $path) {
            $node = $this->tree;
            foreach ($path as $value) {
                $child = $node->getChild($value);
                if ($child === null) {
                    continue;
                }

                $child->visible = true;
                $node = $child;
            }
        }
    }

    /**
     * Generates the HTML code for displaying the fast filter for tables
     *
     * @param Node $node The node for which to generate the fast filter html
     *
     * @return string LI element used for the fast filter
     */
    private function fastFilterHtml(UserPrivileges $userPrivileges, Node $node): string
    {
        $filterDbMin = $this->config->settings['NavigationTreeDisplayDbFilterMinimum'];
        $filterItemMin = $this->config->settings['NavigationTreeDisplayItemFilterMinimum'];
        $urlParams = [];

        $isRootNode = $node === $this->tree && $this->tree->getPresence($userPrivileges) >= $filterDbMin;
        if ($isRootNode) {
            $urlParams = ['pos' => 0];
        } else {
            $nodeIsContainer = $node->type === NodeType::Container;

            $nodeIsSpecial = in_array($node->realName, self::SPECIAL_NODE_NAMES, true);

            $realParent = $node->realParent();
            if (
                $nodeIsContainer && $nodeIsSpecial
                && $realParent instanceof Node
                && $realParent->getPresence($userPrivileges, $node->realName) >= $filterItemMin
            ) {
                $paths = $node->getPaths();
                $urlParams = [
                    'pos' => $this->pos,
                    'aPath' => $paths['aPath'],
                    'vPath' => $paths['vPath'],
                    'pos2_name' => $node->realName,
                    'pos2_value' => 0,
                ];
            }
        }

        return $this->template->render('navigation/tree/fast_filter', [
            'url_params' => $urlParams,
            'is_root_node' => $isRootNode,
        ]);
    }

    /**
     * Creates the code for displaying the controls
     * at the top of the navigation tree
     *
     * @return string HTML code for the controls
     */
    private function controls(): string
    {
        $collapseAll = Generator::getNavigationLink(
            '#',
            __('Collapse all'),
            's_collapseall',
            'pma_navigation_collapse',
        );
        $syncImage = 's_unlink';
        $title = __('Link with main panel');
        if ($this->config->settings['NavigationLinkWithMainPanel']) {
            $syncImage = 's_link';
            $title = __('Unlink from main panel');
        }

        $unlink = Generator::getNavigationLink('#', $title, $syncImage, 'pma_navigation_sync');

        return $this->template->render('navigation/tree/controls', [
            'collapse_all' => $collapseAll,
            'unlink' => $unlink,
        ]);
    }

    /**
     * Generates the HTML code for displaying the list pagination
     *
     * @param Node $node The node for whose children the page
     *                   selector will be created
     */
    private function getPageSelector(UserPrivileges $userPrivileges, Node $node): string
    {
        $retval = '';
        if ($node === $this->tree) {
            $retval .= Generator::getListNavigator(
                $this->tree->getPresence($userPrivileges, 'databases', $this->searchClause),
                $this->pos,
                ['server' => Current::$server],
                Url::getFromRoute('/navigation'),
                'frame_navigation',
                $this->config->settings['FirstLevelNavigationItems'],
                'pos',
                ['dbselector'],
            );
        } elseif ($node->type === NodeType::Container && ! $node->isGroup) {
            $paths = $node->getPaths();
            $level = isset($paths['aPath_clean'][4]) ? 3 : 2;
            $urlParams = [
                'aPath' => $paths['aPath'],
                'vPath' => $paths['vPath'],
                'pos' => $this->pos,
                'server' => Current::$server,
                'pos2_name' => $paths['aPath_clean'][2],
            ];
            if ($level === 3) {
                $pos = $node->pos3;
                $urlParams['pos2_value'] = $node->pos2;
                $urlParams['pos3_name'] = $paths['aPath_clean'][4];
            } else {
                $pos = $node->pos2;
            }

            /** @var Node $realParent */
            $realParent = $node->realParent();
            $num = $realParent->getPresence($userPrivileges, $node->realName, $this->searchClause2);
            $retval .= Generator::getListNavigator(
                $num,
                $pos,
                $urlParams,
                Url::getFromRoute('/navigation'),
                'frame_navigation',
                $this->config->settings['MaxNavigationItems'],
                'pos' . $level . '_value',
            );
        }

        return $retval;
    }

    /**
     * Called by usort() for sorting the nodes in a container
     *
     * @param Node $a The first element used in the comparison
     * @param Node $b The second element used in the comparison
     *
     * @return int See strnatcmp() and strcmp()
     */
    private function sortNode(Node $a, Node $b): int
    {
        if ($a->isNew) {
            return -1;
        }

        if ($b->isNew) {
            return 1;
        }

        if ($this->config->settings['NaturalOrder']) {
            return strnatcasecmp($a->name, $b->name);
        }

        return strcasecmp($a->name, $b->name);
    }

    /**
     * Display quick warp links, contain Recents and Favorites
     *
     * @return string HTML code
     */
    private function quickWarp(): string
    {
        $renderDetails = [];
        if ($this->config->settings['NumRecentTables'] > 0) {
            $renderDetails['recent'] = RecentFavoriteTables::getInstance(TableType::Recent)->getHtml();
        }

        if ($this->config->settings['NumFavoriteTables'] > 0) {
            $renderDetails['favorite'] = RecentFavoriteTables::getInstance(TableType::Favorite)->getHtml();
        }

        return $this->template->render('navigation/tree/quick_warp', $renderDetails);
    }
}
