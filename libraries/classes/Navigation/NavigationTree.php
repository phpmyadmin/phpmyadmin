<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Navigation\Nodes\Node;
use PhpMyAdmin\Navigation\Nodes\NodeDatabase;
use PhpMyAdmin\Navigation\Nodes\NodeTable;
use PhpMyAdmin\Navigation\Nodes\NodeTableContainer;
use PhpMyAdmin\Navigation\Nodes\NodeViewContainer;
use PhpMyAdmin\RecentFavoriteTable;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
use const E_USER_WARNING;
use function array_key_exists;
use function array_keys;
use function array_shift;
use function base64_decode;
use function count;
use function explode;
use function floor;
use function get_class;
use function htmlspecialchars;
use function in_array;
use function is_array;
use function is_bool;
use function is_object;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function method_exists;
use function sort;
use function sprintf;
use function strcasecmp;
use function strlen;
use function strnatcasecmp;
use function strpos;
use function strrpos;
use function strstr;
use function substr;
use function trigger_error;
use function trim;
use function urlencode;
use function usort;
use function vsprintf;
use function parse_url;
use function parse_str;
use function htmlspecialchars_decode;

/**
 * Displays a collapsible of database objects in the navigation frame
 */
class NavigationTree
{
    /** @var Node Reference to the root node of the tree */
    private $tree;
    /**
     * @var array The actual paths to all expanded nodes in the tree
     *            This does not include nodes created after the grouping
     *            of nodes has been performed
     */
    private $aPath = [];
    /**
     * @var array The virtual paths to all expanded nodes in the tree
     *            This includes nodes created after the grouping of
     *            nodes has been performed
     */
    private $vPath = [];
    /**
     * @var int Position in the list of databases,
     *          used for pagination
     */
    private $pos;
    /**
     * @var string[] The names of the type of items that are being paginated on
     *               the second level of the navigation tree. These may be
     *               tables, views, functions, procedures or events.
     */
    private $pos2Name = [];
    /**
     * @var int[] The positions of nodes in the lists of tables, views,
     *            routines or events used for pagination
     */
    private $pos2Value = [];
    /**
     * @var string[] The names of the type of items that are being paginated
     *               on the second level of the navigation tree.
     *               These may be columns or indexes
     */
    private $pos3Name = [];
    /**
     * @var int[] The positions of nodes in the lists of columns or indexes
     *            used for pagination
     */
    private $pos3Value = [];
    /**
     * @var string The search clause to use in SQL queries for
     *             fetching databases
     *             Used by the asynchronous fast filter
     */
    private $searchClause = '';
    /**
     * @var string The search clause to use in SQL queries for
     *             fetching nodes
     *             Used by the asynchronous fast filter
     */
    private $searchClause2 = '';
    /**
     * @var bool Whether a warning was raised for large item groups
     *           which can affect performance.
     */
    private $largeGroupWarning = false;

    /** @var Template */
    private $template;

    /** @var DatabaseInterface */
    private $dbi;

