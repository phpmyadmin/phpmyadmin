<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation frame
 *
 * @package PhpMyAdmin-Navigation
 */
/**
 * the navigation frame - displays server, db and table selection tree
 */
class PMA_Navigation {
    /**
     * @var int Position in the list of databases,
     *          used for pagination
     */
    private $pos;

    /**
     * Initialises the class, handles incoming requests
     * and fires up rendering of the output
     *
     * return nothing
     */
    public function __construct()
    {
        // Keep the offset of the db list in session before closing it
        if (! isset($_SESSION['tmp_user_values']['navi_limit_offset'])) {
            $_SESSION['tmp_user_values']['navi_limit_offset'] = 0;
        }
        $this->pos = $_SESSION['tmp_user_values']['navi_limit_offset'];
        if (isset($_REQUEST['pos'])) {
            $pos = (int) $_REQUEST['pos'];
            $_SESSION['tmp_user_values']['navi_limit_offset'] = $pos;
            $this->pos = $pos;
        }
        // free the session file, for the other frames to be loaded
        // but only if debugging is not enabled
        if (empty($_SESSION['debug'])) {
            session_write_close();
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
        $tree   = new PMA_NavigationTree($this->pos);
        if (! empty($_REQUEST['full']) || ! empty($_REQUEST['reload'])) {
            $_url_params = array('pos' => $this->pos, 'server' => $GLOBALS['server']);
            $num_db = PMA_DBI_fetch_value(
                "SELECT COUNT(*) FROM `INFORMATION_SCHEMA`.`SCHEMATA`"
            );
            $retval .= PMA_commonFunctions::getInstance()->getListNavigator(
                $num_db,
                $this->pos,
                $_url_params,
                'navigation.php',
                'frame_navigation',
                $GLOBALS['cfg']['MaxDbList']
            );
            $retval .= $tree->renderState();
        } else {
            $retval = $tree->renderPath();
        }

        if (! $retval) {
            $retval = PMA_Message::error(
                __('An error has occured while loading the navigation tree')
            );
        }

        return $retval;
    }
}
?>
