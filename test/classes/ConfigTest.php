<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PhpMyAdmin\Config class
 *
 * @package PhpMyAdmin-test
 * @group current
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Config;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Theme;
use PHPUnit_Framework_Assert as Assert;

/**
 * Tests behaviour of PhpMyAdmin\Config class
 *
 * @package PhpMyAdmin-test
 */
class ConfigTest extends PmaTestCase
{
    /**
     * Turn off backup globals
     */
    protected $backupGlobals = false;

    /**
     * @var PhpMyAdmin\Config
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
        $this->object = new Config;
        $GLOBALS['server'] = 0;
        $_SESSION['git_location'] = '.git';
        $_SESSION['is_git_revision'] = true;
        $GLOBALS['PMA_Config'] = new Config(CONFIG_FILE);
        $GLOBALS['cfg']['ProxyUrl'] = '';

        //for testing file permissions
        $this->permTestObj = new Config("./config.sample.inc.php");
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    protected function tearDown()
    {
        parent::tearDown();
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

        $this->assertNotEmpty($this->object->get('PMA_VERSION'));
        $this->assertNotEmpty($this->object->get('PMA_MAJOR_VERSION'));
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
            Config::getFontsizeForm()
        );

        $this->assertContains(
            '<label for="select_fontsize">',
            Config::getFontsizeForm()
        );

        //test getFontsizeOptions for "em" unit
        $fontsize = $GLOBALS['PMA_Config']->get('FontSize');
        $GLOBALS['PMA_Config']->set('FontSize', '10em');
        $this->assertContains(
            '<option value="7em"',
            Config::getFontsizeForm()
        );
        $this->assertContains(
            '<option value="8em"',
            Config::getFontsizeForm()
        );

        //test getFontsizeOptions for "pt" unit
        $GLOBALS['PMA_Config']->set('FontSize', '10pt');
        $this->assertContains(
            '<option value="2pt"',
            Config::getFontsizeForm()
        );
        $this->assertContains(
            '<option value="4pt"',
            Config::getFontsizeForm()
        );

        //test getFontsizeOptions for "px" unit
        $GLOBALS['PMA_Config']->set('FontSize', '10px');
        $this->assertContains(
            '<option value="5px"',
            Config::getFontsizeForm()
        );
        $this->assertContains(
            '<option value="6px"',
            Config::getFontsizeForm()
        );

        //test getFontsizeOptions for unknown unit
        $GLOBALS['PMA_Config']->set('FontSize', '10abc');
        $this->assertContains(
            '<option value="7abc"',
            Config::getFontsizeForm()
        );
        $this->assertContains(
            '<option value="8abc"',
            Config::getFontsizeForm()
        );
        //rollback the fontsize setting
        $GLOBALS['PMA_Config']->set('FontSize', $fontsize);
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
        $this->assertTrue($this->object->get("OBGzip"));

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
     * @return void
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

        $this->object->set('GD2Available', 'auto');

        if (!function_exists('imagecreatetruecolor')) {
            $this->object->checkGd2();
            $this->assertEquals(
                0,
                $this->object->get('PMA_IS_GD2'),
                'imagecreatetruecolor does not exist, PMA_IS_GD2 should be 0'
            );
        }

        if (function_exists('gd_info')) {
            $this->object->checkGd2();
            $gd_nfo = gd_info();
            if (mb_strstr($gd_nfo["GD Version"], '2.')) {
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
            if (mb_strstr($v, '2.')) {
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
     * @return void
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

        $expectedSettings = array_replace_recursive(
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
     * @return void
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
     * test for IsHttp
     *
     * @return void
     *
     * @dataProvider httpsParams
     */
    public function testIsHttps($scheme, $https, $uri, $lb, $front, $proto, $port, $expected)
    {
        $_SERVER['HTTP_SCHEME'] = $scheme;
        $_SERVER['HTTPS'] = $https;
        $_SERVER['REQUEST_URI'] = $uri;
        $_SERVER['HTTP_HTTPS_FROM_LB'] = $lb;
        $_SERVER['HTTP_FRONT_END_HTTPS'] = $front;
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = $proto;
        $_SERVER['SERVER_PORT'] = $port;

        $this->object->set('is_https', null);
        $this->assertEquals($expected, $this->object->isHttps());
    }

