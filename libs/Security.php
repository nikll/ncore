<?

class Security {
    private static function pkcs5Pad($text, $blockSize) {
        $pad = $blockSize - (strlen($text) % $blockSize);
        return $text.str_repeat(chr($pad), $pad);
    }

    private static function pkcs5UnPad($decrypted) {
        return substr($decrypted, 0, -ord($decrypted[strlen($decrypted) - 1]));
    }

    public static function encrypt($data, $key) {
        return base64_encode(mcrypt_encrypt(
            MCRYPT_RIJNDAEL_128,
            $key,
            self::pkcs5Pad($data, mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB)),
            MCRYPT_MODE_ECB
        ));
    }

    public static function decrypt($data, $key) {
        return self::pkcs5UnPad(mcrypt_decrypt(
            MCRYPT_RIJNDAEL_128,
            $key,
            base64_decode($data),
            MCRYPT_MODE_ECB
        ));
    }
}

