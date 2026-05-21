<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/Attendance.php';

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

        if (!isset($input['student_id']) || !isset($input['class_id']) || !isset($input['date']) || !isset($input['status'])) {
            Response::error('student_id, class_id, date, and status are required', 400);
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
