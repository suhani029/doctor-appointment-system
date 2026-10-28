<?php
// Database connection. Change these values if your MySQL settings are different.
$host = "localhost";
$dbname = "doctor_appointment";
$username = "root";
$password = "";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) {
    exit("Database connection failed. Please check db.php and import database.sql.");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function clean($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function showMessage($message, $type = "success") {
    $_SESSION["message"] = $message;
    $_SESSION["message_type"] = $type;
}

function getMessage() {
    if (!isset($_SESSION["message"])) {
        return "";
    }

    $message = '<div class="message ' . clean($_SESSION["message_type"]) . '">'
        . clean($_SESSION["message"]) . '</div>';

    unset($_SESSION["message"], $_SESSION["message_type"]);
    return $message;
}

function checkLogin() {
    if (!isset($_SESSION["user"])) {
        header("Location: login.php");
        exit;
    }
}
?>
