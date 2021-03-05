<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\ServerConfigChecks;
use PhpMyAdmin\Core;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Sanitize;
use PhpMyAdmin\Setup\Index;

use function preg_replace;
use function uniqid;

class HomeController extends AbstractController
{
    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function index(array $params): string
    {
        $pages = $this->getPages();

        // Handle done action info
        $actionDone = Core::isValid($params['action_done'], 'scalar') ? $params['action_done'] : '';
        $actionDone = preg_replace('/[^a-z_]/', '', $actionDone);

        // message handling
        Index::messagesBegin();

        // Check phpMyAdmin version
        if (isset($params['version_check'])) {
            Index::versionCheck();
        }

        // Perform various security, compatibility and consistency checks
        $configChecker = new ServerConfigChecks($this->config);
        $configChecker->performConfigChecks();

        $text = __(
            'You are not using a secure connection; all data (including potentially '
            . 'sensitive information, like passwords) is transferred unencrypted!'
        );
        $text .= ' <a href="#">';
        $text .= __(
            'If your server is also configured to accept HTTPS requests '
            . 'follow this link to use a secure connection.'
        );
        $text .= '</a>';
        Index::messagesSet('notice', 'no_https', __('Insecure connection'), $text);

        // Check for done action info and set notice message if present
        switch ($actionDone) {
            case 'config_saved':
                /* Use uniqid to display this message every time configuration is saved */
                Index::messagesSet(
                    'notice',
                    uniqid('config_saved'),
                    __('Configuration saved.'),
                    Sanitize::sanitizeMessage(
                        __(
                            'Configuration saved to file config/config.inc.php in phpMyAdmin '
                            . 'top level directory, copy it to top level one and delete '
                            . 'directory config to use it.'
                        )
                    )
                );
                break;
            case 'config_not_saved':
                /* Use uniqid to display this message every time configuration is saved */
                Index::messagesSet(
                    'notice',
                    uniqid('config_not_saved'),
                    __('Configuration not saved!'),
                    Sanitize::sanitizeMessage(
                        __(
                            'Please create web server writable folder [em]config[/em] in '
                            . 'phpMyAdmin top level directory as described in '
                            . '[doc@setup_script]documentation[/doc]. Otherwise you will be '
                            . 'only able to download or display it.'
                        )
                    )
                );
                break;
            default:
                break;
        }

        Index::messagesEnd();
        $messages = Index::messagesShowHtml();

        // prepare unfiltered language list
        $sortedLanguages = LanguageManager::getInstance()->sortedLanguages();
        $languages = [];
        foreach ($sortedLanguages as $language) {
            $languages[] = [
                'code' => $language->getCode(),
                'name' => $language->getName(),
                'is_active' => $language->isActive(),
            ];
        }

        $servers = [];
        foreach ($this->config->getServers() as $id => $server) {
            $servers[$id] = [
                'id' => $id,
                'name' => $this->config->getServerName($id),
                'auth_type' => $this->config->getValue('Servers/' . $id . '/auth_type'),
                'dsn' => $this->config->getServerDSN($id),
                'params' => [
                    'token' => $_SESSION[' PMA_token '],
                    'edit' => [
                        'page' => 'servers',
                        'mode' => 'edit',
                        'id' => $id,
                    ],
                    'remove' => [
                        'page' => 'servers',
                        'mode' => 'remove',
                        'id' => $id,
                    ],
                ],
            ];
        }

        static $hasCheckPageRefresh = false;
        if (! $hasCheckPageRefresh) {
            $hasCheckPageRefresh = true;
        }

        return $this->template->render('setup/home/index', [
            'formset' => $params['formset'] ?? '',
            'languages' => $languages,
            'messages' => $messages,
            'server_count' => $this->config->getServerCount(),
            'servers' => $servers,
            'pages' => $pages,
            'has_check_page_refresh' => $hasCheckPageRefresh,
            'eol' => Core::ifSetOr($_SESSION['eol'], (PMA_IS_WINDOWS ? 'win' : 'unix')),
        ]);
    }
}
