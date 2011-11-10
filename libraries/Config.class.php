<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * Load vendor configuration.
 */
require_once('./libraries/vendor_config.php');

/**
 * Configuration class
 *
 * @package phpMyAdmin
 */
class PMA_Config
{
    /**
     * @var string  default config source
     */
    var $default_source = './libraries/config.default.php';

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
    var $default_source_mtime = 0;
    var $set_mtime = 0;

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
     * @var boolean whether init is done or not
     * set this to false to force some initial checks
     * like checking for required functions
     */
    var $done = false;

    /**
     * constructor
     *
     * @param   string  source to read config from
     */
    function __construct($source = null)
    {
        $this->settings = array();

        // functions need to refresh in case of config file changed goes in
        // PMA_Config::load()
        $this->load($source);

        // other settings, independent from config file, comes in
        $this->checkSystem();

        $this->checkIsHttps();
    }

    /**
     * sets system and application settings
     */
    function checkSystem()
    {
        $this->set('PMA_VERSION', '3.3.10.5');
        /**
         * @deprecated
         */
        $this->set('PMA_THEME_VERSION', 2);
        /**
         * @deprecated
         */
        $this->set('PMA_THEME_GENERATION', 2);

        $this->checkPhpVersion();
        $this->checkWebServerOs();
        $this->checkWebServer();
        $this->checkGd2();
        $this->checkClient();
        $this->checkUpload();
        $this->checkUploadSize();
        $this->checkOutputCompression();
    }

    /**
     * whether to use gzip output compression or not
     */
    function checkOutputCompression()
    {
        // If zlib output compression is set in the php configuration file, no
        // output buffering should be run
        if (@ini_get('zlib.output_compression')) {
            $this->set('OBGzip', false);
        }

        // disable output-buffering (if set to 'auto') for IE6, else enable it.
        if (strtolower($this->get('OBGzip')) == 'auto') {
            if ($this->get('PMA_USR_BROWSER_AGENT') == 'IE'
              && $this->get('PMA_USR_BROWSER_VER') >= 6
              && $this->get('PMA_USR_BROWSER_VER') < 7) {
                $this->set('OBGzip', false);
            } else {
                $this->set('OBGzip', true);
            }
        }
    }

