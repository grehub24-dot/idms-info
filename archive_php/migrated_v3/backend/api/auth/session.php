<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

function start_session(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function require_admin(): array {
    start_session();

    if (!isset($_SESSION['actor_type']) || $_SESSION['actor_type'] !== 'admin' || !isset($_SESSION['admin_id'])) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    return [
        'admin_id' => (int)$_SESSION['admin_id'],
        'role' => isset($_SESSION['role']) ? (string)$_SESSION['role'] : 'admin',
    ];
}

function require_student(): array {
    start_session();

    if (!isset($_SESSION['actor_type']) || $_SESSION['actor_type'] !== 'student' || !isset($_SESSION['student_id'])) {
        json_response(['error' => 'Unauthorized'], 401);
    }

    return [
        'student_id' => (int)$_SESSION['student_id'],
    ];
}

function current_actor(): ?array {
    start_session();

    if (!isset($_SESSION['actor_type'])) {
        return null;
    }

    if ($_SESSION['actor_type'] === 'admin' && isset($_SESSION['admin_id'])) {
        return [
            'type' => 'admin',
            'admin_id' => (int)$_SESSION['admin_id'],
            'role' => isset($_SESSION['role']) ? (string)$_SESSION['role'] : 'admin',
        ];
    }

    if ($_SESSION['actor_type'] === 'student' && isset($_SESSION['student_id'])) {
        return [
            'type' => 'student',
            'student_id' => (int)$_SESSION['student_id'],
        ];
    }

    return null;
}
