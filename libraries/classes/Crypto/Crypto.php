<?php

namespace PhpMyAdmin\Crypto;

use phpseclib\Crypt\AES;
use phpseclib\Crypt\Random;

final class Crypto
{
    /**
     * @param string $plaintext
     *
     * @return string
     */
    public static function encrypt($plaintext)
    {
        return self::encryptWithPhpseclib($plaintext);
    }

    /**
     * @param string $ciphertext
     *
     * @return string
     */
    public static function decrypt($ciphertext)
    {
        return self::decryptWithPhpseclib($ciphertext);
    }

    /**
     * @param string $plaintext
     *
     * @return string
     */
    private static function encryptWithPhpseclib($plaintext)
    {
        $key = self::getEncryptionKey();
        $cipher = new AES(AES::MODE_CBC);
        $iv = Random::string(16);
        $cipher->setIV($iv);
        $cipher->setKey($key);
        $ciphertext = $cipher->encrypt($plaintext);
        $hmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);

        return $hmac . $iv . $ciphertext;
    }

    /**
     * @param string $encrypted
     *
     * @return string|null
     */
    private static function decryptWithPhpseclib($encrypted)
    {
        $key = self::getEncryptionKey();
        $hmac = mb_substr($encrypted, 0, 32, '8bit');
        $iv = mb_substr($encrypted, 32, 16, '8bit');
        $ciphertext = mb_substr($encrypted, 48, null, '8bit');
        $calculatedHmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        if (! hash_equals($hmac, $calculatedHmac)) {
            return null;
        }

        $cipher = new AES(AES::MODE_CBC);
        $cipher->setIV($iv);
        $cipher->setKey($key);

        return $cipher->decrypt($ciphertext);
    }

    /**
     * @return string
     */
    private static function getEncryptionKey()
    {
        global $PMA_Config;

        return $_SESSION[' HMAC_secret '] . $PMA_Config->get('blowfish_secret');
    }
}
