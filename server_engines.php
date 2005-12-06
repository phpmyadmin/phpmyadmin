<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:
/**
 * display list of server enignes and additonal information about them
 */

/**
 * requirements
 */
require_once('./libraries/common.lib.php');

/**
 * Does the common work
 */
require('./libraries/server_common.inc.php');
require('./libraries/storage_engines.lib.php');


/**
 * Displays the links
 */
require('./libraries/server_links.inc.php');

/**
 * defines
 */
define('PMA_ENGINE_DETAILS_TYPE_PLAINTEXT', 0);
define('PMA_ENGINE_DETAILS_TYPE_SIZE',      1);
define('PMA_ENGINE_DETAILS_TYPE_NUMERIC',   2); //Has no effect yet...
define('PMA_ENGINE_DETAILS_TYPE_BOOLEAN',   3); // 'ON' or 'OFF'

/**
 * Function for displaying the table of an engine's parameters
 *
 * @param   array   List of MySQL variables and corresponding localized descriptions.
 *                  The array elements should have the following format:
 *                      $variable => array('title' => $title, 'desc' => $description);
 * @param   string  Prefix for the SHOW VARIABLES query.
 * @return  string  The table that was generated based on the given information.
 */
function PMA_generateEngineDetails($variables, $like = null) {

    /**
     * Get the variables!
     */
    if (!empty($variables)) {
        $sql_query = 'SHOW '
                   . (PMA_MYSQL_INT_VERSION >= 40102 ? 'GLOBAL ' : '')
                   . 'VARIABLES'
                   . (empty($like) ? '' : ' LIKE \'' . $like . '\'')
                   . ';';
        $res = PMA_DBI_query($sql_query);
        $mysql_vars = array();
        while ($row = PMA_DBI_fetch_row($res)) {
            if (isset($variables[$row[0]])) {
                $mysql_vars[$row[0]] = $row[1];
            }
        }
        PMA_DBI_free_result($res);
        unset($res, $row, $sql_query);
    }

    if (empty($mysql_vars)) {
        return '<p>' . "\n"
             . '    ' . $GLOBALS['strNoDetailsForEngine'] . "\n"
             . '</p>' . "\n";
    }

    $dt_table       = '<table class="data">' . "\n";
    $odd_row        = false;
    $has_content    = false;

    foreach ($variables as $var => $details) {
        if (!isset($mysql_vars[$var])) {
            continue;
        }

        if (!isset($details['type'])) {
            $details['type'] = PMA_ENGINE_DETAILS_TYPE_PLAINTEXT;
        }
        $is_num = $details['type'] == PMA_ENGINE_DETAILS_TYPE_SIZE
            || $details['type'] == PMA_ENGINE_DETAILS_TYPE_NUMERIC;

        $dt_table     .= '<tr class="' . ( $odd_row ? 'odd' : 'even' ) . '">' . "\n"
                       . '    <td>' . "\n";
        if (!empty($variables[$var]['desc'])) {
            $dt_table .= '        ' . PMA_showHint($details['desc']) . "\n";
        }
        $dt_table     .= '    </td>' . "\n"
    	               . '    <th>' . htmlspecialchars($details['title']) . "\n"
                       . '    </th>' . "\n"
                       . '    <td class="value">';
        switch ($details['type']) {
            case PMA_ENGINE_DETAILS_TYPE_SIZE:
                $parsed_size = PMA_formatByteDown($mysql_vars[$var]);
                $dt_table .= $parsed_size[0] . '&nbsp;' . $parsed_size[1];
                unset($parsed_size);
            break;
            default:
                $dt_table .= htmlspecialchars($mysql_vars[$var]);
        }
        $dt_table     .= '</td>' . "\n"
                      . '</tr>' . "\n";
        $odd_row    = !$odd_row;
        $has_content   = true;
    }

    if (!$has_content) {
        return '';
    }

    $dt_table       .= '</table>' . "\n";

    return $dt_table;
}


/**
 * Did the user request information about a certain storage engine?
 */
