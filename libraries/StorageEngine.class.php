<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for extracting information about the available storage engines
 *
 * @version $Id$
 */

/**
 * defines
 */
define('PMA_ENGINE_SUPPORT_NO', 0);
define('PMA_ENGINE_SUPPORT_DISABLED', 1);
define('PMA_ENGINE_SUPPORT_YES', 2);
define('PMA_ENGINE_SUPPORT_DEFAULT', 3);

define('PMA_ENGINE_DETAILS_TYPE_PLAINTEXT', 0);
define('PMA_ENGINE_DETAILS_TYPE_SIZE',      1);
define('PMA_ENGINE_DETAILS_TYPE_NUMERIC',   2); //Has no effect yet...
define('PMA_ENGINE_DETAILS_TYPE_BOOLEAN',   3); // 'ON' or 'OFF'

/**
 * base Storage Engine Class
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
     * returns array of storage engines
     *
     * @static
     * @staticvar array $storage_engines storage engines
     * @access  public
     * @uses    PMA_MYSQL_INT_VERSION
     * @uses    PMA_StorageEngine::getStorageEnginesBefore40102()
     * @uses    PMA_DBI_fetch_result()
     * @return  array    of storage engines
     */
    function getStorageEngines()
    {
        static $storage_engines = null;

        if (null !== $storage_engines) {
            return $storage_engines;
        }

        $storage_engines = array();

        // SHOW STORAGE ENGINES comes with MySQL 4.1.2
        if (PMA_MYSQL_INT_VERSION < 40102) {
            $storage_engines = PMA_StorageEngine::getStorageEnginesBefore40102();
        } else {
            $storage_engines = PMA_DBI_fetch_result('SHOW STORAGE ENGINES', 'Engine');
        }

        return $storage_engines;
    }

    /**
     * returns HTML code for storage engine select box
     *
     * @author  rabus
     * @static
     * @uses    PMA_StorageEngine::getStorageEngines()
     * @uses    strtolower()
     * @uses    htmlspecialchars()
     * @param   string  $name       The name of the select form element
     * @param   string  $id         The ID of the form field
     * @param   string  $selected   The selected engine
     * @param   boolean $offerUnavailableEngines
     *                              Should unavailable storage engines be offered?
     * @return  string  html selectbox
     */
    function getHtmlSelect($name = 'engine', $id = null,
      $selected = null, $offerUnavailableEngines = false)
    {
        $selected   = strtolower($selected);
        $output     = '<select name="' . $name . '"'
            . (empty($id) ? '' : ' id="' . $id . '"') . '>' . "\n";

        foreach (PMA_StorageEngine::getStorageEngines() as $key => $details) {
            if (!$offerUnavailableEngines
              && ($details['Support'] == 'NO' || $details['Support'] == 'DISABLED')) {
                continue;
            }
            $output .= '    <option value="' . htmlspecialchars($key). '"'
                . (empty($details['Comment'])
                    ? '' : ' title="' . htmlspecialchars($details['Comment']) . '"')
                . (strtolower($key) == $selected || (empty($selected) && $details['Support'] == 'DEFAULT')
                    ? ' selected="selected"' : '') . '>' . "\n"
                . '        ' . htmlspecialchars($details['Engine']) . "\n"
                . '    </option>' . "\n";
        }
        $output .= '</select>' . "\n";
        return $output;
    }

    /**
     * returns array of storage engines for MySQL < 4.1, hard coded
     * Emulating SHOW STORAGE ENGINES...
     *
     * @static
     * @access  public
     * @uses    PMA_DBI_query()
     * @uses    PMA_DBI_fetch_row()
     * @uses    PMA_DBI_free_result()
     * @uses    substr()
     * @return  array    of storage engines
     */
    function getStorageEnginesBefore40102()
    {
        $storage_engines = array(
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
            if (! empty($known_engines[$current])) {
                $storage_engines[$current] = array(
                    'Engine'  => $known_engines[$current],
                    'Support' => $row[1]
                );
            }
        }
        PMA_DBI_free_result($res);

        return $storage_engines;
    }


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
        $engine_lowercase_filename = strtolower($engine);
        if (file_exists('./libraries/engines/' . $engine_lowercase_filename . '.lib.php')
          && include_once './libraries/engines/' . $engine_lowercase_filename . '.lib.php') {
            $class_name = 'PMA_StorageEngine_' . $engine;
            $engine_object = new $class_name($engine);
        } else {
            $engine_object = new PMA_StorageEngine($engine);
        }
        return $engine_object;
    }

    /**
     * return true if given engine name is supported/valid, otherwise false
     *
     * @static
     * @uses    PMA_StorageEngine::getStorageEngines()
     * @param   string  $engine name of engine
     * @reutrn  boolean whether $engine is valid or not
     */
    function isValid($engine)
    {
        $storage_engines = PMA_StorageEngine::getStorageEngines();
        return isset($storage_engines[$engine]);
    }

    /**
     * returns as HTML table of the engine's server variables
     *
     * @uses    PMA_ENGINE_DETAILS_TYPE_SIZE
     * @uses    PMA_ENGINE_DETAILS_TYPE_NUMERIC
     * @uses    PMA_StorageEngine::getVariablesStatus()
     * @uses    $GLOBALS['strNoDetailsForEngine']
     * @uses    PMA_showHint()
     * @uses    PMA_formatByteDown()
     * @uses    PMA_formatNumber()
     * @uses    htmlspecialchars()
     * @return  string  The table that was generated based on the retrieved information
     */
    function getHtmlVariables()
    {
        $odd_row    = false;
        $ret        = '';

        foreach ($this->getVariablesStatus() as $details) {
            $ret .= '<tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n"
                  . '    <td>' . "\n";
            if (!empty($details['desc'])) {
                $ret .= '        ' . PMA_showHint($details['desc']) . "\n";
            }
            $ret .= '    </td>' . "\n"
                  . '    <th>' . htmlspecialchars($details['title']) . '</th>' . "\n"
                  . '    <td class="value">';
            switch ($details['type']) {
                case PMA_ENGINE_DETAILS_TYPE_SIZE:
                    $parsed_size = PMA_formatByteDown($details['value']);
                    $ret .= $parsed_size[0] . '&nbsp;' . $parsed_size[1];
                    unset($parsed_size);
                break;
                case PMA_ENGINE_DETAILS_TYPE_NUMERIC:
                    $ret .= PMA_formatNumber($details['value']) . ' ';
                break;
                default:
                    $ret .= htmlspecialchars($details['value']) . '   ';
            }
            $ret .= '</td>' . "\n"
                  . '</tr>' . "\n";
            $odd_row = !$odd_row;
        }

        if (! $ret) {
            $ret = '<p>' . "\n"
                 . '    ' . $GLOBALS['strNoDetailsForEngine'] . "\n"
                 . '</p>' . "\n";
        } else {
            $ret = '<table class="data">' . "\n" . $ret . '</table>' . "\n";
        }

        return $ret;
    }

    /**
     * returns array with detailed info about engine specific server variables
     *
     * @uses    PMA_ENGINE_DETAILS_TYPE_PLAINTEXT
     * @uses    PMA_StorageEngine::getVariables()
     * @uses    PMA_StorageEngine::getVariablesLikePattern()
     * @uses    PMA_MYSQL_INT_VERSION
     * @uses    PMA_DBI_query()
     * @uses    PMA_DBI_fetch_assoc()
     * @uses    PMA_DBI_free_result()
     * @return  array   with detailed info about specific engine server variables
     */
    function getVariablesStatus()
    {
        $variables = $this->getVariables();
        $like = $this->getVariablesLikePattern();

        if ($like) {
            $like = " LIKE '" . $like . "' ";
        } else {
            $like = '';
        }

        if (PMA_MYSQL_INT_VERSION >= 40102) {
            $global = ' GLOBAL ';
        } else {
            $global = '';
        }

        $mysql_vars = array();

        $sql_query = 'SHOW ' . $global . ' VARIABLES ' . $like . ';';
        $res = PMA_DBI_query($sql_query);
        while ($row = PMA_DBI_fetch_assoc($res)) {
            if (isset($variables[$row['Variable_name']])) {
                $mysql_vars[$row['Variable_name']] = $variables[$row['Variable_name']];
            } elseif (! $like
             && strpos(strtolower($row['Variable_name']), strtolower($this->engine)) !== 0) {
                continue;
            }
            $mysql_vars[$row['Variable_name']]['value'] = $row['Value'];

            if (empty($mysql_vars[$row['Variable_name']]['title'])) {
                $mysql_vars[$row['Variable_name']]['title'] = $row['Variable_name'];
            }

            if (! isset($mysql_vars[$row['Variable_name']]['type'])) {
                $mysql_vars[$row['Variable_name']]['type'] = PMA_ENGINE_DETAILS_TYPE_PLAINTEXT;
            }
        }
        PMA_DBI_free_result($res);

        return $mysql_vars;
    }

    /**
     * Constructor
     *
     * @uses    PMA_StorageEngine::getStorageEngines()
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
        $storage_engines = PMA_StorageEngine::getStorageEngines();
        if (!empty($storage_engines[$engine])) {
            $this->engine  = $engine;
            $this->title   = $storage_engines[$engine]['Engine'];
            $this->comment =
                (isset($storage_engines[$engine]['Comment'])
                    ? $storage_engines[$engine]['Comment']
                    : '');
            switch ($storage_engines[$engine]['Support']) {
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
