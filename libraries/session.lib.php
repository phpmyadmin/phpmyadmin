<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * session library
 *
 * @package PhpMyAdmin
 */

use PhpMyAdmin\Core;
use PhpMyAdmin\Util;

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
    $_SESSION[' PMA_token '] = Util::generateRandom(16);

    /**
     * Check if token is properly generated (the generation can fail, for example
     * due to missing /dev/random for openssl).
     */
    if (empty($_SESSION[' PMA_token '])) {
        Core::fatalError(
            'Failed to generate random CSRF token!'
        );
    }
}
