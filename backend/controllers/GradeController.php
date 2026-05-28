<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/Grade.php';
require_once __DIR__ . '/../helpers/Validation.php';

class GradeController {
    private $gradeModel;
    private $user;

    public function __construct() {
        $this->gradeModel = new Grade();
        $this->user = AuthMiddleware::getUser();
        RoleMiddleware::checkRole($this->user, ['admin', 'teacher']);
    }

    public function getAll() {
        $grades = $this->gradeModel->getAll();
        Response::success($grades, 'Grades retrieved successfully', 200);
    }

    public function getByStudent($studentId) {
        $grades = $this->gradeModel->getByStudent($studentId);
        Response::success($grades, 'Student grades retrieved successfully', 200);
    }

    public function getByClass($classId) {
        $grades = $this->gradeModel->getByClass($classId);
        Response::success($grades, 'Class grades retrieved successfully', 200);
    }

    public function getBySubject($subjectId) {
        $grades = $this->gradeModel->getBySubject($subjectId);
        Response::success($grades, 'Subject grades retrieved successfully', 200);
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [
            'student_id' => ['required', 'numeric'],
            'subject_id' => ['required', 'numeric'],
            'score' => ['required', 'numeric', ['between', [0, 100]]]
        ];

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }

        $input['teacher_id'] = $this->user['id'] ?? null;

        $grade = $this->gradeModel->create($input);

        if (!$grade) {
            Response::error('Failed to create grade record', 500);
        }

        Response::success($grade, 'Grade recorded successfully', 201);
    }

    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [];
        if (isset($input['score'])) {
            $rules['score'] = ['required', 'numeric', ['between', [0, 100]]];
        }
        if (isset($input['student_id'])) {
            $rules['student_id'] = ['required', 'numeric'];
        }
        if (isset($input['subject_id'])) {
            $rules['subject_id'] = ['required', 'numeric'];
        }
        if (isset($input['term'])) {
            $rules['term'] = [['min', 1]];
        }

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }

        $grade = $this->gradeModel->update($id, $input);

        if (!$grade) {
            Response::error('Failed to update grade', 500);
        }

        Response::success($grade, 'Grade updated successfully', 200);
    }

    public function delete($id) {
        $result = $this->gradeModel->delete($id);

        if (!$result) {
            Response::error('Failed to delete grade', 500);
        }

        Response::success(null, 'Grade deleted successfully', 200);
    }
}
?>
