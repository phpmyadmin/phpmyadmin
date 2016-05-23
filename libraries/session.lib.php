<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * session library
 *
 * @package PhpMyAdmin
 */

/**
 * tries to secure session from hijacking and fixation
 * should be called before login and after successful login
 * (only required if sensitive information stored in session)
 *
 * @return void
 */
function PMA_secureSession()
{
    // prevent session fixation and XSS
    if (session_status() === PHP_SESSION_ACTIVE && ! defined('TESTSUITE')) {
        session_regenerate_id(true);
    }
    PMA_generateToken();
}


/**
 * Generates PMA_token session variable.
 *
 * @return void
 */
function PMA_generateToken()
{
    if (class_exists('phpseclib\Crypt\Random')) {
        $_SESSION[' PMA_token '] = bin2hex(phpseclib\Crypt\Random::string(16));
    } else {
        $_SESSION[' PMA_token '] = bin2hex(openssl_random_pseudo_bytes(16));
    }

    /**
     * Check if token is properly generated (the genration can fail, for example
     * due to missing /dev/random for openssl).
     */
    if (empty($_SESSION[' PMA_token '])) {
        PMA_fatalError(
            'Failed to generate random CSRF token!'
        );
    }
}
