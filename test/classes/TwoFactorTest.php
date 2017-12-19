<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for TwoFactor class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\TwoFactor;
use Samyoul\U2F\U2FServer\RegistrationRequest;
use Samyoul\U2F\U2FServer\SignRequest;

/**
 * Tests behaviour of TwoFactor class
 *
 * @package PhpMyAdmin-test
 */
class TwoFactorTest extends PmaTestCase
{
    public function setUp()
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
    }

    /**
     * Creates TwoFactor mock with custom configuration
     *
     * @param string $user   Username
     * @param array  $config Two factor authentication configuration
     *
     * @return TwoFactor
     */
    public function getTwoFactorMock($user, $config)
    {
        if (! isset($config['backend'])) {
            $config['backend'] = '';
        }
        if (! isset($config['settings'])) {
            $config['settings'] = [];
        }
        $result = $this->getMockbuilder('PhpMyAdmin\TwoFactor')
            ->setMethods(['readConfig'])
            ->disableOriginalConstructor()
            ->getMock();
        $result->method('readConfig')->willReturn($config);
        $result->__construct($user);
        return $result;
    }

    public function testNone()
    {
        $object = $this->getTwoFactorMock('user', ['type' => 'db']);
        $backend = $object->backend;
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

    public function testSimple()
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = $this->getTwoFactorMock('user', ['type' => 'db', 'backend' => 'simple']);
        $backend = $object->backend;
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

    public function testLoad()
    {
        $object = new TwoFactor('user');
        $backend = $object->backend;
        $this->assertEquals('', $backend::$id);
    }

    public function testConfigureSimple()
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = new TwoFactor('user');
        $this->assertTrue($object->configure('simple'));
        $backend = $object->backend;
        $this->assertEquals('simple', $backend::$id);
        $this->assertTrue($object->configure(''));
        $backend = $object->backend;
        $this->assertEquals('', $backend::$id);
        $GLOBALS['cfg']['DBG']['simple2fa'] = false;
        $object = new TwoFactor('user');
        $this->assertFalse($object->configure('simple'));
    }

    public function testApplication()
    {
        $object = new TwoFactor('user');
        if (! in_array('application', $object->available)) {
            $this->markTestSkipped('google2fa not available');
        }
        /* Without providing code this should fail */
        unset($_POST['2fa_code']);
        $this->assertFalse($object->configure('application'));

        /* Invalid code */
        $_POST['2fa_code'] = 'invalid';
        $this->assertFalse($object->configure('application'));

        /* Generate valid code */
        $google2fa = $object->backend->google2fa;
        $_POST['2fa_code'] = $google2fa->oathHotp(
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
        $_POST['2fa_code'] = $google2fa->oathHotp(
            $object->config['settings']['secret'],
            $google2fa->getTimestamp()
        );
        $this->assertTrue($object->check(true));
        unset($_POST['2fa_code']);

        /* Test rendering */
        $this->assertNotEquals('', $object->render());
        $this->assertNotEquals('', $object->setup());
    }

    public function testKey()
    {
        $object = new TwoFactor('user');
        if (! in_array('key', $object->available)) {
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
    public function testKeyAppId()
    {
        $object = new TwoFactor('user');
        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', 'http://demo.example.com');
        $this->assertEquals('http://demo.example.com', $object->backend->getAppId(true));
        $this->assertEquals('demo.example.com', $object->backend->getAppId(false));
        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', 'https://demo.example.com:123');
        $this->assertEquals('https://demo.example.com:123', $object->backend->getAppId(true));
        $this->assertEquals('demo.example.com', $object->backend->getAppId(false));
        $GLOBALS['PMA_Config']->set('PmaAbsoluteUri', '');
        $GLOBALS['PMA_Config']->set('is_https', true);
        $_SERVER['HTTP_HOST'] = 'pma.example.com';
        $this->assertEquals('https://pma.example.com', $object->backend->getAppId(true));
        $this->assertEquals('pma.example.com', $object->backend->getAppId(false));
        $GLOBALS['PMA_Config']->set('is_https', false);
        $this->assertEquals('http://pma.example.com', $object->backend->getAppId(true));
        $this->assertEquals('pma.example.com', $object->backend->getAppId(false));
    }

    /**
     * Test based on upstream test data:
     * https://github.com/Yubico/php-u2flib-server
     */
    public function testKeyAuthentication()
    {
        $object = new TwoFactor('user');
        if (! in_array('key', $object->available)) {
            $this->markTestSkipped('u2f-php-server not available');
        }
        $_SESSION['registrationRequest'] = new RegistrationRequest('yKA0x075tjJ-GE7fKTfnzTOSaNUOWQxRd9TWz5aFOg8', 'http://demo.example.com');
        unset($_POST['u2f_registration_response']);
        $this->assertFalse($object->configure('key'));

        $_POST['u2f_registration_response'] = '';
        $this->assertFalse($object->configure('key'));

        $_POST['u2f_registration_response'] = '{ "registrationData": "BQQtEmhWVgvbh-8GpjsHbj_d5FB9iNoRL8mNEq34-ANufKWUpVdIj6BSB_m3eMoZ3GqnaDy3RA5eWP8mhTkT1Ht3QAk1GsmaPIQgXgvrBkCQoQtMFvmwYPfW5jpRgoMPFxquHS7MTt8lofZkWAK2caHD-YQQdaRBgd22yWIjPuWnHOcwggLiMIHLAgEBMA0GCSqGSIb3DQEBCwUAMB0xGzAZBgNVBAMTEll1YmljbyBVMkYgVGVzdCBDQTAeFw0xNDA1MTUxMjU4NTRaFw0xNDA2MTQxMjU4NTRaMB0xGzAZBgNVBAMTEll1YmljbyBVMkYgVGVzdCBFRTBZMBMGByqGSM49AgEGCCqGSM49AwEHA0IABNsK2_Uhx1zOY9ym4eglBg2U5idUGU-dJK8mGr6tmUQflaNxkQo6IOc-kV4T6L44BXrVeqN-dpCPr-KKlLYw650wDQYJKoZIhvcNAQELBQADggIBAJVAa1Bhfa2Eo7TriA_jMA8togoA2SUE7nL6Z99YUQ8LRwKcPkEpSpOsKYWJLaR6gTIoV3EB76hCiBaWN5HV3-CPyTyNsM2JcILsedPGeHMpMuWrbL1Wn9VFkc7B3Y1k3OmcH1480q9RpYIYr-A35zKedgV3AnvmJKAxVhv9GcVx0_CewHMFTryFuFOe78W8nFajutknarupekDXR4tVcmvj_ihJcST0j_Qggeo4_3wKT98CgjmBgjvKCd3Kqg8n9aSDVWyaOZsVOhZj3Fv5rFu895--D4qiPDETozJIyliH-HugoQpqYJaTX10mnmMdCa6aQeW9CEf-5QmbIP0S4uZAf7pKYTNmDQ5z27DVopqaFw00MIVqQkae_zSPX4dsNeeoTTXrwUGqitLaGap5ol81LKD9JdP3nSUYLfq0vLsHNDyNgb306TfbOenRRVsgQS8tJyLcknSKktWD_Qn7E5vjOXprXPrmdp7g5OPvrbz9QkWa1JTRfo2n2AXV02LPFc-UfR9bWCBEIJBxvmbpmqt0MnBTHWnth2b0CU_KJTDCY3kAPLGbOT8A4KiI73pRW-e9SWTaQXskw3Ei_dHRILM_l9OXsqoYHJ4Dd3tbfvmjoNYggSw4j50l3unI9d1qR5xlBFpW5sLr8gKX4bnY4SR2nyNiOQNLyPc0B0nW502aMEUCIQDTGOX-i_QrffJDY8XvKbPwMuBVrOSO-ayvTnWs_WSuDQIgZ7fMAvD_Ezyy5jg6fQeuOkoJi8V2naCtzV-HTly8Nww=", "clientData": "eyAiY2hhbGxlbmdlIjogInlLQTB4MDc1dGpKLUdFN2ZLVGZuelRPU2FOVU9XUXhSZDlUV3o1YUZPZzgiLCAib3JpZ2luIjogImh0dHA6XC9cL2RlbW8uZXhhbXBsZS5jb20iLCAidHlwIjogIm5hdmlnYXRvci5pZC5maW5pc2hFbnJvbGxtZW50IiB9", "errorCode": 0 }';
        $this->assertTrue($object->configure('key'));

        unset($_POST['u2f_authentication_response']);
        $this->assertFalse($object->check(true));

        $_POST['u2f_authentication_response'] = '';
        $this->assertFalse($object->check(true));

        $_SESSION['authenticationRequest'] = [new SignRequest([
            'challenge' => 'fEnc9oV79EaBgK5BoNERU5gPKM2XGYWrz4fUjgc0Q7g',
            'keyHandle' => 'CTUayZo8hCBeC-sGQJChC0wW-bBg99bmOlGCgw8XGq4dLsxO3yWh9mRYArZxocP5hBB1pEGB3bbJYiM-5acc5w',
            'appId' => 'http://demo.example.com'
        ])];
        $this->assertFalse($object->check(true));
        $_POST['u2f_authentication_response'] = '{ "signatureData": "AQAAAAQwRQIhAI6FSrMD3KUUtkpiP0jpIEakql-HNhwWFngyw553pS1CAiAKLjACPOhxzZXuZsVO8im-HStEcYGC50PKhsGp_SUAng==", "clientData": "eyAiY2hhbGxlbmdlIjogImZFbmM5b1Y3OUVhQmdLNUJvTkVSVTVnUEtNMlhHWVdyejRmVWpnYzBRN2ciLCAib3JpZ2luIjogImh0dHA6XC9cL2RlbW8uZXhhbXBsZS5jb20iLCAidHlwIjogIm5hdmlnYXRvci5pZC5nZXRBc3NlcnRpb24iIH0=", "keyHandle": "CTUayZo8hCBeC-sGQJChC0wW-bBg99bmOlGCgw8XGq4dLsxO3yWh9mRYArZxocP5hBB1pEGB3bbJYiM-5acc5w", "errorCode": 0 }';
        $this->assertTrue($object->check(true));
    }

    /**
     * Test listing of available backends.
     */
    public function testBackends()
    {
        $GLOBALS['cfg']['DBG']['simple2fa'] = true;
        $object = new TwoFactor('user');
        $backends = $object->getAllBackends();
        $this->assertCount(
            count($object->available) + 1,
            $backends
        );
        $GLOBALS['cfg']['DBG']['simple2fa'] = false;
    }
}
