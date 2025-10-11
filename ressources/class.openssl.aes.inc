<?php
class OpenSSLAES{
    const METHOD = 'aes-256-cbc';

    public function __construct(){
    }

    public function encrypt($message, $key)
    {
        if (mb_strlen($key, '8bit') !== 32) {
            echo "Needs a 256-bit key!";
            return false;
        }
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = openssl_random_pseudo_bytes($ivsize);

        $ciphertext = openssl_encrypt(
            $message,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        return base64_encode($iv . $ciphertext);
    }

    public function decrypt($message, $key)
    {
        if (mb_strlen($key, '8bit') !== 32) {
            echo "Needs a 256-bit key!";
            return false;
        }
        $message=base64_decode($message);
        $ivsize = openssl_cipher_iv_length(self::METHOD);
        $iv = mb_substr($message, 0, $ivsize, '8bit');
        $ciphertext = mb_substr($message, $ivsize, null, '8bit');

        return openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
    }
}
?>