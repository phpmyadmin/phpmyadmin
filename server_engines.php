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
    $engine_supported = FALSE;
    switch ($mysql_storage_engines[$engine]['Support']) {
        case 'DEFAULT':
	    echo '<p>'
	       . '    ' . sprintf($strDefaultEngine, htmlspecialchars($mysql_storage_engines[$engine]['Engine'])) . "\n"
	       . '</p>' . "\n";
	    $engine_supported = TRUE;
	break;
	case 'YES':
	    echo '<p>' . "\n"
	       . '    ' . sprintf($strEngineAvailable, htmlspecialchars($mysql_storage_engines[$engine]['Engine'])) . "\n"
	       . '</p>' . "\n";
	    $engine_supported = TRUE;
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

    if ($engine_supported) switch ($engine) {
	case 'innodb':
	case 'innobase':
	    if ($res = PMA_DBI_try_query('SHOW INNODB STATUS;')) { // We might not have the privileges to do that...
                echo '<h3>' . "\n"
                   . '    ' . $strInnodbStat . "\n"
                   . '</h3>' . "\n\n";
                $row = PMA_DBI_fetch_row($res);
                echo '<pre>' . "\n"
                    . htmlspecialchars($row[0]) . "\n"
                    . '</pre>' . "\n";
                PMA_DBI_free_result($res);
		unset($row);
		break;
            }
	    unset($res);
//	break;
        default:
	    echo '<p>' . "\n"
	       . '    ' . $strNoDetailsForEngine . "\n"
	       . '</p>' . "\n";
        break;
    }
    unset($engine_supported);
}


/**
 * Sends the footer
 */
require_once('./footer.inc.php');

?>