if ( empty($_REQUEST['engine'])
  || empty($mysql_storage_engines[$_REQUEST['engine']]) ) {

    /**
     * Displays the sub-page heading
     */
    echo '<h2>' . "\n"
       . ($GLOBALS['cfg']['MainPageIconic']
            ? '<img class="icon" src="' . $pmaThemeImage . 'b_engine.png"'
                .' width="16" height="16" alt="" />' : '' )
       . "\n" . $strStorageEngines . "\n"
       . '</h2>' . "\n";


    /**
     * Displays the table header
     */
    echo '<table>' . "\n"
       . '<thead>' . "\n"
       . '<tr><th>' . $strStorageEngine . '</th>' . "\n";
    if (PMA_MYSQL_INT_VERSION >= 40102) {
        echo '    <th>' . $strDescription . '</th>' . "\n";
    }
    echo '</tr>' . "\n"
       . '</thead>' . "\n"
       . '<tbody>' . "\n";


    /**
     * Listing the storage engines
     */
    $odd_row = true;
    foreach ($mysql_storage_engines as $engine => $details) {
        echo '<tr class="'
           . ($odd_row ? 'odd' : 'even')
           . ($details['Support'] == 'NO' || $details['Support'] == 'DISABLED'
                ? ' disabled'
                : '')
           . '">' . "\n"
           . '    <td><a href="./server_engines.php'
           . PMA_generate_common_url(array( 'engine' => $engine )) . '">' . "\n"
           . '            ' . htmlspecialchars($details['Engine']) . "\n"
           . '        </a>' . "\n"
           . '    </td>' . "\n";
        if (PMA_MYSQL_INT_VERSION >= 40102) {
            echo '    <td>' . htmlspecialchars($details['Comment']) . "\n"
               . '    </td>' . "\n";
        }
        echo '</tr>' . "\n";
        $odd_row = !$odd_row;
    }
    unset($odd_row, $engine, $details);
    echo '</tbody>' . "\n"
       . '</table>' . "\n";

} else {

    /**
     * Displays details about a given Storage Engine
     */

    $engine_plugin = PMA_StorageEngine::getEngine($_REQUEST['engine']);
    echo '<h2>' . "\n"
       . ($GLOBALS['cfg']['MainPageIconic']
            ? '<img class="icon" src="' . $pmaThemeImage . 'b_engine.png"'
                .' width="16" height="16" alt="" />' : '' )
       . '    ' . htmlspecialchars($engine_plugin->getTitle()) . "\n"
       . '</h2>' . "\n\n";
    if (PMA_MYSQL_INT_VERSION >= 40102) {
        echo '<p>' . "\n"
           . '    <em>' . "\n"
           . '        ' . htmlspecialchars($engine_plugin->getComment()) . "\n"
           . '    </em>' . "\n"
           . '</p>' . "\n\n";
    }
    $infoPages = $engine_plugin->getInfoPages();
    if (!empty($infoPages) && is_array($infoPages)) {
        echo '<p>' . "\n"
           . '    <strong>[</strong>' . "\n";
        if (empty($_REQUEST['page'])) {
            echo '    <strong>' . $strServerTabVariables . '</strong>' . "\n";
        } else {
            echo '    <a href="./server_engines.php'
                . PMA_generate_common_url(array( 'engine' => $engine )) . '">'
                . $strServerTabVariables . '</a>' . "\n";
        }
        foreach ($infoPages as $current => $label) {
            echo '    <strong>|</strong>' . "\n";
            if (isset($_REQUEST['page']) && $_REQUEST['page'] == $current) {
                echo '    <strong>' . $label . '</strong>' . "\n";
            } else {
                echo '    <a href="./server_engines.php'
                    . PMA_generate_common_url(
                        array( 'engine' => $engine, 'page' => $current ))
                    . '">' . htmlspecialchars($label) . '</a>' . "\n";
            }
        }
        unset($current, $label);
        echo '    <strong>]</strong>' . "\n"
           . '</p>' . "\n\n";
    }
    unset($infoPages, $page_output);
	if (!empty($_REQUEST['page'])) {
        $page_output = $engine_plugin->getPage($_REQUEST['page']);
    }
    if (!empty($page_output)) {
        echo $page_output;
    } else {
        echo '<p> ' . $engine_plugin->getSupportInformationMessage() . "\n"
           . '</p>' . "\n"
           . PMA_generateEngineDetails($engine_plugin->getVariables(),
                $engine_plugin->getVariablesLikePattern());
    }
}

/**
 * Sends the footer
 */
require_once('./libraries/footer.inc.php');

?>
