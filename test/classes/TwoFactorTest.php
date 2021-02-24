<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Plugins\TwoFactor\Application;
use PhpMyAdmin\TwoFactor;
use Samyoul\U2F\U2FServer\RegistrationRequest;
use Samyoul\U2F\U2FServer\SignRequest;
use function count;
use function in_array;

class TwoFactorTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setTheme();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['DBG'] = [
            'simple2fa' => false,
            'sql' => false,
        ];
        $GLOBALS['cfg']['NaturalOrder'] = true;
    }

    /**
     * Creates TwoFactor mock with custom configuration
     *
     * @param string $user   Username
     * @param array  $config Two factor authentication configuration
     */
    public function getTwoFactorMock(string $user, array $config): TwoFactor
    {
        if (! isset($config['backend'])) {
            $config['backend'] = '';
        }
        if (! isset($config['settings'])) {
            $config['settings'] = [];
        }
        $result = $this->getMockBuilder(TwoFactor::class)
            ->setMethods(['readConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        $result->method('readConfig')->willReturn($config);
        $result->__construct($user);

        return $result;
    }

    public function testNone(): void
    {
        $object = $this->getTwoFactorMock('user', ['type' => 'db']);
        $backend = $object->getBackend();
        $this->assertEquals('', $backend::$id);
        // Is always valid
        $this->assertTrue($object->check(true));
        // Test session persistence
        $this->assertTrue($object->check());
        $this->assertTrue($object->check());
        $this->assertEquals('', $object->render());
        $this->assertTrue($object->configure(''));
        $this->assertEquals('', $object->setup());
    }

    public function testSimple(): void
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = $this->getTwoFactorMock('user', ['type' => 'db', 'backend' => 'simple']);
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
        $object = new TwoFactor('user');
        $backend = $object->getBackend();
        $this->assertEquals('', $backend::$id);
    }

    public function testConfigureSimple(): void
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = new TwoFactor('user');
        $this->assertTrue($object->configure('simple'));
        $backend = $object->getBackend();
        $this->assertEquals('simple', $backend::$id);
        $this->assertTrue($object->configure(''));
        $backend = $object->getBackend();
        $this->assertEquals('', $backend::$id);
        $GLOBALS['cfg']['DBG']['simple2fa'] = false;
        $object = new TwoFactor('user');
        $this->assertFalse($object->configure('simple'));
    }

    /**
     * @requires extension xmlwriter
     */
    public function testApplication(): void
    {
        parent::setLanguage();
        parent::loadDefaultConfig();

        $object = new TwoFactor('user');
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
        $this->assertTrue($object->configure('application'));
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
        parent::loadDefaultConfig();
        parent::setLanguage();

        $object = new TwoFactor('user');
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
        $object = new TwoFactor('user');
        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', 'http://demo.example.com');
        $this->assertEquals('http://demo.example.com', $object->getBackend()->getAppId(true));
        $this->assertEquals('demo.example.com', $object->getBackend()->getAppId(false));
        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', 'https://demo.example.com:123');
        $this->assertEquals('https://demo.example.com:123', $object->getBackend()->getAppId(true));
        $this->assertEquals('demo.example.com', $object->getBackend()->getAppId(false));
        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', '');
        $GLOBALS['PMA_Config']->set('is_https', true);
        $_SERVER['HTTP_HOST'] = 'pma.example.com';
        $this->assertEquals('https://pma.example.com', $object->getBackend()->getAppId(true));
        $this->assertEquals('pma.example.com', $object->getBackend()->getAppId(false));
        $GLOBALS['PMA_Config']->set('is_https', false);
        $this->assertEquals('http://pma.example.com', $object->getBackend()->getAppId(true));
        $this->assertEquals('pma.example.com', $object->getBackend()->getAppId(false));
    }

    /**
     * Test based on upstream test data:
     * https://github.com/Yubico/php-u2flib-server
     */
    public function testKeyAuthentication(): void
    {
        $object = new TwoFactor('user');
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
        $this->assertTrue($object->check(true));
    }

    /**
     * Test listing of available backends.
     */
    public function testBackends(): void
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = new TwoFactor('user');
        $backends = $object->getAllBackends();
        $this->assertCount(
            count($object->getAvailable()) + 1,
            $backends
        );
        $GLOBALS['cfg']['DBG']['simple2fa'] = false;
    }
}
