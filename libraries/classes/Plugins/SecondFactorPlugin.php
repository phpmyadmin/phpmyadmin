<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Second authentication factor handling
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins;

use PhpMyAdmin\Message;
use PhpMyAdmin\SecondFactor;

/**
 * Second factor authentication plugin class
 *
 * This is basic implementation which does no
 * additional authentication, subclasses are expected
 * to implement this.
 */
class SecondFactorPlugin
{
    /**
     * @var string
     */
    public static $id = '';

    /**
     * @var SecondFactor
     */
    protected $_second;

    /**
     * @var boolean
     */
    protected $_provided;

    /**
     * Creates object
     *
     * @param SecondFactor $second SecondFactor instance
     */
    public function __construct(SecondFactor $second)
    {
        $this->_second = $second;
        $this->_provided = false;
    }

    /**
     * Returns authentication error message
     *
     * @return string
     */
    public function getError()
    {
        if ($this->_provided) {
            return Message::rawError(
                __('Two-factor authentication failed.')
            )->getDisplay();
        }
        return '';
    }

    /**
     * Checks authentication, returns true on success
     *
     * @return boolean
     */
    public function check()
    {
        return true;
    }

    /**
     * Renders user interface to enter second factor
     *
     * @return string HTML code
     */
    public function render()
    {
        return '';
    }

    /**
     * Renders user interface to configure second factor
     *
     * @return string HTML code
     */
    public function setup()
    {
        return '';
    }

    /**
     * Performs backend configuration
     *
     * @return boolean
     */
    public function configure()
    {
        return true;
    }

    /**
     * Get user visible name
     *
     * @return string
     */
    public static function getName()
    {
        return __('None two-factor');
    }

    /**
     * Get user visible description
     *
     * @return string
     */
    public static function getDescription()
    {
        return __('Login using password only.');
    }
}
