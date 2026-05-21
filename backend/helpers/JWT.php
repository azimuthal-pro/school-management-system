<?php

class JWT {
    private static $secret = 'your-secret-key-change-this-in-env';

    public static function encode($data, $expiration = 3600) {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payload = base64_encode(json_encode(array_merge($data, ['exp' => time() + $expiration])));
        $signature = base64_encode(hash_hmac('sha256', "$header.$payload", self::$secret, true));

        return "$header.$payload.$signature";
    }

    public static function decode($token) {
        list($header, $payload, $signature) = explode('.', $token);

        $valid_signature = base64_encode(hash_hmac('sha256', "$header.$payload", self::$secret, true));

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
