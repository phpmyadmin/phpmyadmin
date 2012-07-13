<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree in the left frame
 *
 * @package PhpMyAdmin-Navigation
 */
/**
 * Displays a collapsible of database objects in the navigation frame
 */
class PMA_NavigationTree {
    /**
     * @var Node Reference to the root node of the tree
     */
    private $tree;

    /**
     * @var array The actual paths to all expanded nodes in the tree
     *            This does not include nodes created after the grouping
     *            of nodes has been performed
     */
    private $a_path = array();

    /**
     * @var array The virtual paths to all expanded nodes in the tree
     *            This includes nodes created after the grouping of
     *            nodes has been performed
     */
    private $v_path = array();

    /**
     * @var int Position in the list of databases,
     *          used for pagination
     */
    private $pos;

    /**
     * @var int The names of the type of items that are being paginated on the second
     *          level of the navigation tree. These may be tables, views, functions,
     *          procedures or events.
     */
    private $pos2_name = array();

    /**
     * @var int The positions of nodes in the lists of tables, views, routines or events
     *          used for pagination
     */
    private $pos2_value = array();

    /**
     * @var int The names of the type of items that are being paginated on the second
     *          level of the navigation tree. These may be columns or indexes
     */
    private $pos3_name = array();

    /**
     * @var int The positions of nodes in the lists of columns or indexes
     *          used for pagination
     */
    private $pos3_value = array();

    /**
     * @var string The search clause to use in SQL queries for fetching nodes
     *             Used by the asynchronous fast filter
     */
    private $searchClause = '';

    /**
     * @var object A reference to the common functions object
     */
    private $_commonFunctions;

    /**
     * Initialises the class
     *
     * @param int $pos Position in the list of databases,
     *                 used for pagination
     *
     * @return nothing
     */
    public function __construct($pos)
    {
        $this->_commonFunctions = PMA_commonFunctions::getInstance();

        // Save the position at which we are in the database list
        $this->pos = $pos;
        // Get the active node
        if (isset($_REQUEST['a_path'])) {
            $this->a_path[0] = $this->parsePath($_REQUEST['a_path']);
            $this->pos2_name[0] = $_REQUEST['pos2_name'];
            $this->pos2_value[0] = $_REQUEST['pos2_value'];
            if (isset($_REQUEST['pos3_name'])) {
                $this->pos3_name[0] = $_REQUEST['pos3_name'];
                $this->pos3_value[0] = $_REQUEST['pos3_value'];
            }
        } else if (isset($_REQUEST['a_path_0'])) {
            $count = 0;
            while (isset($_REQUEST['a_path_' . $count])) {
                $this->a_path[$count] = $this->parsePath(
                    $_REQUEST['a_path_' . $count]
                );
                $this->pos2_name[$count]  = $_REQUEST['pos2_name_' . $count];
                $this->pos2_value[$count] = $_REQUEST['pos2_value_' . $count];
                if (isset($_REQUEST['pos3_name_' . $count])) {
                    $this->pos3_name[$count]  = $_REQUEST['pos3_name_' . $count];
                    $this->pos3_value[$count] = $_REQUEST['pos3_value_' . $count];
                }
                $count++;
            }
        }
        if (isset($_REQUEST['v_path'])) {
            $this->v_path[0] = $this->parsePath($_REQUEST['v_path']);
        } else if (isset($_REQUEST['v_path_0'])) {
            $count = 0;
            while (isset($_REQUEST['v_path_' . $count])) {
                $this->v_path[$count] = $this->parsePath(
                    $_REQUEST['v_path_' . $count]
                );
                $count++;
            }
        }
        if (isset($_REQUEST['searchClause'])) {
            $this->searchClause = $_REQUEST['searchClause'];
        }
        // Initialise the tree by creating a root node
        $node = new Node('root', Node::CONTAINER);
        $this->tree = $node;
        if ($GLOBALS['cfg']['LeftFrameDBTree']) {
            $this->tree->separator = $GLOBALS['cfg']['LeftFrameDBSeparator'];
            $this->tree->separator_depth = 10000;
        }
    }

