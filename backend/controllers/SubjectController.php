<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/Subject.php';
require_once __DIR__ . '/../helpers/Validation.php';

class SubjectController {
    private $subjectModel;
    private $user;

    public function __construct() {
        $this->subjectModel = new Subject();
        $this->user = AuthMiddleware::verify();
        RoleMiddleware::checkRole($this->user, ['admin', 'teacher']);
    }

    public function getAll() {
        $subjects = $this->subjectModel->getAll();
        Response::success($subjects, 'Subjects retrieved successfully', 200);
    }

    public function getById($id) {
        $subject = $this->subjectModel->getById($id);
        
        if (!$subject) {
            Response::error('Subject not found', 404);
        }

        Response::success($subject, 'Subject retrieved successfully', 200);
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [
            'name' => ['required', ['min', 2]],
            'code' => ['required', ['min', 1]]
        ];

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }

        $existing = $this->subjectModel->findByCode($input['code']);
        if ($existing) {
            Response::error('Subject code already exists', 409);
        }

        $subject = $this->subjectModel->create($input);

        if (!$subject) {
            Response::error('Failed to create subject', 500);
        }

        Response::success($subject, 'Subject created successfully', 201);
    }

    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [];
        if (isset($input['name'])) {
            $rules['name'] = ['required', ['min', 2]];
        }
        if (isset($input['code'])) {
            $rules['code'] = ['required', ['min', 1]];
        }
        if (isset($input['teacher_id'])) {
            $rules['teacher_id'] = ['numeric'];
        }

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }

        if (isset($input['code'])) {
            $existing = $this->subjectModel->findByCode($input['code']);
            if ($existing && $existing['id'] != $id) {
                Response::error('Subject code already exists', 409);
            }
        }

        $subject = $this->subjectModel->update($id, $input);

        if (!$subject) {
            Response::error('Failed to update subject', 500);
        }

        Response::success($subject, 'Subject updated successfully', 200);
    }

    public function delete($id) {
        $result = $this->subjectModel->delete($id);

        if (!$result) {
            Response::error('Failed to delete subject', 500);
        }

        Response::success(null, 'Subject deleted successfully', 200);
    }

    public function getByTeacher($teacherId) {
        $subjects = $this->subjectModel->getByTeacher($teacherId);
        Response::success($subjects, 'Teacher subjects retrieved successfully', 200);
    }
}
?>
