<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/Grade.php';

class GradeController {
    private $gradeModel;
    private $user;

    public function __construct() {
        $this->gradeModel = new Grade();
        $this->user = AuthMiddleware::verify();
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

        if (!isset($input['student_id']) || !isset($input['subject_id']) || !isset($input['score'])) {
            Response::error('student_id, subject_id, and score are required', 400);
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
