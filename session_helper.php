<?php
header('Content-Type: text/html; charset=UTF-8');
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['email']);
}

// Function to check if user is student
function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

// Function to check if user is faculty
function isFaculty() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'faculty';
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: loginpage.php");
        exit();
    }
}

// Function to require student role
function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header("Location: loginpage.php");
        exit();
    }
}

// Function to require faculty role
function requireFaculty() {
    requireLogin();
    if (!isFaculty()) {
        header("Location: loginpage.php");
        exit();
    }
}

// Function to get logged in user data
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