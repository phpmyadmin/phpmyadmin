<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;
use function defined;
use function strlen;

abstract class AbstractController
{
    /** @var ResponseRenderer */
    protected $response;

    /** @var Template */
    protected $template;

    public function __construct(ResponseRenderer $response, Template $template)
    {
        $this->response = $response;
        $this->template = $template;
    }

    /**
     * @param array<string, mixed> $templateData
     */
    protected function render(string $templatePath, array $templateData = []): void
    {
        $this->response->addHTML($this->template->render($templatePath, $templateData));
    }

    /**
     * @param string[] $files
     */
    protected function addScriptFiles(array $files): void
    {
        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFiles($files);
    }

    protected function hasDatabase(): bool
    {
        if (isset($GLOBALS['is_db']) && $GLOBALS['is_db']) {
            return true;
        }

        $GLOBALS['is_db'] = false;
        if (strlen($GLOBALS['db']) > 0) {
            $GLOBALS['is_db'] = $GLOBALS['dbi']->selectDb($GLOBALS['db']);
            // This "Command out of sync" 2014 error may happen, for example
            // after calling a MySQL procedure; at this point we can't select
            // the db but it's not necessarily wrong
            if ($GLOBALS['dbi']->getError() && $GLOBALS['errno'] == 2014) {
                $GLOBALS['is_db'] = true;
                unset($GLOBALS['errno']);
            }
        }

        if (strlen($GLOBALS['db']) === 0 || ! $GLOBALS['is_db']) {
            if ($this->response->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON(
                    'message',
                    Message::error(__('No databases selected.'))
                );

                return false;
            }

            // Not a valid db name -> back to the welcome page
            $params = ['reload' => '1'];
            if (isset($GLOBALS['message'])) {
                $params['message'] = $GLOBALS['message'];
            }

            $this->redirect('/', $params);

            return false;
        }

        return $GLOBALS['is_db'];
    }

    /**
     * @param array<string, mixed> $params
     */
    protected function redirect(string $route, array $params = []): void
    {
        if (defined('TESTSUITE')) {
            return;
        }

        $uri = './index.php?route=' . $route . Url::getCommonRaw($params, '&');
        Core::sendHeaderLocation($uri);
    }
}
