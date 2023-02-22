<?php
/**
 * Second authentication factor handling
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use PhpMyAdmin\Plugins\TwoFactorPlugin;

/**
 * Invalid two-factor authentication showing that configured choice is not available.
 */
class Invalid extends TwoFactorPlugin
{
    public static string $id = 'invalid';

    public static bool $showSubmit = false;

    /**
     * Checks authentication, returns true on success
     */
    public function check(): bool
    {
        return false;
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render(): string
    {
        return $this->template->render('login/twofactor/invalid');
    }

    /**
     * Get user visible name
     */
    public static function getName(): string
    {
        return 'Invalid two-factor authentication';
    }

    /**
     * Get user visible description
     */
    public static function getDescription(): string
    {
        return 'Error fallback only!';
    }
}
