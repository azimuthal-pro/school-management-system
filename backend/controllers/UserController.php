<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Validation.php';
require_once __DIR__ . '/../config/database.php';

class UserController {
    private $userModel;
    private $authUser;

    public function __construct() {
        $this->userModel = new User();
        $this->authUser = AuthMiddleware::getUser();
        RoleMiddleware::checkRole($this->authUser, ['admin']);
    }

    public function getAll() {
        // Get all users without password hashes
        $db = Database::getInstance()->connect();
        $stmt = $db->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = $result->fetch_all(MYSQLI_ASSOC);
        Response::success($users, 'Users retrieved successfully', 200);
    }

    public function getById($id) {
        $db = Database::getInstance()->connect();
        $stmt = $db->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $user = $result->fetch_assoc();
        
        if (!$user) {
            Response::error('User not found', 404);
        }
        
        Response::success($user, 'User retrieved successfully', 200);
    }

    public function updateRole($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $rules = [
            'role' => ['required', ['in', ['admin', 'teacher', 'student']]]
        ];
        
        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }
        
        $db = Database::getInstance()->connect();
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $input['role'], $id);
        
        if ($stmt->execute()) {
            Response::success(null, 'User role updated successfully', 200);
        } else {
            Response::error('Failed to update user role', 500);
        }
    }

    public function delete($id) {
        $db = Database::getInstance()->connect();
        
        // Prevent self-deletion
        if ($id == $this->authUser['id']) {
            Response::error('Cannot delete your own account', 400);
        }
        
        $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            Response::success(null, 'User deleted successfully', 200);
        } else {
            Response::error('Failed to delete user', 500);
        }
    }
}
?>