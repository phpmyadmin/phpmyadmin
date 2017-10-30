<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Second authentication factor handling
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins\SecondFactor;

use PhpMyAdmin\Plugins\SecondFactorPlugin;
use PhpMyAdmin\Template;

/**
 * Simple second factor auth asking just for confirmation.
 *
 * This has no practical use, but can be used for testing.
 */
class Simple extends SecondFactorPlugin
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
     * Renders user interface to enter second factor
     *
     * @return string HTML code
     */
    public function render()
    {
        return Template::get('login/second/simple')->render();
    }

    /**
     * Get user visible name
     *
     * @return string
     */
    public static function getName()
    {
        return __('Simple second factor');
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
