<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

class PMA_Config {

    /**
     * @var string  default config source
     */
    var $default_source = './config.default.php';

    /**
     * @var array   configuration settings
     */
    var $settings = array();

    /**
     * @var boolean
     */
    var $success_apply_user_config = true;

    /**
     * @var string  config source
     */
    var $source = '';

    /**
     * @var int     source modification time
     */
    var $source_mtime = 0;

    /**
     * @var boolean
     */
    var $cannot_load_configuration_defaults = false;

    /**
     * @var array
     */
    var $default_server = array();

    /**
     * constructor
     *
     * @param   string  source to read config from
     */
    function __construct( $source ) {
        $this->load( $source );
    }

    function __wakeup() {
        if ( $this->source_mtime !== filemtime( $this->getSource() ) ) {
            $this->load( $this->getSource() );
        }
    }

    /**
     * loads default values from default source
     *
     * @uses    file_exists()
     * @uses    $this->default_source
     * @uses    $this->cannot_load_configuration_defaults
     * @uses    $this->cfg
     * @return  boolean     success
     */
    function loadDefaults() {
        $cfg = array();
        if ( ! file_exists( $this->default_source ) ) {
            $this->cannot_load_configuration_defaults = true;
            return false;
        }
        include $this->default_source;

        $this->default_server = $cfg['Servers'][1];
        unset( $cfg['Servers'] );

        $this->cfg = $cfg;
        return true;
    }

    function load( $source ) {

        $this->loadDefaults();

        if ( $this->setSource( $source ) ) {
            return false;
        }

        /**
         * Parses the configuration file
         */
        $old_error_reporting = error_reporting( 0 );
        if ( function_exists( 'file_get_contents' ) ) {
            $this->success_apply_user_config =
                eval( '?>' . file_get_contents( $this->setSource() ) );
        } else {
            $this->success_apply_user_config =
                eval( '?>' . implode( '\n', file( $this->setSource() ) ) );
        }
        error_reporting( $old_error_reporting );

        if ( $this->success_apply_user_config ) {
            $this->source_mtime = filemtime( $this->getSource() );
        }
    }

    /**
     * set source
     * @param   string  $source
     */
    function setSource( $source ) {
        if ( ! file_exists( $source ) ) {
            trigger_error(
                'phpMyAdmin-ERROR: unkown configuration source: ' . $source,
                E_USER_WARNING );
            return false;
        }
        $this->source = trim( $source );
        return true;
    }

    /**
     * returns specific config setting
     * @param   string  $setting
     * @return  mixed   value
     */
    function get( $setting ) {
        if ( isset( $this->settings[$setting] ) ) {
            return $this->settings[$setting];
        }

        return NULL;
    }

    /**
     * returns source for current config
     * @return  string  config source
     */
    function getSource() {
        return $this->source;
    }

    /**
     * old PHP 4 style constructor
     *
     * @deprecated
     */
    function PMA_Config( $source ) {
        $this->__construct( $source );
    }

    /**
     * @todo finish
     */
    function save() {}
}
?>