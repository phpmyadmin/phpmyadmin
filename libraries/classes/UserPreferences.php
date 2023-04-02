<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\User\UserFormList;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\Connection;
use PhpMyAdmin\Dbal\DatabaseName;

use function __;
use function array_flip;
use function array_merge;
use function htmlspecialchars;
use function http_build_query;
use function is_array;
use function is_int;
use function is_numeric;
use function is_string;
use function json_decode;
use function json_encode;
use function str_contains;
use function time;
use function urlencode;

/**
 * Functions for displaying user preferences pages
 */
class UserPreferences
{
    private Relation $relation;

    public Template $template;

    public function __construct(private DatabaseInterface $dbi)
    {
        $this->relation = new Relation($this->dbi);
        $this->template = new Template();
    }

    /**
     * Common initialization for user preferences modification pages
     *
     * @param ConfigFile $cf Config file instance
     */
    public function pageInit(ConfigFile $cf): void
    {
        $formsAllKeys = UserFormList::getFields();
        $cf->resetConfigData(); // start with a clean instance
        $cf->setAllowedKeys($formsAllKeys);
        $cf->setCfgUpdateReadMapping(
            ['Server/hide_db' => 'Servers/1/hide_db', 'Server/only_db' => 'Servers/1/only_db'],
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
     * @psalm-return array{config_data: mixed[], mtime: int, type: 'session'|'db'}
     */
    public function load(): array
    {
        $relationParameters = $this->relation->getRelationParameters();
        if ($relationParameters->userPreferencesFeature === null) {
            // no pmadb table, use session storage
            if (! isset($_SESSION['userconfig']) || ! is_array($_SESSION['userconfig'])) {
                $_SESSION['userconfig'] = ['db' => [], 'ts' => time()];
            }

            $configData = $_SESSION['userconfig']['db'] ?? null;
            $timestamp = $_SESSION['userconfig']['ts'] ?? null;

            return [
                'config_data' => is_array($configData) ? $configData : [],
                'mtime' => is_int($timestamp) ? $timestamp : time(),
                'type' => 'session',
            ];
        }

        // load configuration from pmadb
        $queryTable = Util::backquote($relationParameters->userPreferencesFeature->database) . '.'
            . Util::backquote($relationParameters->userPreferencesFeature->userConfig);
        $query = 'SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts'
            . ' FROM ' . $queryTable
            . ' WHERE `username` = '
            . $this->dbi->quoteString((string) $relationParameters->user);
        $row = $this->dbi->fetchSingleRow($query, DatabaseInterface::FETCH_ASSOC, Connection::TYPE_CONTROL);
        if (! is_array($row) || ! isset($row['config_data']) || ! isset($row['ts'])) {
            return ['config_data' => [], 'mtime' => time(), 'type' => 'db'];
        }

        $configData = is_string($row['config_data']) ? json_decode($row['config_data'], true) : [];

        return [
            'config_data' => is_array($configData) ? $configData : [],
            'mtime' => is_numeric($row['ts']) ? (int) $row['ts'] : time(),
            'type' => 'db',
        ];
    }

    /**
     * Saves user preferences
     *
     * @param mixed[] $configArray configuration array
     *
     * @return true|Message
     */
    public function save(array $configArray): bool|Message
    {
        $relationParameters = $this->relation->getRelationParameters();
        $server = $GLOBALS['server'] ?? $GLOBALS['cfg']['ServerDefault'];
        $cacheKey = 'server_' . $server;
        if (
            $relationParameters->userPreferencesFeature === null
            || $relationParameters->user === null
            || $relationParameters->db === null
        ) {
            // no pmadb table, use session storage
            $_SESSION['userconfig'] = ['db' => $configArray, 'ts' => time()];
            if (isset($_SESSION['cache'][$cacheKey]['userprefs'])) {
                unset($_SESSION['cache'][$cacheKey]['userprefs']);
            }

            return true;
        }

        // save configuration to pmadb
        $queryTable = Util::backquote($relationParameters->userPreferencesFeature->database) . '.'
            . Util::backquote($relationParameters->userPreferencesFeature->userConfig);
        $query = 'SELECT `username` FROM ' . $queryTable
            . ' WHERE `username` = '
            . $this->dbi->quoteString($relationParameters->user);

        $hasConfig = $this->dbi->fetchValue($query, 0, Connection::TYPE_CONTROL);
        $configData = json_encode($configArray);
        if ($hasConfig) {
            $query = 'UPDATE ' . $queryTable
                . ' SET `timevalue` = NOW(), `config_data` = '
                . $this->dbi->quoteString($configData)
                . ' WHERE `username` = '
                . $this->dbi->quoteString($relationParameters->user);
        } else {
            $query = 'INSERT INTO ' . $queryTable
                . ' (`username`, `timevalue`,`config_data`) '
                . 'VALUES ('
                . $this->dbi->quoteString($relationParameters->user) . ', NOW(), '
                . $this->dbi->quoteString($configData) . ')';
        }

        if (isset($_SESSION['cache'][$cacheKey]['userprefs'])) {
            unset($_SESSION['cache'][$cacheKey]['userprefs']);
        }

        if (! $this->dbi->tryQuery($query, Connection::TYPE_CONTROL)) {
            $message = Message::error(__('Could not save configuration'));
            $message->addMessage(
                Message::error($this->dbi->getError(Connection::TYPE_CONTROL)),
                '<br><br>',
            );
            if (! $this->hasAccessToDatabase($relationParameters->db)) {
                /**
                 * When phpMyAdmin cached the configuration storage parameters, it checked if the database can be
                 * accessed, so if it could not be accessed anymore, then the cache must be cleared as it's out of date.
                 *
                 * @psalm-suppress MixedArrayAssignment
                 */
                $_SESSION['relation'][$GLOBALS['server']] = [];
                $message->addMessage(Message::error(htmlspecialchars(
                    __('The phpMyAdmin configuration storage database could not be accessed.'),
                )), '<br><br>');
            }

            return $message;
        }

        return true;
    }

    private function hasAccessToDatabase(DatabaseName $database): bool
    {
        $query = 'SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '
            . $this->dbi->quoteString($database->getName());
        if ($GLOBALS['cfg']['Server']['DisableIS']) {
            $query = 'SHOW DATABASES LIKE '
                . $this->dbi->quoteString(
                    $this->dbi->escapeMysqlWildcards($database->getName()),
                );
        }

        return (bool) $this->dbi->fetchSingleRow($query, 'ASSOC', Connection::TYPE_CONTROL);
    }

    /**
     * Returns a user preferences array filtered by $cfg['UserprefsDisallow']
     * (exclude list) and keys from user preferences form (allow list)
     *
     * @param mixed[] $configData path => value pairs
     *
     * @return mixed[]
     */
    public function apply(array $configData): array
    {
        $cfg = [];
        $excludeList = array_flip($GLOBALS['cfg']['UserprefsDisallow']);
        $allowList = array_flip(UserFormList::getFields());
        // allow some additional fields which are custom handled
        $allowList['ThemeDefault'] = true;
        $allowList['lang'] = true;
        $allowList['Server/hide_db'] = true;
        $allowList['Server/only_db'] = true;
        $allowList['2fa'] = true;
        foreach ($configData as $path => $value) {
            if (! isset($allowList[$path]) || isset($excludeList[$path])) {
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
     * @param string $path         configuration
     * @param mixed  $value        value
     * @param mixed  $defaultValue default value
     *
     * @return true|Message
     */
    public function persistOption(string $path, mixed $value, mixed $defaultValue): bool|Message
    {
        $prefs = $this->load();
        if ($value === $defaultValue) {
            if (! isset($prefs['config_data'][$path])) {
                return true;
            }

            unset($prefs['config_data'][$path]);
        } else {
            $prefs['config_data'][$path] = $value;
        }

        return $this->save($prefs['config_data']);
    }

    /**
     * Redirects after saving new user preferences
     *
     * @param string       $fileName Filename
     * @param mixed[]|null $params   URL parameters
     * @param string|null  $hash     Hash value
     */
    public function redirect(
        string $fileName,
        array|null $params = null,
        string|null $hash = null,
    ): void {
        // redirect
        $urlParams = ['saved' => 1];
        if (is_array($params)) {
            $urlParams = array_merge($params, $urlParams);
        }

        if ($hash) {
            $hash = '#' . urlencode($hash);
        }

        Core::sendHeaderLocation('./' . $fileName
            . Url::getCommonRaw($urlParams, ! str_contains($fileName, '?') ? '?' : '&') . $hash);
    }

    /**
     * Shows form which allows to quickly load
     * settings stored in browser's local storage
     */
    public function autoloadGetHeader(): string
    {
        if (isset($_REQUEST['prefs_autoload']) && $_REQUEST['prefs_autoload'] === 'hide') {
            $_SESSION['userprefs_autoload'] = true;

            return '';
        }

        $returnUrl = '?' . http_build_query($_GET, '', '&');

        return $this->template->render('preferences/autoload', [
            'hidden_inputs' => Url::getHiddenInputs(),
            'return_url' => $returnUrl,
        ]);
    }
}
