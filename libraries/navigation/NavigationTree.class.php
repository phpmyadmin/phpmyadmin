<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Displays a collapsible of database objects in the navigation frame
 *
 * @package PhpMyAdmin-Navigation
 */
class PMA_NavigationTree
{
    /**
     * @var Node Reference to the root node of the tree
     */
    private $_tree;

    /**
     * @var array The actual paths to all expanded nodes in the tree
     *            This does not include nodes created after the grouping
     *            of nodes has been performed
     */
    private $_aPath = array();

    /**
     * @var array The virtual paths to all expanded nodes in the tree
     *            This includes nodes created after the grouping of
     *            nodes has been performed
     */
    private $_vPath = array();

    /**
     * @var int Position in the list of databases,
     *          used for pagination
     */
    private $_pos;

    /**
     * @var int The names of the type of items that are being paginated on
     *          the second level of the navigation tree. These may be
     *          tables, views, functions, procedures or events.
     */
    private $_pos2_name = array();

    /**
     * @var int The positions of nodes in the lists of tables, views,
     *          routines or events used for pagination
     */
    private $_pos2_value = array();

    /**
     * @var int The names of the type of items that are being paginated
     *          on the second level of the navigation tree.
     *          These may be columns or indexes
     */
    private $_pos3_name = array();

    /**
     * @var int The positions of nodes in the lists of columns or indexes
     *          used for pagination
     */
    private $_pos3_value = array();

    /**
     * @var string The search clause to use in SQL queries for
     *             fetching databases
     *             Used by the asynchronous fast filter
     */
    private $_searchClause = '';

    /**
     * @var string The search clause to use in SQL queries for
     *             fetching nodes
     *             Used by the asynchronous fast filter
     */
    private $_searchClause2 = '';

    /**
     * Initialises the class
     *
     * @return void
     */
    public function __construct()
    {
        // Save the position at which we are in the database list
        if (isset($_REQUEST['pos'])) {
            $this->_pos = (int) $_REQUEST['pos'];
        }
        if (! isset($this->_pos)) {
            $this->_pos = $this->_getNavigationDbPos();
        }
        // Get the active node
        if (isset($_REQUEST['aPath'])) {
            $this->_aPath[0]      = $this->_parsePath($_REQUEST['aPath']);
            $this->_pos2_name[0]  = $_REQUEST['pos2_name'];
            $this->_pos2_value[0] = $_REQUEST['pos2_value'];
            if (isset($_REQUEST['pos3_name'])) {
                $this->_pos3_name[0]  = $_REQUEST['pos3_name'];
                $this->_pos3_value[0] = $_REQUEST['pos3_value'];
            }
        } else if (isset($_REQUEST['n0_aPath'])) {
            $count = 0;
            while (isset($_REQUEST['n' . $count . '_aPath'])) {
                $this->_aPath[$count] = $this->_parsePath(
                    $_REQUEST['n' . $count . '_aPath']
                );
                $index = 'n' . $count . '_pos2_';
                $this->_pos2_name[$count]  = $_REQUEST[$index . 'name'];
                $this->_pos2_value[$count] = $_REQUEST[$index . 'value'];
                $index = 'n' . $count . '_pos3_';
                if (isset($_REQUEST[$index])) {
                    $this->_pos3_name[$count]  = $_REQUEST[$index . 'name'];
                    $this->_pos3_value[$count] = $_REQUEST[$index . 'value'];
                }
                $count++;
            }
        }
        if (isset($_REQUEST['vPath'])) {
            $this->_vPath[0] = $this->_parsePath($_REQUEST['vPath']);
        } else if (isset($_REQUEST['n0_vPath'])) {
            $count = 0;
            while (isset($_REQUEST['n' . $count . '_vPath'])) {
                $this->_vPath[$count] = $this->_parsePath(
                    $_REQUEST['n' . $count . '_vPath']
                );
                $count++;
            }
        }
        if (isset($_REQUEST['searchClause'])) {
            $this->_searchClause = $_REQUEST['searchClause'];
        }
        if (isset($_REQUEST['searchClause2'])) {
            $this->_searchClause2 = $_REQUEST['searchClause2'];
        }
        // Initialise the tree by creating a root node
        $node = PMA_NodeFactory::getInstance('Node', 'root', Node::CONTAINER);
        $this->_tree = $node;
        if ($GLOBALS['cfg']['NavigationTreeEnableGrouping']) {
            $this->_tree->separator = $GLOBALS['cfg']['NavigationTreeDbSeparator'];
            $this->_tree->separator_depth = 10000;
        }
    }

