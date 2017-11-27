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
 * Invalid two-factor authentication showing that configured choice is not available.
 */
class Invalid extends TwoFactorPlugin
{
    /**
     * @var string
     */
    public static $id = 'invalid';

    public static $showSubmit = false;

    /**
     * Checks authentication, returns true on success
     *
     * @return boolean
     */
    public function check()
    {
        return false;
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render()
    {
        return Template::get('login/twofactor/invalid')->render();
    }

    /**
     * Get user visible name
     *
     * @return string
     */
    public static function getName()
    {
        return 'Invalid two-factor authentication';
    }

    /**
     * Get user visible description
     *
     * @return string
     */
    public static function getDescription()
    {
        return 'Error fallback only!';
    }
}

