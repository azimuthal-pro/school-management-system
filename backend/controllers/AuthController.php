<?php

require_once __DIR__ . '/../helpers/JWT.php';
require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../models/User.php';

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

        if (!isset($input['name']) || !isset($input['email']) || !isset($input['password'])) {
            Response::error('Name, email, and password are required', 400);
        }

        $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);

        $user = $this->userModel->create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password_hash' => $hashedPassword,
            'role' => 'student'
        ]);

        Response::success($user, 'Registration successful', 201);
    }
}
?>
