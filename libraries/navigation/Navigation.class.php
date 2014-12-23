<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This class is responsible for instantiating
 * the various components of the navigation panel
 *
 * @package PhpMyAdmin-navigation
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/navigation/NodeFactory.class.php';
require_once 'libraries/navigation/NavigationHeader.class.php';
require_once 'libraries/navigation/NavigationTree.class.php';

/**
 * The navigation panel - displays server, db and table selection tree
 *
 * @package PhpMyAdmin-Navigation
 */
class PMA_Navigation
{
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
        $tree = new PMA_NavigationTree();
        if (! PMA_Response::getInstance()->isAjax()
            || ! empty($_REQUEST['full'])
            || ! empty($_REQUEST['reload'])
        ) {
            $treeRender = $tree->renderState();
        } else {
            $treeRender = $tree->renderPath();
        }

        if (! $treeRender) {
            $retval .= PMA_Message::error(
                __('An error has occurred while loading the navigation tree')
            )->getDisplay();
        } else {
            $retval .= $treeRender;
        }

        if (! PMA_Response::getInstance()->isAjax()) {
            // closes the tags that were opened by the navigation header
            $retval .= '</div>';
            $retval .= '</div>';
            $retval .= $this->_getDropHandler();
            $retval .= '</div>';
        }

        return $retval;
    }

    /**
     * Add an item of navigation tree to the hidden items list in PMA database.
     *
     * @param string $itemName  name of the navigation tree item
     * @param string $itemType  type of the navigation tree item
     * @param string $dbName    database name
     * @param string $tableName table name if applicable
     *
     * @return void
     */
    public function hideNavigationItem(
        $itemName, $itemType, $dbName, $tableName = null
    ) {
        $navTable = PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
            . "." . PMA_Util::backquote($GLOBALS['cfgRelation']['navigationhiding']);
        $sqlQuery = "INSERT INTO " . $navTable
            . "(`username`, `item_name`, `item_type`, `db_name`, `table_name`)"
            . " VALUES ("
            . "'" . PMA_Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user']) . "',"
            . "'" . PMA_Util::sqlAddSlashes($itemName) . "',"
            . "'" . PMA_Util::sqlAddSlashes($itemType) . "',"
            . "'" . PMA_Util::sqlAddSlashes($dbName) . "',"
            . "'" . (! empty($tableName)? PMA_Util::sqlAddSlashes($tableName) : "" )
            . "')";
        PMA_queryAsControlUser($sqlQuery, false);
    }

    /**
     * Inserts Drag and Drop Import handler
     *
     * @return string html code for drop handler
     */
    private function _getDropHandler()
    {
        $retval = '';
        $retval .= '<div class="pma_drop_handler">'
            . __('Drop files here')
            . '</div>';
        $retval .= '<div class="pma_sql_import_status">';
        $retval .= '<h2>SQL upload ( ';
        $retval .= '<span class="pma_import_count">0</span> ';
        $retval .= ') <span class="close">x</span>';
        $retval .= '<span class="minimize">-</span></h2>';
        $retval .= '<div></div>';
        $retval .= '</div>';
        return $retval;
    }

    /**
     * Remove a hidden item of navigation tree from the
     * list of hidden items in PMA database.
     *
     * @param string $itemName  name of the navigation tree item
     * @param string $itemType  type of the navigation tree item
     * @param string $dbName    database name
     * @param string $tableName table name if applicable
     *
     * @return void
     */
    public function unhideNavigationItem(
        $itemName, $itemType, $dbName, $tableName = null
    ) {
        $navTable = PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
            . "." . PMA_Util::backquote($GLOBALS['cfgRelation']['navigationhiding']);
        $sqlQuery = "DELETE FROM " . $navTable
            . " WHERE"
            . " `username`='"
            . PMA_Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user']) . "'"
            . " AND `item_name`='" . PMA_Util::sqlAddSlashes($itemName) . "'"
            . " AND `item_type`='" . PMA_Util::sqlAddSlashes($itemType) . "'"
            . " AND `db_name`='" . PMA_Util::sqlAddSlashes($dbName) . "'"
            . (! empty($tableName)
                ? " AND `table_name`='" . PMA_Util::sqlAddSlashes($tableName) . "'"
                : ""
            );
        PMA_queryAsControlUser($sqlQuery, false);
    }

    /**
     * Returns HTML for the dialog to show hidden navigation items.
     *
     * @param string $dbName    database name
     * @param string $itemType  type of the items to include
     * @param string $tableName table name
     *
     * @return string HTML for the dialog to show hidden navigation items
     */
    public function getItemUnhideDialog($dbName, $itemType = null, $tableName = null)
    {
        $html  = '<form method="post" action="navigation.php" class="ajax">';
        $html .= '<fieldset>';
        $html .= PMA_URL_getHiddenInputs($dbName, $tableName);

        $navTable = PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
            . "." . PMA_Util::backquote($GLOBALS['cfgRelation']['navigationhiding']);
        $sqlQuery = "SELECT `item_name`, `item_type` FROM " . $navTable
            . " WHERE `username`='"
            . PMA_Util::sqlAddSlashes($GLOBALS['cfg']['Server']['user']) . "'"
            . " AND `db_name`='" . PMA_Util::sqlAddSlashes($dbName) . "'"
            . " AND `table_name`='"
            . (! empty($tableName) ? PMA_Util::sqlAddSlashes($tableName) : '') . "'";
        $result = PMA_queryAsControlUser($sqlQuery, false);

        $hidden = array();
        if ($result) {
            while ($row = $GLOBALS['dbi']->fetchArray($result)) {
                $type = $row['item_type'];
                if (! isset($hidden[$type])) {
                    $hidden[$type] = array();
                }
                $hidden[$type][] = $row['item_name'];
            }
        }
        $GLOBALS['dbi']->freeResult($result);

        $typeMap = array(
            'event' => __('Events:'),
            'function' => __('Functions:'),
            'procedure' => __('Procedures:'),
            'table' => __('Tables:'),
            'view' => __('Views:'),
        );
        if (empty($tableName)) {
            $first = true;
            foreach ($typeMap as $t => $lable) {
                if ((empty($itemType) || $itemType == $t)
                    && isset($hidden[$t])
                ) {
                    $html .= (! $first ? '<br/>' : '')
                        . '<strong>' . $lable . '</strong>';
                    $html .= '<table width="100%"><tbody>';
                    $odd = true;
                    foreach ($hidden[$t] as $hiddenItem) {
                        $html .= '<tr class="' . ($odd ? 'odd' : 'even') . '">';
                        $html .= '<td>' . htmlspecialchars($hiddenItem) . '</td>';
                        $html .= '<td style="width:80px"><a href="navigation.php'
                            . PMA_URL_getCommon()
                            . '&unhideNavItem=true'
                            . '&itemType=' . urlencode($t)
                            . '&itemName=' . urlencode($hiddenItem)
                            . '&dbName=' . urlencode($dbName) . '"'
                            . ' class="unhideNavItem ajax">'
                            . PMA_Util::getIcon('lightbulb.png', __('Show'))
                            .  '</a></td>';
                        $odd = ! $odd;
                    }
                    $html .= '</tbody></table>';
                    $first = false;
                }
            }
        }

        $html .= '</fieldset>';
        $html .= '</form>';
        return $html;
    }
}
?>
