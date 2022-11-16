<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins\AuthenticationPluginFactory;

class LogoutController
{
    /** @var AuthenticationPluginFactory */
    private $authPluginFactory;

    public function __construct(AuthenticationPluginFactory $authPluginFactory)
    {
        $this->authPluginFactory = $authPluginFactory;
    }

    public function __invoke(ServerRequest $request): void
    {
        if (! $request->isPost() || $GLOBALS['token_mismatch']) {
            Core::sendHeaderLocation('./index.php?route=/');

            return;
        }

        $authPlugin = $this->authPluginFactory->create();
        $authPlugin->logOut();
    }
}