    /**
     * Determines platform (OS), browser and version of the user
     * Based on a phpBuilder article:
     * @see http://www.phpbuilder.net/columns/tim20000821.php
     */
    function checkClient()
    {
        if (PMA_getenv('HTTP_USER_AGENT')) {
            $HTTP_USER_AGENT = PMA_getenv('HTTP_USER_AGENT');
        } elseif (!isset($HTTP_USER_AGENT)) {
            $HTTP_USER_AGENT = '';
        }

        // 1. Platform
        if (strstr($HTTP_USER_AGENT, 'Win')) {
            $this->set('PMA_USR_OS', 'Win');
        } elseif (strstr($HTTP_USER_AGENT, 'Mac')) {
            $this->set('PMA_USR_OS', 'Mac');
        } elseif (strstr($HTTP_USER_AGENT, 'Linux')) {
            $this->set('PMA_USR_OS', 'Linux');
        } elseif (strstr($HTTP_USER_AGENT, 'Unix')) {
            $this->set('PMA_USR_OS', 'Unix');
        } elseif (strstr($HTTP_USER_AGENT, 'OS/2')) {
            $this->set('PMA_USR_OS', 'OS/2');
        } else {
            $this->set('PMA_USR_OS', 'Other');
        }

        // 2. browser and version
        // (must check everything else before Mozilla)

        if (preg_match('@Opera(/| )([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'OPERA');
        } elseif (preg_match('@MSIE ([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'IE');
        } elseif (preg_match('@OmniWeb/([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'OMNIWEB');
        // Konqueror 2.2.2 says Konqueror/2.2.2
        // Konqueror 3.0.3 says Konqueror/3
        } elseif (preg_match('@(Konqueror/)(.*)(;)@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[2]);
            $this->set('PMA_USR_BROWSER_AGENT', 'KONQUEROR');
        } elseif (preg_match('@Mozilla/([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)
                   && preg_match('@Safari/([0-9]*)@', $HTTP_USER_AGENT, $log_version2)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[1] . '.' . $log_version2[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'SAFARI');
        } elseif (preg_match('@rv:1.9(.*)Gecko@', $HTTP_USER_AGENT)) {
            $this->set('PMA_USR_BROWSER_VER', '1.9');
            $this->set('PMA_USR_BROWSER_AGENT', 'GECKO');
        } elseif (preg_match('@Mozilla/([0-9].[0-9]{1,2})@', $HTTP_USER_AGENT, $log_version)) {
            $this->set('PMA_USR_BROWSER_VER', $log_version[1]);
            $this->set('PMA_USR_BROWSER_AGENT', 'MOZILLA');
        } else {
            $this->set('PMA_USR_BROWSER_VER', 0);
            $this->set('PMA_USR_BROWSER_AGENT', 'OTHER');
        }
    }

    /**
     * Whether GD2 is present
     */
    function checkGd2()
    {
        if ($this->get('GD2Available') == 'yes') {
            $this->set('PMA_IS_GD2', 1);
        } elseif ($this->get('GD2Available') == 'no') {
            $this->set('PMA_IS_GD2', 0);
        } else {
            if (!@function_exists('imagecreatetruecolor')) {
                $this->set('PMA_IS_GD2', 0);
            } else {
                if (@function_exists('gd_info')) {
                    $gd_nfo = gd_info();
                    if (strstr($gd_nfo["GD Version"], '2.')) {
                        $this->set('PMA_IS_GD2', 1);
                    } else {
                        $this->set('PMA_IS_GD2', 0);
                    }
                } else {
                    /* We must do hard way... but almost no chance to execute this */
                    ob_start();
                    phpinfo(INFO_MODULES); /* Only modules */
                    $a = strip_tags(ob_get_contents());
                    ob_end_clean();
                    /* Get GD version string from phpinfo output */
                    if (preg_match('@GD Version[[:space:]]*\(.*\)@', $a, $v)) {
                        if (strstr($v, '2.')) {
                            $this->set('PMA_IS_GD2', 1);
                        } else {
                            $this->set('PMA_IS_GD2', 0);
                        }
                    } else {
                        $this->set('PMA_IS_GD2', 0);
                    }
                }
            }
        }
    }

    /**
     * Whether the Web server php is running on is IIS
     */
    function checkWebServer()
    {
        if (PMA_getenv('SERVER_SOFTWARE')
          // some versions return Microsoft-IIS, some Microsoft/IIS
          // we could use a preg_match() but it's slower
          && stristr(PMA_getenv('SERVER_SOFTWARE'), 'Microsoft')
          && stristr(PMA_getenv('SERVER_SOFTWARE'), 'IIS')) {
            $this->set('PMA_IS_IIS', 1);
        } else {
            $this->set('PMA_IS_IIS', 0);
        }
    }

    /**
     * Whether the os php is running on is windows or not
     */
    function checkWebServerOs()
    {
        // Default to Unix or Equiv
        $this->set('PMA_IS_WINDOWS', 0);
        // If PHP_OS is defined then continue
        if (defined('PHP_OS')) {
            if (stristr(PHP_OS, 'win')) {
                // Is it some version of Windows
                $this->set('PMA_IS_WINDOWS', 1);
            } elseif (stristr(PHP_OS, 'OS/2')) {
                // Is it OS/2 (No file permissions like Windows)
                $this->set('PMA_IS_WINDOWS', 1);
            }
        }
    }

    /**
     * detects PHP version
     */
    function checkPhpVersion()
    {
        $match = array();
        if (! preg_match('@([0-9]{1,2}).([0-9]{1,2}).([0-9]{1,2})@',
                phpversion(), $match)) {
            $result = preg_match('@([0-9]{1,2}).([0-9]{1,2})@',
                phpversion(), $match);
        }
        if (isset($match) && ! empty($match[1])) {
            if (! isset($match[2])) {
                $match[2] = 0;
            }
            if (! isset($match[3])) {
                $match[3] = 0;
            }
            $this->set('PMA_PHP_INT_VERSION',
                (int) sprintf('%d%02d%02d', $match[1], $match[2], $match[3]));
        } else {
            $this->set('PMA_PHP_INT_VERSION', 0);
        }
        $this->set('PMA_PHP_STR_VERSION', phpversion());
    }

    /**
     * re-init object after loading from session file
     * checks config file for changes and relaods if neccessary
     */
    function __wakeup()
    {
        if (SKIP_MTIME_CONFIG_CHECK
          || ! $this->checkConfigSource()
          || $this->source_mtime !== filemtime($this->getSource())
          || $this->default_source_mtime !== filemtime($this->default_source)
          || $this->error_config_file
          || $this->error_config_default_file) {
            $this->settings = array();
            $this->load();
            $this->checkSystem();
        }

        // check for https needs to be done everytime,
        // as https and http uses same session so this info can not be stored
        // in session
        $this->checkIsHttps();

        $this->checkCollationConnection();
        $this->checkFontsize();
    }

    /**
     * loads default values from default source
     *
     * @uses    file_exists()
     * @uses    $this->default_source
     * @uses    $this->error_config_default_file
     * @uses    $this->settings
     * @return  boolean     success
     */
    function loadDefaults()
    {
        $cfg = array();
        if (! file_exists($this->default_source)) {
            $this->error_config_default_file = true;
            return false;
        }
        include $this->default_source;

        $this->default_source_mtime = filemtime($this->default_source);

        $this->default_server = $cfg['Servers'][1];
        unset($cfg['Servers']);

        $this->settings = PMA_array_merge_recursive($this->settings, $cfg);

        $this->error_config_default_file = false;

        return true;
    }

    /**
     * loads configuration from $source, usally the config file
     * should be called on object creation and from __wakeup if config file
     * has changed
     *
     * @param   string $source  config file
     */
    function load($source = null)
    {
        $this->loadDefaults();

        if (null !== $source) {
            $this->setSource($source);
        }

        if (! $this->checkConfigSource()) {
            return false;
        }

        $cfg = array();

        /**
         * Parses the configuration file
         */
        $old_error_reporting = error_reporting(0);
        if (function_exists('file_get_contents')) {
            $eval_result =
                eval('?>' . trim(file_get_contents($this->getSource())));
        } else {
            $eval_result =
                eval('?>' . trim(implode("\n", file($this->getSource()))));
        }
        error_reporting($old_error_reporting);

        if ($eval_result === false) {
            $this->error_config_file = true;
        } else  {
            $this->error_config_file = false;
            $this->source_mtime = filemtime($this->getSource());
        }

        /**
         * Backward compatibility code
         */
        if (!empty($cfg['DefaultTabTable'])) {
            $cfg['DefaultTabTable'] = str_replace('_properties', '', str_replace('tbl_properties.php', 'tbl_sql.php', $cfg['DefaultTabTable']));
        }
        if (!empty($cfg['DefaultTabDatabase'])) {
            $cfg['DefaultTabDatabase'] = str_replace('_details', '', str_replace('db_details.php', 'db_sql.php', $cfg['DefaultTabDatabase']));
        }

        $this->checkFontsize();
        //$this->checkPmaAbsoluteUri();
        $this->settings = PMA_array_merge_recursive($this->settings, $cfg);

        $this->checkPermissions();

        // Handling of the collation must be done after merging of $cfg
        // (from config.inc.php) so that $cfg['DefaultConnectionCollation']
        // can have an effect. Note that the presence of collation
        // information in a cookie has priority over what is defined
        // in the default or user's config files.
        /**
         * @todo check validity of $_COOKIE['pma_collation_connection']
         */
        if (! empty($_COOKIE['pma_collation_connection'])) {
            $this->set('collation_connection',
                strip_tags($_COOKIE['pma_collation_connection']));
        } else {
            $this->set('collation_connection',
                $this->get('DefaultConnectionCollation'));
        }
        // Now, a collation information could come from REQUEST
        // (an example of this: the collation selector in main.php)
        // so the following handles the setting of collation_connection
        // and later, in common.inc.php, the cookie will be set
        // according to this.
        $this->checkCollationConnection();

        return true;
    }

    /**
     * set source
     * @param   string  $source
     */
    function setSource($source)
    {
        $this->source = trim($source);
    }

    /**
     * checks if the config folder still exists and terminates app if true
     */
    function checkConfigFolder()
    {
        // Refuse to work while there still might be some world writable dir:
        if (is_dir('./config')) {
            die('Remove "./config" directory before using phpMyAdmin!');
        }
    }

    /**
     * check config source
     *
     * @return  boolean whether source is valid or not
     */
    function checkConfigSource()
    {
        if (! $this->getSource()) {
            // no configuration file set at all
            return false;
        }

        if (! file_exists($this->getSource())) {
            // do not trigger error here
            // https://sf.net/tracker/?func=detail&aid=1370269&group_id=23067&atid=377408
            /*
            trigger_error(
                'phpMyAdmin-ERROR: unkown configuration source: ' . $source,
                E_USER_WARNING);
            */
            $this->source_mtime = 0;
            return false;
        }

        if (! is_readable($this->getSource())) {
            $this->source_mtime = 0;
            die('Existing configuration file (' . $this->getSource() . ') is not readable.');
        }

        return true;
    }

    /**
     * verifies the permissions on config file (if asked by configuration)
     * (must be called after config.inc.php has been merged)
     */
    function checkPermissions()
    {
        // Check for permissions (on platforms that support it):
        if ($this->get('CheckConfigurationPermissions')) {
            $perms = @fileperms($this->getSource());
            if (!($perms === false) && ($perms & 2)) {
                // This check is normally done after loading configuration
                $this->checkWebServerOs();
                if ($this->get('PMA_IS_WINDOWS') == 0) {
                    $this->source_mtime = 0;
                    die('Wrong permissions on configuration file, should not be world writable!');
                }
            }
        }
    }

    /**
     * returns specific config setting
     * @param   string  $setting
     * @return  mixed   value
     */
    function get($setting)
    {
        if (isset($this->settings[$setting])) {
            return $this->settings[$setting];
        }
        return null;
    }

    /**
     * sets configuration variable
     *
     * @uses    $this->settings
     * @param   string  $setting    configuration option
     * @param   string  $value      new value for configuration option
     */
    function set($setting, $value)
    {
        if (!isset($this->settings[$setting]) || $this->settings[$setting] != $value) {
            $this->settings[$setting] = $value;
            $this->set_mtime = time();
        }
    }

    /**
     * returns source for current config
     * @return  string  config source
     */
    function getSource()
    {
        return $this->source;
    }

    /**
     * returns a unique value to force a CSS reload if either the config
     * or the theme changes
     * must also check the pma_fontsize cookie in case there is no
     * config file
     * @return  int  Unix timestamp
     */
    function getThemeUniqueValue()
    {
        return intval((null !== $_SESSION['PMA_Config']->get('fontsize') ? $_SESSION['PMA_Config']->get('fontsize') : (isset($_COOKIE['pma_fontsize']) ? $_COOKIE['pma_fontsize'] : 0))) + ($this->source_mtime + $this->default_source_mtime + $_SESSION['PMA_Theme']->mtime_info + $_SESSION['PMA_Theme']->filesize_info) . (isset($_SESSION['tmp_user_values']['custom_color']) ? substr($_SESSION['tmp_user_values']['custom_color'],1,6) : '');
    }

    /**
     * $cfg['PmaAbsoluteUri'] is a required directive else cookies won't be
     * set properly and, depending on browsers, inserting or updating a
     * record might fail
     */
    function checkPmaAbsoluteUri()
    {
        // Setup a default value to let the people and lazy sysadmins work anyway,
        // they'll get an error if the autodetect code doesn't work
        $pma_absolute_uri = $this->get('PmaAbsoluteUri');
        $is_https = $this->get('is_https');

        if (strlen($pma_absolute_uri) < 5
            // needed to catch http/https switch
            || ($is_https && substr($pma_absolute_uri, 0, 6) != 'https:')
            || (!$is_https && substr($pma_absolute_uri, 0, 5) != 'http:')
        ) {
            $url = array();

            // At first we try to parse REQUEST_URI, it might contain full URL
            /**
             * REQUEST_URI contains PATH_INFO too, this is not what we want
             * script-php/pathinfo/
            if (PMA_getenv('REQUEST_URI')) {
                $url = @parse_url(PMA_getenv('REQUEST_URI')); // produces E_WARNING if it cannot get parsed, e.g. '/foobar:/'
                if ($url === false) {
                    $url = array('path' => $_SERVER['REQUEST_URI']);
                }
            }
             */

            // If we don't have scheme, we didn't have full URL so we need to
            // dig deeper
            if (empty($url['scheme'])) {
                // Scheme
                if (PMA_getenv('HTTP_SCHEME')) {
                    $url['scheme'] = PMA_getenv('HTTP_SCHEME');
                } else {
                    $url['scheme'] =
                        PMA_getenv('HTTPS') && strtolower(PMA_getenv('HTTPS')) != 'off'
                            ? 'https'
                            : 'http';
                }

                // Host and port
                if (PMA_getenv('HTTP_HOST')) {
                    // Prepend the scheme before using parse_url() since this is not part of the RFC2616 Host request-header
                    $parsed_url = parse_url($url['scheme'] . '://' . PMA_getenv('HTTP_HOST'));
                    if (!empty($parsed_url['host'])) {
                        $url = $parsed_url;
                    } else {
                        $url['host'] = PMA_getenv('HTTP_HOST');
                    }
                } elseif (PMA_getenv('SERVER_NAME')) {
                    $url['host'] = PMA_getenv('SERVER_NAME');
                } else {
                    $this->error_pma_uri = true;
                    return false;
                }

                // If we didn't set port yet...
                if (empty($url['port']) && PMA_getenv('SERVER_PORT')) {
                    $url['port'] = PMA_getenv('SERVER_PORT');
                }

                // And finally the path could be already set from REQUEST_URI
                if (empty($url['path'])) {
                    /**
                     * REQUEST_URI contains PATH_INFO too, this is not what we want
                     * script-php/pathinfo/
                    if (PMA_getenv('PATH_INFO')) {
                        $path = parse_url(PMA_getenv('PATH_INFO'));
                    } else {
                        // PHP_SELF in CGI often points to cgi executable, so use it
                        // as last choice
                     */
                        $path = parse_url($GLOBALS['PMA_PHP_SELF']);
                    //}
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
            if (! empty($url['port'])
              && (($url['scheme'] == 'http' && $url['port'] != 80)
                || ($url['scheme'] == 'https' && $url['port'] != 443))) {
                $pma_absolute_uri .= ':' . $url['port'];
            }
            // And finally path, without script name, the 'a' is there not to
            // strip our directory, when path is only /pmadir/ without filename.
            // Backslashes returned by Windows have to be changed.
            // Only replace backslashes by forward slashes if on Windows,
            // as the backslash could be valid on a non-Windows system.
            $this->checkWebServerOs();
            if ($this->get('PMA_IS_WINDOWS') == 1) {
                $path = str_replace("\\", "/", dirname($url['path'] . 'a'));
            } else {
                $path = dirname($url['path'] . 'a');
            }

            // To work correctly within transformations overview:
            if (defined('PMA_PATH_TO_BASEDIR') && PMA_PATH_TO_BASEDIR == '../../') {
                if ($this->get('PMA_IS_WINDOWS') == 1) {
                    $path = str_replace("\\", "/", dirname(dirname($path)));
                } else {
                    $path = dirname(dirname($path));
                }
            }

            // PHP's dirname function would have returned a dot when $path contains no slash
            if ($path == '.') {
                $path = '';
            }
            // in vhost situations, there could be already an ending slash
            if (substr($path, -1) != '/') {
                $path .= '/';
            }
            $pma_absolute_uri .= $path;

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
            if (substr($pma_absolute_uri, 0, 7) != 'http://'
              && substr($pma_absolute_uri, 0, 8) != 'https://') {
                $pma_absolute_uri =
                    (PMA_getenv('HTTPS') && strtolower(PMA_getenv('HTTPS')) != 'off'
                        ? 'https'
                        : 'http')
                    . ':' . (substr($pma_absolute_uri, 0, 2) == '//' ? '' : '//')
                    . $pma_absolute_uri;
            }
        }
        $this->set('PmaAbsoluteUri', $pma_absolute_uri);
    }

    /**
     * check selected collation_connection
     * @todo check validity of $_REQUEST['collation_connection']
     */
    function checkCollationConnection()
    {
        if (! empty($_REQUEST['collation_connection'])) {
            $this->set('collation_connection',
                strip_tags($_REQUEST['collation_connection']));
        }
    }

    /**
     * checks for font size configuration, and sets font size as requested by user
     *
     * @uses    $_GET
     * @uses    $_POST
     * @uses    $_COOKIE
     * @uses    preg_match()
     * @uses    function_exists()
     * @uses    PMA_Config::set()
     * @uses    PMA_Config::get()
     * @uses    PMA_setCookie()
     */
    function checkFontsize()
    {
        $new_fontsize = '';

        if (isset($_GET['fontsize'])) {
            $new_fontsize = $_GET['fontsize'];
        } elseif (isset($_POST['fontsize'])) {
            $new_fontsize = $_POST['fontsize'];
        } elseif (isset($_COOKIE['pma_fontsize'])) {
            $new_fontsize = $_COOKIE['pma_fontsize'];
        }

        if (preg_match('/^[0-9.]+(px|em|pt|\%)$/', $new_fontsize)) {
            $this->set('fontsize', $new_fontsize);
        } elseif (! $this->get('fontsize')) {
            // 80% would correspond to the default browser font size
            // of 16, but use 82% to help read the monoface font
            $this->set('fontsize', '82%');
        }

        if (function_exists('PMA_setCookie')) {
            PMA_setCookie('pma_fontsize', $this->get('fontsize'), '82%');
        }
    }

    /**
     * checks if upload is enabled
     *
     */

    function checkUpload()
    {
        if (ini_get('file_uploads')) {
            $this->set('enable_upload', true);
            // if set "php_admin_value file_uploads Off" in httpd.conf
            // ini_get() also returns the string "Off" in this case:
            if ('off' == strtolower(ini_get('file_uploads'))) {
                $this->set('enable_upload', false);
            }
         } else {
            $this->set('enable_upload', false);
         }
    }

    /**
     * Maximum upload size as limited by PHP
     * Used with permission from Moodle (http://moodle.org) by Martin Dougiamas
     *
     * this section generates $max_upload_size in bytes
     */
    function checkUploadSize()
    {
        if (! $filesize = ini_get('upload_max_filesize')) {
            $filesize = "5M";
        }

        if ($postsize = ini_get('post_max_size')) {
            $this->set('max_upload_size',
                min(PMA_get_real_size($filesize), PMA_get_real_size($postsize)));
        } else {
            $this->set('max_upload_size', PMA_get_real_size($filesize));
        }
    }

    /**
     * check for https
     */
    function checkIsHttps()
    {
        $this->set('is_https', PMA_Config::isHttps());
    }

    /**
     * @static
     */
    static public function isHttps()
    {
        $is_https = false;

        $url = array();

        // At first we try to parse REQUEST_URI, it might contain full URL,
        if (PMA_getenv('REQUEST_URI')) {
            $url = @parse_url(PMA_getenv('REQUEST_URI')); // produces E_WARNING if it cannot get parsed, e.g. '/foobar:/'
            if($url === false) {
                $url = array();
            }
        }

        // If we don't have scheme, we didn't have full URL so we need to
        // dig deeper
        if (empty($url['scheme'])) {
            // Scheme
            if (PMA_getenv('HTTP_SCHEME')) {
                $url['scheme'] = PMA_getenv('HTTP_SCHEME');
            } else {
                $url['scheme'] =
                    PMA_getenv('HTTPS') && strtolower(PMA_getenv('HTTPS')) != 'off'
                        ? 'https'
                        : 'http';
            }
        }

        if (isset($url['scheme'])
          && $url['scheme'] == 'https') {
            $is_https = true;
        } else {
            $is_https = false;
        }

        return $is_https;
    }

    /**
     * detect correct cookie path
     */
    function checkCookiePath()
    {
        $this->set('cookie_path', PMA_Config::getCookiePath());
    }

    /**
     * @static
     */
    static public function getCookiePath()
    {
        static $cookie_path = null;

        if (null !== $cookie_path) {
            return $cookie_path;
        }

        $url = '';

        /**
         * REQUEST_URI contains PATH_INFO too, this is not what we want
         * script-php/pathinfo/
        if (PMA_getenv('REQUEST_URI')) {
            $url = PMA_getenv('REQUEST_URI');
        }
         */

        // If we don't have path
        if (empty($url)) {
            if ($GLOBALS['PMA_PHP_SELF']) {
                // PHP_SELF in CGI often points to cgi executable, so use it
                // as last choice
                $url = $GLOBALS['PMA_PHP_SELF'];
            // on IIS with PHP-CGI:
            } elseif (PMA_getenv('SCRIPT_NAME')) {
                $url = PMA_getenv('SCRIPT_NAME');
            }
        }

        /**
         * REQUEST_URI contains PATH_INFO too, this is not what we want
         * script-php/pathinfo/
        $parsed_url = @parse_url($_SERVER['REQUEST_URI']); // produces E_WARNING if it cannot get parsed, e.g. '/foobar:/'
        if ($parsed_url === false) {
         */
            $parsed_url = array('path' => $url);
        //}

        $cookie_path   = substr($parsed_url['path'], 0, strrpos($parsed_url['path'], '/'))  . '/';

        return $cookie_path;
    }

    /**
     * enables backward compatibility
     */
    function enableBc()
    {
        $GLOBALS['cfg']             = $this->settings;
        $GLOBALS['default_server']  = $this->default_server;
        unset($this->default_server);
        $GLOBALS['collation_connection'] = $this->get('collation_connection');
        $GLOBALS['is_upload']       = $this->get('enable_upload');
        $GLOBALS['max_upload_size'] = $this->get('max_upload_size');
        $GLOBALS['cookie_path']     = $this->get('cookie_path');
        $GLOBALS['is_https']        = $this->get('is_https');

        $defines = array(
            'PMA_VERSION',
            'PMA_THEME_VERSION',
            'PMA_THEME_GENERATION',
            'PMA_PHP_STR_VERSION',
            'PMA_PHP_INT_VERSION',
            'PMA_IS_WINDOWS',
            'PMA_IS_IIS',
            'PMA_IS_GD2',
            'PMA_USR_OS',
            'PMA_USR_BROWSER_VER',
            'PMA_USR_BROWSER_AGENT'
            );

        foreach ($defines as $define) {
            if (! defined($define)) {
                define($define, $this->get($define));
            }
        }
    }

    /**
     * @todo finish
     */
    function save() {}

    /**
     * returns options for font size selection
     *
     * @uses    preg_replace()
     * @uses    ksort()
     * @static
     * @param   string  $current_size   current selected font size with unit
     * @return  array   selectable font sizes
     */
    static protected function _getFontsizeOptions($current_size = '82%')
    {
        $unit = preg_replace('/[0-9.]*/', '', $current_size);
        $value = preg_replace('/[^0-9.]*/', '', $current_size);

        $factors = array();
        $options = array();
        $options["$value"] = $value . $unit;

        if ($unit === '%') {
            $factors[] = 1;
            $factors[] = 5;
            $factors[] = 10;
        } elseif ($unit === 'em') {
            $factors[] = 0.05;
            $factors[] = 0.2;
            $factors[] = 1;
        } elseif ($unit === 'pt') {
            $factors[] = 0.5;
            $factors[] = 2;
        } elseif ($unit === 'px') {
            $factors[] = 1;
            $factors[] = 5;
            $factors[] = 10;
        } else {
            //unknown font size unit
            $factors[] = 0.05;
            $factors[] = 0.2;
            $factors[] = 1;
            $factors[] = 5;
            $factors[] = 10;
        }

        foreach ($factors as $key => $factor) {
            $option_inc = $value + $factor;
            $option_dec = $value - $factor;
            while (count($options) < 21) {
                $options["$option_inc"] = $option_inc . $unit;
                if ($option_dec > $factors[0]) {
                    $options["$option_dec"] = $option_dec . $unit;
                }
                $option_inc += $factor;
                $option_dec -= $factor;
                if (isset($factors[$key + 1])
                 && $option_inc >= $value + $factors[$key + 1]) {
                    break;
                }
            }
        }
        ksort($options);
        return $options;
    }

    /**
     * returns html selectbox for font sizes
     *
     * @uses    $_SESSION['PMA_Config']
     * @uses    PMA_Config::get()
     * @uses    PMA_Config::_getFontsizeOptions()
     * @uses    $GLOBALS['strFontSize']
     * @static
     * @param   string  $current_size   currently slected font size with unit
     * @return  string  html selectbox
     */
    static protected function _getFontsizeSelection()
    {
        $current_size = $_SESSION['PMA_Config']->get('fontsize');
        // for the case when there is no config file (this is supported)
        if (empty($current_size)) {
            if (isset($_COOKIE['pma_fontsize'])) {
                $current_size = $_COOKIE['pma_fontsize'];
            } else {
                $current_size = '82%';
            }
        }
        $options = PMA_Config::_getFontsizeOptions($current_size);

        $return = '<label for="select_fontsize">' . $GLOBALS['strFontSize'] . ':</label>' . "\n";
        $return .= '<select name="fontsize" id="select_fontsize" onchange="this.form.submit();">' . "\n";
        foreach ($options as $option) {
            $return .= '<option value="' . $option . '"';
            if ($option == $current_size) {
                $return .= ' selected="selected"';
            }
            $return .= '>' . $option . '</option>' . "\n";
        }
        $return .= '</select>';

        return $return;
    }

    /**
     * return complete font size selection form
     *
     * @uses    PMA_generate_common_hidden_inputs()
     * @uses    PMA_Config::_getFontsizeSelection()
     * @uses    $GLOBALS['strGo']
     * @static
     * @param   string  $current_size   currently slected font size with unit
     * @return  string  html selectbox
     */
    static public function getFontsizeForm()
    {
        return '<form name="form_fontsize_selection" id="form_fontsize_selection"'
            . ' method="post" action="index.php" target="_parent">' . "\n"
            . PMA_generate_common_hidden_inputs() . "\n"
            . PMA_Config::_getFontsizeSelection() . "\n"
            . '<noscript>' . "\n"
            . '<input type="submit" value="' . $GLOBALS['strGo'] . '" />' . "\n"
            . '</noscript>' . "\n"
            . '</form>';
    }
}
?>
