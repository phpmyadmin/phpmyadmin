<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Plugins;

class LogoutController
{
    public function __invoke(ServerRequest $request): void
    {
        if (! $request->isPost() || $GLOBALS['token_mismatch']) {
            Core::sendHeaderLocation('./index.php?route=/');

            return;
        }

        Plugins::getAuthPlugin()->logOut();
    }
}
