<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Exceptions\AuthenticationPluginException;

use function __;
use function class_exists;
use function is_subclass_of;
use function strtolower;
use function ucfirst;

class AuthenticationPluginFactory
{
    private AuthenticationPlugin|null $plugin = null;

    /** @throws AuthenticationPluginException */
    public function create(): AuthenticationPlugin
    {
        if ($this->plugin instanceof AuthenticationPlugin) {
            return $this->plugin;
        }

        $authType = $GLOBALS['cfg']['Server']['auth_type'];
        $class = 'PhpMyAdmin\\Plugins\\Auth\\Authentication' . ucfirst(strtolower($authType));
        if (! class_exists($class) || ! is_subclass_of($class, AuthenticationPlugin::class)) {
            throw new AuthenticationPluginException(
                __('Invalid authentication method set in configuration:') . ' ' . $authType,
            );
        }

        $this->plugin = new $class();

        return $this->plugin;
    }
}
