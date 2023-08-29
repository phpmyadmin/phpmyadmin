<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Normalization;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Http\ServerRequest;

/**
 * Normalization process (temporarily specific to 1NF).
 */
class MainController extends AbstractController
{
    public function __invoke(ServerRequest $request): void
    {
        $this->addScriptFiles(['normalization.js', 'vendor/jquery/jquery.uitablefilter.js']);
        $this->render('table/normalization/normalization', ['db' => $GLOBALS['db'], 'table' => $GLOBALS['table']]);
    }
}