    /**
     * Data provider for https detection
     *
     * @return array
     */
    public function httpsParams()
    {
        return array(
            array('http', '', '', '', '', 'http', 80, false),
            array('http', '', 'http://', '', '', 'http', 80, false),
            array('http', '', '', '', '', 'http', 443, true),
            array('http', '', '', '', '', 'https', 80, true),
            array('http', '', '', '', 'on', 'http', 80, true),
            array('http', '', '', 'on', '', 'http', 80, true),
            array('http', '', 'https://', '', '', 'http', 80, true),
            array('http', 'on', '', '', '', 'http', 80, true),
            array('https', '', '', '', '', 'http', 80, true),
        );
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
            'PMA_MAJOR_VERSION',
            'PMA_IS_WINDOWS',
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
     * Test for getting root path
     *
     * @param string $request  The request URL used for phpMyAdmin
     * @param string $absolute The absolute URL used for phpMyAdmin
     * @param string $expected Expected root path
     *
     * @return void
     *
     * @dataProvider rootUris
     */
    public function testGetRootPath($request, $absolute, $expected)
    {
        $GLOBALS['PMA_PHP_SELF'] = $request;
        $this->object->set('PmaAbsoluteUri', $absolute);
        $this->assertEquals($expected, $this->object->getRootPath());
    }

    /**
     * Data provider for testGetRootPath
     *
     * @return array data for testGetRootPath
     */
    public function rootUris()
    {
        return array(
            array(
                '',
                '',
                '/',
            ),
            array(
                '/',
                '',
                '/',
            ),
            array(
                '/index.php',
                '',
                '/',
            ),
            array(
                '\\index.php',
                '',
                '/',
            ),
            array(
                '\\',
                '',
                '/',
            ),
            array(
                '\\path\\to\\index.php',
                '',
                '/path/to/',
            ),
            array(
                '/foo/bar/phpmyadmin/index.php',
                '',
                '/foo/bar/phpmyadmin/',
            ),
            array(
                '/foo/bar/phpmyadmin/',
                '',
                '/foo/bar/phpmyadmin/',
            ),
            array(
                'https://example.net/baz/phpmyadmin/',
                '',
                '/baz/phpmyadmin/',
            ),
            array(
                'http://example.net/baz/phpmyadmin/',
                '',
                '/baz/phpmyadmin/',
            ),
            array(
                'http://example.net/phpmyadmin/',
                '',
                '/phpmyadmin/',
            ),
            array(
                'http://example.net/',
                '',
                '/',
            ),
            array(
                'http://example.net/',
                'http://example.net/phpmyadmin/',
                '/phpmyadmin/',
            ),
            array(
                'http://example.net/',
                'http://example.net/phpmyadmin',
                '/phpmyadmin/',
            ),
            array(
                'http://example.net/',
                '/phpmyadmin2',
                '/phpmyadmin2/',
            ),
            array(
                'http://example.net/',
                '/phpmyadmin3/',
                '/phpmyadmin3/',
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
        $partial_sum = (
            Assert::readAttribute($this->object, 'source_mtime') +
            Assert::readAttribute(
                $this->object,
                'default_source_mtime'
            ) +
            $this->object->get('user_preferences_mtime') +
            $GLOBALS['PMA_Theme']->mtime_info +
            $GLOBALS['PMA_Theme']->filesize_info
        );

        $this->object->set('FontSize', 10);
        $this->assertEquals(10 + $partial_sum, $this->object->getThemeUniqueValue());

        $this->object->set('FontSize', 20);
        $this->assertEquals(20 + $partial_sum, $this->object->getThemeUniqueValue());
        $this->object->set('FontSize', null);

        $this->assertEquals($partial_sum, $this->object->getThemeUniqueValue());
        $this->object->set('FontSize', '82%');

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
            $this->assertNotSame(0, $this->permTestObj->get('PMA_IS_WINDOWS'));
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
        $git_location = '';

        $this->assertTrue(
            $this->object->isGitRevision($git_location)
        );

        $this->assertEquals(
            null,
            $this->object->get('PMA_VERSION_GIT')
        );

        $this->assertEquals('.git', $git_location);
    }

    /**
     * Test for isGitRevision
     *
     * @return void
     */
    public function testIsGitRevisionSkipped()
    {
        $this->object->set('ShowGitRevision', false);
        $this->assertFalse(
            $this->object->isGitRevision($git_location)
        );
    }

    /**
     * Test for isGitRevision
     *
     * @return void
     */
    public function testIsGitRevisionLocalGitDir()
    {
        $cwd = getcwd();
        $test_dir = "gittestdir";

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir($test_dir);
        chdir($test_dir);

        $this->assertFalse(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->object->get('PMA_VERSION_GIT')
        );

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir('.git');

        $this->assertFalse(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->object->get('PMA_VERSION_GIT')
        );

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        file_put_contents('.git/config','');

        $this->assertTrue(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->object->get('PMA_VERSION_GIT')
        );

        unlink('.git/config');
        rmdir('.git');

        chdir($cwd);
        rmdir($test_dir);
    }

    /**
     * Test for isGitRevision
     *
     * @return void
     */
    public function testIsGitRevisionExternalGitDir()
    {
        $cwd = getcwd();
        $test_dir = "gittestdir";

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir($test_dir);
        chdir($test_dir);

        file_put_contents('.git','gitdir: ./.customgitdir');
        $this->assertFalse(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->object->get('PMA_VERSION_GIT')
        );

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir('.customgitdir');

        $this->assertTrue(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->object->get('PMA_VERSION_GIT')
        );

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        file_put_contents('.git','random data here');

        $this->assertFalse(
            $this->object->isGitRevision()
        );

        $this->assertEquals(
            null,
            $this->object->get('PMA_VERSION_GIT')
        );

        unlink('.git');
        rmdir('.customgitdir');

        chdir($cwd);
        rmdir($test_dir);
    }

    /**
     * Test for checkGitRevision packs folder
     *
     * @return void
     */
    public function testCheckGitRevisionPacksFolder()
    {
        $cwd = getcwd();
        $test_dir = "gittestdir";

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir($test_dir);
        chdir($test_dir);

        mkdir('.git');
        file_put_contents('.git/config','');

        $this->object->checkGitRevision();

        $this->assertEquals(
            '0',
            $this->object->get('PMA_VERSION_GIT')
        );

        $this->assertEmpty(
            $this->object->get('PMA_VERSION_GIT_COMMITHASH')
        );


        file_put_contents('.git/HEAD','ref: refs/remotes/origin/master');
        $this->object->checkGitRevision();
        $this->assertEmpty(
            $this->object->get('PMA_VERSION_GIT_COMMITHASH')
        );

        file_put_contents('.git/packed-refs',
        '# pack-refs with: peeled fully-peeled sorted'.PHP_EOL.
        'c1f2ff2eb0c3fda741f859913fd589379f4e4a8f refs/tags/4.3.10'.PHP_EOL.
        '^6f2e60343b0a324c65f2d1411bf4bd03e114fb98'.PHP_EOL.
        '17bf8b7309919f8ac593d7c563b31472780ee83b refs/remotes/origin/master'.PHP_EOL
        );
        mkdir('.git/objects/pack', 0777, true);//default = 0777, recursive mode
        $this->object->checkGitRevision();

        $this->assertNotEmpty(
            $this->object->get('PMA_VERSION_GIT_COMMITHASH')
        );
        $this->assertNotEmpty(
            $this->object->get('PMA_VERSION_GIT_BRANCH')
        );

        rmdir(".git/objects/pack");
        rmdir(".git/objects");
        unlink('.git/packed-refs');
        unlink('.git/HEAD');
        unlink('.git/config');
        rmdir('.git');

        chdir($cwd);
        rmdir($test_dir);
    }

    /**
     * Test for checkGitRevision packs folder
     *
     * @return void
     */
    public function testCheckGitRevisionRefFile()
    {
        $cwd = getcwd();
        $test_dir = "gittestdir";

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir($test_dir);
        chdir($test_dir);

        mkdir('.git');
        file_put_contents('.git/config','');

        $this->object->checkGitRevision();

        $this->assertEquals(
            '0',
            $this->object->get('PMA_VERSION_GIT')
        );

        $this->assertEmpty(
            $this->object->get('PMA_VERSION_GIT_COMMITHASH')
        );


        file_put_contents('.git/HEAD','ref: refs/remotes/origin/master');
        mkdir('.git/refs/remotes/origin', 0777, true);
        file_put_contents('.git/refs/remotes/origin/master','c1f2ff2eb0c3fda741f859913fd589379f4e4a8f');
        mkdir('.git/objects/pack', 0777, true);//default = 0777, recursive mode
        $this->object->checkGitRevision();

        $this->assertEquals(
            0,
            $this->object->get('PMA_VERSION_GIT')
        );

        unlink('.git/refs/remotes/origin/master');
        rmdir('.git/refs/remotes/origin');
        rmdir('.git/refs/remotes');
        rmdir('.git/refs');
        rmdir(".git/objects/pack");
        rmdir(".git/objects");
        unlink('.git/HEAD');
        unlink('.git/config');
        rmdir('.git');

        chdir($cwd);
        rmdir($test_dir);
    }

    /**
     * Test for checkGitRevision with packs as file
     *
     * @return void
     */
    public function testCheckGitRevisionPacksFile()
    {
        $cwd = getcwd();
        $test_dir = "gittestdir";

        unset($_SESSION['git_location']);
        unset($_SESSION['is_git_revision']);

        mkdir($test_dir);
        chdir($test_dir);

        mkdir('.git');
        file_put_contents('.git/config','');

        $this->object->checkGitRevision();

        $this->assertEquals(
            '0',
            $this->object->get('PMA_VERSION_GIT')
        );

        $this->assertEmpty(
            $this->object->get('PMA_VERSION_GIT_COMMITHASH')
        );


        file_put_contents('.git/HEAD','ref: refs/remotes/origin/master');
        $this->object->checkGitRevision();
        $this->assertEmpty(
            $this->object->get('PMA_VERSION_GIT_COMMITHASH')
        );

        file_put_contents('.git/packed-refs',
            '# pack-refs with: peeled fully-peeled sorted'.PHP_EOL.
            'c1f2ff2eb0c3fda741f859913fd589379f4e4a8f refs/tags/4.3.10'.PHP_EOL.
            '^6f2e60343b0a324c65f2d1411bf4bd03e114fb98'.PHP_EOL.
            '17bf8b7309919f8ac593d7c563b31472780ee83b refs/remotes/origin/master'.PHP_EOL
        );
        mkdir('.git/objects/info', 0777 ,true);
        file_put_contents('.git/objects/info/packs',
            'P pack-faea49765800da462c70bea555848cc8c7a1c28d.pack'. PHP_EOL .
            '  pack-.pack'. PHP_EOL .
            PHP_EOL .
            'P pack-420568bae521465fd11863bff155a2b2831023.pack'. PHP_EOL .
            PHP_EOL
        );

        $this->object->checkGitRevision();

        $this->assertNotEmpty(
            $this->object->get('PMA_VERSION_GIT_COMMITHASH')
        );
        $this->assertNotEmpty(
            $this->object->get('PMA_VERSION_GIT_BRANCH')
        );

        unlink(".git/objects/info/packs");
        rmdir(".git/objects/info");
        rmdir(".git/objects");
        unlink('.git/packed-refs');
        unlink('.git/HEAD');
        unlink('.git/config');
        rmdir('.git');

        chdir($cwd);
        rmdir($test_dir);
    }

    /**
     * Test for checkGitRevision
     *
     * @return void
     */
    public function testCheckGitRevisionSkipped()
    {
        $this->object->set('ShowGitRevision', false);
        $this->object->checkGitRevision();

        $this->assertEquals(
            null,
            $this->object->get('PMA_VERSION_GIT')
        );

        $this->assertEmpty(
            $this->object->get('PMA_VERSION_GIT_COMMITHASH')
        );
    }

    /**
     * Test for git infos in session
     *
     * @return void
     */
    public function testSessionCacheGitFolder()
    {
        $_SESSION['git_location'] = 'customdir/.git';
        $_SESSION['is_git_revision'] = true;
        $gitFolder = '';
        $this->assertTrue($this->object->isGitRevision($gitFolder));

        $this->assertEquals(
            $gitFolder,
            'customdir/.git'
        );
    }

    /**
     * Test that git folder is not looked up if cached value is false
     *
     * @return void
     */
    public function testSessionCacheGitFolderNotRevisionNull()
    {
        $_SESSION['is_git_revision'] = false;
        $_SESSION['git_location'] = null;
        $gitFolder = 'defaultvaluebyref';
        $this->assertFalse($this->object->isGitRevision($gitFolder));

        // Assert that the value is replaced by cached one
        $this->assertEquals(
            $gitFolder,
            null
        );
    }

    /**
     * Test that git folder is not looked up if cached value is false
     *
     * @return void
     */
    public function testSessionCacheGitFolderNotRevisionString()
    {
        $_SESSION['is_git_revision'] = false;
        $_SESSION['git_location'] = 'randomdir/.git';
        $gitFolder = 'defaultvaluebyref';
        $this->assertFalse($this->object->isGitRevision($gitFolder));

        // Assert that the value is replaced by cached one
        $this->assertEquals(
            $gitFolder,
            'randomdir/.git'
        );
    }

    /**
     * Test for checkServers
     *
     * @return void
     *
     * @dataProvider serverSettingsProvider
     */
    public function testCheckServers($settings, $expected, $error = false)
    {
        if ($error) {
            $this->setExpectedException('PHPUnit_Framework_Error');
        }

        $this->object->settings['Servers'] = $settings;
        $this->object->checkServers();
        if (is_null($expected)) {
            $expected = $this->object->default_server;
        } else {
            $expected = array_merge($this->object->default_server, $expected);
        }
        $this->assertEquals($expected, $this->object->settings['Servers'][1]);
    }

    /**
     * Data provider for checkServers test
     *
     * @return array
     */
    public function serverSettingsProvider()
    {
        return [
            'empty' => [
                [],
                [],
            ],
            'only_host' => [
                [1 => ['host' => '127.0.0.1']],
                ['host' => '127.0.0.1'],
            ],
            'empty_host' => [
                [1 => ['host' => '']],
                ['verbose' => 'Server 1', 'host' => ''],
            ],
            'invalid' => [
                ['invalid' => ['host' => '127.0.0.1']],
                ['host' => '127.0.0.1'],
                true
            ],
        ];
    }

    /**
     * Test for selectServer
     *
     * @return void
     *
     * @dataProvider selectServerProvider
     * @depends testCheckServers
     */
    public function testSelectServer($settings, $request, $expected)
    {
        $this->object->settings['Servers'] = $settings;
        $this->object->checkServers();
        $_REQUEST['server'] = $request;
        $this->assertEquals($expected, $this->object->selectServer());
    }

    /**
     * Data provider for selectServer test
     *
     * @return array
     */
    public function selectServerProvider()
    {
        return [
            'zero' => [
                [],
                '0',
                1,
            ],
            'number' => [
                [1 => []],
                '1',
                1,
            ],
            'host' => [
                [2 => ['host' => '127.0.0.1']],
                '127.0.0.1',
                2,
            ],
            'verbose' => [
                [1 => ['verbose' => 'Server 1', 'host' => '']],
                'Server 1',
                1
            ],
            'md5' => [
                [66 => ['verbose' => 'Server 1', 'host' => '']],
                '753f173bd4ac8a45eae0fe9a4fbe0fc0',
                66
            ],
            'nonexisting_string' => [
                [1 => []],
                'invalid',
                1,
            ],
            'nonexisting' => [
                [1 => []],
                '100',
                1,
            ],
        ];
    }
}
