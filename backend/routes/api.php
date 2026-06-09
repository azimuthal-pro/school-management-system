<?php

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Response.php';


$request = $_GET['request'] ?? '';
if ($request === '') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    $request = trim($path, '/');
}
$method = $_SERVER['REQUEST_METHOD'];

// Handle CORS preflight requests
if ($method === 'OPTIONS') {
    exit(0);
}

// Route handling
$parts = array_filter(explode('/', trim($request, '/')));
$parts = array_values($parts); // Re-index array

// Strip 'api' prefix if present, or strip 'sms/public' if using Apache in htdocs
if (($parts[0] ?? '') === 'api') {
    array_shift($parts);
} else if (($parts[0] ?? '') === 'sms' && ($parts[1] ?? '') === 'public') {
    // Remove 'sms/public' prefix for Apache setup
    array_shift($parts); // Remove 'sms'
    array_shift($parts); // Remove 'public'
}

$endpoint = $parts[0] ?? '';
$action = $parts[1] ?? '';
$id = $parts[2] ?? '';

// AUTH ROUTES — no auth middleware required
if ($endpoint === 'auth') {
    require_once __DIR__ . '/../controllers/AuthController.php';
    $controller = new AuthController();
    
    if ($action === 'login' && $method === 'POST') {
        $controller->login();
    } else if ($action === 'register' && $method === 'POST') {
        $controller->register();
    } else {
        Response::error('Auth endpoint not found', 404);
    }
}

// All other endpoints require authentication
if ($endpoint !== 'auth') {
    require_once __DIR__ . '/../middleware/AuthMiddleware.php';
    AuthMiddleware::verify();
}

    // STUDENT ROUTES
if ($endpoint === 'students') {
    require_once __DIR__ . '/../controllers/StudentController.php';
    $controller = new StudentController();
    
    if (!$action) {
        // /students - GET all or POST create
        if ($method === 'GET') {
            $controller->getAll();
        } else if ($method === 'POST') {
            $controller->create();
        }
    } else if (is_numeric($action)) {
        // /students/{id} - GET by id, PUT update, DELETE
        $studentId = $action;
        if ($method === 'GET') {
            $controller->getById($studentId);
        } else if ($method === 'PUT') {
            $controller->update($studentId);
        } else if ($method === 'DELETE') {
            $controller->delete($studentId);
        }
    } else {
        Response::error('Student endpoint not found', 404);
    }
}

// TEACHER ROUTES
else if ($endpoint === 'teachers') {
    require_once __DIR__ . '/../controllers/TeacherController.php';
    $controller = new TeacherController();
    
    if (!$action) {
        // /teachers - GET all or POST create
        if ($method === 'GET') {
            $controller->getAll();
        } else if ($method === 'POST') {
            $controller->create();
        }
    } else if (is_numeric($action)) {
        // /teachers/{id} - GET by id, PUT update, DELETE
        $teacherId = $action;
        if ($method === 'GET') {
            $controller->getById($teacherId);
        } else if ($method === 'PUT') {
            $controller->update($teacherId);
        } else if ($method === 'DELETE') {
            $controller->delete($teacherId);
        }
    } else {
        Response::error('Teacher endpoint not found', 404);
    }
}

// ATTENDANCE ROUTES
else if ($endpoint === 'attendance') {
    require_once __DIR__ . '/../controllers/AttendanceController.php';
    $controller = new AttendanceController();
    
    if (!$action) {
        // /attendance - GET all or POST create
        if ($method === 'GET') {
            $controller->getAll();
        } else if ($method === 'POST') {
            $controller->create();
        }
    } else if ($action === 'class' && is_numeric($id)) {
        // /attendance/class/{classId}
        if ($method === 'GET') {
            $controller->getByClass($id);
        }
    } else if ($action === 'student' && is_numeric($id)) {
        // /attendance/student/{studentId}
        if ($method === 'GET') {
            $controller->getByStudent($id);
        }
    } else if ($action === 'date') {
        // /attendance/date/{date}
        $date = $id;
        if ($method === 'GET') {
            $controller->getByDate($date);
        }
    } else if (is_numeric($action)) {
        // /attendance/{id} - PUT update, DELETE
        $attendanceId = $action;
        if ($method === 'PUT') {
            $controller->update($attendanceId);
        } else if ($method === 'DELETE') {
            $controller->delete($attendanceId);
        }
    } else {
        Response::error('Attendance endpoint not found', 404);
    }
}

