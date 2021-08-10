<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Auth;

use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use PhpMyAdmin\Plugins\AuthenticationPlugin;

/**
 * Handles the Keycloak authentication methods
 */
class AuthenticationKeycloak extends AuthenticationPlugin
{
    public function showLoginForm(): bool
    {
        return false;
    }

    public function readCredentials(): bool
    {
        if (! isset($_COOKIE['kc-access'])) {
            return false;
        }

        $keycloakToken = $_COOKIE['kc-access'];
        if (! $keycloakToken) {
            return false;
        }

        $token = $this->getToken($keycloakToken);
        if (! $token->hasClaim('preferred_username') || ! $token->hasClaim('sub')) {
            return false;
        }

        $this->user = $token->getClaim('preferred_username');
        $this->password = $token->getClaim('sub');

        return true;
    }

    protected function getToken(string $keycloakToken): Token
    {
        $parser = new Parser();

        return $parser->parse($keycloakToken);
    }
}
