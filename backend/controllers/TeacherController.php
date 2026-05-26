<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/Teacher.php';
require_once __DIR__ . '/../helpers/Validation.php';

class TeacherController {
    private $teacherModel;
    private $user;

    public function __construct() {
        $this->teacherModel = new Teacher();
        $this->user = AuthMiddleware::verify();
        RoleMiddleware::checkRole($this->user, ['admin']);
    }

    public function getAll() {
        $teachers = $this->teacherModel->getAll();
        Response::success($teachers, 'Teachers retrieved successfully', 200);
    }

    public function getById($id) {
        $teacher = $this->teacherModel->getById($id);
        
        if (!$teacher) {
            Response::error('Teacher not found', 404);
        }

        Response::success($teacher, 'Teacher retrieved successfully', 200);
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [
            'user_id' => ['required', 'numeric'],
            'employee_number' => ['required', ['min', 1]]
        ];

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }

        $existing = $this->teacherModel->findByEmployeeNumber($input['employee_number']);
        if ($existing) {
            Response::error('Employee number already exists', 409);
        }

        $teacher = $this->teacherModel->create($input);

        if (!$teacher) {
            Response::error('Failed to create teacher', 500);
        }

        Response::success($teacher, 'Teacher created successfully', 201);
    }

    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [];
        if (isset($input['employee_number'])) {
            $rules['employee_number'] = ['required', ['min', 1]];
        }
        if (isset($input['phone'])) {
            $rules['phone'] = [['min', 7]];
        }

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }

        if (isset($input['employee_number'])) {
            $existing = $this->teacherModel->findByEmployeeNumber($input['employee_number']);
            if ($existing && $existing['id'] != $id) {
                Response::error('Employee number already exists', 409);
            }
        }

        $teacher = $this->teacherModel->update($id, $input);

        if (!$teacher) {
            Response::error('Failed to update teacher', 500);
        }

        Response::success($teacher, 'Teacher updated successfully', 200);
    }

    public function delete($id) {
        $result = $this->teacherModel->delete($id);

        if (!$result) {
            Response::error('Failed to delete teacher', 500);
        }

        Response::success(null, 'Teacher deleted successfully', 200);
    }
}
?>
