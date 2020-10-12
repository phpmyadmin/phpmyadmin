<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Response;
use PhpMyAdmin\Template;

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
}
