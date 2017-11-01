<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Second authentication factor handling
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins\SecondFactor;

use PhpMyAdmin\Core;
use PhpMyAdmin\Response;
use PhpMyAdmin\SecondFactor;
use PhpMyAdmin\Template;
use PhpMyAdmin\Plugins\SecondFactorPlugin;
use Samyoul\U2F\U2FServer\U2FServer;
use Samyoul\U2F\U2FServer\U2FException;

/**
 * Hardware key based second factor
 *
 * Supports FIDO U2F tokens
 */
class Key extends SecondFactorPlugin
{
    /**
     * @var string
     */
    public static $id = 'key';

    /**
     * Creates object
     *
     * @param SecondFactor $second SecondFactor instance
     */
    public function __construct(SecondFactor $second)
    {
        parent::__construct($second);
        if (!isset($this->_second->config['settings']['registrations'])) {
            $this->_second->config['settings']['registrations'] = [];
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
        foreach ($this->_second->config['settings']['registrations'] as $index => $data) {
            $reg = new \StdClass;
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
     * Return FIDO U2F Application ID
     *
     * It has to be URL with hostname only, having https protocol
     *
     * @return string
     */
    public function getAppId()
    {
        global $PMA_Config;

        $url = $PMA_Config->get('PmaAbsoluteUri');
        if (!empty($url)) {
            $parsed = parse_url($url);
            if (isset($parsed['scheme']) && isset($parsed['host'])) {
                return $parsed['scheme'] . '://' . $parsed['host'] . (!empty($parsed['port']) ? ':' . $parsed['port'] : '');
            }
        }
        return ($PMA_Config->isHttps() ? 'https://' : 'http://') . Core::getenv('HTTP_HOST');
    }

    /**
     * Checks authentication, returns true on success
     *
     * @return boolean
     */
    public function check()
    {
        $this->_provided = false;
        if (!isset($_POST['u2f_authentication_response']) || !isset($_SESSION['authenticationRequest'])) {
            return false;
        }
        $this->_provided = true;
        try {
            $response = json_decode($_POST['u2f_authentication_response']);
            if (is_null($response)) {
                return false;
            }
            $authentication = U2FServer::authenticate(
                $_SESSION['authenticationRequest'],
                $this->getRegistrations(),
                $response
            );
            $this->_second->config['settings']['registrations'][$authentication->index]['counter'] = $authentication->counter;
            $this->_second->save();
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
        $scripts->addFile('vendor/u2f-api.js');
        $scripts->addFile('u2f.js');
    }

    /**
     * Renders user interface to enter second factor
     *
     * @return string HTML code
     */
    public function render()
    {
        $request = U2FServer::makeAuthentication(
            $this->getRegistrations(),
            $this->getAppId()
        );
        $_SESSION['authenticationRequest'] = $request;
        $this->loadScripts();
        return Template::get('login/second/key')->render([
            'request' => json_encode($request),
        ]);
    }

    /**
     * Renders user interface to configure second factor
     *
     * @return string HTML code
     */
    public function setup()
    {
        $registrationData = U2FServer::makeRegistration(
            $this->getAppId(),
            $this->getRegistrations()
        );
        $_SESSION['registrationRequest'] = $registrationData['request'];

        $this->loadScripts();
        return Template::get('login/second/key_configure')->render([
            'request' => json_encode($registrationData['request']),
            'signatures' => json_encode($registrationData['signatures']),
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
            if (is_null($response)) {
                return false;
            }
            $registration = U2FServer::register(
                $_SESSION['registrationRequest'], $response
            );
            $this->_second->config['settings']['registrations'][] = [
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
        return __('Security key');
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
