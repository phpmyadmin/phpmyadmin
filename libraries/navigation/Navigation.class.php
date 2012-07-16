<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This class is responsible for instanciating
 * the various components of the navigation panel
 *
 * @package PhpMyAdmin-navigation
 */

// The Nodes are the building blocks for the navigation tree
require_once 'libraries/navigation/Nodes/Node.class.php';
// All of the below Nodes inherit from the base Node
require_once 'libraries/navigation/Nodes/Node_Column.class.php';
require_once 'libraries/navigation/Nodes/Node_Database.class.php';
require_once 'libraries/navigation/Nodes/Node_Event.class.php';
require_once 'libraries/navigation/Nodes/Node_Function.class.php';
require_once 'libraries/navigation/Nodes/Node_Index.class.php';
require_once 'libraries/navigation/Nodes/Node_Procedure.class.php';
require_once 'libraries/navigation/Nodes/Node_Table.class.php';
require_once 'libraries/navigation/Nodes/Node_Trigger.class.php';
require_once 'libraries/navigation/Nodes/Node_View.class.php';
// Containers. Also inherit from the base Node
require_once 'libraries/navigation/Nodes/Node_Column_Container.class.php';
require_once 'libraries/navigation/Nodes/Node_Event_Container.class.php';
require_once 'libraries/navigation/Nodes/Node_Function_Container.class.php';
require_once 'libraries/navigation/Nodes/Node_Index_Container.class.php';
require_once 'libraries/navigation/Nodes/Node_Procedure_Container.class.php';
require_once 'libraries/navigation/Nodes/Node_Table_Container.class.php';
require_once 'libraries/navigation/Nodes/Node_Trigger_Container.class.php';
require_once 'libraries/navigation/Nodes/Node_View_Container.class.php';

// Generates a collapsible tree of database objects
require_once 'libraries/navigation/NavigationTree.class.php';

require_once 'libraries/navigation/NavigationHeader.class.php';

/**
 * The navigation panel - displays server, db and table selection tree
 *
 * @package PhpMyAdmin-Navigation
 */
class PMA_Navigation
{
    /**
     * @var int Position in the list of databases,
     *          used for pagination
     */
    private $_pos;

    /**
     * Initialises the class, handles incoming requests
     * and fires up rendering of the output
     *
     * @return void
     */
    public function __construct()
    {
        $GLOBALS['token'] = $_SESSION[' PMA_token '];

        if (isset($_REQUEST['pos'])) {
            $this->_pos = (int) $_REQUEST['pos'];
        }
        if (! isset($this->_pos)) {
            $this->_pos = $this->_getNavigationDbPos();
        }
    }

    /**
     * Returns the database position for the page selector
     *
     * @return int
     */
    private function _getNavigationDbPos()
    {
        $query  = "SELECT (COUNT(`SCHEMA_NAME`) DIV %d) * %d ";
        $query .= "FROM `INFORMATION_SCHEMA`.`SCHEMATA` ";
        $query .= "WHERE `SCHEMA_NAME` < '%s' ";
        $query .= "ORDER BY `SCHEMA_NAME` ASC";
        return PMA_DBI_fetch_value(
            sprintf(
                $query,
                (int)$GLOBALS['cfg']['MaxNavigationItems'],
                (int)$GLOBALS['cfg']['MaxNavigationItems'],
                PMA_CommonFunctions::getInstance()->sqlAddSlashes($GLOBALS['db'])
            )
        );
    }

    /**
     * Renders the navigation tree, or part of it
     *
     * @return string The navigation tree
     */
    public function getDisplay()
    {
        /* Init */
        $retval = '';
        if (! PMA_Response::getInstance()->isAjax()) {
            $header = new PMA_NavigationHeader();
            $retval = $header->getDisplay();
        }
        $tree = new PMA_NavigationTree($this->_pos);
        if (! PMA_Response::getInstance()->isAjax()
            || ! empty($_REQUEST['full'])
            || ! empty($_REQUEST['reload'])
        ) {
            $_url_params = array('server' => $GLOBALS['server']);
            $num_db      = PMA_DBI_fetch_value(
                "SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`SCHEMATA`"
            );
            $retval .= PMA_commonFunctions::getInstance()->getListNavigator(
                $num_db,
                $this->_pos,
                $_url_params,
                'navigation.php',
                'frame_navigation',
                $GLOBALS['cfg']['MaxNavigationItems'],
                'pos',
                array('dbselector')
            );
            $treeRender = $tree->renderState();
        } else {
            $treeRender = $tree->renderPath();
        }

        if (! $treeRender) {
            $retval .= PMA_Message::error(
                __('An error has occured while loading the navigation tree')
            )->getDisplay();
        } else {
            $retval .= $treeRender;
        }

        if (! PMA_Response::getInstance()->isAjax()) {
            $retval .= '</div>';
            $retval .= '</div>';
            $retval .= '</div>';
        }

        return $retval;
    }
}
?>
