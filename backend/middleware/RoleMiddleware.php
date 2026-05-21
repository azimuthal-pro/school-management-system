<?php

require_once __DIR__ . '/../helpers/Response.php';

class RoleMiddleware {
    public static function checkRole($user, $allowedRoles) {
        if (!isset($user['role']) || !in_array($user['role'], $allowedRoles)) {
            Response::error('Forbidden: Insufficient permissions', 403);
        }
    }
}
?>