    /**
     * Returns the database position for the page selector
     *
     * @return int
     */
    private function _getNavigationDbPos()
    {
        $retval = 0;
        if (! empty($GLOBALS['db'])) {
            $query  = "SELECT (COUNT(`SCHEMA_NAME`) DIV %d) * %d ";
            $query .= "FROM `INFORMATION_SCHEMA`.`SCHEMATA` ";
            $query .= "WHERE `SCHEMA_NAME` < '%s' ";
            $query .= "ORDER BY `SCHEMA_NAME` ASC";
            $retval = PMA_DBI_fetch_value(
                sprintf(
                    $query,
                    (int)$GLOBALS['cfg']['MaxNavigationItems'],
                    (int)$GLOBALS['cfg']['MaxNavigationItems'],
                    PMA_Util::sqlAddSlashes($GLOBALS['db'])
                )
            );
        }
        return $retval;
    }

    /**
     * Converts an encoded path to a node in string format to an array
     *
     * @param string $string The path to parse
     *
     * @return array
     */
    private function _parsePath($string)
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
     * @return Node|false The active node or false in case of failure
     */
    private function _buildPath()
    {
        $retval = $this->_tree;

        // Add all databases unconditionally
        $data = $this->_tree->getData(
            'databases',
            $this->_pos,
            $this->_searchClause
        );
        foreach ($data as $db) {
            $node = PMA_NodeFactory::getInstance('Node_Database', $db);
            $this->_tree->addChild($node);
        }

        // Whether build other parts of the tree depends
        // on whether we have any paths in $this->_aPath
        foreach ($this->_aPath as $key => $path) {
            $retval = $this->_buildPathPart(
                $path,
                $this->_pos2_name[$key],
                $this->_pos2_value[$key],
                isset($this->_pos3_name[$key]) ? $this->_pos3_name[$key] : '',
                isset($this->_pos3_value[$key]) ? $this->_pos3_value[$key] : ''
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
     * @return Node|false The active node or false in case of failure
     */
    private function _buildPathPart($path, $type2, $pos2, $type3, $pos3)
    {
        $retval = true;
        if (count($path) > 1) {
            array_shift($path); // remove 'root'
            $db = $this->_tree->getChild($path[0]);
            $retval = $db;

            if ($db === false) {
                return false;
            }

            $containers = $this->_addDbContainers($db, $type2, $pos2);

            array_shift($path); // remove db

            if ((count($path) > 0
                && array_key_exists($path[0], $containers))
                || count($containers) == 1
            ) {
                if (count($containers) == 1) {
                    $container = array_shift($containers);
                } else {
                    $container = $db->getChild($path[0], true);
                    if ($container === false) {
                        return false;
                    }
                }
                $retval = $container;

                if (count($container->children) <= 1) {
                    $dbData = $db->getData(
                        $container->real_name,
                        $pos2,
                        $this->_searchClause2
                    );
                    foreach ($dbData as $item) {
                        switch ($container->real_name) {
                        case 'events':
                            $node = PMA_NodeFactory::getInstance(
                                'Node_Event',
                                $item
                            );
                            break;
                        case 'functions':
                            $node = PMA_NodeFactory::getInstance(
                                'Node_Function',
                                $item
                            );
                            break;
                        case 'procedures':
                            $node = PMA_NodeFactory::getInstance(
                                'Node_Procedure',
                                $item
                            );
                            break;
                        case 'tables':
                            $node = PMA_NodeFactory::getInstance(
                                'Node_Table',
                                $item
                            );
                            break;
                        case 'views':
                            $node = PMA_NodeFactory::getInstance(
                                'Node_View',
                                $item
                            );
                            break;
                        default:
                            break;
                        }
                        if (isset($node)) {
                            if ($type2 == $container->real_name) {
                                $node->pos2 = $pos2;
                            }
                            $container->addChild($node);
                        }
                    }
                }
                if (count($path) > 1 && $path[0] != 'tables') {
                    $retval = false;
                } else {
                    array_shift($path); // remove container
                    if (count($path) > 0) {
                        $table = $container->getChild($path[0], true);
                        if ($table === false) {
                            return false;
                        }
                        $retval = $table;
                        $containers = $this->_addTableContainers(
                            $table,
                            $pos2,
                            $type3,
                            $pos3
                        );
                        array_shift($path); // remove table
                        if (count($path) > 0
                            && array_key_exists($path[0], $containers)
                        ) {
                            $container = $table->getChild($path[0], true);
                            $retval = $container;
                            $tableData = $table->getData(
                                $container->real_name,
                                $pos3
                            );
                            foreach ($tableData as $item) {
                                switch ($container->real_name) {
                                case 'indexes':
                                    $node = PMA_NodeFactory::getInstance(
                                        'Node_Index',
                                        $item
                                    );
                                    break;
                                case 'columns':
                                    $node = PMA_NodeFactory::getInstance(
                                        'Node_Column',
                                        $item
                                    );
                                    break;
                                case 'triggers':
                                    $node = PMA_NodeFactory::getInstance(
                                        'Node_Trigger',
                                        $item
                                    );
                                    break;
                                default:
                                    break;
                                }
                                if (isset($node)) {
                                    $node->pos2 = $container->parent->pos2;
                                    if ($type3 == $container->real_name) {
                                        $node->pos3 = $pos3;
                                    }
                                    $container->addChild($node);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $retval;
    }

    /**
     * Adds containers to a node that is a table
     *
     * References to existing children are returned
     * if this function is called twice on the same node
     *
     * @param Node   $table The table node, new containers will be
     *                      attached to this node
     * @param int    $pos2  The position for the pagination of
     *                      the branch at the second level of the tree
     * @param string $type3 The type of item being paginated on
     *                      the third level of the tree
     * @param int    $pos3  The position for the pagination of
     *                      the branch at the third level of the tree
     *
     * @return array An array of new nodes
     */
    private function _addTableContainers($table, $pos2, $type3, $pos3)
    {
        $retval = array();
        if ($table->hasChildren(true) == 0) {
            if ($table->getPresence('columns')) {
                $retval['columns'] = PMA_NodeFactory::getInstance(
                    'Node_Column_Container'
                );
            }
            if ($table->getPresence('indexes')) {
                $retval['indexes'] = PMA_NodeFactory::getInstance(
                    'Node_Index_Container'
                );
            }
            if ($table->getPresence('triggers')) {
                $retval['triggers'] = PMA_NodeFactory::getInstance(
                    'Node_Trigger_Container'
                );
            }
            // Add all new Nodes to the tree
            foreach ($retval as $node) {
                $node->pos2 = $pos2;
                if ($type3 == $node->real_name) {
                    $node->pos3 = $pos3;
                }
                $table->addChild($node);
            }
        } else {
            foreach ($table->children as $node) {
                if ($type3 == $node->real_name) {
                    $node->pos3 = $pos3;
                }
                $retval[$node->real_name] = $node;
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
     * @param Node   $db   The database node, new containers will be
     *                     attached to this node
     * @param string $type The type of item being paginated on
     *                     the second level of the tree
     * @param int    $pos2 The position for the pagination of
     *                     the branch at the second level of the tree
     *
     * @return array An array of new nodes
     */
    private function _addDbContainers($db, $type, $pos2)
    {
        $retval = array();
        if ($db->hasChildren(true) == 0) {
            if ($db->getPresence('tables')) {
                $retval['tables'] = PMA_NodeFactory::getInstance(
                    'Node_Table_Container'
                );
            }
            if ($db->getPresence('views')) {
                $retval['views'] = PMA_NodeFactory::getInstance(
                    'Node_View_Container'
                );
            }
            if ($db->getPresence('functions')) {
                $retval['functions'] = PMA_NodeFactory::getInstance(
                    'Node_Function_Container'
                );
            }
            if ($db->getPresence('procedures')) {
                $retval['procedures'] = PMA_NodeFactory::getInstance(
                    'Node_Procedure_Container'
                );
            }
            if ($db->getPresence('events')) {
                $retval['events'] = PMA_NodeFactory::getInstance(
                    'Node_Event_Container'
                );
            }
            // Add all new Nodes to the tree
            foreach ($retval as $node) {
                if ($type == $node->real_name) {
                    $node->pos2 = $pos2;
                }
                $db->addChild($node);
            }
        } else {
            foreach ($db->children as $node) {
                if ($type == $node->real_name) {
                    $node->pos2 = $pos2;
                }
                $retval[$node->real_name] = $node;
            }
        }
        return $retval;
    }

    /**
     * Recursively groups tree nodes given a separator
     *
     * @param mixed $node The node to group or null
     *                    to group the whole tree. If
     *                    passed as an argument, $node
     *                    must be of type CONTAINER
     *
     * @return void
     */
    public function groupTree($node = null)
    {
        if (! isset($node)) {
            $node = $this->_tree;
        }
        $this->groupNode($node);
        foreach ($node->children as $child) {
            $this->groupTree($child);
        }
    }

    /**
     * Recursively groups tree nodes given a sperarator
     *
     * @param Node $node The node to group
     *
     * @return void
     */
    public function groupNode($node)
    {
        if ($node->type == Node::CONTAINER) {
            $separators = array();
            if (is_array($node->separator)) {
                $separators = $node->separator;
            } else if (strlen($node->separator)) {
                $separators[] = $node->separator;
            }
            $prefixes = array();
            if ($node->separator_depth > 0) {
                foreach ($node->children as $child) {
                    $prefix_pos = false;
                    foreach ($separators as $separator) {
                        $sep_pos = strpos($child->name, $separator);
                        if ($sep_pos != false
                            && $sep_pos != strlen($child->name)
                            && $sep_pos != 0
                            && ($prefix_pos == false || $sep_pos < $prefix_pos)
                        ) {
                            $prefix_pos = $sep_pos;
                        }
                    }
                    if ($prefix_pos !== false) {
                        $prefix = substr($child->name, 0, $prefix_pos);
                        if (! isset($prefixes[$prefix])) {
                            $prefixes[$prefix] = 1;
                        } else {
                            $prefixes[$prefix]++;
                        }
                    }
                }
            }
            foreach ($prefixes as $key => $value) {
                if ($value == 1) {
                    unset($prefixes[$key]);
                }
            }
            if (count($prefixes)) {
                $groups = array();
                foreach ($prefixes as $key => $value) {
                    $groups[$key] = new Node(
                        $key,
                        Node::CONTAINER,
                        true
                    );
                    $groups[$key]->separator = $node->separator;
                    $groups[$key]->separator_depth = $node->separator_depth - 1;
                    $groups[$key]->icon = '';
                    if (in_array(
                        $GLOBALS['cfg']['TableNavigationLinksMode'],
                        array('icons', 'both')
                        )
                    ) {
                        $groups[$key]->icon = PMA_Util::getImage(
                            'b_group.png'
                        );
                    }
                    $groups[$key]->pos2 = $node->pos2;
                    $groups[$key]->pos3 = $node->pos3;
                    $node->addChild($groups[$key]);
                    foreach ($separators as $separator) {
                        // FIXME: this could be more efficient
                        foreach ($node->children as $child) {
                            $name_substring = substr(
                                $child->name, 0, strlen($key) + strlen($separator)
                            );
                            if ($name_substring == $key . $separator
                                && $child->type == Node::OBJECT
                            ) {
                                $class = get_class($child);
                                $new_child = PMA_NodeFactory::getInstance(
                                    $class,
                                    substr(
                                        $child->name,
                                        strlen($key) + strlen($separator)
                                    )
                                );
                                $new_child->real_name = $child->real_name;
                                $new_child->icon = $child->icon;
                                $new_child->links = $child->links;
                                $new_child->pos2 = $child->pos2;
                                $new_child->pos3 = $child->pos3;
                                $groups[$key]->addChild($new_child);
                                foreach ($child->children as $elm) {
                                    $new_child->addChild($elm);
                                }
                                $node->removeChild($child->name);
                            }
                        }
                    }
                }
                foreach ($prefixes as $key => $value) {
                    $this->groupNode($groups[$key]);
                    $groups[$key]->classes = "navGroup";
                }
            }
        }
    }

    /**
     * Renders a state of the tree, used in light mode when
     * either JavaScript and/or Ajax are disabled
     *
     * @return string HTML code for the navigation tree
     */
    public function renderState()
    {
        $this->_buildPath();
        $retval  = $this->_fastFilterHtml($this->_tree);
        $retval .= $this->_getPageSelector($this->_tree);
        $this->groupTree();
        $retval .= "<div><ul>";
        $children = $this->_tree->children;
        usort($children, array('PMA_NavigationTree', 'sortNode'));
        $this->_setVisibility();
        for ($i=0; $i<count($children); $i++) {
            if ($i == 0) {
                $retval .= $this->_renderNode($children[0], true, 'first');
            } else if ($i + 1 != count($children)) {
                $retval .= $this->_renderNode($children[$i], true);
            } else {
                $retval .= $this->_renderNode($children[$i], true, 'last');
            }
        }
        $retval .= "</ul></div>";
        return $retval;
    }

    /**
     * Renders a part of the tree, used for Ajax
     * requests in light mode
     *
     * @return string HTML code for the navigation tree
     */
    public function renderPath()
    {
        $node = $this->_buildPath();
        if ($node === false) {
            $retval = false;
        } else {
            $this->groupTree();
            $retval  = "<div class='list_container' style='display: none;'>";
            $retval .= "<ul>";
            $retval .= $this->_fastFilterHtml($node);
            $retval .= $this->_getPageSelector($node);
            $children = $node->children;
            usort($children, array('PMA_NavigationTree', 'sortNode'));
            for ($i=0; $i<count($children); $i++) {
                if ($i + 1 != count($children)) {
                    $retval .= $this->_renderNode($children[$i], true);
                } else {
                    $retval .= $this->_renderNode($children[$i], true, 'last');
                }
            }
            $retval .= "</ul>";
            $retval .= "</div>";
        }

        if (! empty($this->_searchClause) || ! empty($this->_searchClause2)) {
            if (! empty($this->_searchClause2)) {
                $results = $node->realParent()->getPresence(
                    $node->real_name,
                    $this->_searchClause2
                );
            } else {
                $results = $this->_tree->getPresence(
                    'databases',
                    $this->_searchClause
                );
            }

            $clientResults = 0;
            if (! empty($_REQUEST['results'])) {
                $clientResults = (int)$_REQUEST['results'];
            }
            $otherResults = $results - $clientResults;
            if ($otherResults < 1) {
                $otherResults = '';
            } else {
                $otherResults = sprintf(
                    _ngettext(
                        '%s other result found',
                        '%s other results found',
                        $otherResults
                    ),
                    $otherResults
                );
            }
            PMA_Response::getInstance()->addJSON(
                'results',
                $otherResults
            );
        }
        return $retval;
    }

    /**
     * Renders the parameters that are required on the client
     * side to know which page(s) we will be requesting data from
     *
     * @param Node $node The node to create the pagination parameters for
     *
     * @return string
     */
    private function _getPaginationParamsHtml($node)
    {
        $retval = '';
        $paths  = $node->getPaths();
        if (isset($paths['aPath_clean'][2])) {
            $retval .= "<span class='hide pos2_name'>";
            $retval .= $paths['aPath_clean'][2];
            $retval .= "</span>";
            $retval .= "<span class='hide pos2_value'>";
            $retval .= $node->pos2;
            $retval .= "</span>";
        }
        if (isset($paths['aPath_clean'][4])) {
            $retval .= "<span class='hide pos3_name'>";
            $retval .= $paths['aPath_clean'][4];
            $retval .= "</span>";
            $retval .= "<span class='hide pos3_value'>";
            $retval .= $node->pos3;
            $retval .= "</span>";
        }
        return $retval;
    }

    /**
     * Renders a single node or a branch of the tree
     *
     * @param Node     $node      The node to render
     * @param int|bool $recursive Bool: Whether to render a single node or a branch
     *                            Int: How many levels deep to render
     * @param string   $class     An additional class for the list item
     *
     * @return string HTML code for the tree node or branch
     */
    private function _renderNode($node, $recursive = -1, $class = '')
    {
        $retval = '';
        $paths  = $node->getPaths();
        if ($node->hasSiblings()
            || isset($_REQUEST['results'])
            || $node->realParent() === false
        ) {
            if (   $node->type == Node::CONTAINER
                && count($node->children) == 0
                && $GLOBALS['is_ajax_request'] != true
            ) {
                return '';
            }
            $liClass = '';
            if ($class || $node->classes) {
                $liClass = " class='" . trim($class . ' ' . $node->classes) . "'";
            }
            $retval .= "<li$liClass>";
            $sterile = array(
                'events',
                'triggers',
                'functions',
                'procedures',
                'views',
                'columns',
                'indexes'
            );
            $parentName = '';
            $parents = $node->parents(false, true);
            if (count($parents)) {
                $parentName = $parents[0]->real_name;
            }
            if ($node->is_group
                || (! in_array($parentName, $sterile) && ! $node->isNew)
            ) {
                $loaded = '';
                if ($node->is_group) {
                    $loaded = ' loaded';
                }
                $container = '';
                if ($node->type == Node::CONTAINER) {
                    $container = ' container';
                }
                $retval .= "<div class='block'>";
                $iClass = '';
                if ($class == 'first') {
                    $iClass = " class='first'";
                }
                $retval .= "<i$iClass></i>";
                if (strpos($class, 'last') === false) {
                    $retval .= "<b></b>";
                }
                $icon  = PMA_Util::getImage('b_plus.png');
                $match = 1;
                foreach ($this->_aPath as $path) {
                    $match = 1;
                    foreach ($paths['aPath_clean'] as $key => $part) {
                        if (! isset($path[$key]) || $part != $path[$key]) {
                            $match = 0;
                            break;
                        }
                    }
                    if ($match) {
                        $loaded = ' loaded';
                        if (! $node->is_group) {
                            $icon = PMA_Util::getImage(
                                'b_minus.png'
                            );
                        }
                        break;
                    }
                }

                foreach ($this->_vPath as $path) {
                    $match = 1;
                    foreach ($paths['vPath_clean'] as $key => $part) {
                        if ((! isset($path[$key]) || $part != $path[$key])) {
                            $match = 0;
                            break;
                        }
                    }
                    if ($match) {
                        $loaded = ' loaded';
                        $icon  = PMA_Util::getImage('b_minus.png');
                        break;
                    }
                }

                $retval .= "<a class='expander$loaded$container'";
                $retval .= " href='#'>";
                $retval .= "<span class='hide aPath'>";
                $retval .= $paths['aPath'];
                $retval .= "</span>";
                $retval .= "<span class='hide vPath'>";
                $retval .= $paths['vPath'];
                $retval .= "</span>";
                $retval .= "<span class='hide pos'>";
                $retval .= $this->_pos;
                $retval .= "</span>";
                $retval .= $this->_getPaginationParamsHtml($node);
                $retval .= $icon;

                $retval .= "</a>";
                $retval .= "</div>";
            } else {
                $retval .= "<div class='block'>";
                $iClass  = '';
                if ($class == 'first') {
                    $iClass = " class='first'";
                }
                $retval .= "<i$iClass></i>";
                $retval .= $this->_getPaginationParamsHtml($node);
                $retval .= "</div>";
            }

            $linkClass = '';
            $haveAjax = array(
                'functions',
                'procedures',
                'events',
                'triggers',
                'indexes'
            );
            $parent = $node->parents(false, true);
            if ($parent[0]->type == Node::CONTAINER
                && (in_array($parent[0]->real_name, $haveAjax)
                    || ($parent[0]->real_name == 'views'
                        && $node->isNew == true
                    )
                )
            ) {
                $linkClass = ' class="ajax"';
            }

            if ($node->type == Node::CONTAINER) {
                $retval .= "<i>";
            }
            if (in_array(
                $GLOBALS['cfg']['TableNavigationLinksMode'],
                array('icons', 'both')
                )
            ) {
                $retval .= "<div class='block'>";
                if (isset($node->links['icon'])) {
                    $args = array();
                    foreach ($node->parents(true) as $parent) {
                        $args[] = urlencode($parent->real_name);
                    }
                    $link = vsprintf($node->links['icon'], $args);
                    $retval .= "<a$linkClass href='$link'>{$node->icon}</a>";
                } else {
                    $retval .= "<u>{$node->icon}</u>";
                }
                $retval .= "</div>";
            }
            if (isset($node->links['text'])) {
                $args = array();
                foreach ($node->parents(true) as $parent) {
                    $args[] = urlencode($parent->real_name);
                }
                $link = vsprintf($node->links['text'], $args);
                if ($node->type == Node::CONTAINER) {
                    $retval .= "<a href='$link'>";
                    $retval .= htmlspecialchars($node->name);
                    $retval .= "</a>";
                } else {
                    if ($GLOBALS['cfg']['ShowTooltip']) {
                        $title = $node->getComment();
                        if ($title) {
                            $title = ' title="'
                                . htmlentities($title, ENT_QUOTES, 'UTF-8') 
                                . '"';
                        }
                    } else {
                        $title = '';
                    }
                    $retval .= "<a$linkClass$title href='$link'>";
                    $retval .= htmlspecialchars($node->real_name);
                    $retval .= "</a>";
                }
            } else {
                $retval .= "{$node->name}";
            }
            if ($node->type == Node::CONTAINER) {
                $retval .= "</i>";
            }
            $wrap = true;
        } else {
            $node->visible = true;
            $wrap = false;
            $retval .= $this->_getPaginationParamsHtml($node);
        }

        if ($recursive) {
            $hide = '';
            if ($node->visible == false) {
                $hide = " style='display: none;'";
            }
            $children = $node->children;
            usort($children, array('PMA_NavigationTree', 'sortNode'));
            $buffer = '';
            for ($i=0; $i<count($children); $i++) {
                if ($i + 1 != count($children)) {
                    $buffer .= $this->_renderNode(
                        $children[$i],
                        true,
                        $children[$i]->classes
                    );
                } else {
                    $buffer .= $this->_renderNode(
                        $children[$i],
                        true,
                        $children[$i]->classes . ' last'
                    );
                }
            }
            if (! empty($buffer)) {
                if ($wrap) {
                    $retval .= "<div$hide class='list_container'><ul>";
                }
                $retval .= $this->_fastFilterHtml($node);
                $retval .= $this->_getPageSelector($node);
                $retval .= $buffer;
                if ($wrap) {
                    $retval .= "</ul></div>";
                }
            }
        }
        if ($node->hasSiblings() || isset($_REQUEST['results'])) {
            $retval .= "</li>";
        }
        return $retval;
    }

    /**
     * Makes some nodes visible based on the which node is active
     *
     * @return nothing
     */
    private function _setVisibility()
    {
        foreach ($this->_vPath as $path) {
            $node = $this->_tree;
            foreach ($path as $value) {
                $child = $node->getChild($value);
                if ($child !== false) {
                    $child->visible = true;
                    $node = $child;
                }
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
    private function _fastFilterHtml($node)
    {
        $retval = '';
        if ($node === $this->_tree
            && $this->_tree->getPresence() >= (int)$GLOBALS['cfg']['NavigationTreeDisplayDbFilterMinimum']
        ) {
            $url_params = array(
                'pos' => 0
            );
            $retval .= "<ul>";
            $retval .= "<li class='fast_filter db_fast_filter'>";
            $retval .= "<form class='ajax fast_filter'>";
            $retval .= PMA_getHiddenFields($url_params);
            $retval .= "<input class='searchClause' name='searchClause'";
            $retval .= " value='" . __('filter databases by name') . "' />";
            $retval .= "<span title='" . __('Clear Fast Filter') . "'>X</span>";
            $retval .= "</form>";
            $retval .= "</li>";
            $retval .= "</ul>";
        } else if (($node->type == Node::CONTAINER
            && (   $node->real_name == 'tables'
                || $node->real_name == 'views'
                || $node->real_name == 'functions'
                || $node->real_name == 'procedures'
                || $node->real_name == 'events')
            )
            && $node->realParent()->getPresence($node->real_name) >= (int)$GLOBALS['cfg']['NavigationTreeDisplayItemFilterMinimum']
        ) {
            $paths = $node->getPaths();
            $url_params = array(
                'pos' => $this->_pos,
                'aPath' => $paths['aPath'],
                'vPath' => $paths['vPath'],
                'pos2_name' => $node->real_name,
                'pos2_value' => 0
            );
            $retval .= "<li class='fast_filter'>";
            $retval .= "<form class='ajax fast_filter'>";
            $retval .= PMA_getHiddenFields($url_params);
            $retval .= "<input class='searchClause' name='searchClause2'";
            $retval .= " value='" . __('filter items by name') . "' />";
            $retval .= "<span title='" . __('Clear Fast Filter') . "'>X</span>";
            $retval .= "</form>";
            $retval .= "</li>";
        }
        return $retval;
    }

    /**
     * Generates the HTML code for displaying the list pagination
     *
     * @param Node $node The node for whose children the page
     *                   selector will be created
     *
     * @return string
     */
    private function _getPageSelector($node)
    {
        $retval = '';
        if ($node === $this->_tree) {
             $retval .= PMA_Util::getListNavigator(
                 $this->_tree->getPresence('databases', $this->_searchClause),
                 $this->_pos,
                 array('server' => $GLOBALS['server']),
                 'navigation.php',
                 'frame_navigation',
                 $GLOBALS['cfg']['MaxNavigationItems'],
                 'pos',
                 array('dbselector')
             );
        } else if ($node->type == Node::CONTAINER && ! $node->is_group) {
            $paths = $node->getPaths();

            $level = isset($paths['aPath_clean'][4]) ? 3 : 2;
            $_url_params = array(
                'aPath' => $paths['aPath'],
                'vPath' => $paths['vPath'],
                'pos' => $this->_pos,
                'server' => $GLOBALS['server'],
                'pos2_name' => $paths['aPath_clean'][2]
            );
            if ($level == 3) {
                $pos = $node->pos3;
                $_url_params['pos2_value'] = $node->pos2;
                $_url_params['pos3_name'] = $paths['aPath_clean'][4];
            } else {
                $pos = $node->pos2;
            }
            $num = $node->realParent()->getPresence(
                $node->real_name,
                $this->_searchClause2
            );
            $retval .= PMA_Util::getListNavigator(
                $num,
                $pos,
                $_url_params,
                'navigation.php',
                'frame_navigation',
                $GLOBALS['cfg']['MaxNavigationItems'],
                'pos' . $level . '_value'
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
    static public function sortNode($a, $b)
    {
        if ($a->isNew) {
            return -1;
        } else if ($b->isNew) {
            return 1;
        }
        if ($GLOBALS['cfg']['NaturalOrder']) {
            return strnatcasecmp($a->name, $b->name);
        } else {
            return strcasecmp($a->name, $b->name);
        }
    }
}
?>
