<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;

abstract class AbstractController implements InvocableController
{
    public function __construct(protected ResponseRenderer $response, protected Template $template)
    {
    }

    /** @param array<string, mixed> $templateData */
    protected function render(string $templatePath, array $templateData = []): void
    {
        $this->response->addHTML($this->template->render($templatePath, $templateData));
    }
}
