<?php

class JWT {
    private static $secret;

    private static function getSecret() {
        if (self::$secret === null) {
            self::$secret = getenv('JWT_SECRET');
            if (empty(self::$secret)) {
                error_log('CRITICAL: JWT_SECRET environment variable is not set.');
                throw new \RuntimeException('JWT_SECRET environment variable is required.');
            }
        }
        return self::$secret;
    }

    /**
     * Base64Url encode — URL-safe alternative to base64_encode.
     */
    private static function base64urlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64Url decode.
     */
    private static function base64urlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    public static function encode($payload, $expiration = 3600) {
        $secret = self::getSecret();
        $issuedAt = time();

        $header = self::base64urlEncode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $data = array_merge($payload, [
            'iat' => $issuedAt,
            'exp' => $issuedAt + $expiration,
        ]);
        $payloadEncoded = self::base64urlEncode(json_encode($data));
        $signature = self::base64urlEncode(
            hash_hmac('sha256', "$header.$payloadEncoded", $secret, true)
        );

        return "$header.$payloadEncoded.$signature";
    }

    public static function decode($token) {
        $secret = self::getSecret();
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payloadEncoded, $signature] = $parts;

        $expectedSignature = self::base64urlEncode(
            hash_hmac('sha256', "$header.$payloadEncoded", $secret, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }

        $decoded = json_decode(self::base64urlDecode($payloadEncoded), true);

        if (!$decoded || !isset($decoded['exp']) || $decoded['exp'] < time()) {
            return null;
        }

        return $decoded;
    }
}
?>
