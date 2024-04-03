<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\ResponseRenderer;

class LogoutController implements InvocableController
{
    public function __construct(private AuthenticationPluginFactory $authPluginFactory)
    {
    }

    public function __invoke(ServerRequest $request): Response|null
    {
        if (! $request->isPost() || $GLOBALS['token_mismatch']) {
            ResponseRenderer::getInstance()->redirect('./index.php?route=/');

            return null;
        }

        $authPlugin = $this->authPluginFactory->create();
        $authPlugin->logOut();

        return null;
    }
}
