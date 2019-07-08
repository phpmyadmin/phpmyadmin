<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Second authentication factor handling
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Plugins\TwoFactor;

use PhpMyAdmin\Plugins\TwoFactorPlugin;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\TwoFactor;
use Samyoul\U2F\U2FServer\U2FException;
use Samyoul\U2F\U2FServer\U2FServer;
use stdClass;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * Hardware key based two-factor authentication
 *
 * Supports FIDO U2F tokens
 *
 * @package PhpMyAdmin
 */
class Key extends TwoFactorPlugin
{
    /**
     * @var string
     */
    public static $id = 'key';

    /**
     * Creates object
     *
     * @param TwoFactor $twofactor TwoFactor instance
     */
    public function __construct(TwoFactor $twofactor)
    {
        parent::__construct($twofactor);
        if (! isset($this->_twofactor->config['settings']['registrations'])) {
            $this->_twofactor->config['settings']['registrations'] = [];
        }
    }

    /**
     * Returns array of U2F registration objects
     *
     * @return array
     */
    public function getRegistrations()
    {
        $result = [];
        foreach ($this->_twofactor->config['settings']['registrations'] as $index => $data) {
            $reg = new stdClass();
            $reg->keyHandle = $data['keyHandle'];
            $reg->publicKey = $data['publicKey'];
            $reg->certificate = $data['certificate'];
            $reg->counter = $data['counter'];
            $reg->index = $index;
            $result[] = $reg;
        }
        return $result;
    }

    /**
     * Checks authentication, returns true on success
     *
     * @return boolean
     */
    public function check()
    {
        $this->_provided = false;
        if (! isset($_POST['u2f_authentication_response']) || ! isset($_SESSION['authenticationRequest'])) {
            return false;
        }
        $this->_provided = true;
        try {
            $response = json_decode($_POST['u2f_authentication_response']);
            if ($response === null) {
                return false;
            }
            $authentication = U2FServer::authenticate(
                $_SESSION['authenticationRequest'],
                $this->getRegistrations(),
                $response
            );
            $this->_twofactor->config['settings']['registrations'][$authentication->index]['counter'] = $authentication->counter;
            $this->_twofactor->save();
            return true;
        } catch (U2FException $e) {
            $this->_message = $e->getMessage();
            return false;
        }
    }

    /**
     * Loads needed javascripts into the page
     *
     * @return void
     */
    public function loadScripts()
    {
        $response = Response::getInstance();
        $scripts = $response->getHeader()->getScripts();
        $scripts->addFile('vendor/u2f-api-polyfill.js');
        $scripts->addFile('u2f.js');
    }

    /**
     * Renders user interface to enter two-factor authentication
     *
     * @return string HTML code
     */
    public function render()
    {
        $request = U2FServer::makeAuthentication(
            $this->getRegistrations(),
            $this->getAppId(true)
        );
        $_SESSION['authenticationRequest'] = $request;
        $this->loadScripts();
        return $this->template->render('login/twofactor/key', [
            'request' => json_encode($request),
            'is_https' => $GLOBALS['PMA_Config']->isHttps(),
        ]);
    }

    /**
     * Renders user interface to configure two-factor authentication
     *
     * @return string HTML code
     * @throws U2FException
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function setup()
    {
        $registrationData = U2FServer::makeRegistration(
            $this->getAppId(true),
            $this->getRegistrations()
        );
        $_SESSION['registrationRequest'] = $registrationData['request'];

        $this->loadScripts();
        return $this->template->render('login/twofactor/key_configure', [
            'request' => json_encode($registrationData['request']),
            'signatures' => json_encode($registrationData['signatures']),
            'is_https' => $GLOBALS['PMA_Config']->isHttps(),
        ]);
    }

    /**
     * Performs backend configuration
     *
     * @return boolean
     */
    public function configure()
    {
        $this->_provided = false;
        if (! isset($_POST['u2f_registration_response']) || ! isset($_SESSION['registrationRequest'])) {
            return false;
        }
        $this->_provided = true;
        try {
            $response = json_decode($_POST['u2f_registration_response']);
            if ($response === null) {
                return false;
            }
            $registration = U2FServer::register(
                $_SESSION['registrationRequest'],
                $response
            );
            $this->_twofactor->config['settings']['registrations'][] = [
                'keyHandle' => $registration->getKeyHandle(),
                'publicKey' => $registration->getPublicKey(),
                'certificate' => $registration->getCertificate(),
                'counter' => $registration->getCounter(),
            ];
            return true;
        } catch (U2FException $e) {
            $this->_message = $e->getMessage();
            return false;
        }
    }

    /**
     * Get user visible name
     *
     * @return string
     */
    public static function getName()
    {
        return __('Hardware Security Key (FIDO U2F)');
    }

    /**
     * Get user visible description
     *
     * @return string
     */
    public static function getDescription()
    {
        return __('Provides authentication using hardware security tokens supporting FIDO U2F.');
    }
}