    /**
     * Converts an encoded path to a node in string format to an array
     *
     * @param string $string The path to parse
     *
     * @return array
     */
    private function parsePath($string)
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
    private function buildPath()
    {
        $retval = $this->tree;

        // Add all databases unconditionally
        foreach ($this->tree->getData('', $this->pos) as $db) {
            $node = new Node_Database($db);
            $this->tree->addChild($node);
        }

        // Whether build other parts of the tree depends
        // on whether we have any paths in $this->a_path
        foreach ($this->a_path as $key => $path) {
            $retval = $this->buildPathPart(
                $path,
                $this->pos2_name[$key],
                $this->pos2_value[$key],
                isset($this->pos3_name[$key]) ? $this->pos3_name[$key] : '',
                isset($this->pos3_value[$key]) ? $this->pos3_value[$key] : ''
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
    private function buildPathPart($path, $type2, $pos2, $type3, $pos3)
    {
        if (count($path) > 1) {
            array_shift($path); // remove 'root'
            $db = $this->tree->getChild($path[0]);
            $retval = $db;

            if ($db === false) {
                return false;
            }

            $containers = $this->addDbContainers($db, $type2, $pos2);

            array_shift($path); // remove db

            if (count($path) > 0 && array_key_exists($path[0], $containers) || count($containers) == 1) {
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
                    foreach ($db->getData($container->real_name, $pos2, $this->searchClause) as $item) {
                        switch ($container->real_name) {
                        case 'events':
                            $node = new Node_Event($item);
                            break;
                        case 'functions':
                            $node = new Node_Function($item);
                            break;
                        case 'procedures':
                            $node = new Node_Procedure($item);
                            break;
                        case 'tables':
                            $node = new Node_Table($item);
                            break;
                        case 'views':
                            $node = new Node_View($item);
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
                        $containers = $this->addTableContainers($table, $pos2, $type3, $pos3);
                        array_shift($path); // remove table
                        if (count($path) > 0 && array_key_exists($path[0], $containers)) {
                            $container = $table->getChild($path[0], true);
                            $retval = $container;
                            foreach ($table->getData($container->real_name, $pos3) as $item) {
                                switch ($container->real_name) {
                                case 'indexes':
                                    $node = new Node_Index($item);
                                    break;
                                case 'columns':
                                    $node = new Node_Column($item);
                                    break;
                                case 'triggers':
                                    $node = new Node_Trigger($item);
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
    private function addTableContainers($table, $pos2, $type3, $pos3)
    {
        $retval = array();
        if ($table->hasChildren(true) == 0) {
            if ($table->getPresence('columns')) {
                $retval['columns'] = new Node_Column_Container();
            }
            if ($table->getPresence('indexes')) {
                $retval['indexes'] = new Node_Index_Container();
            }
            if ($table->getPresence('triggers')) {
                $retval['triggers'] = new Node_Trigger_Container();
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
    private function addDbContainers($db, $type, $pos2)
    {
        $retval = array();
        if ($db->hasChildren(true) == 0) {
            if ($db->getPresence('tables')) {
                $retval['tables'] = new Node_Table_Container();
            }
            if ($db->getPresence('views')) {
                $retval['views'] = new Node_View_Container();
            }
            if ($db->getPresence('functions')) {
                $retval['functions'] = new Node_Function_Container();
            }
            if ($db->getPresence('procedures')) {
                $retval['procedures'] = new Node_Procedure_Container();
            }
            if ($db->getPresence('events')) {
                $retval['events'] = new Node_Event_Container();
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
     * @param null|Node $node The node to group or null
     *                        to group the whole tree. If
     *                        passed as an argument, $node
     *                        must be of type CONTAINER
     *
     * @return nothing
     */
    public function groupTree($node = null)
    {
        if (! isset($node)) {
            $node = $this->tree;
        }
        $this->groupNode($node);
        foreach ($node->children as $child) {
            $this->groupNode($child);
            $this->groupTree($child);
        }
    }

    /**
     * Recursively groups tree nodes given a sperarator
     *
     * @param null|Node $node The node to group
     *
     * @return nothing
     */
    public function groupNode($node)
    {
        if ($node->type == Node::CONTAINER) {
            $prefixes = array();
            foreach ($node->children as $child) {
                if (strlen($node->separator) && $node->separator_depth > 0) {
                    $separator = $node->separator;
                    $sep_pos = strpos($child->name, $separator);
                    if ($sep_pos != false && $sep_pos != strlen($child->name)) {
                        $sep_pos++;
                        $prefix = substr($child->name, 0, $sep_pos);
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
                    $groups[$key] = new Node($key, Node::CONTAINER, true);
                    $groups[$key]->separator = $node->separator;
                    $groups[$key]->separator_depth = $node->separator_depth - 1;
                    if ($GLOBALS['cfg']['NavigationBarIconic']) {
                        $groups[$key]->icon = $this->_commonFunctions->getImage('b_group.png');
                    } else {
                        $groups[$key]->icon = '';
                    }
                    $groups[$key]->pos2 = $node->pos2;
                    $groups[$key]->pos3 = $node->pos3;
                    $node->addChild($groups[$key]);
                    foreach ($node->children as $child) { // FIXME: this could be more efficient
                        if (substr($child->name, 0, strlen($key)) == $key && $child->type == Node::OBJECT) {
                            $class = get_class($child);
                            $new_child = new $class(substr($child->name, strlen($key)));
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
                foreach ($prefixes as $key => $value) {
                    $this->groupNode($groups[$key]);
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
        $node = $this->buildPath();
        if ($node === false) {
            $retval = false;
        } else {
            $this->groupTree();
            $retval  = $this->_commonFunctions->getImage(
                'ajax_clock_small.gif',
                __('Loading'),
                array('style' => 'visibility: hidden;', 'class' => 'throbber')
            );
            $retval .= "<div><ul>";
            $children = $this->tree->children;
            usort($children, array('PMA_NavigationTree', 'sortNode'));
            $this->setVisibility();
            for ($i=0; $i<count($children); $i++) {
                if ($i == 0) {
                    $retval .= $this->renderNode($children[0], true, 'first');
                } else if ($i + 1 != count($children)) {
                    $retval .= $this->renderNode($children[$i], true);
                } else {
                    $retval .= $this->renderNode($children[$i], true, 'last');
                }
            }
            $retval .= "</ul></div>";
        }
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
        $node = $this->buildPath();
        if ($node === false) {
            $retval = false;
        } else {
            $this->groupTree();
            $retval = "<div class='list_container' style='display: none;'>";
            $retval .= "<ul>";
            $retval .= $this->fastFilterHtml($node);
            $retval .= $this->getPageSelector($node);
            $children = $node->children;
            usort($children, array('PMA_NavigationTree', 'sortNode'));
            for ($i=0; $i<count($children); $i++) {
                if ($i + 1 != count($children)) {
                    $retval .= $this->renderNode($children[$i], true);
                } else {
                    $retval .= $this->renderNode($children[$i], true, 'last');
                }
            }
            $retval .= "</ul>";
            $retval .= "</div>";
        }
        if (! empty($this->searchClause)) {
            $results = $node->realParent()->getPresence(
                $node->real_name,
                $this->searchClause
            );
            $clientResults = ! empty($_REQUEST['results']) ? (int)$_REQUEST['results'] : 0;
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
    private function getPaginationParamsHtml($node)
    {
        $retval = '';
        $paths = $node->getPaths();
        if (isset($paths['a_path_clean'][2])) {
            $retval .= "<span class='hide pos2_name'>" . $paths['a_path_clean'][2] . "</span>";
            $retval .= "<span class='hide pos2_value'>" . $node->pos2 . "</span>";
        }
        if (isset($paths['a_path_clean'][4])) {
            $retval .= "<span class='hide pos3_name'>" . $paths['a_path_clean'][4] . "</span>";
            $retval .= "<span class='hide pos3_value'>" . $node->pos3 . "</span>";
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
    public function renderNode($node, $recursive = -1, $class = '')
    {
        $retval = '';
        $paths = $node->getPaths();
        if ($node->hasSiblings()) {
            if (   $node->type == Node::CONTAINER
                && count($node->children) == 0
                && $GLOBALS['is_ajax_request'] != true
                && $GLOBALS['cfg']['LeftFrameLight'] != true
            ) {
                return '';
            }
            $retval .= "<li" . ( $class || $node->classes ? " class='" . trim($class . ' ' . $node->classes) . "'" : '') . ">";
            $hasChildren = $node->hasChildren(false);
            $sterile = array('events', 'triggers', 'functions', 'procedures', 'views', 'columns', 'indexes');
            if (($GLOBALS['is_ajax_request'] || $hasChildren || $GLOBALS['cfg']['LeftFrameLight'])
                && ! in_array($node->parent->real_name, $sterile) && ! preg_match('/^' . __('New') . '/', $node->real_name)
            ) {
                $loaded = '';
                if ($node->is_group || $GLOBALS['cfg']['LeftFrameLight'] != true) {
                    $loaded = ' loaded';
                }
                $container = '';
                if ($node->type == Node::CONTAINER) {
                    $container = ' container';
                }
                $retval .= "<div class='block'>";
                $retval .= "<i" . ( $class == 'first' ? " class='first'" : '') . "></i>";
                if (strpos($class, 'last') === false) {
                    $retval .= "<b></b>";
                }

                $icon = $this->_commonFunctions->getImage('b_plus.png');
                $match = 1;
                foreach ($this->a_path as $path) {
                    $match = 1;
                    foreach ($paths['a_path_clean'] as $key => $part) {
                        if (! isset($path[$key]) || $part != $path[$key]) {
                            $match = 0;
                            break;
                        }
                    }
                    if ($match) {
                        $loaded = ' loaded';
                        if (! $node->is_group) {
                            $icon = $this->_commonFunctions->getImage('b_minus.png');
                        }
                        break;
                    }
                }

                foreach ($this->v_path as $path) {
                    $match = 1;
                    foreach ($paths['v_path_clean'] as $key => $part) {
                        if ((! isset($path[$key]) || $part != $path[$key])) {
                            $match = 0;
                            break;
                        }
                    }
                    if ($match) {
                        $loaded = ' loaded';
                        $icon = $this->_commonFunctions->getImage('b_minus.png');
                        break;
                    }
                }

                $retval .= "<a class='expander$loaded$container' target='_self' href='#'>";
                $retval .= "<span class='hide a_path'>" . $paths['a_path'] . "</span>";
                $retval .= "<span class='hide v_path'>" . $paths['v_path'] . "</span>";
                $retval .= "<span class='hide pos'>" . $this->pos . "</span>";
                $retval .= $this->getPaginationParamsHtml($node);
                $retval .= $icon;

                $retval .= "</a>";
                $retval .= "</div>";
            } else {
                $retval .= "<div class='block'>";
                $retval .= "<i" . ( $class == 'first' ? " class='first'" : '') . "></i>";
                $retval .= $this->getPaginationParamsHtml($node);
                $retval .= "</div>";
            }

            if ($node->type == Node::CONTAINER) {
                $retval .= "<i>";
            }
            if ($GLOBALS['cfg']['NavigationBarIconic']) {
                $retval .= "<div class='block'>";
                if (isset($node->links['icon'])) {
                    $args = array();
                    foreach ($node->parents(true) as $parent) {
                        $args[] = urlencode($parent->real_name);
                    }
                    $link = vsprintf($node->links['icon'], $args);
                    $retval .= "<a href='$link'>{$node->icon}</a>";
                } else {
                    $retval .= "<a>{$node->icon}</a>";
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
                    $retval .= "<a href='$link'>" . htmlspecialchars($node->name) . "</a>";
                } else {
                    $retval .= "<a href='$link'>" . htmlspecialchars($node->real_name) . "</a>";
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
            $retval .= $this->getPaginationParamsHtml($node);
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
                    $buffer .= $this->renderNode($children[$i], true, $node->classes);
                } else {
                    $buffer .= $this->renderNode($children[$i], true, $node->classes . ' last');
                }
            }
            if (! empty($buffer)) {
                if ($wrap) {
                    $retval .= "<div$hide class='list_container'><ul>";
                }
                $retval .= $this->fastFilterHtml($node);
                $retval .= $this->getPageSelector($node);
                $retval .= $buffer;
                if ($wrap) {
                    $retval .= "</ul></div>";
                }
            }
        }
        $retval .= "</li>";
        return $retval;
    }

    /**
     * Makes some nodes visible based on the which node is active
     *
     * @return nothing
     */
    private function setVisibility()
    {
        foreach ($this->v_path as $path) {
            $node = $this->tree;
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
    private function fastFilterHtml($node)
    {
        $retval = '';
        if (($node->type == Node::CONTAINER
            && (   $node->real_name == 'tables'
                || $node->real_name == 'views'
                || $node->real_name == 'functions'
                || $node->real_name == 'procedures'
                || $node->real_name == 'events')
            )
            && $node->realParent()->getPresence($node->real_name) >= (int)$GLOBALS['cfg']['LeftDisplayTableFilterMinimum']
        ) {
            $paths = $node->getPaths();
            $url_params = array(
                'pos' => $this->pos,
                'a_path' => $paths['a_path'],
                'v_path' => $paths['v_path'],
                'pos2_name' => $node->real_name,
                'pos2_value' => 0
            );
            $retval .= "<li class='fast_filter'>";
            $retval .= "<form class='ajax'>";
            $retval .= PMA_getHiddenFields($url_params);
            $retval .= "<input class='searchClause' name='searchClause' value='" . __('filter tables by name') . "' />";
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
    private function getPageSelector($node)
    {
        $retval = '';
        if ($node->type == Node::CONTAINER && ! $node->is_group) {
            $paths = $node->getPaths();

            $level = isset($paths['a_path_clean'][4]) ? 3 : 2;
            $_url_params = array(
                'a_path' => $paths['a_path'],
                'v_path' => $paths['v_path'],
                'pos' => $this->pos,
                'server' => $GLOBALS['server'],
                'pos2_name' => $paths['a_path_clean'][2]
            );
            if ($level == 3) {
                $pos = $node->pos3;
                $_url_params['pos2_value'] = $node->pos2;
                $_url_params['pos3_name'] = $paths['a_path_clean'][4];
            } else {
                $pos = $node->pos2;
            }
            $num = $node->realParent()->getPresence($node->real_name, $this->searchClause);
            $retval = $this->_commonFunctions->getListNavigator(
                $num,
                $pos,
                $_url_params,
                'navigation.php',
                'frame_navigation',
                $GLOBALS['cfg']['MaxTableList'],
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
        if (property_exists($a, 'classes')
            && strpos($a->classes, 'new') === 0
        ) {
            return -1;
        } else if (property_exists($b, 'classes')
            && strpos($b->classes, 'new') === 0
        ) {
            return 1;
        }
        if ($GLOBALS['cfg']['NaturalOrder']) {
            return strnatcmp($a->name, $b->name);
        } else {
            return strcmp($a->name, $b->name);
        }
    }
}
?>
