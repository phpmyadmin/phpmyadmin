<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for extracting information about the available storage engines
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

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
 * Base Storage Engine Class
 *
 * @package PhpMyAdmin
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
    var $comment
        = 'If you read this text inside phpMyAdmin, something went wrong...';

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
     * @return array    of storage engines
     */
    static public function getStorageEngines()
    {
        static $storage_engines = null;

        if (null == $storage_engines) {
            if (PMA_DRIZZLE) {
                $sql = "SELECT
                        p.plugin_name            AS Engine,
                        (CASE
                            WHEN p.plugin_name = @@storage_engine THEN 'DEFAULT'
                            WHEN p.is_active THEN 'YES'
                            ELSE 'DISABLED' END) AS Support,
                        m.module_description     AS Comment
                    FROM data_dictionary.plugins p
                        JOIN data_dictionary.modules m USING (module_name)
                    WHERE p.plugin_type = 'StorageEngine'
                        AND p.plugin_name NOT IN ('FunctionEngine', 'schema')";
                $storage_engines = $GLOBALS['dbi']->fetchResult($sql, 'Engine');
            } else {
                $storage_engines
                    = $GLOBALS['dbi']->fetchResult('SHOW STORAGE ENGINES', 'Engine');
            }
        }

        return $storage_engines;
    }

    /**
     * returns HTML code for storage engine select box
     *
     * @param string  $name                    The name of the select form element
     * @param string  $id                      The ID of the form field
     * @param string  $selected                The selected engine
     * @param boolean $offerUnavailableEngines Should unavailable storage
     *                                         engines be offered?
     *
     * @static
     * @return string  html selectbox
     */
    static public function getHtmlSelect(
        $name = 'engine', $id = null,
        $selected = null, $offerUnavailableEngines = false
    ) {
        $selected   = strtolower($selected);
        $output     = '<select name="' . $name . '"'
            . (empty($id) ? '' : ' id="' . $id . '"') . '>' . "\n";

        foreach (PMA_StorageEngine::getStorageEngines() as $key => $details) {
            // Don't show PERFORMANCE_SCHEMA engine (MySQL 5.5)
            // Don't show MyISAM for Drizzle (allowed only for temporary tables)
            if (! $offerUnavailableEngines
                && ($details['Support'] == 'NO'
                || $details['Support'] == 'DISABLED'
                || $details['Engine'] == 'PERFORMANCE_SCHEMA')
                || (PMA_DRIZZLE && $details['Engine'] == 'MyISAM')
            ) {
                continue;
            }

            $output .= '    <option value="' . htmlspecialchars($key). '"'
                . (empty($details['Comment'])
                    ? '' : ' title="' . htmlspecialchars($details['Comment']) . '"')
                . (strtolower($key) == $selected
                    || (empty($selected) && $details['Support'] == 'DEFAULT')
                    ? ' selected="selected"' : '')
                . '>' . "\n"
                . '        ' . htmlspecialchars($details['Engine']) . "\n"
                . '    </option>' . "\n";
        }
        $output .= '</select>' . "\n";
        return $output;
    }

    /**
     * public static final PMA_StorageEngine getEngine()
     *
     * Loads the corresponding engine plugin, if available.
     *
     * @param string $engine The engine ID
     *
     * @return PMA_StorageEngine The engine plugin
     */
    static public function getEngine($engine)
    {
        $engine = str_replace('/', '', str_replace('.', '', $engine));
        $filename = './libraries/engines/' . strtolower($engine) . '.lib.php';
        if (file_exists($filename) && include_once $filename) {
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
     * @param string $engine name of engine
     *
     * @static
     * @return boolean whether $engine is valid or not
     */
    static public function isValid($engine)
    {
        if ($engine == "PBMS") {
            return true;
        }
        $storage_engines = PMA_StorageEngine::getStorageEngines();
        return isset($storage_engines[$engine]);
    }

    /**
     * returns as HTML table of the engine's server variables
     *
     * @return string The table that was generated based on the retrieved
     *                information
     */
    function getHtmlVariables()
    {
        $odd_row    = false;
        $ret        = '';

        foreach ($this->getVariablesStatus() as $details) {
            $ret .= '<tr class="' . ($odd_row ? 'odd' : 'even') . '">' . "\n"
                  . '    <td>' . "\n";
            if (! empty($details['desc'])) {
                $ret .= '        '
                    . PMA_Util::showHint($details['desc'])
                    . "\n";
            }
            $ret .= '    </td>' . "\n"
                  . '    <th>' . htmlspecialchars($details['title']) . '</th>'
                  . "\n"
                  . '    <td class="value">';
            switch ($details['type']) {
            case PMA_ENGINE_DETAILS_TYPE_SIZE:
                $parsed_size = $this->resolveTypeSize($details['value']);
                $ret .= $parsed_size[0] . '&nbsp;' . $parsed_size[1];
                unset($parsed_size);
                break;
            case PMA_ENGINE_DETAILS_TYPE_NUMERIC:
                $ret .= PMA_Util::formatNumber($details['value']) . ' ';
                break;
            default:
                $ret .= htmlspecialchars($details['value']) . '   ';
            }
            $ret .= '</td>' . "\n"
                  . '</tr>' . "\n";
            $odd_row = ! $odd_row;
        }

        if (! $ret) {
            $ret = '<p>' . "\n"
                 . '    '
                 . __('There is no detailed status information available for this storage engine.')
                 . "\n"
                 . '</p>' . "\n";
        } else {
            $ret = '<table class="data">' . "\n" . $ret . '</table>' . "\n";
        }

        return $ret;
    }

    /**
     * Returns the engine specific handling for
     * PMA_ENGINE_DETAILS_TYPE_SIZE type variables.
     *
     * This function should be overridden when
     * PMA_ENGINE_DETAILS_TYPE_SIZE type needs to be
     * handled differently for a particular engine.
     *
     * @param integer $value Value to format
     *
     * @return string the formatted value and its unit
     */
    function resolveTypeSize($value)
    {
        return PMA_Util::formatByteDown($value);
    }

    /**
     * returns array with detailed info about engine specific server variables
     *
     * @return array   with detailed info about specific engine server variables
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

        $mysql_vars = array();

        $sql_query = 'SHOW GLOBAL VARIABLES ' . $like . ';';
        $res = $GLOBALS['dbi']->query($sql_query);
        while ($row = $GLOBALS['dbi']->fetchAssoc($res)) {
            if (isset($variables[$row['Variable_name']])) {
                $mysql_vars[$row['Variable_name']] = $variables[$row['Variable_name']];
            } elseif (! $like
                && strpos(strtolower($row['Variable_name']), strtolower($this->engine)) !== 0
            ) {
                continue;
            }
            $mysql_vars[$row['Variable_name']]['value'] = $row['Value'];

            if (empty($mysql_vars[$row['Variable_name']]['title'])) {
                $mysql_vars[$row['Variable_name']]['title'] = $row['Variable_name'];
            }

            if (! isset($mysql_vars[$row['Variable_name']]['type'])) {
                $mysql_vars[$row['Variable_name']]['type']
                    = PMA_ENGINE_DETAILS_TYPE_PLAINTEXT;
            }
        }
        $GLOBALS['dbi']->freeResult($res);

        return $mysql_vars;
    }

    /**
     * Constructor
     *
     * @param string $engine The engine ID
     */
    function __construct($engine)
    {
        $storage_engines = PMA_StorageEngine::getStorageEngines();
        if (! empty($storage_engines[$engine])) {
            $this->engine  = $engine;
            $this->title   = $storage_engines[$engine]['Engine'];
            $this->comment
                = (isset($storage_engines[$engine]['Comment'])
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
     * public String getTitle()
     *
     * Reveals the engine's title
     *
     * @return string The title
     */
    function getTitle()
    {
        return $this->title;
    }

    /**
     * public String getComment()
     *
     * Fetches the server's comment about this engine
     *
     * @return string The comment
     */
    function getComment()
    {
        return $this->comment;
    }

    /**
     * public String getSupportInformationMessage()
     *
     * @return string   The localized message.
     */
    function getSupportInformationMessage()
    {
        switch ($this->support) {
        case PMA_ENGINE_SUPPORT_DEFAULT:
            $message = __('%s is the default storage engine on this MySQL server.');
            break;
        case PMA_ENGINE_SUPPORT_YES:
            $message = __('%s is available on this MySQL server.');
            break;
        case PMA_ENGINE_SUPPORT_DISABLED:
            $message = __('%s has been disabled for this MySQL server.');
            break;
        case PMA_ENGINE_SUPPORT_NO:
        default:
            $message = __('This MySQL server does not support the %s storage engine.');
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
     * @return Array   The list of variables.
     */
    function getVariables()
    {
        return array();
    }

    /**
     * returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string  mysql helppage filename
     */
    function getMysqlHelpPage()
    {
        return $this->engine . '-storage-engine';
    }

    /**
     * public string getVariablesLikePattern()
     *
     * @abstract
     * @return string  SQL query LIKE pattern
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
     * @return array    The list
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
     * @param string $id The page ID
     *
     * @abstract
     * @return string      The page
     *          boolean     or false on error.
     */
    function getPage($id)
    {
        return false;
    }
}

?>
