<?php
header('Content-Type: text/html; charset=UTF-8');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['email']);
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function isFaculty() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'faculty';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: loginpage.php");
        exit();
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header("Location: loginpage.php");
        exit();
    }
}

function requireFaculty() {
    requireLogin();
    if (!isFaculty()) {
        header("Location: loginpage.php");
        exit();
    }
}

function getLoggedInUser() {
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'user_id'        => $_SESSION['user_id'],
        'name'           => $_SESSION['name'],
        'email'          => $_SESSION['email'],
        'role'           => $_SESSION['role'],
        'grade'          => $_SESSION['grade']          ?? null,
        'division'       => $_SESSION['division']       ?? null,
        'roll_no'        => $_SESSION['roll_no']        ?? null,
        'institute_id'   => $_SESSION['institute_id']   ?? null,
        'institute_name' => $_SESSION['institute_name'] ?? null,
    ];
}
?>