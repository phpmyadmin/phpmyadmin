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
function PMA_generateEngineDetails($variables, $prefix = NULL, $indent = 0) {
    global $cfg;

    if (empty($variables)) return '';

    $spaces = '';
    for ($i = 0; $i < $indent; $i++) {
        $spaces .= '    ';
    }

    /**
     * Get the variables!
     */
    $sql_query = 'SHOW '
               . (PMA_MYSQL_INT_VERSION >= 40102 ? 'GLOBAL ' : '')
	       . 'VARIABLES'
	       . (empty($prefix) ? '' : ' LIKE \'' . $prefix . '\\_%\'')
	       . ';';
    $res = PMA_DBI_query($sql_query);
    $mysql_vars = array();
    while ($row = PMA_DBI_fetch_row($res)) {
        if (isset($variables[$row[0]])) $mysql_vars[$row[0]] = $row[1];
    }
    PMA_DBI_free_result($res);
    unset($res, $row, $sql_query);

    if (empty($mysql_vars)) return '';

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
	if (!empty($variables[$var])) {
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
       . ($cfg['MainPageIconic'] ? '<img src="' . $pmaThemeImage . 's_process.png" width="16" height="16" border="0" hspace="2" align="middle" />' : '' )
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
       . '            </th>' . "\n"
       . '            <th>' . "\n"
       . '                ' . $strDescription . "\n"
       . '            </th>' . "\n"
       . '        </tr>' . "\n"
       . '    </thead>' . "\n"
       . '    <tbody>' . "\n";


    /**
     * Listing the storage engines
     */
    $useBgcolorOne = TRUE;
    $common_url = './server_engines.php?' . PMA_generate_common_url() . '&amp;engine=';
    foreach ($mysql_storage_engines as $engine => $details) {
        echo '        <tr>' . "\n"
           . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
           . '                <a href="' . $common_url . $engine . '">' . "\n"
           . '                    ' . htmlspecialchars($details['Engine']) . "\n"
           . '                </a>' . "\n"
           . '            </td>' . "\n"
           . '            <td bgcolor="' . ($useBgcolorOne ? $cfg['BgcolorOne'] : $cfg['BgcolorTwo']) . '">' . "\n"
           . '                ' . htmlspecialchars($details['Comment']) . "\n"
           . '            </td>' . "\n"
           . '        </tr>' . "\n";
        $useBgcolorOne = !$useBgcolorOne;
    }
    unset($useBgcolorOne, $common_url, $engine, $details);
    echo '    </tbody>' . "\n"
       . '</table>' . "\n";

} else {

    /**
     * Displays details about a given Storage Engine
     */

    echo '<h2>' . "\n"
       . ($cfg['MainPageIconic'] ? '<img src="' . $pmaThemeImage . 's_process.png" width="16" height="16" border="0" hspace="2" align="middle" />' : '' )
       . '    ' . htmlspecialchars($mysql_storage_engines[$engine]['Engine']) . "\n"
       . '</h2>' . "\n\n"
       . '<p>' . "\n"
       . '    <i>' . "\n"
       . '        ' . htmlspecialchars($mysql_storage_engines[$engine]['Comment']) . "\n"
       . '    </i>' . "\n"
       . '</p>' . "\n";
    switch ($mysql_storage_engines[$engine]['Support']) {
        case 'DEFAULT':
	    echo '<p>'
	       . '    ' . sprintf($strDefaultEngine, htmlspecialchars($mysql_storage_engines[$engine]['Engine'])) . "\n"
	       . '</p>' . "\n";
	break;
	case 'YES':
	    echo '<p>' . "\n"
	       . '    ' . sprintf($strEngineAvailable, htmlspecialchars($mysql_storage_engines[$engine]['Engine'])) . "\n"
	       . '</p>' . "\n";
	break;
	case 'NO':
	    echo '<p>' . "\n"
	       . '    ' . sprintf($strEngineUnsupported, htmlspecialchars($mysql_storage_engines[$engine]['Engine'])) . "\n"
	       . '</p>' . "\n";
	break;
	case 'DISABLED':
	    echo '<p>' . "\n"
	       . '    ' . sprintf($strEngineDisabled, htmlspecialchars($mysql_storage_engines[$engine]['Engine'])) . "\n"
	       . '</p>' . "\n";
	break;
    }

    switch ($engine) {
	case 'innodb':
	case 'innobase':
	    echo '<h3>' . "\n"
	       . '    ' . $strInnodbStat . "\n"
	       . '</h3>' . "\n\n";
            $res = PMA_DBI_query('SHOW INNODB STATUS;');
            $row = PMA_DBI_fetch_row($res);
	    echo '<pre>' . "\n"
	        . htmlspecialchars($row[0]) . "\n"
	        . '</pre>' . "\n";
	    PMA_DBI_free_result($res);
	    unset($res, $row);
	break;

	case 'myisam':
	    $variables = array(
		'myisam_data_pointer_size' => array(
		    'title' => $strMyISAMDataPointerSize,
		    'desc'  => $strMyISAMDataPointerSizeDesc,
		    'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
		),
		'myisam_recover_options' => array(
		    'title' => $strMyISAMRecoverOptions,
		    'desc'  => $strMyISAMRecoverOptionsDesc
		),
		'myisam_max_sort_file_size' => array(
		    'title' => $strMyISAMMaxSortFileSize,
		    'desc'  => $strMyISAMMaxSortFileSizeDesc,
		    'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
		),
		'myisam_max_extra_sort_file_size' => array(
		    'title' => $strMyISAMMaxExtraSortFileSize,
		    'desc'  => $strMyISAMMaxExtraSortFileSizeDesc,
		    'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
		),
		'myisam_repair_threads' => array(
		    'title' => $strMyISAMRepairThreads,
		    'desc'  => $strMyISAMRepairThreadsDesc,
		    'type'  => PMA_ENGINE_DETAILS_TYPE_NUMERIC
		),
	        'myisam_sort_buffer_size' => array(
		    'title' => $strMyISAMSortBufferSize,
		    'desc'  => $strMyISAMSortBufferSizeDesc,
		    'type'  => PMA_ENGINE_DETAILS_TYPE_SIZE
                )
            );
	    echo PMA_generateEngineDetails($variables, 'myisam');
	break;

        default:
	    echo '<p>' . "\n"
	       . '    ' . $strNoDetailsForEngine . "\n"
	       . '</p>' . "\n";
        break;
    }
}


/**
 * Sends the footer
 */
require_once('./footer.inc.php');

?>
