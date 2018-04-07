<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Second authentication factor handling
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins\TwoFactor;

use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PhpMyAdmin\Template;

/**
 * Simple two-factor authentication auth asking just for confirmation.
 *
 * This has no practical use, but can be used for testing.
 */
class Simple extends TwoFactorPlugin
{
    /**
     * @var string
     */
    public static $id = 'simple';

    /**
     * Checks authentication, returns true on success
     *
     * @return boolean
     */
    public function check()
    {
        return isset($_POST['2fa_confirm']);
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render()
    {
        return Template::get('login/twofactor/simple')->render();
    }

    /**
     * Get user visible name
     *
     * @return string
     */
    public static function getName()
    {
        return __('Simple two-factor authentication');
    }

    /**
     * Get user visible description
     *
     * @return string
     */
    public static function getDescription()
    {
        return __('For testing purposes only!');
    }
}
