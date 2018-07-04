<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Second authentication factor handling
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PhpMyAdmin\Template;

/**
 * Invalid two-factor authentication showing that configured choice is not available.
 *
 * @package PhpMyAdmin
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
        return $this->template->render('login/twofactor/invalid');
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
