<?php

class JWT {
    private static $secret;

    private static function getSecret() {
        if (self::$secret === null) {
            self::$secret = getenv('JWT_SECRET');
            if (empty(self::$secret)) {
                // Fallback: generate a random secret if not set (warn in logs)
                error_log('WARNING: JWT_SECRET not set in environment. Using insecure default.');
                self::$secret = 'your-secret-key-change-this-in-env';
            }
        }
        return self::$secret;
    }

    public static function encode($data, $expiration = 3600) {
        $secret = self::getSecret();
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode(array_merge($data, ['exp' => time() + $expiration])));
        $signature = base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true));

        return "$header.$payload.$signature";
    }

    public static function decode($token) {
        $secret = self::getSecret();
        list($header, $payload, $signature) = explode('.', $token);

        $valid_signature = base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true));

        if ($signature !== $valid_signature) {
            return null;
        }

        $decoded = json_decode(base64_decode($payload), true);

        if (isset($decoded['exp']) && $decoded['exp'] < time()) {
            return null;
        }

        return $decoded;
    }
}
?>
