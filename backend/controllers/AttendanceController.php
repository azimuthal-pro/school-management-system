<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/Attendance.php';
require_once __DIR__ . '/../helpers/Validation.php';

class AttendanceController {
    private $attendanceModel;
    private $user;

    public function __construct() {
        $this->attendanceModel = new Attendance();
        $this->user = AuthMiddleware::verify();
        RoleMiddleware::checkRole($this->user, ['admin', 'teacher']);
    }

    public function getAll() {
        $attendance = $this->attendanceModel->getAll();
        Response::success($attendance, 'Attendance records retrieved successfully', 200);
    }

    public function getByClass($classId) {
        $attendance = $this->attendanceModel->getByClass($classId);
        Response::success($attendance, 'Class attendance retrieved successfully', 200);
    }

    public function getByStudent($studentId) {
        $attendance = $this->attendanceModel->getByStudent($studentId);
        Response::success($attendance, 'Student attendance retrieved successfully', 200);
    }

    public function getByDate($date) {
        $attendance = $this->attendanceModel->getByDate($date);
        Response::success($attendance, 'Attendance for date retrieved successfully', 200);
    }

    public function create() {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [
            'student_id' => ['required', 'numeric'],
            'class_id' => ['required', 'numeric'],
            'date' => ['required', ['date_format', 'Y-m-d']],
            'status' => ['required', ['in', ['present', 'absent', 'late']]]
        ];

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }

        $input['teacher_id'] = $this->user['id'] ?? null;

        $attendance = $this->attendanceModel->create($input);

        if (!$attendance) {
            Response::error('Failed to create attendance record', 500);
        }

        Response::success($attendance, 'Attendance recorded successfully', 201);
    }

    public function update($id) {
        $input = json_decode(file_get_contents('php://input'), true);

        $rules = [];
        if (isset($input['status'])) {
            $rules['status'] = ['required', ['in', ['present', 'absent', 'late']]];
        }
        if (isset($input['date'])) {
            $rules['date'] = ['required', ['date_format', 'Y-m-d']];
        }
        if (isset($input['student_id'])) {
            $rules['student_id'] = ['required', 'numeric'];
        }
        if (isset($input['class_id'])) {
            $rules['class_id'] = ['required', 'numeric'];
        }

        $errors = Validation::validate($rules, $input);
        if (!empty($errors)) {
            Response::error(['validation' => $errors], 422);
        }

        $attendance = $this->attendanceModel->update($id, $input);

        if (!$attendance) {
            Response::error('Failed to update attendance', 500);
        }

        Response::success($attendance, 'Attendance updated successfully', 200);
    }

    public function delete($id) {
        $result = $this->attendanceModel->delete($id);

        if (!$result) {
            Response::error('Failed to delete attendance', 500);
        }

        Response::success(null, 'Attendance deleted successfully', 200);
    }
}
?>
