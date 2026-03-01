<?php
$host     = 'sql110.infinityfree.net';
$username = 'if0_41220044';
$password = '8rX3f6nZjQ4CJ6B';
$database = 'if0_41220044_trackera';

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8');
?>