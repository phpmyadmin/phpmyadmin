<?php

namespace PhpMyAdmin\Crypto;

use Exception;
use phpseclib\Crypt\AES;
use phpseclib\Crypt\Random;

final class Crypto
{
    /** @var bool */
    private $hasRandomBytesSupport;

    /** @var bool */
    private $hasSodiumSupport;

    /**
     * @param bool $forceFallback Force the usage of the fallback functions.
     */
    public function __construct($forceFallback = false)
    {
        $this->hasRandomBytesSupport = ! $forceFallback && is_callable('random_bytes');
        $this->hasSodiumSupport = ! $forceFallback
            && $this->hasRandomBytesSupport
            && is_callable('sodium_crypto_secretbox')
            && is_callable('sodium_crypto_secretbox_open')
            && defined('SODIUM_CRYPTO_SECRETBOX_NONCEBYTES')
            && defined('SODIUM_CRYPTO_SECRETBOX_KEYBYTES');
    }

    /**
     * @param string $plaintext
     *
     * @return string
     */
    public function encrypt($plaintext)
    {
        if ($this->hasSodiumSupport) {
            return $this->encryptWithSodium($plaintext);
        }

        return $this->encryptWithPhpseclib($plaintext);
    }

    /**
     * @param string $ciphertext
     *
     * @return string
     */
    public function decrypt($ciphertext)
    {
        if ($this->hasSodiumSupport) {
            return $this->decryptWithSodium($ciphertext);
        }

        return $this->decryptWithPhpseclib($ciphertext);
    }

    /**
     * @return string
     */
    private function getEncryptionKey()
    {
        global $PMA_Config;

        $keyLength = $this->hasSodiumSupport ? SODIUM_CRYPTO_SECRETBOX_KEYBYTES : 32;

        $key = $PMA_Config->get('URLQueryEncryptionSecretKey');
        if (is_string($key) && mb_strlen($key, '8bit') === $keyLength) {
            return $key;
        }

        $key = isset($_SESSION['URLQueryEncryptionSecretKey']) ? $_SESSION['URLQueryEncryptionSecretKey'] : null;
        if (is_string($key) && mb_strlen($key, '8bit') === $keyLength) {
            return $key;
        }

        $key = $this->hasRandomBytesSupport ? random_bytes($keyLength) : Random::string($keyLength);
        $_SESSION['URLQueryEncryptionSecretKey'] = $key;

        return $key;
    }

    /**
     * @param string $plaintext
     *
     * @return string
     */
    private function encryptWithPhpseclib($plaintext)
    {
        $key = $this->getEncryptionKey();
        $cipher = new AES(AES::MODE_CBC);
        $iv = $this->hasRandomBytesSupport ? random_bytes(16) : Random::string(16);
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
    private function decryptWithPhpseclib($encrypted)
    {
        $key = $this->getEncryptionKey();
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
     * @param string $plaintext
     *
     * @return string
     */
    private function encryptWithSodium($plaintext)
    {
        $key = $this->getEncryptionKey();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $ciphertext = sodium_crypto_secretbox($plaintext, $nonce, $key);

        return $nonce . $ciphertext;
    }

    /**
     * @param string $encrypted
     *
     * @return string|null
     */
    private function decryptWithSodium($encrypted)
    {
        $key = $this->getEncryptionKey();
        $nonce = mb_substr($encrypted, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($encrypted, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');
        try {
            $decrypted = sodium_crypto_secretbox_open($ciphertext, $nonce, $key);
        } catch (Exception $e) {
            return null;
        }

        if ($decrypted === false) {
            return null;
        }

        return $decrypted;
    }
}
