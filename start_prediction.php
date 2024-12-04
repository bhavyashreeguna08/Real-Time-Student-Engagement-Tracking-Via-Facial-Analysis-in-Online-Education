<?php
session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] != 'teacher') {
    header("Location: login.html");
    exit();
}
set_time_limit(300);

// Call the Python script to start the engagement prediction
exec("python ep.py");

header("Location: teacher_dashboard.php");
?>
