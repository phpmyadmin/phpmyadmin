<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * This class is responsible for instanciating
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
     * Initialises the class
     *
     * @return void
     */
    public function __construct()
    {
        if (empty($GLOBALS['token'])) {
            $GLOBALS['token'] = $_SESSION[' PMA_token '];
        }
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
                __('An error has occured while loading the navigation tree')
            )->getDisplay();
        } else {
            $retval .= $treeRender;
        }

        if (! PMA_Response::getInstance()->isAjax()) {
            // closes the tags that were opened by the navigation header
            $retval .= '</div>';
            $retval .= '</div>';
            $retval .= '</div>';
        }

        return $retval;
    }
}
?>
