<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/Student.php';

class StudentController {
    private $studentModel;
    private $user;

    public function __construct() {
        $this->studentModel = new Student();
        $this->user = AuthMiddleware::verify();
        RoleMiddleware::checkRole($this->user, ['admin', 'teacher']);
    }

    public function getAll() {
        $students = $this->studentModel->getAll();
        Response::success($students, 'Students retrieved successfully', 200);
    }

    public function getById($id) {
        $student = $this->studentModel->getById($id);
        
        if (!$student) {
            Response::error('Student not found', 404);
        }

        Response::success($student, 'Student retrieved successfully', 200);
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!isset($input['user_id']) || !isset($input['student_number']) || !isset($input['class_id'])) {
            Response::error('user_id, student_number, and class_id are required', 400);
        }

        $student = $this->studentModel->create($input);

        if (!$student) {
            Response::error('Failed to create student', 500);
        }

        Response::success($student, 'Student created successfully', 201);
    }

    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $student = $this->studentModel->update($id, $input);

        if (!$student) {
            Response::error('Failed to update student', 500);
        }

        Response::success($student, 'Student updated successfully', 200);
    }

    public function delete($id) {
        $result = $this->studentModel->delete($id);

        if (!$result) {
            Response::error('Failed to delete student', 500);
        }

        Response::success(null, 'Student deleted successfully', 200);
    }
}
?>
