<?php

require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../helpers/Response.php';

class AuthMiddleware {
    public static function verify() {
        $headers = getallheaders();
        $token = null;

        if (isset($headers['Authorization'])) {
            $auth = $headers['Authorization'];
            if (preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
                $token = $matches[1];
            }
        }

        if (!$token) {
            Response::error('Unauthorized: No token provided', 401);
        }

        $decoded = JWT::decode($token);

        if (!$decoded) {
            Response::error('Unauthorized: Invalid token', 401);
        }

        return $decoded;
    }
}
?>
