<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_Config class
 *
 * @package PhpMyAdmin-test
 * @group current
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/vendor_config.php';
require_once 'libraries/url_generating.lib.php';
require_once 'libraries/php-gettext/gettext.inc';

/**
 * Tests behaviour of PMA_Config class
 *
 * @package PhpMyAdmin-test
 */
class PMA_ConfigTest extends PHPUnit_Framework_TestCase
{
    /**
     * Turn off backup globals
     */
    protected $backupGlobals = false;

    /**
     * @var PMA_Config
     */
    protected $object;

    /**
     * @var object to test file permission
     */
    protected $permTestObj;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->object = new PMA_Config;
        $GLOBALS['server'] = 0;
        $_SESSION['is_git_revision'] = true;
        $GLOBALS['PMA_Config'] = new PMA_Config(CONFIG_FILE);

        //for testing file permissions
        $this->permTestObj = new PMA_Config("./config.sample.inc.php");
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
        unset($this->permTestObj);
    }

    /**
     * Test for CheckSystem
     *
     * @return void
     * @group medium
     */
    public function testCheckSystem()
    {
        $this->object->checkSystem();

        $this->assertNotNull($this->object->get('PMA_VERSION'));
        $this->assertNotEmpty($this->object->get('PMA_THEME_VERSION'));
        $this->assertNotEmpty($this->object->get('PMA_THEME_GENERATION'));
    }

    /**
     * Test for GetFontsizeForm
     *
     * @return void
     */
    public function testGetFontsizeForm()
    {
        $this->assertContains(
            '<form name="form_fontsize_selection" id="form_fontsize_selection"',
            PMA_Config::getFontsizeForm()
        );

        $this->assertContains(
            '<label for="select_fontsize">',
            PMA_Config::getFontsizeForm()
        );

        //test getFontsizeOptions for "em" unit
        $fontsize = $GLOBALS['PMA_Config']->get('fontsize');
        $GLOBALS['PMA_Config']->set('fontsize', '');
        $_COOKIE['pma_fontsize'] = "10em";
        $this->assertContains(
            '<option value="7em"',
            PMA_Config::getFontsizeForm()
        );
        $this->assertContains(
            '<option value="8em"',
            PMA_Config::getFontsizeForm()
        );

        //test getFontsizeOptions for "pt" unit
        $_COOKIE['pma_fontsize'] = "10pt";
        $this->assertContains(
            '<option value="2pt"',
            PMA_Config::getFontsizeForm()
        );
        $this->assertContains(
            '<option value="4pt"',
            PMA_Config::getFontsizeForm()
        );

        //test getFontsizeOptions for "px" unit
        $_COOKIE['pma_fontsize'] = "10px";
        $this->assertContains(
            '<option value="5px"',
            PMA_Config::getFontsizeForm()
        );
        $this->assertContains(
            '<option value="6px"',
            PMA_Config::getFontsizeForm()
        );

        //test getFontsizeOptions for unknown unit
        $_COOKIE['pma_fontsize'] = "10abc";
        $this->assertContains(
            '<option value="7abc"',
            PMA_Config::getFontsizeForm()
        );
        $this->assertContains(
            '<option value="8abc"',
            PMA_Config::getFontsizeForm()
        );
        unset($_COOKIE['pma_fontsize']);
        //rollback the fontsize setting
        $GLOBALS['PMA_Config']->set('fontsize', $fontsize);
    }

    /**
     * Test for checkOutputCompression
     *
     * @return void
     */
    public function testCheckOutputCompression()
    {

        $this->object->set('OBGzip', 'auto');

        $this->object->set('PMA_USR_BROWSER_AGENT', 'IE');
        $this->object->set('PMA_USR_BROWSER_VER', 6);
        $this->object->checkOutputCompression();
        $this->assertFalse($this->object->get("OBGzip"));

        $this->object->set('OBGzip', 'auto');
        $this->object->set('PMA_USR_BROWSER_AGENT', 'MOZILLA');
        $this->object->set('PMA_USR_BROWSER_VER', 5);
        $this->object->checkOutputCompression();
        $this->assertTrue($this->object->get("OBGzip"));
    }

    /**
     * Tests client parsing code.
     *
     * @param string $agent   User agent string
     * @param string $os      Expected parsed OS (or null if none)
     * @param string $browser Expected parsed browser (or null if none)
     * @param string $version Expected browser version (or null if none)
     *
     * @return void
     *
     * @dataProvider userAgentProvider
     */
    public function testCheckClient($agent, $os, $browser = null, $version = null)
    {
        $_SERVER['HTTP_USER_AGENT'] = $agent;
        $this->object->checkClient();
        $this->assertEquals($os, $this->object->get('PMA_USR_OS'));
        if ($os != null) {
            $this->assertEquals(
                $browser,
                $this->object->get('PMA_USR_BROWSER_AGENT')
            );
        }
        if ($version != null) {
            $this->assertEquals(
                $version,
                $this->object->get('PMA_USR_BROWSER_VER')
            );
        }
    }

    /**
     * user Agent Provider
     *
     * @return array
     */
    public function userAgentProvider()
    {
        return array(
            array(
                'Opera/9.80 (X11; Linux x86_64; U; pl) Presto/2.7.62 Version/11.00',
                'Linux',
                'OPERA',
                '9.80',
            ),
            array(
                'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US) AppleWebKit/'
                . '528.16 OmniWeb/622.8.0.112941',
                'Mac',
                'OMNIWEB',
                '622',
            ),
            array(
                'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1)',
                'Win',
                'IE',
                '8.0',
            ),
            array(
                'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)',
                'Win',
                'IE',
                '9.0',
            ),
            array(
                'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2; Win64; x64; '
                . 'Trident/6.0)',
                'Win',
                'IE',
                '10.0',
            ),
            array(
                'Mozilla/5.0 (IE 11.0; Windows NT 6.3; Trident/7.0; .NET4.0E; '
                . '.NET4.0C; rv:11.0) like Gecko',
                'Win',
                'IE',
                '11.0',
            ),
            array(
                'Mozilla/5.0 (Windows NT 6.3; WOW64; Trident/7.0; .NET4.0E; '
                . '.NET4.0C; .NET CLR 3.5.30729; .NET CLR 2.0.50727; '
                . '.NET CLR 3.0.30729; InfoPath.3; rv:11.0) like Gecko',
                'Win',
                'IE',
                '11.0',
            ),
            array(
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.22 (KHTML, '
                . 'like Gecko) Chrome/25.0.1364.172 Safari/537.22',
                'Win',
                'CHROME',
                '25.0.1364.172',
            ),
            array(
                'Mozilla/5.0 (Unknown; U; Unix BSD/SYSV system; C -) '
                . 'AppleWebKit/527+ (KHTML, like Gecko, Safari/419.3) Arora/0.10.2',
                'Unix',
                'SAFARI',
                '5.0.419',
            ),
            array(
                'Mozilla/5.0 (Windows; U; Win95; en-US; rv:1.9b) Gecko/20031208',
                'Win',
                'GECKO',
                '1.9',
            ),
            array(
                'Mozilla/5.0 (compatible; Konqueror/4.5; NetBSD 5.0.2; X11; '
                . 'amd64; en_US) KHTML/4.5.4 (like Gecko)',
                'Other',
                'KONQUEROR',
            ),
            array(
                'Mozilla/5.0 (X11; Linux x86_64; rv:5.0) Gecko/20100101 Firefox/5.0',
                'Linux',
                'FIREFOX',
                '5.0',
            ),
            array(
                'Mozilla/5.0 (X11; Linux x86_64; rv:12.0) Gecko/20100101 '
                . 'Firefox/12.0',
                'Linux',
                'FIREFOX',
                '12.0',
            ),
            /**
             * @todo Is this version really expected?
             */
            array(
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.4+ (KHTML, like G'
                . 'ecko) Version/5.0 Safari/535.4+ SUSE/12.1 (3.2.1) Epiphany/3.2.1',
                'Linux',
                'SAFARI',
                '5.0',
            ),
        );

    }


    /**
     * test for CheckGd2
     *
     * @return array
     */
    public function testCheckGd2()
    {
        $prevIsGb2Val = $this->object->get('PMA_IS_GD2');

        $this->object->set('GD2Available', 'yes');
        $this->object->checkGd2();
        $this->assertEquals(1, $this->object->get('PMA_IS_GD2'));

        $this->object->set('GD2Available', 'no');
        $this->object->checkGd2();
        $this->assertEquals(0, $this->object->get('PMA_IS_GD2'));

        $this->object->set('GD2Available', $prevIsGb2Val);

        if (!@function_exists('imagecreatetruecolor')) {
            $this->object->checkGd2();
            $this->assertEquals(
                0,
                $this->object->get('PMA_IS_GD2'),
                'imagecreatetruecolor does not exist, PMA_IS_GD2 should be 0'
            );
        }

        if (@function_exists('gd_info')) {
            $this->object->checkGd2();
            $gd_nfo = gd_info();
            if (/*overload*/mb_strstr($gd_nfo["GD Version"], '2.')) {
                $this->assertEquals(
                    1,
                    $this->object->get('PMA_IS_GD2'),
                    'GD Version >= 2, PMA_IS_GD2 should be 1'
                );
            } else {
                $this->assertEquals(
                    0,
                    $this->object->get('PMA_IS_GD2'),
                    'GD Version < 2, PMA_IS_GD2 should be 0'
                );
            }
        }

        /* Get GD version string from phpinfo output */
        ob_start();
        phpinfo(INFO_MODULES); /* Only modules */
        $a = strip_tags(ob_get_contents());
        ob_end_clean();

        if (preg_match('@GD Version[[:space:]]*\(.*\)@', $a, $v)) {
            if (/*overload*/mb_strstr($v, '2.')) {
                $this->assertEquals(
                    1,
                    $this->object->get('PMA_IS_GD2'),
                    'PMA_IS_GD2 should be 1'
                );
            } else {
                $this->assertEquals(
                    0,
                    $this->object->get('PMA_IS_GD2'),
                    'PMA_IS_GD2 should be 0'
                );
            }
        }
    }

    /**
     * Web server detection test
     *
     * @param string  $server Server identification
     * @param boolean $iis    Whether server should be detected as IIS
     *
     * @return void
     *
     * @dataProvider serverNames
     */
    public function testCheckWebServer($server, $iis)
    {
        $_SERVER['SERVER_SOFTWARE'] = $server;
        $this->object->checkWebServer();
        $this->assertEquals($iis, $this->object->get('PMA_IS_IIS'));
        unset($_SERVER['SERVER_SOFTWARE']);
    }


    /**
     * return server names
     *
     * @return array
     */
    public function serverNames()
    {
        return array(
            array(
                "Microsoft-IIS 7.0",
                1,
            ),
            array(
                "Apache/2.2.17",
                0,
            ),
        );
    }


    /**
     * test for CheckWebServerOs
     *
     * @return array
     */
    public function testCheckWebServerOs()
    {
        $this->object->checkWebServerOs();

        if (defined('PHP_OS')) {
            if (stristr(PHP_OS, 'darwin')) {
                $this->assertEquals(0, $this->object->get('PMA_IS_WINDOWS'));
            } elseif (stristr(PHP_OS, 'win')) {
                $this->assertEquals(1, $this->object->get('PMA_IS_WINDOWS'));
            } elseif (stristr(PHP_OS, 'OS/2')) {
                $this->assertEquals(1, $this->object->get('PMA_IS_WINDOWS'));
            } elseif (stristr(PHP_OS, 'Linux')) {
                $this->assertEquals(0, $this->object->get('PMA_IS_WINDOWS'));
            } else {
                $this->markTestIncomplete('Not known PHP_OS: ' . PHP_OS);
            }
        } else {
            $this->assertEquals(0, $this->object->get('PMA_IS_WINDOWS'));

            define('PHP_OS', 'Windows');
            $this->assertEquals(1, $this->object->get('PMA_IS_WINDOWS'));
        }
    }

    /**
     * test for CheckPhpVersion
     *
     * @return array
     */
    public function testCheckPhpVersion()
    {
        $this->object->checkPhpVersion();

        $php_int_ver = 0;
        $php_str_ver = phpversion();

        $match = array();
        preg_match(
            '@([0-9]{1,2}).([0-9]{1,2}).([0-9]{1,2})@',
            phpversion(),
            $match
        );
        if (isset($match) && ! empty($match[1])) {
            if (! isset($match[2])) {
                $match[2] = 0;
            }
            if (! isset($match[3])) {
                $match[3] = 0;
            }
            $php_int_ver = (int) sprintf(
                '%d%02d%02d',
                $match[1],
                $match[2],
                $match[3]
            );
        } else {
            $php_int_ver = 0;
        }

        $this->assertEquals(
            $php_str_ver,
            $this->object->get('PMA_PHP_STR_VERSION')
        );
        $this->assertEquals(
            $php_int_ver,
            $this->object->get('PMA_PHP_INT_VERSION')
        );
    }

    /**
     * Tests loading of default values
     *
     * @return void
     *
     * @group large
     */
    public function testLoadDefaults()
    {
        $prevDefaultSource = $this->object->default_source;

        $this->object->default_source = 'unexisted.file.php';
        $this->assertFalse($this->object->loadDefaults());

        $this->object->default_source = $prevDefaultSource;

        include $this->object->default_source;

        $loadedConf = $cfg;
        unset($cfg);

        $this->assertTrue($this->object->loadDefaults());

        $this->assertEquals(
            $this->object->default_source_mtime,
            filemtime($prevDefaultSource)
        );
        $this->assertEquals(
            $loadedConf['Servers'][1],
            $this->object->default_server
        );

        unset($loadedConf['Servers']);

        $this->assertEquals($loadedConf, $this->object->default);

        $expectedSettings = PMA_arrayMergeRecursive(
            $this->object->settings,
            $loadedConf
        );

        $this->assertEquals(
            $expectedSettings,
            $this->object->settings,
            'Settings loaded wrong'
        );

        $this->assertFalse($this->object->error_config_default_file);
    }

    /**
     * test for CheckConfigSource
     *
     * @return array
     */
    public function testCheckConfigSource()
    {
        $this->object->setSource('unexisted.config.php');
        $this->assertFalse($this->object->checkConfigSource());
        $this->assertEquals(0, $this->object->source_mtime);

        $this->object->setSource('libraries/config.default.php');

        $this->assertNotEmpty($this->object->getSource());
        $this->assertTrue($this->object->checkConfigSource());
    }

    /**
     * Test getting and setting config values
     *
     * @return void
     */
    public function testGetAndSet()
    {
        $this->assertNull($this->object->get("unresisting_setting"));

        $this->object->set('test_setting', 'test_value');

        $this->assertEquals('test_value', $this->object->get('test_setting'));
    }

    /**
     * Tests setting configuration source
     *
     * @return void
     */
    public function testGetSetSource()
    {
        echo $this->object->getSource();

        $this->assertEmpty($this->object->getSource(), "Source is null by default");

        $this->object->setSource("config.sample.inc.php");

        $this->assertEquals(
            "config.sample.inc.php",
            $this->object->getSource(),
            "Cant set new source"
        );
    }


    /**
     * test for CheckPmaAbsoluteUriEmpty
     *
     * @return array
     */
    public function testCheckPmaAbsoluteUriEmpty()
    {
        $this->object->set('PmaAbsoluteUri', '');
        $this->assertNull(
            $this->object->checkPmaAbsoluteUri(),
            'PmaAbsoluteUri is not set and should be error'
        );
        $this->assertTrue(
            $this->object->error_pma_uri,
            'PmaAbsoluteUri is not set and should be error'
        );
    }

    /**
     * Checks correcting of absolute URI
     *
     * @param string $real     Real URI received
     * @param string $expected Expected corrected URI
     *
     * @return void
     *
     * @depends testCheckPmaAbsoluteUriEmpty
     * @dataProvider absoluteUris
     */
    public function testCheckPmaAbsoluteUri($real, $expected)
    {
        $this->object->set('PmaAbsoluteUri', $real);
        $this->object->checkPmaAbsoluteUri();
        $this->assertEquals($expected, $this->object->get('PmaAbsoluteUri'));
    }

    /**
     * return absolute Uris
     *
     * @return array
     */
    public function absoluteUris()
    {
        return array(
            array(
                'http://localhost/phpmyadmin/',
                'http://localhost/phpmyadmin/',
            ),
            array(
                'http://localhost/phpmyadmin',
                'http://localhost/phpmyadmin/',
            ),
            array(
                'localhost/phpmyadmin/',
                'http://localhost/phpmyadmin/',
            ),
            array(
                'http://user:pwd@localhost/phpmyadmin/index.php',
                "http://user:pwd@localhost/phpmyadmin/index.php/",
            ),
            array(
                'https://user:pwd@localhost/phpmyadmin/index.php',
                "https://user:pwd@localhost/phpmyadmin/index.php/",
            ),
        );
    }

    /**
     * Test for absolute URI composition
     *
     * @return void
     *
     * @depends testCheckPmaAbsoluteUri
     */
    public function testCheckPmaAbsoluteUriScheme()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_SCHEME'] = 'http';
        $_SERVER['HTTPS'] = 'off';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $this->object->set('PmaAbsoluteUri', '');

        $this->object->checkPmaAbsoluteUri();
        $this->assertEquals(
            "http://localhost/",
            $this->object->get('PmaAbsoluteUri')
        );
    }

    /**
     * test for CheckCollationConnection
     *
     * @return array
     */
    public function testCheckCollationConnection()
    {
        $_REQUEST['collation_connection'] = 'utf-8';
        $this->object->checkCollationConnection();

        $this->assertEquals(
            $_REQUEST['collation_connection'],
            $this->object->get('collation_connection')
        );
    }

    /**
     * test for IsHttp
     *
     * @return array
     */
    public function testIsHttps()
    {
        $this->object->set('is_https', null);
        $this->object->set('PmaAbsoluteUri', 'http://some_host.com/phpMyAdmin');
        $this->assertFalse($this->object->isHttps());

        $this->object->set('is_https', null);
        $this->object->set('PmaAbsoluteUri', 'https://some_host.com/phpMyAdmin');
        $this->assertTrue($this->object->isHttps());
    }

    /**
     * test for DetectHttps
     *
     * @return array
     */
    public function testDetectHttps()
    {
        unset($_SERVER['REQUEST_URI']);
        unset($_SERVER['HTTP_SCHEME']);
        unset($_SERVER['HTTPS']);

        $this->assertFalse($this->object->detectHttps());

        $_SERVER['REQUEST_URI'] = '/url:\this_is_not_url';
        $this->assertFalse($this->object->detectHttps());

        $_SERVER['REQUEST_URI'] = 'file://localhost/phpmyadmin/index.php';
        $this->assertFalse($this->object->detectHttps());

        $_ENV['REQUEST_URI'] = 'http://localhost/phpmyadmin/index.php';
        $this->assertFalse($this->object->detectHttps());

        $_SERVER['REQUEST_URI'] = 'https://localhost/phpmyadmin/index.php';
        $this->assertTrue($this->object->detectHttps());

        $_SERVER['REQUEST_URI'] = 'localhost/phpmyadmin/index.php';
        $_SERVER['HTTP_SCHEME'] = 'https';
        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue($this->object->detectHttps());
    }

    /**
     * Test for checking cookie path
     *
     * @return void
     *
     * @depends testDetectHttps
     */
    public function testCheckCookiePath()
    {
        $this->object->checkCookiePath();
        echo $this->object->get('cookie_path');
        $this->assertEquals('', $this->object->get('cookie_path'));
    }

    /**
     * Test for backward compatibility globals
     *
     * @return void
     *
     * @depends testCheckSystem
     * @depends testCheckWebServer
     * @depends testLoadDefaults
     *
     * @group large
     */
    public function testEnableBc()
    {
        $this->object->enableBc();

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
            $this->assertTrue(defined($define));
            $this->assertEquals(constant($define), $this->object->get($define));
        }
    }

    /**
     * Test for getting cookie path
     *
     * @param string $absolute The absolute URL used for phpMyAdmin
     * @param string $expected Expected cookie path
     *
     * @return void
     *
     * @dataProvider cookieUris
     */
    public function testGetCookiePath($absolute, $expected)
    {
        $this->object->set('PmaAbsoluteUri', $absolute);
        $this->assertEquals($expected, $this->object->getCookiePath());
    }

    /**
     * Data provider for testGetCookiePath
     *
     * @return array data for testGetCookiePath
     */
    public function cookieUris()
    {
        return array(
            array(
                'http://example.net/phpmyadmin/',
                '/phpmyadmin/',
            ),
            array(
                'http://example.net/',
                '/',
            ),
        );
    }

    /**
     * Tests loading of config file
     *
     * @param string  $source File name of config to load
     * @param boolean $result Expected result of loading
     *
     * @return void
     *
     * @dataProvider configPaths
     */
    public function testLoad($source, $result)
    {
        if ($result) {
            $this->assertTrue($this->object->load($source));
        } else {
            $this->assertFalse($this->object->load($source));
        }
    }

    /**
     * return of config Paths
     *
     * @return array
     */
    public function configPaths()
    {
        return array(
            array(
                './test/test_data/config.inc.php',
                true,
            ),
            array(
                './test/test_data/config-nonexisting.inc.php',
                false,
            ),
            array(
                './libraries/config.default.php',
                true,
            ),
        );
    }

    /**
     * Test for loading user preferences
     *
     * @return void
     * @todo Test actually preferences loading
     */
    public function testLoadUserPreferences()
    {
        $this->assertNull($this->object->loadUserPreferences());
    }

    /**
     * Test for setting user config value
     *
     * @return void
     */
    public function testSetUserValue()
    {
        $this->object->setUserValue(null, 'lang', 'cs', 'en');
        $this->object->setUserValue("TEST_COOKIE_USER_VAL", '', 'cfg_val_1');
        $this->assertEquals(
            $this->object->getUserValue("TEST_COOKIE_USER_VAL", 'fail'),
            'cfg_val_1'
        );
    }

    /**
     * Test for getting user config value
     *
     * @return void
     */
    public function testGetUserValue()
    {
        $this->assertEquals($this->object->getUserValue('test_val', 'val'), 'val');
    }

    /**
     * Should test getting unique value for theme
     *
     * @return void
     */
    public function testGetThemeUniqueValue()
    {

        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');

        $partial_sum = (
            PHPUnit_Framework_Assert::readAttribute($this->object, 'source_mtime') +
            PHPUnit_Framework_Assert::readAttribute(
                $this->object,
                'default_source_mtime'
            ) +
            $this->object->get('user_preferences_mtime') +
            $_SESSION['PMA_Theme']->mtime_info +
            $_SESSION['PMA_Theme']->filesize_info
        );

        $this->object->set('fontsize', 10);
        $this->assertEquals(10 + $partial_sum, $this->object->getThemeUniqueValue());
        $this->object->set('fontsize', null);

        $_COOKIE['pma_fontsize'] = 20;
        $this->assertEquals(20 + $partial_sum, $this->object->getThemeUniqueValue());
        unset($_COOKIE['pma_fontsize']);

        $this->assertEquals($partial_sum, $this->object->getThemeUniqueValue());

    }

    /**
     * Should test checking of config permissions
     *
     * @return void
     */
    public function testCheckPermissions()
    {
        //load file permissions for the current permissions file
        $perms = @fileperms($this->object->getSource());
        //testing for permissions for no configuration file
        $this->assertFalse(!($perms === false) && ($perms & 2));

        //load file permissions for the current permissions file
        $perms = @fileperms($this->permTestObj->getSource());
        //testing for permissions
        $this->assertFalse(!($perms === false) && ($perms & 2));

        //if the above assertion is false then applying further assertions
        if (!($perms === false) && ($perms & 2)) {
            $this->assertFalse($this->permTestObj->get('PMA_IS_WINDOWS') == 0);
        }
    }


    /**
     * Test for setting cookies
     *
     * @return void
     */
    public function testSetCookie()
    {
        $this->assertFalse(
            $this->object->setCookie(
                'TEST_DEF_COOKIE',
                'test_def_123',
                'test_def_123'
            )
        );

        $this->assertTrue(
            $this->object->setCookie(
                'TEST_CONFIG_COOKIE',
                'test_val_123',
                null,
                3600
            )
        );

        $this->assertTrue(
            $this->object->setCookie(
                'TEST_CONFIG_COOKIE',
                '',
                'default_val'
            )
        );

        $_COOKIE['TEST_MANUAL_COOKIE'] = 'some_test_val';
        $this->assertTrue(
            $this->object->setCookie(
                'TEST_MANUAL_COOKIE',
                'other',
                'other'
            )
        );

    }

    /**
     * Test for isGitRevision
     *
     * @return void
     */
    public function testIsGitRevision()
    {
        $this->assertTrue(
            $this->object->isGitRevision()
        );
    }

    /**
     * Test for Check HTTP
     *
     * @group medium
     *
     * @return void
     */
    /*
    public function testCheckHTTP()
    {
        if (! function_exists('curl_init')) {
            $this->markTestSkipped('Missing curl extension!');
        }
        $this->assertTrue(
            $this->object->checkHTTP("https://www.phpmyadmin.net/test/data")
        );
        $this->assertContains(
            "TEST DATA",
            $this->object->checkHTTP("https://www.phpmyadmin.net/test/data", true)
        );
        $this->assertFalse(
            $this->object->checkHTTP("https://www.phpmyadmin.net/test/nothing")
        );
    }
    */
    /**
     * Tests for rewriting URL to SSL variant
     *
     * @param string $original Original URL
     * @param string $expected Expected URL rewritten to SSL
     *
     * @return void
     *
     * @dataProvider sslUris
     */
    public function testSSLUri($original, $expected)
    {
        $this->object->set('PmaAbsoluteUri', $original);
        $this->assertEquals($expected, $this->object->getSSLUri());
    }


    /**
     * return of ssl Uris
     *
     * @return array
     */
    public function sslUris()
    {
        return array(
            array(
                'http://server.foo/path/',
                'https://server.foo:443/path/'
            ),
            array(
                'http://server.foo:80/path/',
                'https://server.foo:443/path/'
            ),
            array(
                'http://server.foo.bar:123/path/',
                'https://server.foo.bar:443/path/'
            ),
            array(
                'http://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:80/',
                'https://[FEDC:BA98:7654:3210:FEDC:BA98:7654:3210]:443/'
            ),
            );
    }
}
?>