// GRADES ROUTES
else if ($endpoint === 'grades') {
    require_once __DIR__ . '/../controllers/GradeController.php';
    $controller = new GradeController();
    
    if (!$action) {
        // /grades - GET all or POST create
        if ($method === 'GET') {
            $controller->getAll();
        } else if ($method === 'POST') {
            $controller->create();
        }
    } else if ($action === 'student' && is_numeric($id)) {
        // /grades/student/{studentId}
        if ($method === 'GET') {
            $controller->getByStudent($id);
        }
    } else if ($action === 'class' && is_numeric($id)) {
        // /grades/class/{classId}
        if ($method === 'GET') {
            $controller->getByClass($id);
        }
    } else if ($action === 'subject' && is_numeric($id)) {
        // /grades/subject/{subjectId}
        if ($method === 'GET') {
            $controller->getBySubject($id);
        }
    } else if (is_numeric($action)) {
        // /grades/{id} - PUT update, DELETE
        $gradeId = $action;
        if ($method === 'PUT') {
            $controller->update($gradeId);
        } else if ($method === 'DELETE') {
            $controller->delete($gradeId);
        }
    } else {
        Response::error('Grade endpoint not found', 404);
    }
}

// CLASSES ROUTES
else if ($endpoint === 'classes') {
    require_once __DIR__ . '/../controllers/ClassController.php';
    $controller = new ClassController();
    
    if (!$action) {
        // /classes - GET all or POST create
        if ($method === 'GET') {
            $controller->getAll();
        } else if ($method === 'POST') {
            $controller->create();
        }
    } else if (is_numeric($action)) {
        // /classes/{id} - GET by id, PUT update, DELETE
        $classId = $action;
        if ($id === 'students' && $method === 'GET') {
            $controller->getStudents($classId);
        } else if ($method === 'GET') {
            $controller->getById($classId);
        } else if ($method === 'PUT') {
            $controller->update($classId);
        } else if ($method === 'DELETE') {
            $controller->delete($classId);
        }
    } else if ($action === 'students' && is_numeric($id)) {
        // /classes/students/{id}
        if ($method === 'GET') {
            $controller->getStudents($id);
        }
    } else {
        Response::error('Class endpoint not found', 404);
    }
}

// SUBJECTS ROUTES
else if ($endpoint === 'subjects') {
    require_once __DIR__ . '/../controllers/SubjectController.php';
    $controller = new SubjectController();
    
    if (!$action) {
        // /subjects - GET all or POST create
        if ($method === 'GET') {
            $controller->getAll();
        } else if ($method === 'POST') {
            $controller->create();
        }
    } else if ($action === 'teacher' && is_numeric($id)) {
        // /subjects/teacher/{teacherId}
        if ($method === 'GET') {
            $controller->getByTeacher($id);
        }
    } else if (is_numeric($action)) {
        // /subjects/{id} - GET by id, PUT update, DELETE
        $subjectId = $action;
        if ($method === 'GET') {
            $controller->getById($subjectId);
        } else if ($method === 'PUT') {
            $controller->update($subjectId);
        } else if ($method === 'DELETE') {
            $controller->delete($subjectId);
        }
    } else {
        Response::error('Subject endpoint not found', 404);
    }
}

// REPORTS ROUTES
else if ($endpoint === 'reports') {
    require_once __DIR__ . '/../controllers/ReportController.php';
    $controller = new ReportController();

    if ($action === 'attendance' && $method === 'GET') {
        $controller->attendanceSummary();
    } else if ($action === 'grades' && $method === 'GET') {
        $controller->gradeSummary();
    } else if ($action === 'student' && is_numeric($id) && $method === 'GET') {
        $controller->studentReport($id);
    } else {
        Response::error('Report endpoint not found', 404);
    }
}

// USERS ROUTES (Admin only)
else if ($endpoint === 'users') {
    require_once __DIR__ . '/../controllers/UserController.php';
    $controller = new UserController();
    
    if (!$action) {
        // /users - GET all
        if ($method === 'GET') {
            $controller->getAll();
        }
    } else if (is_numeric($action)) {
        // /users/{id} - GET by id
        $userId = $action;
        if ($method === 'GET') {
            $controller->getById($userId);
        } else if ($method === 'PUT') {
            $controller->updateRole($userId);
        } else if ($method === 'DELETE') {
            $controller->delete($userId);
        }
    } else {
        Response::error('User endpoint not found', 404);
    }
}

// UNKNOWN ROUTE
else {
    Response::error('Endpoint not found', 404);
}
?>
