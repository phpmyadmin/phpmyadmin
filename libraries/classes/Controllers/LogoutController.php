<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;

class LogoutController
{
    public function __invoke(): void
    {
        $GLOBALS['auth_plugin'] = $GLOBALS['auth_plugin'] ?? null;
        $GLOBALS['token_mismatch'] = $GLOBALS['token_mismatch'] ?? null;

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || $GLOBALS['token_mismatch']) {
            Core::sendHeaderLocation('./index.php?route=/');

            return;
        }

        $GLOBALS['auth_plugin']->logOut();
    }
}
