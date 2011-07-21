<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_Config class
 *
 *
 * @package phpMyAdmin-test
 * @group current
 */

/*
 * Include to test.
 */
require_once 'libraries/Config.class.php';
require_once 'libraries/relation.lib.php';

class PMA_ConfigTest extends PHPUnit_Framework_TestCase
{
    /**
     * Turn off backup globals
     */
    protected $backupGlobals = FALSE;

    /**
     * @var PMA_Config
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new PMA_Config;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }

    public function testCheckSystem()
    {
        $this->object->checkSystem();
        
        $this->assertNotNull($this->object->get('PMA_VERSION'));
        $this->assertNotEmpty($this->object->get('PMA_THEME_VERSION'));
        $this->assertNotEmpty($this->object->get('PMA_THEME_GENERATION'));
    }

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
        $this->assertEquals('auto',$this->object->get("OBGzip"));

        ini_set('zlib.output_compression', 'Off');
        $this->object->checkOutputCompression();
        $this->assertFalse($this->object->get("OBGzip"));

        ini_set('zlib.output_compression', 'On');
    }

    public function testCheckClient()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Opera/9.80 (X11; Linux x86_64; U; pl) Presto/2.7.62 Version/11.00';
        $this->object->checkClient();
        $this->assertEquals("Linux", $this->object->get('PMA_USR_OS'), "User OS expected to be Linux");
        $this->assertEquals("OPERA", $this->object->get('PMA_USR_BROWSER_AGENT'), "Browser expected to be Opera");
        $this->assertEquals("9.80", $this->object->get('PMA_USR_BROWSER_VER'), "Browser ver expected to be 9.80");

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X; en-US) AppleWebKit/528.16 OmniWeb/622.8.0.112941';
        $this->object->checkClient();
        $this->assertEquals("Mac", $this->object->get('PMA_USR_OS'), "User OS expected to be Mac");
        $this->assertEquals("OMNIWEB", $this->object->get('PMA_USR_BROWSER_AGENT'), "Browser expected to be OmniWeb");

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1)';
        $this->object->checkClient();
        $this->assertEquals("Win", $this->object->get('PMA_USR_OS'), "User OS expected to be Windows");
        $this->assertEquals("IE", $this->object->get('PMA_USR_BROWSER_AGENT'), "Browser expected to be IE");

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Unknown; U; Unix BSD/SYSV system; C -) AppleWebKit/527+ (KHTML, like Gecko, Safari/419.3) Arora/0.10.2';
        $this->object->checkClient();
        $this->assertEquals("Unix", $this->object->get('PMA_USR_OS'), "User OS expected to be Unix");

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; OS/2 Webexplorer)';
        $this->object->checkClient();
        $this->assertEquals("OS/2", $this->object->get('PMA_USR_OS'), "User OS expected to be OS/2");

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows; U; Win95; en-US; rv:1.9b) Gecko/20031208';
        $this->object->checkClient();
        $this->assertEquals("GECKO", $this->object->get('PMA_USR_BROWSER_AGENT'), "Browser expected to be Gecko");

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; Konqueror/4.5; NetBSD 5.0.2; X11; amd64; en_US) KHTML/4.5.4 (like Gecko)';
        $this->object->checkClient();
        $this->assertEquals("KONQUEROR", $this->object->get('PMA_USR_BROWSER_AGENT'), "Browser expected to be Konqueror");

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Linux x86_64; rv:5.0) Gecko/20100101 Firefox/5.0';
        $this->object->checkClient();
        $this->assertEquals("MOZILLA", $this->object->get('PMA_USR_BROWSER_AGENT'), "Browser expected to be Mozilla");
        $this->assertEquals("Linux", $this->object->get('PMA_USR_OS'), "User OS expected to be Linux");

    }

    public function testCheckGd2()
    {
        $prevIsGb2Val = $this->object->get('PMA_IS_GD2');

        $this->object->set('GD2Available','yes');
        $this->object->checkGd2();
        $this->assertEquals(1, $this->object->get('PMA_IS_GD2'));

        $this->object->set('GD2Available','no');
        $this->object->checkGd2();
        $this->assertEquals(0, $this->object->get('PMA_IS_GD2'));

        $this->object->set('GD2Available',$prevIsGb2Val);

        if (!@function_exists('imagecreatetruecolor'))
        {
            $this->object->checkGd2();
            $this->assertEquals(0, $this->object->get('PMA_IS_GD2'), 'Function imagecreatetruecolor does not exist, PMA_IS_GD2 should be 0');
        }

        if (@function_exists('gd_info')) {
            $this->object->checkGd2();
            $gd_nfo = gd_info();
            if (strstr($gd_nfo["GD Version"], '2.')) {
                $this->assertEquals(1, $this->object->get('PMA_IS_GD2'), 'GD Version >= 2, PMA_IS_GD2 should be 1');
            } else {
                $this->assertEquals(0, $this->object->get('PMA_IS_GD2'), 'GD Version < 2, PMA_IS_GD2 should be 0');
            }
        }

        /* Get GD version string from phpinfo output */
        ob_start();
        phpinfo(INFO_MODULES); /* Only modules */
        $a = strip_tags(ob_get_contents());
        ob_end_clean();

