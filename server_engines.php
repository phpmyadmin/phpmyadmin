<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:


/**
 * Does the common work
 */
require('./server_common.inc.php');
require('./libraries/storage_engines.lib.php');


/**
 * Displays the links
 */
require('./server_links.inc.php');


/**
 * Function for displaying the table of an engine's parameters
 *
 * @param   array   List of MySQL variables and corresponding localized descriptions.
 *                  The array elements should have the following format:
 *                      $variable => array('title' => $title, 'desc' => $description);
 * @param   string  Prefix for the SHOW VARIABLES query.
 * @param   int     The indentation level
 *
 * @global  array   The global phpMyAdmin configuration.
 *
 * @return  string  The table that was generated based on the given information.
 */
define('PMA_ENGINE_DETAILS_TYPE_PLAINTEXT', 0);
define('PMA_ENGINE_DETAILS_TYPE_SIZE',      1);
define('PMA_ENGINE_DETAILS_TYPE_NUMERIC',   2); //Has no effect yet...
define('PMA_ENGINE_DETAILS_TYPE_BOOLEAN',   3); // 'ON' or 'OFF'
function PMA_generateEngineDetails($variables, $like = NULL, $indent = 0) {
    global $cfg;

    $spaces = '';
    for ($i = 0; $i < $indent; $i++) {
        $spaces .= '    ';
    }

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
            if (isset($variables[$row[0]])) $mysql_vars[$row[0]] = $row[1];
        }
        PMA_DBI_free_result($res);
        unset($res, $row, $sql_query);
    }

    if (empty($mysql_vars)) return $spaces . '<p>' . "\n"
                                 . $spaces . '    ' . $GLOBALS['strNoDetailsForEngine'] . "\n"
                                 . $spaces . '</p>' . "\n";

    $dt_table          = $spaces . '<table>' . "\n";
    $useBgcolorOne     = TRUE;
    $has_content       = FALSE;

    foreach ($variables as $var => $details) {
        if (!isset($mysql_vars[$var])) continue;

        if (!isset($details['type'])) $details['type'] = PMA_ENGINE_DETAILS_TYPE_PLAINTEXT;
        $is_num = $details['type'] == PMA_ENGINE_DETAILS_TYPE_SIZE || $details['type'] == PMA_ENGINE_DETAILS_TYPE_NUMERIC;

        $bgcolor = $useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo'];

        $dt_table     .= $spaces . '    <tr>' . "\n"
                       . $spaces . '        <td bgcolor="' . $bgcolor . '">' . "\n";
        if (!empty($variables[$var]['desc'])) {
            $dt_table .= $spaces . '            ' . PMA_showHint($details['desc']) . "\n";
        }
        $dt_table     .= $spaces . '        </td>' . "\n"
    	               . $spaces . '        <td bgcolor="' . $bgcolor . '">' . "\n"
                       . $spaces . '            &nbsp;' . $details['title'] . '&nbsp;' . "\n"
                       . $spaces . '        </td>' . "\n"
                       . $spaces . '        <td bgcolor="' . $bgcolor . '"' . ($is_num ? ' align="right"' : '') . '>' . "\n"
                       . $spaces . '            &nbsp;';
        switch ($details['type']) {
            case PMA_ENGINE_DETAILS_TYPE_SIZE:
                $parsed_size = PMA_formatByteDown($mysql_vars[$var]);
                $dt_table .= $parsed_size[0] . '&nbsp;' . $parsed_size[1];
                unset($parsed_size);
            break;
            default:
                $dt_table .= htmlspecialchars($mysql_vars[$var]);
        }
        $dt_table     .= '&nbsp;' . "\n"
                      . $spaces . '        </td>' . "\n"
                      . $spaces . '    </tr>' . "\n";
        $useBgcolorOne = !$useBgcolorOne;
        $has_content   = TRUE;
    }

    if (!$has_content) return '';

    return $dt_table;
}


/**
 * Did the user request information about a certain storage engine?
 */
