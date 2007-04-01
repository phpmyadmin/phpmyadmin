<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for extracting information about the available storage engines
 *
 * @version $Id$
 */

/**
 *
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
 * @author  rabus
 * @uses    $GLOBALS['mysql_storage_engines']
 * @param   string  $name       The name of the select form element
 * @param   string  $id         The ID of the form field
 * @param   boolean $offerUnavailableEngines
 *                              Should unavailable storage engines be offered?
 * @param   string  $selected   The selected engine
 * @param   int     $indent     The indentation level
 * @return  string  html selectbox
 */
function PMA_generateEnginesDropdown($name = 'engine', $id = null,
  $offerUnavailableEngines = false, $selected = null, $indent = 0)
{
    $selected   = strtolower($selected);
    $spaces     = str_repeat('    ', $indent);
    $output     = $spaces . '<select name="' . $name . '"'
        . (empty($id) ? '' : ' id="' . $id . '"') . '>' . "\n";

    foreach ($GLOBALS['mysql_storage_engines'] as $key => $details) {
        if (!$offerUnavailableEngines
          && ($details['Support'] == 'NO' || $details['Support'] == 'DISABLED')) {
            continue;
        }
        $output .= $spaces . '    <option value="' . htmlspecialchars($key). '"'
            . (empty($details['Comment'])
                ? '' : ' title="' . htmlspecialchars($details['Comment']) . '"')
            . ($key == $selected || (empty($selected) && $details['Support'] == 'DEFAULT')
                ? ' selected="selected"' : '') . '>' . "\n"
            . $spaces . '        ' . htmlspecialchars($details['Engine']) . "\n"
            . $spaces . '    </option>' . "\n";
    }
    $output .= $spaces . '</select>' . "\n";
    return $output;
}

/**
 * defines
 */
define('PMA_ENGINE_SUPPORT_NO', 0);
define('PMA_ENGINE_SUPPORT_DISABLED', 1);
define('PMA_ENGINE_SUPPORT_YES', 2);
define('PMA_ENGINE_SUPPORT_DEFAULT', 3);

/**
 * Abstract Storage Engine Class
 */
class PMA_StorageEngine
{
    /**
     * @var string engine name
     */
    var $engine  = 'dummy';

    /**
     * @var string engine title/description
     */
    var $title   = 'PMA Dummy Engine Class';

    /**
     * @var string engine lang description
     */
    var $comment = 'If you read this text inside phpMyAdmin, something went wrong...';

    /**
     * @var integer engine supported by current server
     */
    var $support = PMA_ENGINE_SUPPORT_NO;

    /**
     * public static final PMA_StorageEngine getEngine()
     *
     * Loads the corresponding engine plugin, if available.
     *
     * @uses    str_replace()
     * @uses    file_exists()
     * @uses    PMA_StorageEngine
     * @param   string  $engine   The engine ID
     * @return  object  The engine plugin
     */
    function getEngine($engine)
    {
        $engine = str_replace('/', '', str_replace('.', '', $engine));
        if (file_exists('./libraries/engines/' . $engine . '.lib.php')
          && include_once './libraries/engines/' . $engine . '.lib.php') {
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
     * @uses    $GLOBALS['mysql_storage_engines']
     * @uses    PMA_ENGINE_SUPPORT_DEFAULT
     * @uses    PMA_ENGINE_SUPPORT_YES
     * @uses    PMA_ENGINE_SUPPORT_DISABLED
     * @uses    PMA_ENGINE_SUPPORT_NO
     * @uses    $this->engine
     * @uses    $this->title
     * @uses    $this->comment
     * @uses    $this->support
     * @param   string  $engine The engine ID
     */
    function __construct($engine)
    {
        if (!empty($GLOBALS['mysql_storage_engines'][$engine])) {
            $this->engine  = $engine;
            $this->title   = $GLOBALS['mysql_storage_engines'][$engine]['Engine'];
            $this->comment =
                (isset($GLOBALS['mysql_storage_engines'][$engine]['Comment'])
                    ? $GLOBALS['mysql_storage_engines'][$engine]['Comment']
                    : '');
            switch ($GLOBALS['mysql_storage_engines'][$engine]['Support']) {
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
     * old PHP 4 style constructor
     * @deprecated
     * @see     PMA_StorageEngine::__construct()
     * @uses    PMA_StorageEngine::__construct()
     * @param   string  $engine engine name
     */
    function PMA_StorageEngine($engine)
    {
        $this->__construct($engine);
    }

    /**
     * public String getTitle()
     *
     * Reveals the engine's title
     * @uses    $this->title
     * @return  string   The title
     */
    function getTitle()
    {
        return $this->title;
    }

    /**
     * public String getComment()
     *
     * Fetches the server's comment about this engine
     * @uses    $this->comment
     * @return  string   The comment
     */
    function getComment()
    {
        return $this->comment;
    }

    /**
     * public String getSupportInformationMessage()
     *
     * @uses    $GLOBALS['strDefaultEngine']
     * @uses    $GLOBALS['strEngineAvailable']
     * @uses    $GLOBALS['strEngineDisabled']
     * @uses    $GLOBALS['strEngineUnsupported']
     * @uses    $GLOBALS['strEngineUnsupported']
     * @uses    PMA_ENGINE_SUPPORT_DEFAULT
     * @uses    PMA_ENGINE_SUPPORT_YES
     * @uses    PMA_ENGINE_SUPPORT_DISABLED
     * @uses    PMA_ENGINE_SUPPORT_NO
     * @uses    $this->support
     * @uses    $this->title
     * @uses    sprintf
     * @return  string   The localized message.
     */
    function getSupportInformationMessage()
    {
        switch ($this->support) {
            case PMA_ENGINE_SUPPORT_DEFAULT:
                $message = $GLOBALS['strDefaultEngine'];
                break;
            case PMA_ENGINE_SUPPORT_YES:
                $message = $GLOBALS['strEngineAvailable'];
                break;
            case PMA_ENGINE_SUPPORT_DISABLED:
                $message = $GLOBALS['strEngineDisabled'];
                break;
            case PMA_ENGINE_SUPPORT_NO:
            default:
                $message = $GLOBALS['strEngineUnsupported'];
        }
        return sprintf($message, htmlspecialchars($this->title));
    }

    /**
     * public string[][] getVariables()
     *
     * Generates a list of MySQL variables that provide information about this
     * engine. This function should be overridden when extending this class
     * for a particular engine.
     *
     * @abstract
     * @return   Array   The list of variables.
     */
    function getVariables()
    {
        return array();
    }

    /**
     * returns string with filename for the MySQL helppage
     * about this storage engne
     *
     * @return  string  mysql helppage filename
     */
    function getMysqlHelpPage()
    {
        return $this->engine . '-storage-engine';
    }

    /**
     * public string getVariablesLikePattern()
     *
     * @abstract
     * @return  string  SQL query LIKE pattern
     */
    function getVariablesLikePattern()
    {
        return false;
    }

    /**
     * public String[] getInfoPages()
     *
     * Returns a list of available information pages with labels
     *
     * @abstract
     * @return  array    The list
     */
    function getInfoPages()
    {
        return array();
    }

    /**
     * public String getPage()
     *
     * Generates the requested information page
     *
     * @abstract
     * @param   string  $id The page ID
     *
     * @return  string      The page
     *          boolean     or false on error.
     */
    function getPage($id)
    {
        return false;
    }
}

?>
