<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;
use PhpMyAdmin\ResponseRenderer;

class LogoutController
{
    public function __construct(private AuthenticationPluginFactory $authPluginFactory)
    {
    }

    public function __invoke(ServerRequest $request): void
    {
        if (! $request->isPost() || $GLOBALS['token_mismatch']) {
            ResponseRenderer::getInstance()->redirect('./index.php?route=/');

            return;
        }

        $authPlugin = $this->authPluginFactory->create();
        $authPlugin->logOut();
    }
}
