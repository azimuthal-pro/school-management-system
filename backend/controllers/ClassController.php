<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/SchoolClass.php';
require_once __DIR__ . '/../helpers/Validation.php';

class ClassController {
    private $classModel;
    private $user;

    public function __construct() {
        $this->classModel = new SchoolClass();
        $this->user = AuthMiddleware::verify();
        RoleMiddleware::checkRole($this->user, ['admin']);
    }

    public function getAll() {
        $classes = $this->classModel->getAll();
        Response::success($classes, 'Classes retrieved successfully', 200);
    }

    public function getById($id) {
        $class = $this->classModel->getById($id);
        
        if (!$class) {
            Response::error('Class not found', 404);
        }

        Response::success($class, 'Class retrieved successfully', 200);
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [
            'name' => ['required', ['min', 2]],
            'year' => ['numeric']
        ];

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }

        $class = $this->classModel->create($input);

        if (!$class) {
            Response::error('Failed to create class', 500);
        }

        Response::success($class, 'Class created successfully', 201);
    }

    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [];
        if (isset($input['name'])) {
            $rules['name'] = ['required', ['min', 2]];
        }
        if (isset($input['year'])) {
            $rules['year'] = ['numeric'];
        }
        if (isset($input['section'])) {
            $rules['section'] = [['min', 1]];
        }

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }

        $class = $this->classModel->update($id, $input);

        if (!$class) {
            Response::error('Failed to update class', 500);
        }

        Response::success($class, 'Class updated successfully', 200);
    }

    public function delete($id) {
        $result = $this->classModel->delete($id);

        if (!$result) {
            Response::error('Failed to delete class', 500);
        }

        Response::success(null, 'Class deleted successfully', 200);
    }

    public function getStudents($id) {
        $students = $this->classModel->getStudents($id);
        Response::success($students, 'Class students retrieved successfully', 200);
    }
}
?>
