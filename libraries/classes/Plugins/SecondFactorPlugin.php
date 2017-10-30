<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Second authentication factor handling
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins;

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
     * @var string
     */
    protected $_user;
    /**
     * @var array
     */
    protected $_config;

    /**
     * Creates object
     *
     * @param string $user   User name
     * @param array  $config Second factor configuration
     */
    public function __construct($user, $config)
    {
        $this->_user = $user;
        $this->_config = $config;
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
     * Return current configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
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
