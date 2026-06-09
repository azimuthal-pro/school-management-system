<?php

class Response {
    private static function setCorsHeaders() {
        $origin = getenv('FRONTEND_URL') ?: 'http://localhost:3000';
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        header('Access-Control-Max-Age: 86400');
    }

    /**
     * Send a success JSON response.
     *
     * @param mixed $data    The response payload.
     * @param string $message A human-readable success message.
     * @param int $code       HTTP status code.
     */
    public static function success($data, $message = 'Success', $code = 200) {
        self::setCorsHeaders();
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send an error JSON response.
     *
     * @param string $message A human-readable error message.
     * @param int $code        HTTP status code.
     * @param array|null $errors Optional structured error details (e.g. validation errors).
     */
    public static function error($message = 'Error', $code = 400, $errors = null) {
        self::setCorsHeaders();
        http_response_code($code);
        header('Content-Type: application/json');
        $body = [
            'success' => false,
            'message' => $message,
            'data' => null,
        ];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        echo json_encode($body, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
?>
