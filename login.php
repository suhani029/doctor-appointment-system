<?php
require "db.php";

$error = "";

// Create the demo admin account the first time this page is opened.
$adminCheck = $conn->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
if (!$adminCheck->fetch()) {
    $adminPassword = password_hash("Admin@123", PASSWORD_DEFAULT);
    $addAdmin = $conn->prepare(
        "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'admin')"
    );
    $addAdmin->execute(["Clinic Admin", "admin@clinic.local", $adminPassword]);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $query = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $query->execute([$email]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
        session_regenerate_id(true);
        $_SESSION["user"] = [
            "id" => $user["id"],
            "name" => $user["name"],
            "email" => $user["email"],
            "role" => $user["role"]
        ];
        header("Location: index.php");
        exit;
    } else {
        $error = "Email or password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | CareSlot</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="logo">+</div>
        <h1>CareSlot</h1>
        <p class="muted">Doctor Appointment System</p>

        <?php if ($error !== ""): ?>
            <div class="message error"><?= clean($error) ?></div>
        <?php endif; ?>
        <?= getMessage() ?>

        <form method="post">
            <label>Email address</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <input type="password" name="password" required>

            <button class="button primary full-width">Login</button>
        </form>

        <div class="demo-login">
            <strong>Demo admin account</strong><br>
            admin@clinic.local<br>
            Admin@123
        </div>
        <p class="center">New patient? <a href="register.php">Create an account</a></p>
    </main>
</body>
</html>
