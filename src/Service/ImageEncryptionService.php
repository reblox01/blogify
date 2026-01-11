<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ImageEncryptionService
{
    private string $key;
    private string $cipher = 'aes-256-cbc';

    public function __construct(ParameterBagInterface $params)
    {
        // Use a 32-byte key derived from APP_SECRET
        $secret = $params->get('kernel.secret');
        $this->key = hash('sha256', $secret, true);
    }

    public function encrypt(string $data): string
    {
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($data, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $this->key, OPENSSL_RAW_DATA);
        return base64_encode($iv . $hmac . $ciphertext_raw);
    }

    public function decrypt(string $ciphertext): ?string
    {
        $c = base64_decode($ciphertext);
        $ivlen = openssl_cipher_iv_length($this->cipher);
        $iv = substr($c, 0, $ivlen);
        $hmac = substr($c, $ivlen, 32);
        $ciphertext_raw = substr($c, $ivlen + 32);
        $original_plaintext = openssl_decrypt($ciphertext_raw, $this->cipher, $this->key, OPENSSL_RAW_DATA, $iv);
        $calcmac = hash_hmac('sha256', $ciphertext_raw, $this->key, OPENSSL_RAW_DATA);

        if (hash_equals($hmac, $calcmac)) {
            return $original_plaintext;
        }

        return null;
    }
}
