<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LogoutController
{
    public function index(Request $request, Response $response): Response
    {
        global $auth_plugin, $token_mismatch;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $token_mismatch) {
            Core::sendHeaderLocation('./index.php?route=/');

            return $response;
        }

        $auth_plugin->logOut();

        return $response;
    }
}
