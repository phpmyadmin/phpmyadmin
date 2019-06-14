<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\UserPreferences class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * Functions for displaying user preferences pages
 *
 * @package PhpMyAdmin
 */
class UserPreferences
{
    /**
     * @var Relation
     */
    private $relation;

    /**
     * @var Template
     */
    public $template;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->relation = new Relation($GLOBALS['dbi']);
        $this->template = new Template();
    }

    /**
     * Common initialization for user preferences modification pages
     *
     * @param ConfigFile $cf Config file instance
     *
     * @return void
     */
    public function pageInit(ConfigFile $cf)
    {
        $forms_all_keys = UserFormList::getFields();
        $cf->resetConfigData(); // start with a clean instance
        $cf->setAllowedKeys($forms_all_keys);
        $cf->setCfgUpdateReadMapping(
            [
                'Server/hide_db' => 'Servers/1/hide_db',
                'Server/only_db' => 'Servers/1/only_db',
            ]
        );
        $cf->updateWithGlobalConfig($GLOBALS['cfg']);
    }

    /**
     * Loads user preferences
     *
     * Returns an array:
     * * config_data - path => value pairs
     * * mtime - last modification time
     * * type - 'db' (config read from pmadb) or 'session' (read from user session)
     *
     * @return array
     */
    public function load()
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! $cfgRelation['userconfigwork']) {
            // no pmadb table, use session storage
            if (! isset($_SESSION['userconfig'])) {
                $_SESSION['userconfig'] = [
                    'db' => [],
                    'ts' => time(),
                ];
            }
            return [
                'config_data' => $_SESSION['userconfig']['db'],
                'mtime' => $_SESSION['userconfig']['ts'],
                'type' => 'session',
            ];
        }
        // load configuration from pmadb
        $query_table = Util::backquote($cfgRelation['db']) . '.'
            . Util::backquote($cfgRelation['userconfig']);
        $query = 'SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts'
            . ' FROM ' . $query_table
            . ' WHERE `username` = \''
            . $GLOBALS['dbi']->escapeString($cfgRelation['user'])
            . '\'';
        $row = $GLOBALS['dbi']->fetchSingleRow($query, 'ASSOC', DatabaseInterface::CONNECT_CONTROL);

        return [
            'config_data' => $row ? json_decode($row['config_data'], true) : [],
            'mtime' => $row ? $row['ts'] : time(),
            'type' => 'db',
        ];
    }

    /**
     * Saves user preferences
     *
     * @param array $config_array configuration array
     *
     * @return true|Message
     */
    public function save(array $config_array)
    {
        $cfgRelation = $this->relation->getRelationsParam();
        $server = isset($GLOBALS['server'])
            ? $GLOBALS['server']
            : $GLOBALS['cfg']['ServerDefault'];
        $cache_key = 'server_' . $server;
        if (! $cfgRelation['userconfigwork']) {
            // no pmadb table, use session storage
            $_SESSION['userconfig'] = [
                'db' => $config_array,
                'ts' => time(),
            ];
            if (isset($_SESSION['cache'][$cache_key]['userprefs'])) {
                unset($_SESSION['cache'][$cache_key]['userprefs']);
            }
            return true;
        }

        // save configuration to pmadb
        $query_table = Util::backquote($cfgRelation['db']) . '.'
            . Util::backquote($cfgRelation['userconfig']);
        $query = 'SELECT `username` FROM ' . $query_table
            . ' WHERE `username` = \''
            . $GLOBALS['dbi']->escapeString($cfgRelation['user'])
            . '\'';

        $has_config = $GLOBALS['dbi']->fetchValue(
            $query,
            0,
            0,
            DatabaseInterface::CONNECT_CONTROL
        );
        $config_data = json_encode($config_array);
        if ($has_config) {
            $query = 'UPDATE ' . $query_table
                . ' SET `timevalue` = NOW(), `config_data` = \''
                . $GLOBALS['dbi']->escapeString($config_data)
                . '\''
                . ' WHERE `username` = \''
                . $GLOBALS['dbi']->escapeString($cfgRelation['user'])
                . '\'';
        } else {
            $query = 'INSERT INTO ' . $query_table
                . ' (`username`, `timevalue`,`config_data`) '
                . 'VALUES (\''
                . $GLOBALS['dbi']->escapeString($cfgRelation['user']) . '\', NOW(), '
                . '\'' . $GLOBALS['dbi']->escapeString($config_data) . '\')';
        }
        if (isset($_SESSION['cache'][$cache_key]['userprefs'])) {
            unset($_SESSION['cache'][$cache_key]['userprefs']);
        }
        if (! $GLOBALS['dbi']->tryQuery($query, DatabaseInterface::CONNECT_CONTROL)) {
            $message = Message::error(__('Could not save configuration'));
            $message->addMessage(
                Message::rawError(
                    $GLOBALS['dbi']->getError(DatabaseInterface::CONNECT_CONTROL)
                ),
                '<br><br>'
            );
            return $message;
        }
        return true;
    }

    /**
     * Returns a user preferences array filtered by $cfg['UserprefsDisallow']
     * (blacklist) and keys from user preferences form (whitelist)
     *
     * @param array $config_data path => value pairs
     *
     * @return array
     */
    public function apply(array $config_data)
    {
        $cfg = [];
        $blacklist = array_flip($GLOBALS['cfg']['UserprefsDisallow']);
        $whitelist = array_flip(UserFormList::getFields());
        // whitelist some additional fields which are custom handled
        $whitelist['ThemeDefault'] = true;
        $whitelist['lang'] = true;
        $whitelist['Server/hide_db'] = true;
        $whitelist['Server/only_db'] = true;
        $whitelist['2fa'] = true;
        foreach ($config_data as $path => $value) {
            if (! isset($whitelist[$path]) || isset($blacklist[$path])) {
                continue;
            }
            Core::arrayWrite($path, $cfg, $value);
        }
        return $cfg;
    }

    /**
     * Updates one user preferences option (loads and saves to database).
     *
     * No validation is done!
     *
     * @param string $path          configuration
     * @param mixed  $value         value
     * @param mixed  $default_value default value
     *
     * @return true|Message
     */
    public function persistOption($path, $value, $default_value)
    {
        $prefs = $this->load();
        if ($value === $default_value) {
            if (isset($prefs['config_data'][$path])) {
                unset($prefs['config_data'][$path]);
            } else {
                return true;
            }
        } else {
            $prefs['config_data'][$path] = $value;
        }
        return $this->save($prefs['config_data']);
    }

    /**
     * Redirects after saving new user preferences
     *
     * @param string     $file_name Filename
     * @param array|null $params    URL parameters
     * @param string     $hash      Hash value
     *
     * @return void
     */
    public function redirect(
        $file_name,
        $params = null,
        $hash = null
    ) {
        // redirect
        $url_params = ['saved' => 1];
        if (is_array($params)) {
            $url_params = array_merge($params, $url_params);
        }
        if ($hash) {
            $hash = '#' . urlencode($hash);
        }
        Core::sendHeaderLocation('./' . $file_name
            . Url::getCommonRaw($url_params) . $hash);
    }

    /**
     * Shows form which allows to quickly load
     * settings stored in browser's local storage
     *
     * @return string
     */
    public function autoloadGetHeader()
    {
        if (isset($_REQUEST['prefs_autoload'])
            && $_REQUEST['prefs_autoload'] == 'hide'
        ) {
            $_SESSION['userprefs_autoload'] = true;
            return '';
        }

        $script_name = basename(basename($GLOBALS['PMA_PHP_SELF']));
        $return_url = $script_name . '?' . http_build_query($_GET, '', '&');

        return $this->template->render('preferences/autoload', [
            'hidden_inputs' => Url::getHiddenInputs(),
            'return_url' => $return_url,
        ]);
    }
}
