<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

final class CollationConnectionController extends AbstractController
{
    public function __construct(ResponseRenderer $response, Template $template, private Config $config)
    {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->config->setUserValue(
            null,
            'DefaultConnectionCollation',
            $request->getParsedBodyParam('collation_connection'),
            'utf8mb4_unicode_ci',
        );

        $this->response->header('Location: index.php?route=/' . Url::getCommonRaw([], '&'));
    }
}
