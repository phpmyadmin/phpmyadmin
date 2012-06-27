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
class CollapsibleTree {
    /**
     * @var Node Reference to the root node of the tree
     */
    private $tree;

    /**
     * @var array The actual path to the active node from the tree
     *            This does not include nodes created after the grouping
     *            of nodes has been performed
     */
    private $a_path = array();

    /**
     * @var array The virtual path to the active node from the tree
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
     * @var bool Indicated whether the rest of the tree from the current
     *           node down should be marked as loaded. Used only in AJAX requests
     */
    private $is_loaded = false;

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
            $a_path = explode('.', $_REQUEST['a_path']);
            foreach ($a_path as $key => $value) {
                $a_path[$key] = base64_decode($value);
            }
            $this->a_path = $a_path;
        }
        if (isset($_REQUEST['v_path'])) {
            $v_path = explode('.', $_REQUEST['v_path']);
            foreach ($v_path as $key => $value) {
                $v_path[$key] = base64_decode($value);
            }
            $this->v_path = $v_path;
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
     * Generates the tree structure for when "Light mode" is off
     *
     * @return nothing
     */
    private function buildTree()
    {
        foreach ($this->tree->getData($this->pos) as $db) {
            $node = new Node_Database($db);
            $this->tree->addChild($node);
        }
        foreach ($this->tree->children as $child) {
            $containers = $this->addDbContainers($child);
            foreach ($containers as $key => $value) {
                foreach ($child->getData($key) as $item) {
                    switch ($key) {
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
                        $value->addChild($node);
                    }
                }
            }
        }
    }

    /**
     * Generates the tree structure for when "Light mode" is on
     *
     * @return Node|false The active node or false in case of failure
     */
    private function buildPath()
    {
        $retval = $this->tree;
        foreach ($this->tree->getData($this->pos) as $db) {
            $node = new Node_Database($db);
            $this->tree->addChild($node);
        }
        if (count($this->a_path) > 1) {
            array_shift($this->a_path); // remove 'root'
            $db = $this->tree->getChild($this->a_path[0]);
            $retval = $db;
            $containers = $this->addDbContainers($db);
            array_shift($this->a_path); // remove db
            if (count($this->a_path) > 0 && array_key_exists($this->a_path[0], $containers) || count($containers) == 1) {
                if (count($containers) == 1) {
                    $container = array_shift($containers);
                } else {
                    $container = $db->getChild($this->a_path[0], true);
                }
                $retval = $container;
                foreach ($db->getData($container->real_name) as $item) {
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
                        $container->addChild($node);
                    }
                }
                if (count($this->a_path) > 1 && $this->a_path[0] != 'tables') {
                    $retval = false;
                } else {
                    array_shift($this->a_path); // remove container
                    if (count($this->a_path) > 0) {
                        $table = $container->getChild($this->a_path[0], true);
                        $retval = $table;
                        $containers = $this->addTableContainers($table);
                        array_shift($this->a_path); // remove table
                        foreach ($containers as $container) {
                            foreach ($table->getData($container->real_name) as $item) {
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
                                    $container->addChild($node);
                                }
                            }
                        }
                        $this->is_loaded = true;
                    }
                }
            }
        }
        return $retval;
    }

    /**
     * Adds containers to a node that is a table
     *
     * @param Node $table The table node, new containers will be
     *                    attached to this node
     *
     * @return array An array of new nodes
     */
    private function addTableContainers($table)
    {
        $retval = array();
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
            $table->addChild($node);
        }
        return $retval;
    }

    /**
     * Adds containers to a node that is a database
     *
     * @param Node $db The database node, new containers will be
     *                 attached to this node
     *
     * @return array An array of new nodes
     */
    private function addDbContainers($db)
    {
        $retval = array();
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
            $db->addChild($node);
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
                    $node->addChild($groups[$key]);
                    foreach ($node->children as $child) { // FIXME: this could be more efficient
                        if (substr($child->name, 0, strlen($key)) == $key && $child->type == Node::OBJECT) {
                            $new_child = new Node(substr($child->name, strlen($key)));
                            $new_child->real_name = $child->real_name;
                            $new_child->icon = $child->icon;
                            $new_child->links = $child->links;
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
     * Renders the whole tree for display
     * Used in non-light mode
     *
     * @return string HTML code for the navigation tree
     */
    public function renderTree()
    {
        $this->buildTree();
        $this->groupTree();
        $retval = "<div><ul>\n";
        $children = $this->tree->children;
        usort($children, array('CollapsibleTree', 'sortNode'));
        $this->setVisibility();
        for ($i=0; $i<count($children); $i++) {
            if ($i == 0) {
                $retval .= $this->renderNode($children[0], true, $indent . '    ', 'first');
            } else if ($i + 1 != count($children)) {
                $retval .= $this->renderNode($children[$i], true, $indent . '    ');
            } else {
                $retval .= $this->renderNode($children[$i], true, $indent . '    ', 'last');
            }
        }
        $retval .= "</ul></div>\n";
        return $retval;
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
            $retval  = $this->_commonFunctions->getImage('ajax_clock_small.gif', __('Loading'), array('style' => 'display: none;', 'class' => 'throbber'));
            $retval .= "<div><ul>\n";
            $children = $this->tree->children;
            usort($children, array('CollapsibleTree', 'sortNode'));
            $this->setVisibility();
            for ($i=0; $i<count($children); $i++) {
                if ($i == 0) {
                    $retval .= $this->renderNode($children[0], true, '', 'first');
                } else if ($i + 1 != count($children)) {
                    $retval .= $this->renderNode($children[$i], true, '');
                } else {
                    $retval .= $this->renderNode($children[$i], true, '', 'last');
                }
            }
            $retval .= "</ul></div>\n";
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
            $retval .= "<ul>\n";
            if (($node->real_name == 'tables' || $node->real_name == 'views')
                && $node->numChildren() >= (int)$GLOBALS['cfg']['LeftDisplayTableFilterMinimum']) {
                // fast filter
                $retval .= $this->fastFilterHtml();
            }
            $children = $node->children;
            usort($children, array('CollapsibleTree', 'sortNode'));
            for ($i=0; $i<count($children); $i++) {
                if ($i + 1 != count($children)) {
                    $retval .= $this->renderNode($children[$i], true, '');
                } else {
                    $retval .= $this->renderNode($children[$i], true, '', 'last');
                }
            }
            $retval .= "</ul>";
            $retval .= "</div>\n";
        }
        return $retval;
    }

    /**
     * Renders a single node or a branch of the tree
     *
     * @param Node     $node      The node to render
     * @param int|bool $recursive Bool: Whether to render a single node or a branch
     *                            Int: How many levels deep to render
     * @param string   $indent    String used for indentation of output
     * @param string   $class     An additional class for the list item
     *
     * @return string HTML code for the tree node or branch
     */
    public function renderNode($node, $recursive = -1, $indent = '  ', $class = '')
    {
        if (   $node->type == Node::CONTAINER
            && count($node->children) == 0
            && $GLOBALS['is_ajax_request'] != true
            && $GLOBALS['cfg']['LeftFrameLight'] != true
        ) {
            return '';
        }
        $retval = $indent . "<li" . ( $class || $node->classes ? " class='" . trim($class . ' ' . $node->classes) . "'" : '') . ">";
        $hasChildren = $node->hasChildren(false);
        $sterile = array('events', 'triggers', 'functions', 'procedures', 'views', 'columns', 'indexes');
        if (($GLOBALS['is_ajax_request'] || $hasChildren || $GLOBALS['cfg']['LeftFrameLight'])
            && ! in_array($node->parent->real_name, $sterile) && ! preg_match('/^' . __('New') . '/', $node->real_name)
        ) {
            $a_path = array();
            foreach ($node->parents(true, true, false) as $parent) {
                $a_path[] = base64_encode($parent->real_name);
            }
            $a_path = implode('.', array_reverse($a_path));
            $v_path = array();
            foreach ($node->parents(true, true, true) as $parent) {
                $v_path[] = base64_encode($parent->name);
            }
            $v_path = implode('.', array_reverse($v_path));
            $loaded = '';
            if ($node->is_group || $GLOBALS['cfg']['LeftFrameLight'] != true || $this->is_loaded) {
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
            $retval .= "<a class='expander$loaded$container' target='_self' href='#'>";
            $retval .= "<span class='hide a_path'>" . $a_path . "</span>";
            $retval .= "<span class='hide v_path'>" . $v_path . "</span>";
            $retval .= $this->_commonFunctions->getImage('b_plus.png');
            $retval .= "</a>";
            $retval .= "</div>";
        } else {
            $retval .= "<div class='block'>";
            $retval .= "<i" . ( $class == 'first' ? " class='first'" : '') . "></i>";
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
        if ($recursive) {
            $hide = '';
            if ($node->visible == false) {
                $hide = " style='display: none;'";
            }
            $children = $node->children;
            usort($children, array('CollapsibleTree', 'sortNode'));
            $buffer = '';
            for ($i=0; $i<count($children); $i++) {
                if ($i + 1 != count($children)) {
                    $buffer .= $this->renderNode($children[$i], true, $indent . '    ', $this->classes);
                } else {
                    $buffer .= $this->renderNode($children[$i], true, $indent . '    ', $this->classes . ' last');
                }
            }
            if (! empty($buffer)) {
                $retval .= "\n" . $indent ."  <div$hide class='list_container'><ul>\n";
                if ($GLOBALS['cfg']['LeftFrameLight'] != true
                    && ($node->real_name == 'tables' || $node->real_name == 'views')
                    && $node->numChildren() >= (int)$GLOBALS['cfg']['LeftDisplayTableFilterMinimum']
                ) {
                    $retval .= $this->fastFilterHtml();
                }
                $retval .= $buffer;
                $retval .= $indent . "  </ul></div>\n" . $indent;
            }
        }
        $retval .= "</li>\n";
        return $retval;
    }

    /**
     * Makes some nodes visible based on the which node is active
     *
     * @return nothing
     */
    private function setVisibility()
    {
        $node = $this->tree;
        foreach ($this->v_path as $key => $value) {
            $child = $node->getChild($value);
            if ($child !== false) {
                $child->visible = true;
                $node = $child;
            }
        }
    }

    /**
     * Generates the HTML code for displaying the fast filter for tables
     *
     * @return string LI element used for the fast filter
     */
    private function fastFilterHtml()
    {
        $retval  = "<li class='fast_filter'>";
        $retval .= "<input value='" . __('filter tables by name') . "' />";
        $retval .= "<span title='" . __('Clear Fast Filter') . "'>X</span>";
        $retval .= "</li>";
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
        if ($GLOBALS['cfg']['NaturalOrder']) {
            return strnatcmp($a->name, $b->name);
        } else {
            return strcmp($a->name, $b->name);
        }
    }
}
?>
