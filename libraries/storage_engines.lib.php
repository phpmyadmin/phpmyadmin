<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Library for extracting information about the available storage engines
 */

$GLOBALS['mysql_storage_engines'] = array();

if (PMA_MYSQL_INT_VERSION >= 40102) {
    /**
     * For MySQL >= 4.1.2, the job is easy...
     */
    $res = PMA_DBI_query('SHOW STORAGE ENGINES');
    while ($row = PMA_DBI_fetch_assoc($res)) {
        $GLOBALS['mysql_storage_engines'][strtolower($row['Engine'])] = $row;
    }
    PMA_DBI_free_result($res);
    unset($res, $row);
} else {
    /**
     * Emulating SHOW STORAGE ENGINES...
     */
    $GLOBALS['mysql_storage_engines'] = array(
        'myisam' => array(
            'Engine'  => 'MyISAM',
            'Support' => 'DEFAULT'
        ),
        'merge' => array(
            'Engine'  => 'MERGE',
            'Support' => 'YES'
        ),
        'heap' => array(
            'Engine'  => 'HEAP',
            'Support' => 'YES'
        ),
        'memory' => array(
            'Engine'  => 'MEMORY',
            'Support' => 'YES'
        )
    );
    $known_engines = array(
        'archive' => 'ARCHIVE',
        'bdb'     => 'BDB',
        'csv'     => 'CSV',
        'innodb'  => 'InnoDB',
        'isam'    => 'ISAM',
        'gemini'  => 'Gemini'
    );
    $res = PMA_DBI_query('SHOW VARIABLES LIKE \'have\\_%\';');
    while ($row = PMA_DBI_fetch_row($res)) {
        $current = substr($row[0], 5);
        if (!empty($known_engines[$current])) {
            $GLOBALS['mysql_storage_engines'][$current] = array(
                'Engine'  => $known_engines[$current],
                'Support' => $row[1]
            );
        }
    }
    PMA_DBI_free_result($res);
    unset($known_engines, $res, $row);
}

/**
 * Function for generating the storage engine selection
 *
 * @param   string   The name of the select form element
 * @param   string   The ID of the form field
 * @param   boolean  Should unavailable storage engines be offered?
 * @param   string   The selected engine
 * @param   int      The indentation level
 *
 * @global  array    The storage engines
 *
 * @return  string
 *
 * @author  rabus
 */
function PMA_generateEnginesDropdown($name = 'engine', $id = NULL, $offerUnavailableEngines = FALSE, $selected = NULL, $indent = 0) {
    global $mysql_storage_engines;
    $selected = strtolower($selected);
    $spaces = '';
    for ($i = 0; $i < $indent; $i++) $spaces .= '    ';
    $output  = $spaces . '<select name="' . $name . '"' . (empty($id) ? '' : ' id="' . $id . '"') . '>' . "\n";
    foreach ($mysql_storage_engines as $key => $details) {
        if (!$offerUnavailableEngines && ($details['Support'] == 'NO' || $details['Support'] == 'DISABLED')) {
	    continue;
	}
        $output .= $spaces . '    <option value="' . htmlspecialchars($key). '"'
	         . (empty($details['Comment']) ? '' : ' title="' . htmlspecialchars($details['Comment']) . '"')
		 . ($key == $selected || (empty($selected) && $details['Support'] == 'DEFAULT') ? ' selected="selected"' : '') . '>' . "\n"
	         . $spaces . '        ' . htmlspecialchars($details['Engine']) . "\n"
		 . $spaces . '    </option>' . "\n";
    }
    $output .= $spaces . '</select>' . "\n";
    return $output;
}

/**
 * Abstract Storage Engine Class
 */
