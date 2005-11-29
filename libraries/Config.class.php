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
    var $error_config_file = false;

    /**
     * @var boolean
     */
    var $error_config_default_file = false;

    /**
     * @var boolean
     */
    var $error_pma_uri = false;

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

        $this->checkCollationConnection();
    }

    /**
     * loads default values from default source
     *
     * @uses    file_exists()
     * @uses    $this->default_source
     * @uses    $this->error_config_default_file
     * @uses    $this->cfg
     * @return  boolean     success
     */
    function loadDefaults() {
        $cfg = array();
        if ( ! file_exists( $this->default_source ) ) {
            $this->error_config_default_file = true;
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
            $this->error_config_file =
                eval( '?>' . file_get_contents( $this->setSource() ) );
        } else {
            $this->error_config_file =
                eval( '?>' . implode( '\n', file( $this->setSource() ) ) );
        }
        error_reporting( $old_error_reporting );

        if ( $this->error_config_file ) {
            $this->source_mtime = filemtime( $this->getSource() );
        }

        /**
         * @TODO check validity of $_COOKIE['pma_collation_connection']
         */
        if ( ! empty( $_COOKIE['pma_collation_connection'] ) ) {
            $this->set( 'collation_connection',
                strip_tags( $_COOKIE['pma_collation_connection'] ) );
        } else {
            $this->set( 'collation_connection',
                $this->get( $_COOKIE['DefaultConnectionCollation'] ) );
        }

        $this->checkCollationConnection();

        // If zlib output compression is set in the php configuration file, no
        // output buffering should be run
        if ( @ini_get('zlib.output_compression') ) {
            $this->set( 'OBGzip', false );
        }

        // disable output-buffering (if set to 'auto') for IE6, else enable it.
        if ( strtolower( $cfg['OBGzip'] ) == 'auto' ) {
            if ( PMA_USR_BROWSER_AGENT == 'IE' && PMA_USR_BROWSER_VER >= 6
              && PMA_USR_BROWSER_VER < 7 ) {
                $this->set( 'OBGzip', false );
            } else {
                $this->set( 'OBGzip', true );
            }
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

    function set( $setting, $value ) {
        $this->cfg[$setting] = $value;
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
     * $cfg['PmaAbsoluteUri'] is a required directive else cookies won't be
     * set properly and, depending on browsers, inserting or updating a
     * record might fail
     */
    function checkPmaAbsoluteUri() {

        // Setup a default value to let the people and lazy syadmins work anyway,
        // they'll get an error if the autodetect code doesn't work
        $pma_absolute_uri = $this->get('PmaAbsoluteUri');
        if ( strlen( $pma_absolute_uri ) < 0 ) {

            $url = array();

            // At first we try to parse REQUEST_URI, it might contain full URI
            if ( ! empty($_SERVER['REQUEST_URI'] ) ) {
                $url = parse_url( $_SERVER['REQUEST_URI'] );
            }

            // If we don't have scheme, we didn't have full URL so we need to
            // dig deeper
            if ( empty( $url['scheme'] ) ) {
                // Scheme
                if ( ! empty( $_SERVER['HTTP_SCHEME'] ) ) {
                    $url['scheme'] = $_SERVER['HTTP_SCHEME'];
                } else {
                    $url['scheme'] =
                        !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off'
                            ? 'https'
                            : 'http';
                }

                // Host and port
                if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
                    if ( strpos( $_SERVER['HTTP_HOST'], ':' ) !== false ) {
                        list( $url['host'], $url['port'] ) =
                            explode( ':', $_SERVER['HTTP_HOST'] );
                    } else {
                        $url['host'] = $_SERVER['HTTP_HOST'];
                    }
                } elseif ( ! empty( $_SERVER['SERVER_NAME'] ) ) {
                    $url['host'] = $_SERVER['SERVER_NAME'];
                } else {
                    $this->error_pma_uri = true;
                    return false;
                }

                // If we didn't set port yet...
                if ( empty( $url['port'] ) && ! empty( $_SERVER['SERVER_PORT'] ) ) {
                    $url['port'] = $_SERVER['SERVER_PORT'];
                }

                // And finally the path could be already set from REQUEST_URI
                if ( empty( $url['path'] ) ) {
                    if (!empty($_SERVER['PATH_INFO'])) {
                        $path = parse_url($_SERVER['PATH_INFO']);
                    } else {
                        // PHP_SELF in CGI often points to cgi executable, so use it
                        // as last choice
                        $path = parse_url($_SERVER['PHP_SELF']);
                    }
                    $url['path'] = $path['path'];
                }
            }

            // Make url from parts we have
            $pma_absolute_uri = $url['scheme'] . '://';
            // Was there user information?
            if (!empty($url['user'])) {
                $pma_absolute_uri .= $url['user'];
                if (!empty($url['pass'])) {
                    $pma_absolute_uri .= ':' . $url['pass'];
                }
                $pma_absolute_uri .= '@';
            }
            // Add hostname
            $pma_absolute_uri .= $url['host'];
            // Add port, if it not the default one
            if ( ! empty( $url['port'] )
              && ( ( $url['scheme'] == 'http' && $url['port'] != 80 )
                || ( $url['scheme'] == 'https' && $url['port'] != 443 ) ) ) {
                $pma_absolute_uri .= ':' . $url['port'];
            }
            // And finally path, without script name, the 'a' is there not to
            // strip our directory, when path is only /pmadir/ without filename
            $path = dirname($url['path'] . 'a');
            // To work correctly within transformations overview:
            if (defined('PMA_PATH_TO_BASEDIR') && PMA_PATH_TO_BASEDIR == '../../') {
                $path = dirname(dirname($path));
            }
            $pma_absolute_uri .= $path . '/';

            // We used to display a warning if PmaAbsoluteUri wasn't set, but now
            // the autodetect code works well enough that we don't display the
            // warning at all. The user can still set PmaAbsoluteUri manually.
            // See
            // http://sf.net/tracker/?func=detail&aid=1257134&group_id=23067&atid=377411

        } else {
            // The URI is specified, however users do often specify this
            // wrongly, so we try to fix this.

            // Adds a trailing slash et the end of the phpMyAdmin uri if it
            // does not exist.
            if (substr($pma_absolute_uri, -1) != '/') {
                $pma_absolute_uri .= '/';
            }

            // If URI doesn't start with http:// or https://, we will add
            // this.
            if ( substr($pma_absolute_uri, 0, 7) != 'http://'
              && substr($pma_absolute_uri, 0, 8) != 'https://' ) {
                $pma_absolute_uri =
                    (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off'
                        ? 'https'
                        : 'http')
                    . ':' . (substr($pma_absolute_uri, 0, 2) == '//' ? '' : '//')
                    . $pma_absolute_uri;
            }
        }

        $this->set( 'PmaAbsoluteUri', $pma_absolute_uri );
    }

    /**
     * check selected collation_connection
     * @TODO check validity of $_REQUEST['collation_connection']
     */
    function checkCollationConnection() {
        // (could be improved by executing it after the MySQL connection only if
        //  PMA_MYSQL_INT_VERSION >= 40100 )
        if ( ! empty( $_REQUEST['collation_connection'] ) ) {
            $this->set( 'collation_connection',
                strip_tags( $_REQUEST['collation_connection'] ) );
        }
    }

    /**
     * @todo finish
     */
    function save() {}
}
?>