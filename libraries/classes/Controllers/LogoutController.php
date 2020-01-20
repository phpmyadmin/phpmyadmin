<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Plugins\AuthenticationPlugin;

class LogoutController
{
    public function index(): void
    {
        global $auth_plugin, $token_mismatch;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $token_mismatch) {
            Core::sendHeaderLocation('./index.php?route=/');
            return;
        }

        /** @var AuthenticationPlugin $auth_plugin */
        $auth_plugin->logOut();
    }
}
