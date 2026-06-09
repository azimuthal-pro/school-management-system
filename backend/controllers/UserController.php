<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Validation.php';

class UserController {
    private $userModel;
    private $authUser;

    public function __construct() {
        $this->userModel = new User();
        $this->authUser = AuthMiddleware::getUser();
        RoleMiddleware::checkRole($this->authUser, ['admin']);
    }

    public function getAll() {
        $users = $this->userModel->getAll();
        Response::success($users, 'Users retrieved successfully', 200);
    }

    public function getById($id) {
        $user = $this->userModel->getById($id);

        if (!$user) {
            Response::error('User not found', 404);
        }

        Response::success($user, 'User retrieved successfully', 200);
    }

    public function updateRole($id) {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [
            'role' => ['required', ['in', ['admin', 'teacher', 'student']]],
        ];

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }

        if ($this->userModel->updateRole($id, $input['role'])) {
            Response::success(null, 'User role updated successfully', 200);
        }

        Response::error('Failed to update user role', 500);
    }

    public function delete($id) {
        // Prevent self-deletion
        if ((int) $id === (int) ($this->authUser['id'] ?? 0)) {
            Response::error('Cannot delete your own account', 400);
        }

        if ($this->userModel->delete($id)) {
            Response::success(null, 'User deleted successfully', 200);
        }

        Response::error('Failed to delete user', 500);
    }
}