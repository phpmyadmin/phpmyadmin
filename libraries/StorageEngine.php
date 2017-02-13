<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library for extracting information about the available storage engines
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * defines
 */
use PMA\libraries\engines\Bdb;
use PMA\libraries\engines\Berkeleydb;
use PMA\libraries\engines\Binlog;
use PMA\libraries\engines\Innobase;
use PMA\libraries\engines\Innodb;
use PMA\libraries\engines\Memory;
use PMA\libraries\engines\Merge;
use PMA\libraries\engines\Mrg_Myisam;
use PMA\libraries\engines\Myisam;
use PMA\libraries\engines\Ndbcluster;
use PMA\libraries\engines\Pbxt;
use PMA\libraries\engines\Performance_Schema;

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
class StorageEngine
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
     * Constructor
     *
     * @param string $engine The engine ID
     */
    public function __construct($engine)
    {
        $storage_engines = StorageEngine::getStorageEngines();
        if (! empty($storage_engines[$engine])) {
            $this->engine  = $engine;
            $this->title   = $storage_engines[$engine]['Engine'];
            $this->comment = (isset($storage_engines[$engine]['Comment'])
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
     * Returns array of storage engines
     *
     * @static
     * @staticvar array $storage_engines storage engines
     * @access public
     * @return string[] array of storage engines
     */
    static public function getStorageEngines()
    {
        static $storage_engines = null;

        if (null == $storage_engines) {
            $storage_engines
                = $GLOBALS['dbi']->fetchResult('SHOW STORAGE ENGINES', 'Engine');
            if (PMA_MYSQL_INT_VERSION >= 50708) {
                $disabled = Util::cacheGet(
                    'disabled_storage_engines',
                    function () {
                        return $GLOBALS['dbi']->fetchValue(
                            'SELECT @@disabled_storage_engines'
                        );
                    }
                );
                foreach (explode(",", $disabled) as $engine) {
                    if (isset($storage_engines[$engine])) {
                        $storage_engines[$engine]['Support'] = 'DISABLED';
                    }
                }
            }
        }

        return $storage_engines;
    }

    /**
     * Returns HTML code for storage engine select box
     *
     * @param string  $name                    The name of the select form element
     * @param string  $id                      The ID of the form field
     * @param string  $selected                The selected engine
     * @param boolean $offerUnavailableEngines Should unavailable storage
     *                                         engines be offered?
     * @param boolean $addEmpty                Whether to provide empty option
     *
     * @static
     * @return string html selectbox
     */
    static public function getHtmlSelect(
        $name = 'engine', $id = null,
        $selected = null, $offerUnavailableEngines = false,
        $addEmpty = false
    ) {
        $selected   = mb_strtolower($selected);
        $output     = '<select name="' . $name . '"'
            . (empty($id) ? '' : ' id="' . $id . '"') . '>' . "\n";

        if ($addEmpty) {
            $output .= '<option value=""></option>';
        }

        foreach (StorageEngine::getStorageEngines() as $key => $details) {
            // Don't show PERFORMANCE_SCHEMA engine (MySQL 5.5)
            if (! $offerUnavailableEngines
                && ($details['Support'] == 'NO'
                || $details['Support'] == 'DISABLED'
                || $details['Engine'] == 'PERFORMANCE_SCHEMA')
            ) {
                continue;
            }

            $output .= '    <option value="' . htmlspecialchars($key) . '"'
                . (empty($details['Comment'])
                    ? '' : ' title="' . htmlspecialchars($details['Comment']) . '"')
                . (mb_strtolower($key) == $selected
                    || (empty($selected) && $details['Support'] == 'DEFAULT' && ! $addEmpty)
                    ? ' selected="selected"' : '')
                . '>' . "\n"
                . '        ' . htmlspecialchars($details['Engine']) . "\n"
                . '    </option>' . "\n";
        }
        $output .= '</select>' . "\n";
        return $output;
    }

    /**
     * Loads the corresponding engine plugin, if available.
     *
     * @param string $engine The engine ID
     *
     * @return StorageEngine The engine plugin
     * @static
     */
    static public function getEngine($engine)
    {
        switch(strtolower($engine)) {
        case 'bdb':
            return new Bdb($engine);
        case 'berkeleydb':
            return new Berkeleydb($engine);
        case 'binlog':
            return new Binlog($engine);
        case 'innobase':
            return new Innobase($engine);
        case 'innodb':
            return new Innodb($engine);
        case 'memory':
            return new Memory($engine);
        case 'merge':
            return new Merge($engine);
        case 'mrg_myisam':
            return new Mrg_Myisam($engine);
        case 'myisam':
            return new Myisam($engine);
        case 'ndbcluster':
            return new Ndbcluster($engine);
        case 'pbxt':
            return new Pbxt($engine);
        case 'performance_schema':
            return new Performance_Schema($engine);
        default:
            return new StorageEngine($engine);
        }
    }

    /**
     * Returns true if given engine name is supported/valid, otherwise false
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
        $storage_engines = StorageEngine::getStorageEngines();
        return isset($storage_engines[$engine]);
    }

    /**
     * Returns as HTML table of the engine's server variables
     *
     * @return string The table that was generated based on the retrieved
     *                information
     */
    public function getHtmlVariables()
    {
        $ret        = '';

        foreach ($this->getVariablesStatus() as $details) {
            $ret .= '<tr>' . "\n"
                  . '    <td>' . "\n";
            if (! empty($details['desc'])) {
                $ret .= '        '
                    . Util::showHint($details['desc'])
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
                $ret .= Util::formatNumber($details['value']) . ' ';
                break;
            default:
                $ret .= htmlspecialchars($details['value']) . '   ';
            }
            $ret .= '</td>' . "\n"
                  . '</tr>' . "\n";
        }

        if (! $ret) {
            $ret = '<p>' . "\n"
                . '    '
                . __(
                    'There is no detailed status information available for this '
                    . 'storage engine.'
                )
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
    public function resolveTypeSize($value)
    {
        return Util::formatByteDown($value);
    }

    /**
     * Returns array with detailed info about engine specific server variables
     *
     * @return array array with detailed info about specific engine server variables
     */
    public function getVariablesStatus()
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
                $mysql_vars[$row['Variable_name']]
                    = $variables[$row['Variable_name']];
            } elseif (! $like
                && mb_strpos(mb_strtolower($row['Variable_name']), mb_strtolower($this->engine)) !== 0
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
     * Reveals the engine's title
     *
     * @return string The title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Fetches the server's comment about this engine
     *
     * @return string The comment
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Information message on whether this storage engine is supported
     *
     * @return string The localized message.
     */
    public function getSupportInformationMessage()
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
            $message = __(
                'This MySQL server does not support the %s storage engine.'
            );
        }
        return sprintf($message, htmlspecialchars($this->title));
    }

    /**
     * Generates a list of MySQL variables that provide information about this
     * engine. This function should be overridden when extending this class
     * for a particular engine.
     *
     * @return array The list of variables.
     */
    public function getVariables()
    {
        return array();
    }

    /**
     * Returns string with filename for the MySQL helppage
     * about this storage engine
     *
     * @return string MySQL help page filename
     */
    public function getMysqlHelpPage()
    {
        return $this->engine . '-storage-engine';
    }

    /**
     * Returns the pattern to be used in the query for SQL variables
     * related to the storage engine
     *
     * @return string SQL query LIKE pattern
     */
    public function getVariablesLikePattern()
    {
        return '';
    }

    /**
     * Returns a list of available information pages with labels
     *
     * @return string[] The list
     */
    public function getInfoPages()
    {
        return array();
    }

    /**
     * Generates the requested information page
     *
     * @param string $id page id
     *
     * @return string html output
     */
    public function getPage($id)
    {
        if (! array_key_exists($id, $this->getInfoPages())) {
            return '';
        }

        $id = 'getPage' . $id;

        return $this->$id();
    }
}

