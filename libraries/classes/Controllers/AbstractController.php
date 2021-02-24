<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use function strlen;

abstract class AbstractController
{
    /** @var Response */
    protected $response;

    /** @var Template */
    protected $template;

    /**
     * @param Response $response
     */
    public function __construct($response, Template $template)
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
        global $db, $is_db, $errno, $dbi, $message;

        if (isset($is_db) && $is_db) {
            return true;
        }

        $is_db = false;
        if (strlen($db) > 0) {
            $is_db = $dbi->selectDb($db);
            // This "Command out of sync" 2014 error may happen, for example
            // after calling a MySQL procedure; at this point we can't select
            // the db but it's not necessarily wrong
            if ($dbi->getError() && $errno == 2014) {
                $is_db = true;
                unset($errno);
            }
        }

        if (strlen($db) === 0 || ! $is_db) {
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
            if (isset($message)) {
                $params['message'] = $message;
            }
            $uri = './index.php?route=/' . Url::getCommonRaw($params, '&');
            Core::sendHeaderLocation($uri);

            return false;
        }

        return $is_db;
    }
}
