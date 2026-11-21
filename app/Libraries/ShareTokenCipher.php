<?php
namespace App\Libraries;

/**
 * Stores share bearer tokens in a text-safe, reversible encrypted format.
 * Base64 prevents binary ciphertext from being damaged by TEXT/UTF-8 columns.
 */
class ShareTokenCipher
{
    public static function encrypt(string $token): string
    {
        return base64_encode(service('encrypter')->encrypt($token));
    }

    public static function decrypt(string $stored): string
    {
        $decoded = base64_decode($stored, true);
        if ($decoded !== false) {
            try {
                $token = service('encrypter')->decrypt($decoded);
                if (is_string($token) && preg_match('/^[a-f0-9]{64}$/', $token)) {
                    return $token;
                }
            } catch (\Throwable $e) {
                // Try legacy raw ciphertext below.
            }
        }

        // Backward compatibility with rows written before Base64 storage.
        $token = service('encrypter')->decrypt($stored);
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            throw new \RuntimeException('Saved share token is invalid.');
        }
        return $token;
    }
}
