<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying server engines
 *
 * @usedby  server_engines.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * setup HTML for server Engines information
 *
 * @return string
 */
function PMA_getHtmlForServerEngines()
{
    /**
     * Did the user request information about a certain storage engine?
     */
    $html = '';
    if (empty($_REQUEST['engine'])
        || ! PMA_StorageEngine::isValid($_REQUEST['engine'])
    ) {
        $html .= PMA_getHtmlForAllServerEngines();
    } else {
        $html .= PMA_getHtmlForSpecifiedServerEngines();
    }

    return $html;
}

/**
 * setup HTML for server all Engines information
 *
 * @return string
 */
function PMA_getHtmlForAllServerEngines()
{
    /**
     * Displays the table header
     */
    $html = '<table class="noclick">' . "\n"
        . '<thead>' . "\n"
        . '<tr><th>' . __('Storage Engine') . '</th>' . "\n"
        . '    <th>' . __('Description') . '</th>' . "\n"
        . '</tr>' . "\n"
        . '</thead>' . "\n"
        . '<tbody>' . "\n";

    /**
     * Listing the storage engines
     */
    $odd_row = true;
    foreach (PMA_StorageEngine::getStorageEngines() as $engine => $details) {
        $html .= '<tr class="'
            . ($odd_row ? 'odd' : 'even')
            . ($details['Support'] == 'NO' || $details['Support'] == 'DISABLED'
                ? ' disabled' : '')
            . '">' . "\n"
            . '    <td><a rel="newpage" href="server_engines.php'
            . PMA_URL_getCommon(array('engine' => $engine)) . '">' . "\n"
            . '            ' . htmlspecialchars($details['Engine']) . "\n"
            . '        </a></td>' . "\n"
            . '    <td>' . htmlspecialchars($details['Comment']) . '</td>' . "\n"
            . '</tr>' . "\n";
        $odd_row = !$odd_row;
    }

    unset($odd_row, $engine, $details);
    $html .= '</tbody>' . "\n"
        . '</table>' . "\n";

    return $html;
}

/**
 * setup HTML for a given Storage Engine
 *
 * @return string
 */
function PMA_getHtmlForSpecifiedServerEngines()
{
    /**
     * Displays details about a given Storage Engine
     */
    $html = '';
    $engine_plugin = PMA_StorageEngine::getEngine($_REQUEST['engine']);
    $html .= '<h2>' . "\n"
        . PMA_Util::getImage('b_engine.png')
        . '    ' . htmlspecialchars($engine_plugin->getTitle()) . "\n"
        . '    ' . PMA_Util::showMySQLDocu($engine_plugin->getMysqlHelpPage())
        . "\n" . '</h2>' . "\n\n";
    $html .= '<p>' . "\n"
        . '    <em>' . "\n"
        . '        ' . htmlspecialchars($engine_plugin->getComment()) . "\n"
        . '    </em>' . "\n"
        . '</p>' . "\n\n";
    $infoPages = $engine_plugin->getInfoPages();
    if (! empty($infoPages) && is_array($infoPages)) {
        $html .= '<p>' . "\n"
            . '    <strong>[</strong>' . "\n";
        if (empty($_REQUEST['page'])) {
            $html .= '    <strong>' . __('Variables') . '</strong>' . "\n";
        } else {
            $html .= '    <a href="server_engines.php'
                . PMA_URL_getCommon(array('engine' => $_REQUEST['engine']))
                . '">' . __('Variables') . '</a>' . "\n";
        }
        foreach ($infoPages as $current => $label) {
            $html .= '    <strong>|</strong>' . "\n";
            if (isset($_REQUEST['page']) && $_REQUEST['page'] == $current) {
                $html .= '    <strong>' . $label . '</strong>' . "\n";
            } else {
                $html .= '    <a href="server_engines.php'
                    . PMA_URL_getCommon(
                        array('engine' => $_REQUEST['engine'], 'page' => $current)
                    )
                    . '">' . htmlspecialchars($label) . '</a>' . "\n";
            }
        }
        unset($current, $label);
        $html .= '    <strong>]</strong>' . "\n"
            . '</p>' . "\n\n";
    }
    unset($infoPages, $page_output);
    if (! empty($_REQUEST['page'])) {
        $page_output = $engine_plugin->getPage($_REQUEST['page']);
    }
    if (! empty($page_output)) {
        $html .= $page_output;
    } else {
        $html .= '<p> ' . $engine_plugin->getSupportInformationMessage() . "\n"
           . '</p>' . "\n"
           . $engine_plugin->getHtmlVariables();
    }

    return $html;
}

?>
