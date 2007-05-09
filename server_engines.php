<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display list of server enignes and additonal information about them
 *
 * @version $Id$
 * @todo falcon storage enginge is not listed under dev.mysql.com/doc/refman but dev.mysql.com/doc/falcon/
 */

/**
 *
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}

/**
 * requirements
 */
require_once './libraries/common.inc.php';

/**
 * Does the common work
 */
require './libraries/server_common.inc.php';
require './libraries/StorageEngine.class.php';


/**
 * Displays the links
 */
require './libraries/server_links.inc.php';

/**
 * Did the user request information about a certain storage engine?
 */
if (empty($_REQUEST['engine'])
 || ! PMA_StorageEngine::isValid($_REQUEST['engine'])) {

    /**
     * Displays the sub-page heading
     */
    echo '<h2>' . "\n"
       . ($GLOBALS['cfg']['MainPageIconic']
            ? '<img class="icon" src="' . $pmaThemeImage . 'b_engine.png"'
                .' width="16" height="16" alt="" />' : '')
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
    foreach (PMA_StorageEngine::getStorageEngines() as $engine => $details) {
        echo '<tr class="'
           . ($odd_row ? 'odd' : 'even')
           . ($details['Support'] == 'NO' || $details['Support'] == 'DISABLED'
                ? ' disabled'
                : '')
           . '">' . "\n"
           . '    <td><a href="./server_engines.php'
           . PMA_generate_common_url(array('engine' => $engine)) . '">' . "\n"
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
                .' width="16" height="16" alt="" />' : '')
       . '    ' . htmlspecialchars($engine_plugin->getTitle()) . "\n"
       . '    ' . PMA_showMySQLDocu('', $engine_plugin->getMysqlHelpPage()) . "\n"
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
                . PMA_generate_common_url(array('engine' => $_REQUEST['engine'])) . '">'
                . $strServerTabVariables . '</a>' . "\n";
        }
        foreach ($infoPages as $current => $label) {
            echo '    <strong>|</strong>' . "\n";
            if (isset($_REQUEST['page']) && $_REQUEST['page'] == $current) {
                echo '    <strong>' . $label . '</strong>' . "\n";
            } else {
                echo '    <a href="./server_engines.php'
                    . PMA_generate_common_url(
                        array('engine' => $_REQUEST['engine'], 'page' => $current))
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
           . $engine_plugin->getHtmlVariables();
    }
}

/**
 * Sends the footer
 */
require_once './libraries/footer.inc.php';

?>