        if (preg_match('@GD Version[[:space:]]*\(.*\)@', $a, $v)) {
            if (strstr($v, '2.')) {
                $this->assertEquals(1, $this->object->get('PMA_IS_GD2'), 'PMA_IS_GD2 should be 1');
            } else {
                $this->assertEquals(0, $this->object->get('PMA_IS_GD2'), 'PMA_IS_GD2 should be 0');
            }
        }
    }

    public function testCheckWebServer()
    {
        $_SERVER['SERVER_SOFTWARE'] = "Microsoft-IIS 7.0";
        $this->object->checkWebServer();
        $this->assertEquals(1, $this->object->get('PMA_IS_IIS'));

        $_SERVER['SERVER_SOFTWARE'] = "Apache/2.2.17";
        $this->object->checkWebServer();
        $this->assertEquals(0, $this->object->get('PMA_IS_IIS'));

        unset($_SERVER['SERVER_SOFTWARE']);
    }

    public function testCheckWebServerOs()
    {
        $this->object->checkWebServerOs();

        if (defined('PHP_OS'))
        {
            switch (PHP_OS)
            {
                case stristr(PHP_OS,'win'):
                    $this->assertEquals(1, $this->object->get('PMA_IS_WINDOWS'), 'PHP_OS equals: ' . PHP_OS . ' PMA_IS_WINDOWS should be 1');
                    break;
                case stristr(PHP_OS, 'OS/2'):
                    $this->assertEquals(1, $this->object->get('PMA_IS_WINDOWS'), 'PHP_OS is OS/2 PMA_IS_WINDOWS should be 1 (No file permissions like Windows)');
                    break;
                case stristr(PHP_OS, 'Linux'):
                    $this->assertEquals(0, $this->object->get('PMA_IS_WINDOWS'));
                    break;
            }
        }
        else
        {
            $this->assertEquals(0, $this->object->get('PMA_IS_WINDOWS'), 'PMA_IS_WINDOWS Default to Unix or Equiv');

            define('PHP_OS','Windows');
            $this->assertEquals(1, $this->object->get('PMA_IS_WINDOWS'), 'PMA_IS_WINDOWS must be 1');
        }
    }

    public function testCheckPhpVersion()
    {
        $this->object->checkPhpVersion();

        $php_int_ver = 0;
        $php_str_ver = phpversion();

        $match = array();
        preg_match('@([0-9]{1,2}).([0-9]{1,2}).([0-9]{1,2})@', phpversion(), $match);
        if (isset($match) && ! empty($match[1])) {
            if (! isset($match[2])) {
                $match[2] = 0;
            }
            if (! isset($match[3])) {
                $match[3] = 0;
            }
            $php_int_ver = (int) sprintf('%d%02d%02d', $match[1], $match[2], $match[3]);
        } else {
            $php_int_ver = 0;
        }

        $this->assertEquals($php_str_ver, $this->object->get('PMA_PHP_STR_VERSION'));
        $this->assertEquals($php_int_ver, $this->object->get('PMA_PHP_INT_VERSION'));
    }

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

        $this->assertEquals($this->object->default_source_mtime, filemtime($prevDefaultSource));
        $this->assertEquals($loadedConf['Servers'][1], $this->object->default_server);

        unset($loadedConf['Servers']);

        $this->assertEquals($loadedConf, $this->object->default);

        $expectedSettings = PMA_array_merge_recursive($this->object->settings, $loadedConf);

        $this->assertEquals($expectedSettings, $this->object->settings,'Settings loaded wrong');

        $this->assertFalse($this->object->error_config_default_file);
    }

    public function testCheckConfigSource()
    {
        $this->object->setSource('unexisted.config.php');
        $this->assertFalse($this->object->checkConfigSource());
        $this->assertEquals(0, $this->object->source_mtime);

//        if(! is_readable($this->object->getSource()))
//           $this->markTestSkipped('Configuration file is read only');

        $this->object->setSource('libraries/config.default.php');

        $this->assertNotEmpty($this->object->getSource());
        $this->assertTrue($this->object->checkConfigSource());
    }

    /**
     * @covers PMA_Config::get
     * @covers PMA_Config::set
     * @return void
     */
    public function testGetAndSet()
    {
        $this->assertNull($this->object->get("unresisting_setting"));

        $this->object->set('test_setting', 'test_value');

        $this->assertEquals('test_value', $this->object->get('test_setting'));
    }

    /**
     * @covers PMA_Config::getSource
     * @covers PMA_Config::setSource
     */
    public function testGetSetSource()
    {
        echo $this->object->getSource();

        $this->assertEmpty($this->object->getSource(), "Source is null by default");

        $this->object->setSource("config.sample.inc.php");

        $this->assertEquals("config.sample.inc.php", $this->object->getSource(), "Cant set new source");
    }

    public function testCheckPmaAbsoluteUriEmpty()
    {
        $this->object->set('PmaAbsoluteUri','');
        $this->assertFalse($this->object->checkPmaAbsoluteUri(), 'PmaAbsoluteUri is not set and should be error');
        $this->assertTrue($this->object->error_pma_uri, 'PmaAbsoluteUri is not set and should be error');
    }

    /**
     * @depends testCheckPmaAbsoluteUriEmpty
     */
    public function testCheckPmaAbsoluteUriNormal()
    {
        $this->object->set('PmaAbsoluteUri','http://localhost/phpmyadmin/');
        $this->object->checkPmaAbsoluteUri();
        $this->assertEquals("http://localhost/phpmyadmin/", $this->object->get('PmaAbsoluteUri'));

        $this->object->set('PmaAbsoluteUri','http://localhost/phpmyadmin');
        $this->object->checkPmaAbsoluteUri();
        $this->assertEquals("http://localhost/phpmyadmin/", $this->object->get('PmaAbsoluteUri'), 'Expected trailing slash at the end of the phpMyAdmin uri');

    }

    /**
     * @depends testCheckPmaAbsoluteUriNormal
     */
    public function testCheckPmaAbsoluteUriScheme()
    {
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['HTTP_SCHEME'] = 'http';
        $_SERVER['HTTPS'] = 'off';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';

        $this->object->set('PmaAbsoluteUri','');

        $this->object->checkPmaAbsoluteUri();
        $this->assertEquals("http://localhost/", $this->object->get('PmaAbsoluteUri'));
    }

    /**
     * @depends testCheckPmaAbsoluteUriScheme
     */
    public function testCheckPmaAbsoluteUriUser()
    {
        $this->object->set('PmaAbsoluteUri','http://user:pwd@localhost/phpmyadmin/index.php');

        $this->object->checkPmaAbsoluteUri();
        $this->assertEquals("http://user:pwd@localhost/phpmyadmin/index.php/", $this->object->get('PmaAbsoluteUri'));

        $this->object->set('PmaAbsoluteUri','https://user:pwd@localhost/phpmyadmin/index.php');

        $this->object->checkPmaAbsoluteUri();
        $this->assertEquals("https://user:pwd@localhost/phpmyadmin/index.php/", $this->object->get('PmaAbsoluteUri'));
    }

    public function testCheckCollationConnection()
    {
        $_REQUEST['collation_connection'] = 'utf-8';
        $this->object->checkCollationConnection();

        $this->assertEquals($_REQUEST['collation_connection'], $this->object->get('collation_connection'));
    }

    public function testIsHttps()
    {
        $this->object->set('PmaAbsoluteUri', 'http://some_host.com/phpMyAdmin');
        $this->assertFalse($this->object->isHttps());

        $this->object->set('PmaAbsoluteUri', 'https://some_host.com/phpMyAdmin');
        $this->assertFalse($this->object->isHttps());
    }

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
     * @depends testDetectHttps
     */
    public function testCheckCookiePath()
    {
        $this->object->checkCookiePath();
        echo $this->object->get('cookie_path');
        $this->assertEquals('',$this->object->get('cookie_path'));
    }

    /**
     * @depends testCheckSystem
     * @depends testCheckWebServer
     * @depends testLoadDefaults
     * @depends testLoad
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

        foreach ($defines as $define)
        {
            $this->assertTrue(defined($define));
            $this->assertEquals(constant($define), $this->object->get($define));
        }
    }

    /**
     * @todo Implement testSave().
     */
    public function testSave()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetFontsizeForm().
     */
    public function testGetFontsizeForm()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testRemoveCookie().
     */
    public function testRemoveCookie()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }
        /**
     * @todo Implement testCheckFontsize().
     */
    public function testCheckFontsize()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testCheckUpload().
     */
    public function testCheckUpload()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testCheckUploadSize().
     */
    public function testCheckUploadSize()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testCheckIsHttps().
     */
    public function testCheckIsHttps()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetCookiePath().
     */
    public function testGetCookiePath()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo finish implementing test + dependencies
     */
    public function testLoad()
    {
        $this->assertFalse($this->object->load());

        $this->assertTrue($this->object->load('./libraries/config.default.php'));
    }

    /**
     * @todo Implement testLoadUserPreferences().
     */
    public function testLoadUserPreferences()
    {
        $this->assertNull($this->object->loadUserPreferences());

//        echo $GLOBALS['cfg']['ServerDefault'];
    }

    /**
     * @todo Implement testSetUserValue().
     */
    public function testSetUserValue()
    {
        $this->object->setUserValue(null, 'lang', $GLOBALS['lang'], 'en');
        $this->object->setUserValue("TEST_COOKIE_USER_VAL",'','cfg_val_1');

        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
    }

    /**
     * @todo Implement testGetUserValue().
     */
    public function testGetUserValue()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetThemeUniqueValue().
     */
    public function testGetThemeUniqueValue()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testCheckPermissions().
     */
    public function testCheckPermissions()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }


    /**
     * @todo Implement testSetCookie().
     */
    public function testSetCookie()
    {
        $this->assertFalse($this->object->setCookie('TEST_DEF_COOKIE', 'test_def_123', 'test_def_123'));

        $this->assertTrue($this->object->setCookie('TEST_CONFIG_COOKIE', 'test_val_123', null, 3600));

        $this->assertTrue($this->object->setCookie('TEST_CONFIG_COOKIE', '', 'default_val'));

        $_COOKIE['TEST_MANUAL_COOKIE'] = 'some_test_val';
        $this->assertTrue($this->object->setCookie('TEST_MANUAL_COOKIE', 'other', 'other'));

    }
}
?>
