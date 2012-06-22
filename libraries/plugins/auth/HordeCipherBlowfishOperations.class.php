<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Auxiliary functions for cookie authentication
 *
 * @package    PhpMyAdmin-Auth
 * @subpackage Cookie
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/* Get the Horde_Cipher_blowfish class */
require_once './libraries/blowfish.php';

/**
 * The HordeCipherBlowfishOperations provides encrypt and decrypt functions
 * using the Horde_Cipher_blowfish class
 *
 * @package PhpMyAdmin-Auth
 */
class HordeCipherBlowfishOperations
{
    /**
     * Encryption using blowfish algorithm
     *
     * @param string $data   original data
     * @param string $secret the secret
     *
     * @return string  the encrypted result
     */
    public static function blowfishEncrypt($data, $secret)
    {
        $pma_cipher = new Horde_Cipher_blowfish;
        $encrypt = '';

        $mod = strlen($data) % 8;

        if ($mod > 0) {
            $data .= str_repeat("\0", 8 - $mod);
        }

        foreach (str_split($data, 8) as $chunk) {
            $encrypt .= $pma_cipher->encryptBlock($chunk, $secret);
        }
        return base64_encode($encrypt);
    }

    /**
     * Decryption using blowfish algorithm
     *
     * @param string $encdata encrypted data
     * @param string $secret  the secret
     *
     * @return string  original data
     */
    public static function blowfishDecrypt($encdata, $secret)
    {
        $pma_cipher = new Horde_Cipher_blowfish;
        $decrypt = '';
        $data = base64_decode($encdata);

        foreach (str_split($data, 8) as $chunk) {
            $decrypt .= $pma_cipher->decryptBlock($chunk, $secret);
        }
        return trim($decrypt);
    }
}
?>
