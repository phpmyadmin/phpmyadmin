<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use CodeLts\U2F\U2FServer\RegistrationRequest;
use CodeLts\U2F\U2FServer\SignRequest;
use PhpMyAdmin\Plugins\TwoFactor\Application;
use PhpMyAdmin\TwoFactor;

use function count;
use function in_array;
use function json_encode;
use function str_replace;

use const JSON_UNESCAPED_SLASHES;

/**
 * @covers \PhpMyAdmin\TwoFactor
 */
class TwoFactorTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::setTheme();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['DBG'] = [
            'simple2fa' => false,
            'sql' => false,
        ];
        $GLOBALS['cfg']['NaturalOrder'] = true;
        $this->initStorageConfigAndData();
    }

    protected function tearDown(): void
    {
        $this->assertAllSelectsConsumed();
    }

    private function initStorageConfigAndData(): void
    {
        $GLOBALS['cfg']['Server']['user'] = 'groot';
        $GLOBALS['cfg']['Server']['bookmarktable'] = '';
        $GLOBALS['cfg']['Server']['relation'] = '';
        $GLOBALS['cfg']['Server']['table_info'] = '';
        $GLOBALS['cfg']['Server']['table_coords'] = '';
        $GLOBALS['cfg']['Server']['column_info'] = '';
        $GLOBALS['cfg']['Server']['pdf_pages'] = '';
        $GLOBALS['cfg']['Server']['history'] = '';
        $GLOBALS['cfg']['Server']['recent'] = '';
        $GLOBALS['cfg']['Server']['favorite'] = '';
        $GLOBALS['cfg']['Server']['table_uiprefs'] = '';
        $GLOBALS['cfg']['Server']['tracking'] = '';
        $GLOBALS['cfg']['Server']['userconfig'] = '';
        $GLOBALS['cfg']['Server']['users'] = '';
        $GLOBALS['cfg']['Server']['usergroups'] = '';
        $GLOBALS['cfg']['Server']['navigationhiding'] = '';
        $GLOBALS['cfg']['Server']['savedsearches'] = '';
        $GLOBALS['cfg']['Server']['central_columns'] = '';
        $GLOBALS['cfg']['Server']['designer_settings'] = '';
        $GLOBALS['cfg']['Server']['export_templates'] = '';

        parent::setGlobalDbi();

        $this->dummyDbi->removeDefaultResults();

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`;',
            [
                ['pma__userconfig'],// Minimal working setup for 2FA
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dummyDbi->addResult(
            'SHOW TABLES FROM `phpmyadmin`',
            [
                ['pma__userconfig'],// Minimal working setup for 2FA
            ],
            ['Tables_in_phpmyadmin']
        );

        $this->dummyDbi->addResult(
            'SELECT NULL FROM `pma__userconfig` LIMIT 0',
            [
                ['NULL'],
            ],
            ['NULL']
        );
    }

    /**
     * Creates TwoFactor mock with custom configuration
     *
     * @param string $user   Username
     * @param array  $config Two factor authentication configuration
     */
    private function getTwoFactorAndLoadConfig(string $user, ?array $config): TwoFactor
    {
        if ($config !== null && ! isset($config['backend'])) {
            $config['backend'] = '';
        }

        if ($config !== null && ! isset($config['settings'])) {
            $config['settings'] = [];
        }

        $this->dummyDbi->addSelectDb('phpmyadmin');
        $this->loadResultForConfig($config);

        return new TwoFactor($user);
    }

    private function loadQueriesForConfigure(string $backend, array $backendSettings = []): void
    {
        $this->dummyDbi->addResult(
            'SELECT `username` FROM `phpmyadmin`.`pma__userconfig` WHERE `username` = \'groot\'',
            [
                ['groot'],
            ],
            ['username']
        );

        $jsonData = (string) json_encode([
            'Console\\\\\\/Mode' => 'collapse',
            'lang' => 'fr',
            '2fa' => [
                'backend' => $backend,
                'settings' => $backendSettings,
            ],
        ], JSON_UNESCAPED_SLASHES);
        $jsonData = str_replace('"', '\"', $jsonData);

        $this->dummyDbi->addResult(
            'UPDATE `phpmyadmin`.`pma__userconfig` SET `timevalue` = NOW(),'
            . ' `config_data` = \'' . $jsonData . '\' WHERE `username` = \'groot\'',
            []
        );
    }

    private function loadResultForConfig(?array $config): void
    {
        $this->dummyDbi->addResult(
            'SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts'
            . ' FROM `phpmyadmin`.`pma__userconfig` WHERE `username` = \'groot\'',
            $config === null ? [] : [
                [
                    (string) json_encode([
                        'Console\/Mode' => 'collapse',
                        'lang' => 'fr',
                        '2fa' => $config,
                    ]),
                    '1628632378',
                ],
            ],
            ['config_data', 'ts']
        );
    }

    public function testNone(): void
    {
        $object = $this->getTwoFactorAndLoadConfig('user', ['type' => 'db']);
        $backend = $object->getBackend();
        $this->assertEquals('', $backend::$id);
        // Is always valid
        $this->assertTrue($object->check(true));
        // Test session persistence
        $this->assertTrue($object->check());
        $this->assertTrue($object->check());
        $this->assertEquals('', $object->render());

        $this->assertAllQueriesConsumed();

        $this->loadResultForConfig(['type' => 'db']);
        $this->loadQueriesForConfigure('');

        $this->assertTrue($object->configure(''));
        $this->assertEquals('', $object->setup());
    }

    public function testSimple(): void
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = $this->getTwoFactorAndLoadConfig('user', ['type' => 'db', 'backend' => 'simple']);
        $backend = $object->getBackend();
        $this->assertEquals('simple', $backend::$id);
        $GLOBALS['cfg']['DBG']['simple2fa'] = false;

        unset($_POST['2fa_confirm']);
        $this->assertFalse($object->check(true));

        $_POST['2fa_confirm'] = 1;
        $this->assertTrue($object->check(true));
        unset($_POST['2fa_confirm']);

        /* Test rendering */
        $this->assertNotEquals('', $object->render());
        $this->assertEquals('', $object->setup());
    }

    public function testLoad(): void
    {
        $object = $this->getTwoFactorAndLoadConfig('user', null);
        $backend = $object->getBackend();
        $this->assertEquals('', $backend::$id);
    }

    public function testConfigureSimple(): void
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = $this->getTwoFactorAndLoadConfig('user', null);

        $this->assertAllQueriesConsumed();

        $this->loadResultForConfig([]);
        $this->loadQueriesForConfigure('simple');

        $this->assertTrue($object->configure('simple'));
        $backend = $object->getBackend();
        $this->assertEquals('simple', $backend::$id);

        $this->assertAllQueriesConsumed();

        $this->loadResultForConfig([]);
        $this->loadQueriesForConfigure('');

        $this->assertTrue($object->configure(''));
        $backend = $object->getBackend();
        $this->assertEquals('', $backend::$id);

        $this->assertAllQueriesConsumed();

        $this->initStorageConfigAndData();// Needs a re-init

        $GLOBALS['cfg']['DBG']['simple2fa'] = false;
        $object = $this->getTwoFactorAndLoadConfig('user', null);
        $this->assertFalse($object->configure('simple'));
        $this->assertAllQueriesConsumed();
    }

    /**
     * @group extension-iconv
     * @requires extension xmlwriter
     * @requires extension iconv
     */
    public function testApplication(): void
    {
        parent::setLanguage();

        $object = $this->getTwoFactorAndLoadConfig('user', null);
        if (! in_array('application', $object->getAvailable())) {
            $this->markTestSkipped('google2fa not available');
        }

        /* Without providing code this should fail */
        unset($_POST['2fa_code']);
        $this->assertFalse($object->configure('application'));

        /* Invalid code */
        $_POST['2fa_code'] = 'invalid';
        $this->assertFalse($object->configure('application'));

        /* Generate valid code */
        /** @var Application $app */
        $app = $object->getBackend();
        $google2fa = $app->getGoogle2fa();
        $_POST['2fa_code'] = $google2fa->oathTotp(
            $object->config['settings']['secret'],
            $google2fa->getTimestamp()
        );

        $this->assertAllQueriesConsumed();
        $this->loadResultForConfig([]);
        $this->loadQueriesForConfigure('application', [
            'secret' => $object->config['settings']['secret'],
        ]);

        $this->assertTrue($object->configure('application'));

        $this->assertAllQueriesConsumed();
        unset($_POST['2fa_code']);

        /* Check code */
        unset($_POST['2fa_code']);
        $this->assertFalse($object->check(true));
        $_POST['2fa_code'] = 'invalid';
        $this->assertFalse($object->check(true));
        $_POST['2fa_code'] = $google2fa->oathTotp(
            $object->config['settings']['secret'],
            $google2fa->getTimestamp()
        );
        $this->assertTrue($object->check(true));
        unset($_POST['2fa_code']);

        /* Test rendering */
        $this->assertNotEquals('', $object->render());
        $this->assertNotEquals('', $object->setup());
    }

    public function testKey(): void
    {
        parent::setLanguage();

        $object = $this->getTwoFactorAndLoadConfig('user', null);
        if (! in_array('key', $object->getAvailable())) {
            $this->markTestSkipped('u2f-php-server not available');
        }

        $_SESSION['registrationRequest'] = null;
        /* Without providing code this should fail */
        unset($_POST['u2f_registration_response']);
        $this->assertFalse($object->configure('key'));

        /* Invalid code */
        $_POST['u2f_registration_response'] = 'invalid';
        $this->assertFalse($object->configure('key'));

        /* Invalid code */
        $_POST['u2f_registration_response'] = '[]';
        $this->assertFalse($object->configure('key'));

        /* Without providing code this should fail */
        unset($_POST['u2f_authentication_response']);
        $this->assertFalse($object->check(true));

        /* Invalid code */
        $_POST['u2f_authentication_response'] = 'invalid';
        $this->assertFalse($object->check(true));

        /* Invalid code */
        $_POST['u2f_authentication_response'] = '[]';
        $this->assertFalse($object->check(true));

        /* Test rendering */
        $this->assertNotEquals('', $object->render());
        $this->assertNotEquals('', $object->setup());
    }

    /**
     * Test getting AppId
     */
    public function testKeyAppId(): void
    {
        $object = $this->getTwoFactorAndLoadConfig('user', null);
        $GLOBALS['config']->set('PmaAbsoluteUri', 'http://demo.example.com');
        $this->assertEquals('http://demo.example.com', $object->getBackend()->getAppId(true));
        $this->assertEquals('demo.example.com', $object->getBackend()->getAppId(false));
        $GLOBALS['config']->set('PmaAbsoluteUri', 'https://demo.example.com:123');
        $this->assertEquals('https://demo.example.com:123', $object->getBackend()->getAppId(true));
        $this->assertEquals('demo.example.com', $object->getBackend()->getAppId(false));
        $GLOBALS['config']->set('PmaAbsoluteUri', '');
        $GLOBALS['config']->set('is_https', true);
        $_SERVER['HTTP_HOST'] = 'pma.example.com';
        $this->assertEquals('https://pma.example.com', $object->getBackend()->getAppId(true));
        $this->assertEquals('pma.example.com', $object->getBackend()->getAppId(false));
        $GLOBALS['config']->set('is_https', false);
        $this->assertEquals('http://pma.example.com', $object->getBackend()->getAppId(true));
        $this->assertEquals('pma.example.com', $object->getBackend()->getAppId(false));
    }

    /**
     * Test based on upstream test data:
     * https://github.com/Yubico/php-u2flib-server
     */
    public function testKeyAuthentication(): void
    {
        $object = $this->getTwoFactorAndLoadConfig('user', null);
        if (! in_array('key', $object->getAvailable())) {
            $this->markTestSkipped('u2f-php-server not available');
        }

        $_SESSION['registrationRequest'] = new RegistrationRequest(
            'yKA0x075tjJ-GE7fKTfnzTOSaNUOWQxRd9TWz5aFOg8',
            'http://demo.example.com'
        );
        unset($_POST['u2f_registration_response']);
        $this->assertFalse($object->configure('key'));

        $_POST['u2f_registration_response'] = '';
        $this->assertFalse($object->configure('key'));

        $_POST['u2f_registration_response'] = '{ "registrationData": "BQQtEmhWVgvbh-8GpjsHbj_d5F'
            . 'B9iNoRL8mNEq34-ANufKWUpVdIj6BSB_m3eMoZ3GqnaDy3RA5eWP8mhTkT1Ht3QAk1GsmaPIQgXgvrBk'
            . 'CQoQtMFvmwYPfW5jpRgoMPFxquHS7MTt8lofZkWAK2caHD-YQQdaRBgd22yWIjPuWnHOcwggLiMIHLAg'
            . 'EBMA0GCSqGSIb3DQEBCwUAMB0xGzAZBgNVBAMTEll1YmljbyBVMkYgVGVzdCBDQTAeFw0xNDA1MTUxMjU'
            . '4NTRaFw0xNDA2MTQxMjU4NTRaMB0xGzAZBgNVBAMTEll1YmljbyBVMkYgVGVzdCBFRTBZMBMGByqGSM49'
            . 'AgEGCCqGSM49AwEHA0IABNsK2_Uhx1zOY9ym4eglBg2U5idUGU-dJK8mGr6tmUQflaNxkQo6IOc-kV4T6'
            . 'L44BXrVeqN-dpCPr-KKlLYw650wDQYJKoZIhvcNAQELBQADggIBAJVAa1Bhfa2Eo7TriA_jMA8togoA2S'
            . 'UE7nL6Z99YUQ8LRwKcPkEpSpOsKYWJLaR6gTIoV3EB76hCiBaWN5HV3-CPyTyNsM2JcILsedPGeHMpMuW'
            . 'rbL1Wn9VFkc7B3Y1k3OmcH1480q9RpYIYr-A35zKedgV3AnvmJKAxVhv9GcVx0_CewHMFTryFuFOe78W8'
            . 'nFajutknarupekDXR4tVcmvj_ihJcST0j_Qggeo4_3wKT98CgjmBgjvKCd3Kqg8n9aSDVWyaOZsVOhZj3'
            . 'Fv5rFu895--D4qiPDETozJIyliH-HugoQpqYJaTX10mnmMdCa6aQeW9CEf-5QmbIP0S4uZAf7pKYTNmDQ'
            . '5z27DVopqaFw00MIVqQkae_zSPX4dsNeeoTTXrwUGqitLaGap5ol81LKD9JdP3nSUYLfq0vLsHNDyNgb3'
            . '06TfbOenRRVsgQS8tJyLcknSKktWD_Qn7E5vjOXprXPrmdp7g5OPvrbz9QkWa1JTRfo2n2AXV02LPFc-U'
            . 'fR9bWCBEIJBxvmbpmqt0MnBTHWnth2b0CU_KJTDCY3kAPLGbOT8A4KiI73pRW-e9SWTaQXskw3Ei_dHRI'
            . 'LM_l9OXsqoYHJ4Dd3tbfvmjoNYggSw4j50l3unI9d1qR5xlBFpW5sLr8gKX4bnY4SR2nyNiOQNLyPc0B0'
            . 'nW502aMEUCIQDTGOX-i_QrffJDY8XvKbPwMuBVrOSO-ayvTnWs_WSuDQIgZ7fMAvD_Ezyy5jg6fQeuOko'
            . 'Ji8V2naCtzV-HTly8Nww=", "clientData": "eyAiY2hhbGxlbmdlIjogInlLQTB4MDc1dGpKLUdFN2'
            . 'ZLVGZuelRPU2FOVU9XUXhSZDlUV3o1YUZPZzgiLCAib3JpZ2luIjogImh0dHA6XC9cL2RlbW8uZXhhbXB'
            . 'sZS5jb20iLCAidHlwIjogIm5hdmlnYXRvci5pZC5maW5pc2hFbnJvbGxtZW50IiB9", "errorCode": 0 }';

        $this->assertAllQueriesConsumed();
        $this->loadResultForConfig([]);
        $this->loadQueriesForConfigure('key', [
            'registrations' => [
                [
                    'keyHandle' => 'CTUayZo8hCBeC-sGQJChC0wW-bBg99bmOlGCgw8XGq4'
                    . 'dLsxO3yWh9mRYArZxocP5hBB1pEGB3bbJYiM-5acc5w',
                    'publicKey' => 'BC0SaFZWC9uH7wamOwduP93kUH2I2hEvyY0Srfj4A258pZSlV0iPoFIH'
                    . '+bd4yhncaqdoPLdEDl5Y\\/yaFORPUe3c=',
                    'certificate' => 'MIIC4jCBywIBATANBgkqhkiG9w0BAQsFADAdMRswGQYDVQQDExJZdWJpY28gVTJGIFRlc3QgQ0EwHhcN'
                    . 'MTQwNTE1MTI1ODU0WhcNMTQwNjE0MTI1ODU0WjAdMRswGQYDVQQDExJZdWJpY28gVTJGIFRlc3QgRUUwW'
                    . 'TATBgcqhkjOPQIBBggqhkjOPQMBBwNCAATbCtv1IcdczmPcpuHoJQYNlOYnVBlPnSSvJhq+rZlEH5WjcZ'
                    . 'EKOiDnPpFeE+i+OAV61XqjfnaQj6\\/iipS2MOudMA0GCSqGSIb3DQEBCwUAA4ICAQCVQGtQYX2thKO064'
                    . 'gP4zAPLaIKANklBO5y+mffWFEPC0cCnD5BKUqTrCmFiS2keoEyKFdxAe+oQogWljeR1d\\/gj8k8jbDNiX'
                    . 'CC7HnTxnhzKTLlq2y9Vp\\/VRZHOwd2NZNzpnB9ePNKvUaWCGK\\/gN+cynnYFdwJ75iSgMVYb\\/RnFcd'
                    . 'PwnsBzBU68hbhTnu\\/FvJxWo7rZJ2q7qXpA10eLVXJr4\\/4oSXEk9I\\/0IIHqOP98Ck\\/fAoI5gYI7'
                    . 'ygndyqoPJ\\/Wkg1VsmjmbFToWY9xb+axbvPefvg+KojwxE6MySMpYh\\/h7oKEKamCWk19dJp5jHQmumk'
                    . 'HlvQhH\\/uUJmyD9EuLmQH+6SmEzZg0Oc9uw1aKamhcNNDCFakJGnv80j1+HbDXnqE0168FBqorS2hmqea'
                    . 'JfNSyg\\/SXT950lGC36tLy7BzQ8jYG99Ok32znp0UVbIEEvLSci3JJ0ipLVg\\/0J+xOb4zl6a1z65nae'
                    . '4OTj7628\\/UJFmtSU0X6Np9gF1dNizxXPlH0fW1ggRCCQcb5m6ZqrdDJwUx1p7Ydm9AlPyiUwwmN5ADyx'
                    . 'mzk\\/AOCoiO96UVvnvUlk2kF7JMNxIv3R0SCzP5fTl7KqGByeA3d7W375o6DWIIEsOI+dJd7pyPXdakec'
                    . 'ZQRaVubC6\\/ICl+G52OEkdp8jYjkDS8j3NAdJ1udNmg==',
                    'counter' => -1,
                ],
            ],
        ]);

        $this->assertTrue($object->configure('key'));

        unset($_POST['u2f_authentication_response']);
        $this->assertFalse($object->check(true));

        $_POST['u2f_authentication_response'] = '';
        $this->assertFalse($object->check(true));

        $_SESSION['authenticationRequest'] = [
            new SignRequest([
                'challenge' => 'fEnc9oV79EaBgK5BoNERU5gPKM2XGYWrz4fUjgc0Q7g',
                'keyHandle' => 'CTUayZo8hCBeC-sGQJChC0wW-bBg99bmOlGCgw8XGq4dLsxO3yWh9mRYArZxocP5hBB1pEGB3bbJYiM-5acc5w',
                'appId' => 'http://demo.example.com',
            ]),
        ];
        $this->assertFalse($object->check(true));
        $_POST['u2f_authentication_response'] = '{ "signatureData": "AQAAAAQwRQIhAI6FSrMD3KUUtkpiP0'
            . 'jpIEakql-HNhwWFngyw553pS1CAiAKLjACPOhxzZXuZsVO8im-HStEcYGC50PKhsGp_SUAng==", '
            . '"clientData": "eyAiY2hhbGxlbmdlIjogImZFbmM5b1Y3OUVhQmdLNUJvTkVSVTVnUEtNMlhHWVd'
            . 'yejRmVWpnYzBRN2ciLCAib3JpZ2luIjogImh0dHA6XC9cL2RlbW8uZXhhbXBsZS5jb20iLCAidHlwI'
            . 'jogIm5hdmlnYXRvci5pZC5nZXRBc3NlcnRpb24iIH0=", "keyHandle": "CTUayZo8hCBeC-sGQJC'
            . 'hC0wW-bBg99bmOlGCgw8XGq4dLsxO3yWh9mRYArZxocP5hBB1pEGB3bbJYiM-5acc5w", "errorCode": 0 }';
        $this->assertAllQueriesConsumed();
        $this->loadResultForConfig([]);
        $this->loadQueriesForConfigure('key', [
            'registrations' => [
                [
                    'keyHandle' => 'CTUayZo8hCBeC-sGQJChC0wW-bBg99bmOlGCgw8XGq4dLsxO3yWh9mRYArZxocP5hBB1pEGB3bbJ'
                    . 'YiM-5acc5w',
                    'publicKey' => 'BC0SaFZWC9uH7wamOwduP93kUH2I2hEvyY0Srfj4A258pZSlV0iPoFIH+bd4yhncaqdo'
                        . 'PLdEDl5Y\\/yaFORPUe3c=',
                    'certificate' => 'MIIC4jCBywIBATANBgkqhkiG9w0BAQsFADAdMRswGQYDVQQDExJZdWJpY28gVTJGIFRlc3QgQ0EwHhcN'
                    . 'MTQwNTE1MTI1ODU0WhcNMTQwNjE0MTI1ODU0WjAdMRswGQYDVQQDExJZdWJpY28gVTJGIFRlc3QgRUUwW'
                    . 'TATBgcqhkjOPQIBBggqhkjOPQMBBwNCAATbCtv1IcdczmPcpuHoJQYNlOYnVBlPnSSvJhq+rZlEH5WjcZ'
                    . 'EKOiDnPpFeE+i+OAV61XqjfnaQj6\\/iipS2MOudMA0GCSqGSIb3DQEBCwUAA4ICAQCVQGtQYX2thKO064'
                    . 'gP4zAPLaIKANklBO5y+mffWFEPC0cCnD5BKUqTrCmFiS2keoEyKFdxAe+oQogWljeR1d\\/gj8k8jbDNiX'
                    . 'CC7HnTxnhzKTLlq2y9Vp\\/VRZHOwd2NZNzpnB9ePNKvUaWCGK\\/gN+cynnYFdwJ75iSgMVYb\\/RnFcd'
                    . 'PwnsBzBU68hbhTnu\\/FvJxWo7rZJ2q7qXpA10eLVXJr4\\/4oSXEk9I\\/0IIHqOP98Ck\\/fAoI5gYI7'
                    . 'ygndyqoPJ\\/Wkg1VsmjmbFToWY9xb+axbvPefvg+KojwxE6MySMpYh\\/h7oKEKamCWk19dJp5jHQmumk'
                    . 'HlvQhH\\/uUJmyD9EuLmQH+6SmEzZg0Oc9uw1aKamhcNNDCFakJGnv80j1+HbDXnqE0168FBqorS2hmqea'
                    . 'JfNSyg\\/SXT950lGC36tLy7BzQ8jYG99Ok32znp0UVbIEEvLSci3JJ0ipLVg\\/0J+xOb4zl6a1z65nae'
                    . '4OTj7628\\/UJFmtSU0X6Np9gF1dNizxXPlH0fW1ggRCCQcb5m6ZqrdDJwUx1p7Ydm9AlPyiUwwmN5ADyx'
                    . 'mzk\\/AOCoiO96UVvnvUlk2kF7JMNxIv3R0SCzP5fTl7KqGByeA3d7W375o6DWIIEsOI+dJd7pyPXdakec'
                    . 'ZQRaVubC6\\/ICl+G52OEkdp8jYjkDS8j3NAdJ1udNmg==',
                    'counter' => 4,
                ],
            ],
        ]);
        $this->assertTrue($object->check(true));
        $this->assertAllQueriesConsumed();
    }

    /**
     * Test listing of available backends.
     */
    public function testBackends(): void
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = $this->getTwoFactorAndLoadConfig('user', null);
        $backends = $object->getAllBackends();
        $this->assertCount(
            count($object->getAvailable()) + 1,
            $backends
        );
        $GLOBALS['cfg']['DBG']['simple2fa'] = false;
    }
}
