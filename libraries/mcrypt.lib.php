<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Initialization
 * Store the initialization vector because it will be needed for
 * further decryption. I don't think necessary to have one iv
 * per server so I don't put the server number in the cookie name.
 */
if (empty($_COOKIE['pma_mcrypt_iv'])
 || false === ($iv = base64_decode($_COOKIE['pma_mcrypt_iv']))) {
    srand((double) microtime() * 1000000);
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_BLOWFISH, MCRYPT_MODE_CBC), MCRYPT_RAND);
    PMA_setCookie('pma_mcrypt_iv', base64_encode($iv));
}

/**
 * String padding
 *
 * @param   string  input string
 * @param   integer length of the result
 * @param   string  the filling string
 * @param   integer padding mode
 *
 * @return  string  the padded string
 *
 * @access  public
 */
function full_str_pad($input, $pad_length, $pad_string = '', $pad_type = 0) {
    $str = '';
    $length = $pad_length - strlen($input);
    if ($length > 0) { // str_repeat doesn't like negatives
        if ($pad_type == STR_PAD_RIGHT) { // STR_PAD_RIGHT == 1
            $str = $input.str_repeat($pad_string, $length);
        } elseif ($pad_type == STR_PAD_BOTH) { // STR_PAD_BOTH == 2
            $str = str_repeat($pad_string, floor($length/2));
            $str .= $input;
            $str .= str_repeat($pad_string, ceil($length/2));
        } else { // defaults to STR_PAD_LEFT == 0
            $str = str_repeat($pad_string, $length).$input;
        }
    } else { // if $length is negative or zero we don't need to do anything
        $str = $input;
    }
    return $str;
}
/**
 * Encryption using blowfish algorithm (mcrypt)
 *
 * @param   string  original data
 * @param   string  the secret
 *
 * @return  string  the encrypted result
 *
 * @access  public
 *
 * @author  lem9
 */
function PMA_blowfish_encrypt($data, $secret) {
    global $iv;
    // Seems we don't need the padding. Anyway if we need it,
    // we would have to replace 8 by the next 8-byte boundary.
    //$data = full_str_pad($data, 8, "\0", STR_PAD_RIGHT);
    return base64_encode(mcrypt_encrypt(MCRYPT_BLOWFISH, $secret, $data, MCRYPT_MODE_CBC, $iv));
}

/**
 * Decryption using blowfish algorithm (mcrypt)
 *
 * @param   string  encrypted data
 * @param   string  the secret
 *
 * @return  string  original data
 *
 * @access  public
 *
 * @author  lem9
 */
function PMA_blowfish_decrypt($encdata, $secret) {
    global $iv;
    return trim(mcrypt_decrypt(MCRYPT_BLOWFISH, $secret, base64_decode($encdata), MCRYPT_MODE_CBC, $iv));
}

?>
