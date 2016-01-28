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
    // (better to use session_status() if available)
    if ((PHP_VERSION_ID >= 50400 && session_status() === PHP_SESSION_ACTIVE)
        || (PHP_VERSION_ID < 50400 && session_id() !== '')
    ) {
        session_regenerate_id(true);
    }
    $_SESSION[' PMA_token '] = bin2hex(phpseclib\Crypt\Random::string(16));
}