if (empty($engine) || empty($mysql_storage_engines[$engine])) {

    /**
     * Displays the sub-page heading
     */
    echo '<h2>' . "\n"
       . ($cfg['MainPageIconic'] ? '<img src="' . $pmaThemeImage . 'b_engine.png" width="16" height="16" border="0" hspace="2" align="middle" />' : '' )
       . '    ' . $strStorageEngines . "\n"
       . '</h2>' . "\n";


    /**
     * Displays the table header
     */
    echo '<table>' . "\n"
       . '    <thead>' . "\n"
       . '        <tr>' . "\n"
       . '            <th>' . "\n"
       . '                ' . $strStorageEngine . "\n"
       . '            </th>' . "\n";
    if (PMA_MYSQL_INT_VERSION >= 40102) {
        echo '            <th>' . "\n"
           . '                ' . $strDescription . "\n"
           . '            </th>' . "\n";
    }
    echo '        </tr>' . "\n"
       . '    </thead>' . "\n"
       . '    <tbody>' . "\n";


    /**
     * Listing the storage engines
     */
    $useBgcolorOne = TRUE;
    $common_url = './server_engines.php?' . PMA_generate_common_url() . '&amp;engine=';
    foreach ($mysql_storage_engines as $engine => $details) {
        echo '        <tr' . ($details['Support'] == 'NO' || $details['Support'] == 'DISABLED' ? ' class="disabled"' : '') . '>' . "\n"
           . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
           . '                <a href="' . $common_url . $engine . '">' . "\n"
           . '                    ' . htmlspecialchars($details['Engine']) . "\n"
           . '                </a>' . "\n"
           . '            </td>' . "\n";
        if (PMA_MYSQL_INT_VERSION >= 40102) {
            echo '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
               . '                ' . htmlspecialchars($details['Comment']) . "\n"
               . '            </td>' . "\n";
        }
        echo '        </tr>' . "\n";
        $useBgcolorOne = !$useBgcolorOne;
    }
    unset($useBgcolorOne, $common_url, $engine, $details);
    echo '    </tbody>' . "\n"
       . '</table>' . "\n";

} else {

    /**
     * Displays details about a given Storage Engine
     */

    $engine_plugin = PMA_StorageEngine::getEngine($engine);
    echo '<h2>' . "\n"
       . ($cfg['MainPageIconic'] ? '<img src="' . $pmaThemeImage . 'b_engine.png" width="16" height="16" border="0" hspace="2" align="middle" />' : '' )
       . '    ' . htmlspecialchars($engine_plugin->getTitle()) . "\n"
       . '</h2>' . "\n\n";
    if (PMA_MYSQL_INT_VERSION >= 40102) {
        echo '<p>' . "\n"
           . '    <i>' . "\n"
           . '        ' . htmlspecialchars($engine_plugin->getComment()) . "\n"
           . '    </i>' . "\n"
           . '</p>' . "\n\n";
    }
    $infoPages = $engine_plugin->getInfoPages();
    if (!empty($infoPages) && is_array($infoPages)) {
        $common_url = './server_engines.php?' . PMA_generate_common_url() . '&amp;engine=' . urlencode($engine);
        echo '<p>' . "\n"
           . '    <b>[</b>' . "\n";
        if (empty($page)) {
            echo '    <b>' . $strServerTabVariables . '</b>' . "\n";
        } else {
            echo '    <a href="' . $common_url . '">' . $strServerTabVariables . '</a>' . "\n";
        }
        foreach ($infoPages as $current => $label) {
            echo '    <b>|</b>' . "\n";
            if (isset($page) && $page == $current) {
                echo '    <b>' . $label . '</b>' . "\n";
            } else {
                echo '    <a href="' . $common_url . '&amp;page=' . urlencode($current) . '">' . $label . '</a>' . "\n";
            }
        }
        unset($current, $label);
        echo '    <b>]</b>' . "\n"
           . '</p>' . "\n\n";
    }
    unset($infoPages, $page_output);
	if (!empty($page)) {
        $page_output = $engine_plugin->getPage($page);
    }
    if (!empty($page_output)) {
        echo $page_output;
    } else {
        echo '<p>' . "\n"
           . '    ' . $engine_plugin->getSupportInformationMessage() . "\n"
           . '</p>' . "\n"
           . PMA_generateEngineDetails($engine_plugin->getVariables(), $engine_plugin->getVariablesLikePattern());
    }
}

/**
 * Sends the footer
 */
require_once('./footer.inc.php');

?>
