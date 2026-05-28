<?php

require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Validation.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    private function checkRateLimit($key, $maxAttempts = 5, $windowSeconds = 300) {
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($key) . '.json';
        
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            if ($data && time() - $data['first_attempt'] < $windowSeconds) {
                if ($data['attempts'] >= $maxAttempts) {
                    return false;
                }
            }
        }
        
        // Reset or initialize
        $data = ['first_attempt' => time(), 'attempts' => 1];
        file_put_contents($cacheFile, json_encode($data));
        return true;
    }

    private function incrementRateLimit($key) {
        $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($key) . '.json';
        if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
            $data['attempts'] = ($data['attempts'] ?? 0) + 1;
            file_put_contents($cacheFile, json_encode($data));
        }
    }

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);

        // Validate required fields first before rate limiting
        if (!isset($input['email']) || !isset($input['password'])) {
            Response::error('Email and password are required', 400);
        }

        $email = $input['email'];

        // Rate limiting by IP and email - only after basic validation
        $rateKey = $_SERVER['REMOTE_ADDR'] . ':' . $email;
        if (!$this->checkRateLimit($rateKey, 5, 300)) {
            Response::error('Too many login attempts. Please try again later.', 429);
        }

        $user = $this->userModel->findByEmail($input['email']);

        if (!$user || !password_verify($input['password'], $user['password_hash'])) {
            $this->incrementRateLimit($rateKey);
            Response::error('Invalid credentials', 401);
        }

        // Clear rate limit on successful login
        @unlink(sys_get_temp_dir() . '/rate_limit_' . md5($rateKey) . '.json');

        $token = JWT::encode(['id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);

        // Remove sensitive data before sending to client
        unset($user['password_hash']);

        Response::success(['token' => $token, 'user' => $user], 'Login successful', 200);
    }

    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);

        // Validate required fields first before rate limiting
        $rules = [
            'name' => ['required', ['min', 2]],
            'email' => ['required', 'email'],
            'password' => ['required', ['min', 6]]
        ];

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            // Don't count validation errors against rate limit - these are client mistakes
            Response::error(['validation' => $errors], 422);
        }

        // Rate limiting registration attempts - only after validation passes
        $rateKey = $_SERVER['REMOTE_ADDR'] . ':register';
        if (!$this->checkRateLimit($rateKey, 3, 3600)) {
            Response::error('Too many registration attempts. Please try again later.', 429);
        }

        // check duplicate email
        $existing = $this->userModel->findByEmail($input['email']);
        if ($existing) {
            Response::error('Email already registered', 409);
        }

        $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);

        $user = $this->userModel->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password_hash' => $hashedPassword,
            'role' => $input['role'] ?? 'student'
        ]);

        if (!$user) {
            Response::error('Failed to create user', 500);
        }

        // Clear rate limit on successful registration
        @unlink(sys_get_temp_dir() . '/rate_limit_' . md5($rateKey) . '.json');

        Response::success($user, 'Registration successful', 201);
    }
}
?>
