<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Current;
use PhpMyAdmin\Html\MySQLDocumentation;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function __;

abstract class AbstractController
{
    public function __construct(protected ResponseRenderer $response, protected Template $template)
    {
    }

    /** @param array<string, mixed> $templateData */
    protected function render(string $templatePath, array $templateData = []): void
    {
        $this->response->addHTML($this->template->render($templatePath, $templateData));
    }

    /** @param string[] $files */
    protected function addScriptFiles(array $files): void
    {
        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFiles($files);
    }

    /** @param array<string, mixed> $params */
    protected function redirect(string $route, array $params = []): void
    {
        $this->response->redirect('./index.php?route=' . $route . Url::getCommonRaw($params, '&'));
    }

    /**
     * Function added to avoid path disclosures.
     * Called by each script that needs parameters.
     *
     * @param bool $request Check parameters in request
     * @psalm-param non-empty-list<non-empty-string> $params The names of the parameters needed by the calling script
     */
    protected function checkParameters(array $params, bool $request = false): bool
    {
        $foundError = false;
        $errorMessage = '';
        $array = $request ? $_REQUEST : $GLOBALS;

        foreach ($params as $param) {
            if (isset($array[$param]) && $array[$param] !== '') {
                continue;
            }

            if (! $request && $param === 'server' && Current::$server > 0) {
                continue;
            }

            if (! $request && $param === 'db' && Current::$database !== '') {
                continue;
            }

            if (! $request && $param === 'table' && Current::$table !== '') {
                continue;
            }

            $errorMessage .=
                __('Missing parameter:') . ' '
                . $param
                . MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true)
                . '[br]';
            $foundError = true;
        }

        if ($foundError) {
            $this->response->setStatusCode(StatusCodeInterface::STATUS_BAD_REQUEST);
            $this->response->setRequestStatus(false);
            $this->response->addHTML(Message::error($errorMessage)->getDisplay());
        }

        return ! $foundError;
    }

    /** @psalm-param StatusCodeInterface::STATUS_* $statusCode */
    protected function sendErrorResponse(string $message, int $statusCode = 400): void
    {
        $this->response->setStatusCode($statusCode);
        $this->response->setRequestStatus(false);

        if ($this->response->isAjax()) {
            $this->response->addJSON('isErrorResponse', true);
            $this->response->addJSON('message', $message);

            return;
        }

        $this->response->addHTML(Message::error($message)->getDisplay());
    }
}
