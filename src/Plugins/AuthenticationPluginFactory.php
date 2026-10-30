<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Config;
use PhpMyAdmin\Exceptions\AuthenticationPluginException;
use PhpMyAdmin\Plugins\Auth\AuthenticationConfig;
use PhpMyAdmin\Plugins\Auth\AuthenticationCookie;
use PhpMyAdmin\Plugins\Auth\AuthenticationHttp;
use PhpMyAdmin\Plugins\Auth\AuthenticationSignon;
use PhpMyAdmin\ResponseRenderer;

use function __;

class AuthenticationPluginFactory
{
    private AuthenticationPlugin|null $plugin = null;

    public function __construct(private readonly ResponseRenderer $responseRenderer)
    {
    }

    /** @throws AuthenticationPluginException */
    public function create(): AuthenticationPlugin
    {
        if ($this->plugin instanceof AuthenticationPlugin) {
            return $this->plugin;
        }

        $authType = Config::getInstance()->selectedServer['auth_type'];
        $class = match ($authType) {
            'config' => AuthenticationConfig::class,
            'cookie' => AuthenticationCookie::class,
            'http' => AuthenticationHttp::class,
            'signon' => AuthenticationSignon::class,
            default => throw new AuthenticationPluginException(
                __('Invalid authentication method set in configuration:') . ' ' . $authType,
            ),
        };

        $this->plugin = new $class($this->responseRenderer);

        return $this->plugin;
    }
}
