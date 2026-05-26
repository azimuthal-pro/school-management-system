<?php

require_once __DIR__ . '/../helpers/Response.php';
require_once __DIR__ . '/../middleware/AuthMiddleware.php';
require_once __DIR__ . '/../middleware/RoleMiddleware.php';
require_once __DIR__ . '/../models/Report.php';

class ReportController {
    private $reportModel;
    private $user;

    public function __construct() {
        $this->reportModel = new Report();
        $this->user = AuthMiddleware::verify();
        RoleMiddleware::checkRole($this->user, ['admin', 'teacher']);
    }

    public function attendanceSummary() {
        $filters = [
            'class_id' => $_GET['class_id'] ?? null,
            'date' => $_GET['date'] ?? null,
        ];

        $summary = $this->reportModel->attendanceSummary($filters);
        Response::success($summary, 'Attendance summary retrieved successfully', 200);
    }

    public function gradeSummary() {
        $filters = [
            'class_id' => $_GET['class_id'] ?? null,
            'subject_id' => $_GET['subject_id'] ?? null,
            'term' => $_GET['term'] ?? null,
        ];

        $summary = $this->reportModel->gradeSummary($filters);
        Response::success($summary, 'Grade summary retrieved successfully', 200);
    }

    public function studentReport($studentId) {
        $report = $this->reportModel->studentReport($studentId);
        Response::success($report, 'Student report retrieved successfully', 200);
    }
}
?>