define('PMA_ENGINE_SUPPORT_NO', 0);
define('PMA_ENGINE_SUPPORT_DISABLED', 1);
define('PMA_ENGINE_SUPPORT_YES', 2);
define('PMA_ENGINE_SUPPORT_DEFAULT', 3);
class PMA_StorageEngine {
    var $engine  = 'dummy';
    var $title   = 'PMA Dummy Engine Class';
    var $comment = 'If you read this text inside phpMyAdmin, something went wrong...';
    var $support = PMA_ENGINE_SUPPORT_NO;

    /**
     * public static final PMA_StorageEngine getEngine ()
     *
     * Loads the corresponding engine plugin, if available.
     *
     * @param   String    The engine ID
     *
     * @return  Object    The engine plugin
     */
    function getEngine ($engine) {
        $engine = str_replace('/', '', str_replace('.', '', $engine));
        if (file_exists('./libraries/engines/' . $engine . '.lib.php') && include_once('./libraries/engines/' . $engine . '.lib.php')) {
            $class_name = 'PMA_StorageEngine_' . $engine;
            $engine_object = new $class_name($engine);
        } else {
            $engine_object = new PMA_StorageEngine($engine);
        }
        return $engine_object;
    }

    /**
     * Constructor
     *
     * @param    String    The engine ID
     */
    function PMA_StorageEngine ($engine) {
        global $mysql_storage_engines;

        if (!empty($mysql_storage_engines[$engine])) {
            $this->engine  = $engine;
            $this->title   = $mysql_storage_engines[$engine]['Engine'];
            $this->comment = $mysql_storage_engines[$engine]['Comment'];
            switch ($mysql_storage_engines[$engine]['Support']) {
                case 'DEFAULT':
                    $this->support = PMA_ENGINE_SUPPORT_DEFAULT;
                    break;
                case 'YES':
                    $this->support = PMA_ENGINE_SUPPORT_YES;
                    break;
                case 'DISABLED':
                    $this->support = PMA_ENGINE_SUPPORT_DISABLED;
                    break;
                case 'NO':
                default:
                    $this->support = PMA_ENGINE_SUPPORT_NO;
            }
        }
    }

    /**
     * public String getTitle ()
     *
     * Reveals the engine's title
     *
     * @return   String   The title
     */
    function getTitle () {
        return $this->title;
    }

    /**
     * public String getComment ()
     *
     * Fetches the server's comment about this engine
     *
     * @return   String   The comment
     */
    function getComment () {
        return $this->comment;
    }

    /**
     * public String getSupportInformationMessage ()
     *
     * @return   String   The localized message.
     */
    function getSupportInformationMessage () {
        switch ($this->support) {
            case PMA_ENGINE_SUPPORT_DEFAULT:
                $message = $GLOBALS['strDefaultEngine'];
                break;
            case PMA_ENGINE_SUPPORT_YES:
                $message = $GLOBALS['strEngineAvailable'];
                break;
            case PMA_ENGINE_SUPPORT_DISABLED:
                $message = $GLOBALS['strEngineUnsupported'];
                break;
            case PMA_ENGINE_SUPPORT_NO:
            default:
                $message = $GLOBALS['strEngineUnavailable'];
        }
        return sprintf($message, htmlspecialchars($this->title));
    }

    /**
     * public String[][] getVariables ()
     *
     * Generates a list of MySQL variables that provide information about this
     * engine. This function should be overridden when extending this class
     * for a particular engine.
     *
     * @return   Array   The list of variables.
     */
    function getVariables () {
        return array();
    }

    /**
     * public String getVariablesLikePattern ()
     */
    function getVariablesLikePattern () {
        return FALSE;
    }

    /**
     * public String[] getInfoPages ()
     *
     * Returns a list of available information pages with labels
     *
     * @return   Array    The list
     */
    function getInfoPages () {
        return array();
    }

    /**
     * public String getPage ()
     *
     * Generates the requested information page
     *
     * @param    String    The page ID
     *
     * @return   String    The page
     *           boolean   or FALSE on error.
     */
    function getPage($id) {
        return FALSE;
    }
}

?>
