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

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['email']) || !isset($input['password'])) {
            Response::error('Email and password are required', 400);
        }

        $user = $this->userModel->findByEmail($input['email']);

        if (!$user || !password_verify($input['password'], $user['password_hash'])) {
            Response::error('Invalid credentials', 401);
        }

        $token = JWT::encode(['id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);

        Response::success(['token' => $token, 'user' => $user], 'Login successful', 200);
    }

    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [
            'name' => ['required', ['min', 2]],
            'email' => ['required', 'email'],
            'password' => ['required', ['min', 6]]
        ];

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
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

        Response::success($user, 'Registration successful', 201);
    }
}
?>
