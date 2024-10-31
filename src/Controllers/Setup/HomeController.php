<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\ServerConfigChecks;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\Index;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Template;

use function __;
use function array_keys;
use function file_exists;
use function is_scalar;
use function is_string;

use const CONFIG_FILE;

final class HomeController implements InvocableController
{
    private static bool $hasCheckPageRefresh = false;

    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly ResponseRenderer $responseRenderer,
        private readonly Template $template,
        private readonly Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (@file_exists(CONFIG_FILE) && ! $this->config->config->debug->demo) {
            $response = $this->responseFactory->createResponse(StatusCodeInterface::STATUS_NOT_FOUND);

            return $response->write($this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'error_message' => __('Configuration already exists, setup is disabled!'),
            ]));
        }

        $response = $this->responseFactory->createResponse();
        foreach ($this->responseRenderer->getHeader()->getHttpHeaders() as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        $pages = SetupHelper::getPages();

        // message handling
        Index::messagesBegin();

        // Check phpMyAdmin version
        if ($request->hasQueryParam('version_check')) {
            Index::versionCheck();
        }

        $configFile = SetupHelper::createConfigFile();

        // Perform various security, compatibility and consistency checks
        $configChecker = new ServerConfigChecks($configFile);
        $configChecker->performConfigChecks();

        $text = __(
            'You are not using a secure connection; all data (including potentially '
            . 'sensitive information, like passwords) is transferred unencrypted!',
        );
        $text .= ' <a href="#">';
        $text .= __(
            'If your server is also configured to accept HTTPS requests '
            . 'follow this link to use a secure connection.',
        );
        $text .= '</a>';
        Index::messagesSet('notice', 'no_https', __('Insecure connection'), $text);

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
        foreach (array_keys($configFile->getServers()) as $id) {
            $servers[$id] = [
                'id' => $id,
                'name' => $configFile->getServerName($id),
                'auth_type' => $configFile->getValue('Servers/' . $id . '/auth_type'),
                'dsn' => $configFile->getServerDSN($id),
                'params' => [
                    'token' => $_SESSION[' PMA_token '],
                    'edit' => ['page' => 'servers', 'mode' => 'edit', 'id' => $id],
                    'remove' => ['page' => 'servers', 'mode' => 'remove', 'id' => $id],
                ],
            ];
        }

        if (! self::$hasCheckPageRefresh) {
            self::$hasCheckPageRefresh = true;
        }

        return $response->write($this->template->render('setup/home/index', [
            'formset' => $this->getFormSetParam($request->getQueryParam('formset')),
            'languages' => $languages,
            'messages' => $messages,
            'server_count' => $configFile->getServerCount(),
            'servers' => $servers,
            'pages' => $pages,
            'has_check_page_refresh' => self::$hasCheckPageRefresh,
            'eol' => isset($_SESSION['eol']) && is_scalar($_SESSION['eol'])
                ? $_SESSION['eol']
                : ($this->config->get('PMA_IS_WINDOWS') ? 'win' : 'unix'),
        ]));
    }

    private function getFormSetParam(mixed $formSetParam): string
    {
        return is_string($formSetParam) ? $formSetParam : '';
    }
}
