<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Url;

#[Route('/collation-connection', ['POST'])]
final readonly class CollationConnectionController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private UserPreferencesHandler $userPreferencesHandler,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $this->userPreferencesHandler->setUserValue(
            null,
            'DefaultConnectionCollation',
            $request->getParsedBodyParam('collation_connection'),
            'utf8mb4_unicode_ci',
        );

        $this->response->redirect('index.php?route=/' . Url::getCommonRaw([], '&'));

        return $this->response->response();
    }
}
