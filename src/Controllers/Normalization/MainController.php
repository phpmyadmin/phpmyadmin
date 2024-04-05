<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;

/**
 * Normalization process (temporarily specific to 1NF).
 */
class MainController extends AbstractController
{
    public function __invoke(ServerRequest $request): Response|null
    {
        $this->response->addScriptFiles(['normalization.js', 'vendor/jquery/jquery.uitablefilter.js']);
        $this->response->render('table/normalization/normalization', [
            'db' => Current::$database,
            'table' => Current::$table,
        ]);

        return null;
    }
}