    /**
     * @param Template          $template Template instance
     * @param DatabaseInterface $dbi      DatabaseInterface instance
     */
    public function __construct($template, DatabaseInterface $dbi)
    {
        $this->template = $template;
        $this->dbi = $dbi;

        $checkUserPrivileges = new CheckUserPrivileges($this->dbi);
        $checkUserPrivileges->getPrivileges();

        // Save the position at which we are in the database list
        if (isset($_POST['pos'])) {
            $this->pos = (int) $_POST['pos'];
        } elseif (isset($_GET['pos'])) {
            $this->pos = (int) $_GET['pos'];
        }
        if (! isset($this->pos)) {
            $this->pos = $this->getNavigationDbPos();
        }
        // Get the active node
        if (isset($_POST['aPath'])) {
            $this->aPath[0] = $this->parsePath($_POST['aPath']);
            $this->pos2Name[0] = $_POST['pos2_name'] ?? '';
            $this->pos2Value[0] = (int) ($_POST['pos2_value'] ?? 0);
            if (isset($_POST['pos3_name'])) {
                $this->pos3Name[0] = $_POST['pos3_name'] ?? '';
                $this->pos3Value[0] = (int) $_POST['pos3_value'];
            }
        } else {
            if (isset($_POST['n0_aPath'])) {
                $count = 0;
                while (isset($_POST['n' . $count . '_aPath'])) {
                    $this->aPath[$count] = $this->parsePath(
                        $_POST['n' . $count . '_aPath']
                    );
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
        }
        if (isset($_POST['vPath'])) {
            $this->vPath[0] = $this->parsePath($_POST['vPath']);
        } else {
            if (isset($_POST['n0_vPath'])) {
                $count = 0;
                while (isset($_POST['n' . $count . '_vPath'])) {
                    $this->vPath[$count] = $this->parsePath(
                        $_POST['n' . $count . '_vPath']
                    );
                    $count++;
                }
            }
        }
        if (isset($_POST['searchClause'])) {
            $this->searchClause = $_POST['searchClause'];
        }
        if (isset($_POST['searchClause2'])) {
            $this->searchClause2 = $_POST['searchClause2'];
        }
        // Initialize the tree by creating a root node
        $node = NodeFactory::getInstance('NodeDatabaseContainer', 'root');
        $this->tree = $node;
        if (! $GLOBALS['cfg']['NavigationTreeEnableGrouping']
            || ! $GLOBALS['cfg']['ShowDatabasesNavigationAsTree']
        ) {
            return;
        }

        $this->tree->separator = $GLOBALS['cfg']['NavigationTreeDbSeparator'];
        $this->tree->separatorDepth = 10000;
    }

    /**
     * Returns the database position for the page selector
     */
    private function getNavigationDbPos(): int
    {
        $retval = 0;

        if (strlen($GLOBALS['db'] ?? '') === 0) {
            return $retval;
        }

        /*
         * @todo describe a scenario where this code is executed
         */
        if (! $GLOBALS['cfg']['Server']['DisableIS']) {
            $dbSeparator = $this->dbi->escapeString(
                $GLOBALS['cfg']['NavigationTreeDbSeparator']
            );
            $query = 'SELECT (COUNT(DB_first_level) DIV %d) * %d ';
            $query .= 'from ( ';
            $query .= ' SELECT distinct SUBSTRING_INDEX(SCHEMA_NAME, ';
            $query .= " '%s', 1) ";
            $query .= ' DB_first_level ';
            $query .= ' FROM INFORMATION_SCHEMA.SCHEMATA ';
            $query .= " WHERE `SCHEMA_NAME` < '%s' ";
            $query .= ') t ';

            return (int) $this->dbi->fetchValue(
                sprintf(
                    $query,
                    (int) $GLOBALS['cfg']['FirstLevelNavigationItems'],
                    (int) $GLOBALS['cfg']['FirstLevelNavigationItems'],
                    $dbSeparator,
                    $this->dbi->escapeString($GLOBALS['db'])
                )
            );
        }

        $prefixMap = [];
        if ($GLOBALS['dbs_to_test'] === false) {
            $handle = $this->dbi->tryQuery('SHOW DATABASES');
            if ($handle !== false) {
                while ($arr = $this->dbi->fetchArray($handle)) {
                    if (strcasecmp($arr[0], $GLOBALS['db']) >= 0) {
                        break;
                    }

                    $prefix = strstr(
                        $arr[0],
                        $GLOBALS['cfg']['NavigationTreeDbSeparator'],
                        true
                    );
                    if ($prefix === false) {
                        $prefix = $arr[0];
                    }
                    $prefixMap[$prefix] = 1;
                }
            }
        } else {
            $databases = [];
            foreach ($GLOBALS['dbs_to_test'] as $db) {
                $query = "SHOW DATABASES LIKE '" . $db . "'";
                $handle = $this->dbi->tryQuery($query);
                if ($handle === false) {
                    continue;
                }
                while ($arr = $this->dbi->fetchArray($handle)) {
                    $databases[] = $arr[0];
                }
            }
            sort($databases);
            foreach ($databases as $database) {
                if (strcasecmp($database, $GLOBALS['db']) >= 0) {
                    break;
                }

                $prefix = strstr(
                    $database,
                    $GLOBALS['cfg']['NavigationTreeDbSeparator'],
                    true
                );
                if ($prefix === false) {
                    $prefix = $database;
                }
                $prefixMap[$prefix] = 1;
            }
        }

        $navItems = (int) $GLOBALS['cfg']['FirstLevelNavigationItems'];

        return (int) floor(count($prefixMap) / $navItems) * $navItems;
    }

    /**
     * Converts an encoded path to a node in string format to an array
     *
     * @param string $string The path to parse
     *
     * @return array
     */
    private function parsePath($string): array
    {
        $path = explode('.', $string);
        foreach ($path as $key => $value) {
            $path[$key] = base64_decode($value);
        }

        return $path;
    }

    /**
     * Generates the tree structure so that it can be rendered later
     *
     * @return Node|bool The active node or false in case of failure, or true: (@see buildPathPart())
     */
    private function buildPath()
    {
        $retval = $this->tree;

        // Add all databases unconditionally
        $data = $this->tree->getData(
            'databases',
            $this->pos,
            $this->searchClause
        );
        $hiddenCounts = $this->tree->getNavigationHidingData();
        foreach ($data as $db) {
            /** @var NodeDatabase $node */
            $node = NodeFactory::getInstance('NodeDatabase', $db);
            if (isset($hiddenCounts[$db])) {
                $node->setHiddenCount($hiddenCounts[$db]);
            }
            $this->tree->addChild($node);
        }

        // Whether build other parts of the tree depends
        // on whether we have any paths in $this->aPath
        foreach ($this->aPath as $key => $path) {
            $retval = $this->buildPathPart(
                $path,
                $this->pos2Name[$key] ?? '',
                $this->pos2Value[$key] ?? 0,
                $this->pos3Name[$key] ?? '',
                $this->pos3Value[$key] ?? 0
            );
        }

        return $retval;
    }

    /**
     * Builds a branch of the tree
     *
     * @param array  $path  A paths pointing to the branch
     *                      of the tree that needs to be built
     * @param string $type2 The type of item being paginated on
     *                      the second level of the tree
     * @param int    $pos2  The position for the pagination of
     *                      the branch at the second level of the tree
     * @param string $type3 The type of item being paginated on
     *                      the third level of the tree
     * @param int    $pos3  The position for the pagination of
     *                      the branch at the third level of the tree
     *
     * @return Node|bool    The active node or false in case of failure, true if the path contains <= 1 items
     */
    private function buildPathPart(array $path, string $type2, int $pos2, string $type3, int $pos3)
    {
        if (count($path) <= 1) {
            return true;
        }

        array_shift($path); // remove 'root'
        /** @var NodeDatabase|null $db */
        $db = $this->tree->getChild($path[0]);

        if ($db === null) {
            return false;
        }

        $retval = $db;

        $containers = $this->addDbContainers($db, $type2, $pos2);

        array_shift($path); // remove db

        if ((count($path) <= 0 || ! array_key_exists($path[0], $containers))
            && count($containers) != 1
        ) {
            return $retval;
        }

        if (count($containers) === 1) {
            $container = array_shift($containers);
        } else {
            $container = $db->getChild($path[0], true);
            if ($container === null) {
                return false;
            }
        }
        $retval = $container;

        if (count($container->children) <= 1) {
            $dbData = $db->getData(
                $container->realName,
                $pos2,
                $this->searchClause2
            );
            foreach ($dbData as $item) {
                switch ($container->realName) {
                    case 'events':
                        $node = NodeFactory::getInstance(
                            'NodeEvent',
                            $item
                        );
                        break;
                    case 'functions':
                        $node = NodeFactory::getInstance(
                            'NodeFunction',
                            $item
                        );
                        break;
                    case 'procedures':
                        $node = NodeFactory::getInstance(
                            'NodeProcedure',
                            $item
                        );
                        break;
                    case 'tables':
                        $node = NodeFactory::getInstance(
                            'NodeTable',
                            $item
                        );
                        break;
                    case 'views':
                        $node = NodeFactory::getInstance(
                            'NodeView',
                            $item
                        );
                        break;
                    default:
                        break;
                }
                if (! isset($node)) {
                    continue;
                }

                if ($type2 == $container->realName) {
                    $node->pos2 = $pos2;
                }
                $container->addChild($node);
            }
        }
        if (count($path) > 1 && $path[0] !== 'tables') {
            return false;
        }

        array_shift($path); // remove container
        if (count($path) <= 0) {
            return $retval;
        }

        /** @var NodeTable|null $table */
        $table = $container->getChild($path[0], true);
        if ($table === null) {
            if (! $db->getPresence('tables', $path[0])) {
                return false;
            }

            $node = NodeFactory::getInstance(
                'NodeTable',
                $path[0]
            );
            if ($type2 == $container->realName) {
                $node->pos2 = $pos2;
            }
            $container->addChild($node);
            $table = $container->getChild($path[0], true);
        }
        $retval = $table ?? false;
        $containers = $this->addTableContainers(
            $table,
            $pos2,
            $type3,
            $pos3
        );
        array_shift($path); // remove table
        if (count($path) <= 0
            || ! array_key_exists($path[0], $containers)
        ) {
            return $retval;
        }

        $container = $table->getChild($path[0], true);
        $retval = $container ?? false;
        $tableData = $table->getData(
            $container->realName,
            $pos3
        );
        foreach ($tableData as $item) {
            switch ($container->realName) {
                case 'indexes':
                    $node = NodeFactory::getInstance(
                        'NodeIndex',
                        $item
                    );
                    break;
                case 'columns':
                    $node = NodeFactory::getInstance(
                        'NodeColumn',
                        $item
                    );
                    break;
                case 'triggers':
                    $node = NodeFactory::getInstance(
                        'NodeTrigger',
                        $item
                    );
                    break;
                default:
                    break;
            }
            if (! isset($node)) {
                continue;
            }

            $node->pos2 = $container->parent->pos2;
            if ($type3 == $container->realName) {
                $node->pos3 = $pos3;
            }
            $container->addChild($node);
        }

        return $retval;
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
     * @return array An array of new nodes
     */
    private function addTableContainers(NodeTable $table, int $pos2, string $type3, int $pos3): array
    {
        $retval = [];
        if ($table->hasChildren(true) == 0) {
            if ($table->getPresence('columns')) {
                $retval['columns'] = NodeFactory::getInstance(
                    'NodeColumnContainer'
                );
            }
            if ($table->getPresence('indexes')) {
                $retval['indexes'] = NodeFactory::getInstance(
                    'NodeIndexContainer'
                );
            }
            if ($table->getPresence('triggers')) {
                $retval['triggers'] = NodeFactory::getInstance(
                    'NodeTriggerContainer'
                );
            }
            // Add all new Nodes to the tree
            foreach ($retval as $node) {
                $node->pos2 = $pos2;
                if ($type3 == $node->realName) {
                    $node->pos3 = $pos3;
                }
                $table->addChild($node);
            }
        } else {
            foreach ($table->children as $node) {
                if ($type3 == $node->realName) {
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
     * @return array An array of new nodes
     */
    private function addDbContainers(NodeDatabase $db, string $type, int $pos2): array
    {
        // Get items to hide
        $hidden = $db->getHiddenItems('group');
        if (! $GLOBALS['cfg']['NavigationTreeShowTables']
            && ! in_array('tables', $hidden)
        ) {
            $hidden[] = 'tables';
        }
        if (! $GLOBALS['cfg']['NavigationTreeShowViews']
            && ! in_array('views', $hidden)
        ) {
            $hidden[] = 'views';
        }
        if (! $GLOBALS['cfg']['NavigationTreeShowFunctions']
            && ! in_array('functions', $hidden)
        ) {
            $hidden[] = 'functions';
        }
        if (! $GLOBALS['cfg']['NavigationTreeShowProcedures']
            && ! in_array('procedures', $hidden)
        ) {
            $hidden[] = 'procedures';
        }
        if (! $GLOBALS['cfg']['NavigationTreeShowEvents']
            && ! in_array('events', $hidden)
        ) {
            $hidden[] = 'events';
        }

        $retval = [];
        if ($db->hasChildren(true) == 0) {
            if (! in_array('tables', $hidden) && $db->getPresence('tables')) {
                $retval['tables'] = NodeFactory::getInstance(
                    'NodeTableContainer'
                );
            }
            if (! in_array('views', $hidden) && $db->getPresence('views')) {
                $retval['views'] = NodeFactory::getInstance(
                    'NodeViewContainer'
                );
            }
            if (! in_array('functions', $hidden) && $db->getPresence('functions')) {
                $retval['functions'] = NodeFactory::getInstance(
                    'NodeFunctionContainer'
                );
            }
            if (! in_array('procedures', $hidden) && $db->getPresence('procedures')) {
                $retval['procedures'] = NodeFactory::getInstance(
                    'NodeProcedureContainer'
                );
            }
            if (! in_array('events', $hidden) && $db->getPresence('events')) {
                $retval['events'] = NodeFactory::getInstance(
                    'NodeEventContainer'
                );
            }
            // Add all new Nodes to the tree
            foreach ($retval as $node) {
                if ($type == $node->realName) {
                    $node->pos2 = $pos2;
                }
                $db->addChild($node);
            }
        } else {
            foreach ($db->children as $node) {
                if ($type == $node->realName) {
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
     * @param Node $node The node to group or null
     *                   to group the whole tree. If
     *                   passed as an argument, $node
     *                   must be of type CONTAINER
     */
    public function groupTree(?Node $node = null): void
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
    public function groupNode($node): void
    {
        if ($node->type != Node::CONTAINER
            || ! $GLOBALS['cfg']['NavigationTreeEnableExpansion']
        ) {
            return;
        }

        $separators = [];
        if (is_array($node->separator)) {
            $separators = $node->separator;
        } else {
            if (strlen($node->separator)) {
                $separators[] = $node->separator;
            }
        }
        $prefixes = [];
        if ($node->separatorDepth > 0) {
            foreach ($node->children as $child) {
                $prefixPos = false;
                foreach ($separators as $separator) {
                    $sepPos = mb_strpos((string) $child->name, $separator);
                    if ($sepPos == false
                        || $sepPos == mb_strlen($child->name)
                        || $sepPos == 0
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
        // rfe #1634 Don't group if there's only one group and no other items
        if (count($prefixes) === 1) {
            $keys = array_keys($prefixes);
            $key = $keys[0];
            if ($prefixes[$key] == count($node->children) - 1) {
                unset($prefixes[$key]);
            }
        }
        if (! count($prefixes)) {
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
                        . 'grouping in the navigation panel.'
                    ),
                    E_USER_WARNING
                );
                $this->largeGroupWarning = true;
            }

            $groups[$key] = new Node(
                htmlspecialchars((string) $key),
                Node::CONTAINER,
                true
            );
            $groups[$key]->separator = $node->separator;
            $groups[$key]->separatorDepth = $node->separatorDepth - 1;
            $groups[$key]->icon = Generator::getImage(
                'b_group',
                __('Groups')
            );
            $groups[$key]->pos2 = $node->pos2;
            $groups[$key]->pos3 = $node->pos3;
            if ($node instanceof NodeTableContainer
                || $node instanceof NodeViewContainer
            ) {
                $tblGroup = '&amp;tbl_group=' . urlencode((string) $key);
                $groups[$key]->links = [
                    'text' => $node->links['text'] . $tblGroup,
                    'icon' => $node->links['icon'] . $tblGroup,
                ];
            }
            $node->addChild($groups[$key]);
            foreach ($separators as $separator) {
                $separatorLength = strlen($separator);
                // FIXME: this could be more efficient
                foreach ($node->children as $child) {
                    $keySeparatorLength = mb_strlen((string) $key) + $separatorLength;
                    $nameSubstring = mb_substr(
                        (string) $child->name,
                        0,
                        $keySeparatorLength
                    );
                    if (($nameSubstring != $key . $separator
                        && $child->name != $key)
                        || $child->type != Node::OBJECT
                    ) {
                        continue;
                    }
                    $class = get_class($child);
                    $className = substr($class, strrpos($class, '\\') + 1);
                    unset($class);
                    /** @var NodeDatabase $newChild */
                    $newChild = NodeFactory::getInstance(
                        $className,
                        mb_substr(
                            $child->name,
                            $keySeparatorLength
                        )
                    );
                    if ($child instanceof NodeDatabase
                        && $child->getHiddenCount() > 0
                    ) {
                        $newChild->setHiddenCount($child->getHiddenCount());
                    }

                    $newChild->realName = $child->realName;
                    $newChild->icon = $child->icon;
                    $newChild->links = $child->links;
                    $newChild->pos2 = $child->pos2;
                    $newChild->pos3 = $child->pos3;
                    $groups[$key]->addChild($newChild);
                    foreach ($child->children as $elm) {
                        $newChild->addChild($elm);
                    }
                    $node->removeChild($child->name);
                }
            }
        }
        foreach ($prefixes as $key => $value) {
            $this->groupNode($groups[$key]);
            $groups[$key]->classes = 'navGroup';
        }
    }

    /**
     * Renders a state of the tree, used in light mode when
     * either JavaScript and/or Ajax are disabled
     *
     * @return string HTML code for the navigation tree
     */
    public function renderState(): string
    {
        $this->buildPath();

        $quickWarp = $this->quickWarp();
        $fastFilter = $this->fastFilterHtml($this->tree);
        $controls = '';
        if ($GLOBALS['cfg']['NavigationTreeEnableExpansion']) {
            $controls = $this->controls();
        }
        $pageSelector = $this->getPageSelector($this->tree);

        $this->groupTree();
        $children = $this->tree->children;
        usort($children, [
            self::class,
            'sortNode',
        ]);
        $this->setVisibility();

        $nodes = '';
        for ($i = 0, $nbChildren = count($children); $i < $nbChildren; $i++) {
            if ($i == 0) {
                $nodes .= $this->renderNode($children[0], true, 'first');
            } else {
                if ($i + 1 != $nbChildren) {
                    $nodes .= $this->renderNode($children[$i], true);
                } else {
                    $nodes .= $this->renderNode($children[$i], true, 'last');
                }
            }
        }

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
    public function renderPath()
    {
        $node = $this->buildPath();
        if (! is_bool($node)) {
            $this->groupTree();

            $listContent = $this->fastFilterHtml($node);
            $listContent .= $this->getPageSelector($node);
            $children = $node->children;
            usort($children, [
                self::class,
                'sortNode',
            ]);

            for ($i = 0, $nbChildren = count($children); $i < $nbChildren; $i++) {
                if ($i + 1 != $nbChildren) {
                    $listContent .= $this->renderNode($children[$i], true);
                } else {
                    $listContent .= $this->renderNode($children[$i], true, 'last');
                }
            }

            if (! $GLOBALS['cfg']['ShowDatabasesNavigationAsTree']) {
                $parents = $node->parents(true);
                $parentName = $parents[0]->realName;
            }
        }

        $hasSearchClause = ! empty($this->searchClause) || ! empty($this->searchClause2);
        if ($hasSearchClause && ! is_bool($node)) {
            $results = 0;
            if (! empty($this->searchClause2)) {
                if (is_object($node->realParent())) {
                    $results = $node->realParent()
                        ->getPresence(
                            $node->realName,
                            $this->searchClause2
                        );
                }
            } else {
                $results = $this->tree->getPresence(
                    'databases',
                    $this->searchClause
                );
            }
            $results = sprintf(
                _ngettext(
                    '%s result found',
                    '%s results found',
                    $results
                ),
                $results
            );
            Response::getInstance()
                ->addJSON(
                    'results',
                    $results
                );
        }

        if ($node !== false) {
            return $this->template->render('navigation/tree/path', [
                'has_search_results' => ! empty($this->searchClause) || ! empty($this->searchClause2),
                'list_content' => $listContent ?? '',
                'is_tree' => $GLOBALS['cfg']['ShowDatabasesNavigationAsTree'],
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
     */
    private function getPaginationParamsHtml(Node $node): string
    {
        $retval = '';
        $paths = $node->getPaths();
        if (isset($paths['aPath_clean'][2])) {
            $retval .= '<span class="hide pos2_nav"';
            $retval .= ' data-name="' . $paths['aPath_clean'][2] . '"';
            $retval .= ' data-value="' . htmlspecialchars((string) $node->pos2) . '"';
            $retval .= '"></span>';
        }
        if (isset($paths['aPath_clean'][4])) {
            $retval .= '<span class="hide pos3_nav"';
            $retval .= ' data-name="' . $paths['aPath_clean'][4] . '"';
            $retval .= ' data-value="' . htmlspecialchars((string) $node->pos3) . '"';
            $retval .= '"></span>';
        }

        return $retval;
    }

    /**
     * Finds whether given tree matches this tree.
     *
     * @param array $tree  Tree to check
     * @param array $paths Paths to check
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

    /**
     * Renders a single node or a branch of the tree
     *
     * @param Node   $node      The node to render
     * @param bool   $recursive Whether to render a single node or a branch
     * @param string $class     An additional class for the list item
     *
     * @return string HTML code for the tree node or branch
     */
    private function renderNode(Node $node, bool $recursive, string $class = ''): string
    {
        $retval = '';
        $paths = $node->getPaths();
        $nodeIsContainer = $node->type === Node::CONTAINER;

        if ($node->hasSiblings()
            || $node->realParent() === false
        ) {
            $response = Response::getInstance();
            if ($nodeIsContainer
                && count($node->children) === 0
                && ! $response->isAjax()
            ) {
                return '';
            }
            $retval .= '<li class="' . trim($class . ' ' . $node->classes) . '">';
            $sterile = [
                'events',
                'triggers',
                'functions',
                'procedures',
                'views',
                'columns',
                'indexes',
            ];
            $parentName = '';
            $parents = $node->parents(false, true);
            if (count($parents)) {
                $parentName = $parents[0]->realName;
            }
            // if node name itself is in sterile, then allow
            if ($node->isGroup
                || (! in_array($parentName, $sterile) && ! $node->isNew)
                || (in_array($node->realName, $sterile) && ! empty($node->children))
            ) {
                $retval .= "<div class='block'>";
                $iClass = '';
                if ($class === 'first') {
                    $iClass = " class='first'";
                }
                $retval .= '<i' . $iClass . '></i>';
                if (strpos($class, 'last') === false) {
                    $retval .= '<b></b>';
                }

                $match = $this->findTreeMatch(
                    $this->vPath,
                    $paths['vPath_clean']
                );

                $retval .= '<a class="' . $node->getCssClasses($match) . '"';
                $retval .= " href='#'>";

                $retval .= '<span class="hide paths_nav"';
                $retval .= ' data-apath="' . $paths['aPath'] . '"';
                $retval .= ' data-vpath="' . $paths['vPath'] . '"';
                $retval .= ' data-pos="' . $this->pos . '">';
                $retval .= '</span>';
                $retval .= $this->getPaginationParamsHtml($node);
                if ($GLOBALS['cfg']['ShowDatabasesNavigationAsTree']
                    || $parentName !== 'root'
                ) {
                    $retval .= $node->getIcon($match);
                }

                $retval .= '</a>';
                $retval .= '</div>';
            } else {
                $retval .= "<div class='block'>";
                $iClass = '';
                if ($class === 'first') {
                    $iClass = " class='first'";
                }
                $retval .= '<i' . $iClass . '></i>';
                $retval .= $this->getPaginationParamsHtml($node);
                $retval .= '</div>';
            }

            $linkClass = '';
            $haveAjax = [
                'functions',
                'procedures',
                'events',
                'triggers',
                'indexes',
            ];
            $parent = $node->parents(false, true);
            $isNewView = $parent[0]->realName === 'views' && $node->isNew === true;
            if ($parent[0]->type == Node::CONTAINER
                && (in_array($parent[0]->realName, $haveAjax) || $isNewView)
            ) {
                $linkClass = ' ajax';
            }

            if ($nodeIsContainer) {
                $retval .= '<i>';
            }

            // The .second class is used in js/src/navigation.js
            $divClass = 'second';

            $iconLinks = [];
            $icons = [];
            if (isset($node->links['icon']) && ! empty($node->links['icon'])) {
                $iconLinks = $node->links['icon'];
                /** @var array|string $icons */
                $icons = $node->icon;
                if (! is_array($iconLinks)) {
                    $iconLinks = [$iconLinks];
                    $icons = [$icons];
                }
                /** @var array $icons */
                if (count($icons) > 1) {
                    // Generates: .second double class for NavigationTreeDefaultTabTable2
                    $divClass = 'second double';
                }
            }

            $retval .= '<div class="block ' . $divClass . '">';

            if (isset($node->links['icon']) && ! empty($node->links['icon'])) {
                $args = [];
                foreach ($node->parents(true) as $parent) {
                    $args[] = urlencode($parent->realName);
                }

                foreach ($icons as $key => $icon) {
                    $link = $this->encryptQueryParams(vsprintf($iconLinks[$key], $args));
                    if ($linkClass != '') {
                        $retval .= "<a class='" . $linkClass . "' href='" . $link . "'>";
                        $retval .= '' . $icon . '</a>';
                    } else {
                        $retval .= "<a href='" . $link . "'>" . $icon . '</a>';
                    }
                }
            } else {
                $retval .= '<u>' . $node->icon . '</u>';
            }
            $retval .= '</div>';

            if (isset($node->links['text'])) {
                $args = [];
                foreach ($node->parents(true) as $parent) {
                    $args[] = urlencode($parent->realName);
                }
                $link = $this->encryptQueryParams(vsprintf($node->links['text'], $args));
                $title = $node->links['title'] ?? $node->title ?? '';
                if ($nodeIsContainer) {
                    $retval .= "&nbsp;<a class='hover_show_full' href='" . $link . "'>";
                    $retval .= htmlspecialchars($node->name);
                    $retval .= '</a>';
                } else {
                    $retval .= "<a class='hover_show_full" . $linkClass . "' href='" . $link . "'";
                    $retval .= " title='" . $title . "'>";
                    $retval .= htmlspecialchars($node->displayName ?? $node->realName);
                    $retval .= '</a>';
                }
            } else {
                $retval .= '&nbsp;' . $node->name . '';
            }
            $retval .= $node->getHtmlForControlButtons();
            if ($nodeIsContainer) {
                $retval .= '</i>';
            }
            $retval .= '<div class="clearfloat"></div>';
            $wrap = true;
        } else {
            $node->visible = true;
            $wrap = false;
            $retval .= $this->getPaginationParamsHtml($node);
        }

        if ($recursive) {
            $hide = '';
            if (! $node->visible) {
                $hide = " style='display: none;'";
            }
            $children = $node->children;
            usort(
                $children,
                [
                    self::class,
                    'sortNode',
                ]
            );
            $buffer = '';
            $extraClass = '';
            for ($i = 0, $nbChildren = count($children); $i < $nbChildren; $i++) {
                if ($i + 1 == $nbChildren) {
                    $extraClass = ' last';
                }
                $buffer .= $this->renderNode(
                    $children[$i],
                    true,
                    $children[$i]->classes . $extraClass
                );
            }
            if (! empty($buffer)) {
                if ($wrap) {
                    $retval .= '<div' . $hide . " class='list_container'><ul>";
                }
                $retval .= $this->fastFilterHtml($node);
                $retval .= $this->getPageSelector($node);
                $retval .= $buffer;
                if ($wrap) {
                    $retval .= '</ul></div>';
                }
            }
        }
        if ($node->hasSiblings()) {
            $retval .= '</li>';
        }

        return $retval;
    }

    /**
     * Renders a database select box like the pre-4.0 navigation panel
     *
     * @return string HTML code
     */
    public function renderDbSelect(): string
    {
        $this->buildPath();

        $quickWarp = $this->quickWarp();

        $this->tree->isGroup = false;

        // Provide for pagination in database select
        $listNavigator = Generator::getListNavigator(
            $this->tree->getPresence('databases', ''),
            $this->pos,
            ['server' => $GLOBALS['server']],
            Url::getFromRoute('/navigation'),
            'frame_navigation',
            $GLOBALS['cfg']['FirstLevelNavigationItems'],
            'pos',
            ['dbselector']
        );

        $children = $this->tree->children;
        $selected = $GLOBALS['db'];
        $options = [];
        foreach ($children as $node) {
            if ($node->isNew) {
                continue;
            }
            $paths = $node->getPaths();
            if (! isset($node->links['text'])) {
                continue;
            }

            $title = isset($node->links['title']) ? '' : $node->links['title'];
            $options[] = [
                'title' => $title,
                'name' => $node->realName,
                'data' => [
                    'apath' => $paths['aPath'],
                    'vpath' => $paths['vPath'],
                    'pos' => $this->pos,
                ],
                'isSelected' => $node->realName === $selected,
            ];
        }

        $children = $this->tree->children;
        usort($children, [
            self::class,
            'sortNode',
        ]);
        $this->setVisibility();

        $nodes = '';
        for ($i = 0, $nbChildren = count($children); $i < $nbChildren; $i++) {
            if ($i == 0) {
                $nodes .= $this->renderNode($children[0], true, 'first');
            } else {
                if ($i + 1 != $nbChildren) {
                    $nodes .= $this->renderNode($children[$i], true);
                } else {
                    $nodes .= $this->renderNode($children[$i], true, 'last');
                }
            }
        }

        $databaseUrl = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');

        return $this->template->render('navigation/tree/database_select', [
            'quick_warp' => $quickWarp,
            'list_navigator' => $listNavigator,
            'server' => $GLOBALS['server'],
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
    private function fastFilterHtml(Node $node): string
    {
        $retval = '';
        $filterDbMin
            = (int) $GLOBALS['cfg']['NavigationTreeDisplayDbFilterMinimum'];
        $filterItemMin
            = (int) $GLOBALS['cfg']['NavigationTreeDisplayItemFilterMinimum'];
        if ($node === $this->tree
            && $this->tree->getPresence() >= $filterDbMin
        ) {
            $urlParams = ['pos' => 0];
            $retval .= '<li class="fast_filter db_fast_filter">';
            $retval .= '<form class="ajax fast_filter">';
            $retval .= Url::getHiddenInputs($urlParams);
            $retval .= '<input class="searchClause" type="text"';
            $retval .= ' name="searchClause" accesskey="q"';
            $retval .= " placeholder='"
                . __('Type to filter these, Enter to search all');
            $retval .= "'>";
            $retval .= '<span title="' . __('Clear fast filter') . '">X</span>';
            $retval .= '</form>';
            $retval .= '</li>';

            return $retval;
        }

        $nodeIsContainer = $node->type === Node::CONTAINER;
        $nodeIsSpecial = $node->realName === 'tables'
                            || $node->realName === 'views'
                            || $node->realName === 'functions'
                            || $node->realName === 'procedures'
                            || $node->realName === 'events';
        /** @var Node $realParent */
        $realParent = $node->realParent();
        if (($nodeIsContainer && $nodeIsSpecial)
            && method_exists($realParent, 'getPresence')
            && $realParent->getPresence($node->realName) >= $filterItemMin
        ) {
            $paths = $node->getPaths();
            $urlParams = [
                'pos'        => $this->pos,
                'aPath'      => $paths['aPath'],
                'vPath'      => $paths['vPath'],
                'pos2_name'  => $node->realName,
                'pos2_value' => 0,
            ];
            $retval .= "<li class='fast_filter'>";
            $retval .= "<form class='ajax fast_filter'>";
            $retval .= Url::getHiddenFields($urlParams);
            $retval .= "<input class='searchClause' type='text'";
            $retval .= " name='searchClause2'";
            $retval .= " placeholder='"
                . __('Type to filter these, Enter to search all') . "'>";
            $retval .= "<span title='" . __('Clear fast filter') . "'>X</span>";
            $retval .= '</form>';
            $retval .= '</li>';
        }

        return $retval;
    }

    /**
     * Creates the code for displaying the controls
     * at the top of the navigation tree
     *
     * @return string HTML code for the controls
     */
    private function controls(): string
    {
        // always iconic
        $showIcon = true;
        $showText = false;

        $retval = '<!-- CONTROLS START -->';
        $retval .= '<li id="navigation_controls_outer">';
        $retval .= '<div id="navigation_controls">';
        $retval .= Generator::getNavigationLink(
            '#',
            $showText,
            __('Collapse all'),
            $showIcon,
            's_collapseall',
            'pma_navigation_collapse'
        );
        $syncImage = 's_unlink';
        $title = __('Link with main panel');
        if ($GLOBALS['cfg']['NavigationLinkWithMainPanel']) {
            $syncImage = 's_link';
            $title = __('Unlink from main panel');
        }
        $retval .= Generator::getNavigationLink(
            '#',
            $showText,
            $title,
            $showIcon,
            $syncImage,
            'pma_navigation_sync'
        );
        $retval .= '</div>';
        $retval .= '</li>';
        $retval .= '<!-- CONTROLS ENDS -->';

        return $retval;
    }

    /**
     * Generates the HTML code for displaying the list pagination
     *
     * @param Node $node The node for whose children the page
     *                   selector will be created
     */
    private function getPageSelector(Node $node): string
    {
        $retval = '';
        if ($node === $this->tree) {
            $retval .= Generator::getListNavigator(
                $this->tree->getPresence('databases', $this->searchClause),
                $this->pos,
                ['server' => $GLOBALS['server']],
                Url::getFromRoute('/navigation'),
                'frame_navigation',
                $GLOBALS['cfg']['FirstLevelNavigationItems'],
                'pos',
                ['dbselector']
            );
        } else {
            if ($node->type == Node::CONTAINER && ! $node->isGroup) {
                $paths = $node->getPaths();

                $level = isset($paths['aPath_clean'][4]) ? 3 : 2;
                $urlParams = [
                    'aPath'     => $paths['aPath'],
                    'vPath'     => $paths['vPath'],
                    'pos'       => $this->pos,
                    'server'    => $GLOBALS['server'],
                    'pos2_name' => $paths['aPath_clean'][2],
                ];
                if ($level == 3) {
                    $pos = $node->pos3;
                    $urlParams['pos2_value'] = $node->pos2;
                    $urlParams['pos3_name'] = $paths['aPath_clean'][4];
                } else {
                    $pos = $node->pos2;
                }
                /** @var Node $realParent */
                $realParent = $node->realParent();
                $num = $realParent->getPresence(
                    $node->realName,
                    $this->searchClause2
                );
                $retval .= Generator::getListNavigator(
                    $num,
                    $pos,
                    $urlParams,
                    Url::getFromRoute('/navigation'),
                    'frame_navigation',
                    $GLOBALS['cfg']['MaxNavigationItems'],
                    'pos' . $level . '_value'
                );
            }
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
    public static function sortNode(Node $a, Node $b): int
    {
        if ($a->isNew) {
            return -1;
        }

        if ($b->isNew) {
            return 1;
        }

        if ($GLOBALS['cfg']['NaturalOrder']) {
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
        $retval = '<div class="pma_quick_warp">';
        if ($GLOBALS['cfg']['NumRecentTables'] > 0) {
            $retval .= RecentFavoriteTable::getInstance('recent')
                ->getHtml();
        }
        if ($GLOBALS['cfg']['NumFavoriteTables'] > 0) {
            $retval .= RecentFavoriteTable::getInstance('favorite')
                ->getHtml();
        }
        $retval .= '<div class="clearfloat"></div>';
        $retval .= '</div>';

        return $retval;
    }

    /**
     * @param string $link
     *
     * @return string
     */
    private function encryptQueryParams($link)
    {
        global $PMA_Config;

        if (! $PMA_Config->get('URLQueryEncryption')) {
            return $link;
        }

        $url = parse_url($link);
        parse_str(htmlspecialchars_decode($url['query']), $query);

        return $url['path'] . '?' . htmlspecialchars(Url::buildHttpQuery($query));
    }
}
