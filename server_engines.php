<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display list of server engines and additional information about them
 *
 * @package PhpMyAdmin
 */

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * Does the common work
 */
require 'libraries/server_common.inc.php';
require 'libraries/StorageEngine.class.php';

/**
 * start output
 */
$response = PMA_Response::getInstance();
$response->addHTML(PMA_getServerEnginesHtml());

exit;


/**
 * setup HTML for server Engines information
 *
 * @param null
 *
 * @return string
 */
function PMA_getServerEnginesHtml()
{
    /**
     * Did the user request information about a certain storage engine?
     */
    $html = '';
    if (empty($_REQUEST['engine'])
        || ! PMA_StorageEngine::isValid($_REQUEST['engine'])
    ) {
        $html .= PMA_getAllServerEnginesHtml();    
    } else {
        $html .= PMA_getSpecifiedServerEnginesHtml();
    }
    
    return $html;
}

/**
 * setup HTML for server all Engines information
 *
 * @param null
 *
 * @return string
 */
function PMA_getAllServerEnginesHtml()
{
    /**
     * Displays the sub-page heading
     */
    $html = '<h2>' . "\n"
        . PMA_Util::getImage('b_engine.png')
        . "\n" . __('Storage Engines') . "\n"
        . '</h2>' . "\n";

    /**
     * Displays the table header
     */
    $html .= '<table class="noclick">' . "\n"
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
            . PMA_generate_common_url(array('engine' => $engine)) . '">' . "\n"
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
 * @param null
 *
 * @return string
 */
function PMA_getSpecifiedServerEnginesHtml()
{
    /**
     * Displays details about a given Storage Engine
     */
    $html = ''; 
    $engine_plugin = PMA_StorageEngine::getEngine($_REQUEST['engine']);
    $html .= '<h2>' . "\n"
        . PMA_Util::getImage('b_engine.png')
        . '    ' . htmlspecialchars($engine_plugin->getTitle()) . "\n"
        . '    ' . PMA_Util::showMySQLDocu('', $engine_plugin->getMysqlHelpPage())
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
                . PMA_generate_common_url(array('engine' => $_REQUEST['engine']))
                . '">' . __('Variables') . '</a>' . "\n";
        }
        foreach ($infoPages as $current => $label) {
            $html .= '    <strong>|</strong>' . "\n";
            if (isset($_REQUEST['page']) && $_REQUEST['page'] == $current) {
                $html .= '    <strong>' . $label . '</strong>' . "\n";
            } else {
                $html .= '    <a href="server_engines.php'
                    . PMA_generate_common_url(
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
