<?php
require "db.php";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $email = strtolower(trim($_POST["email"] ?? ""));
    $password = $_POST["password"] ?? "";

    if ($name === "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = "Enter your name, a valid email, and a password of at least 6 characters.";
    } else {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = $conn->prepare(
                "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'patient')"
            );
            $query->execute([$name, $email, $hashedPassword]);
            showMessage("Account created. You can now log in.");
            header("Location: login.php");
            exit;
        } catch (PDOException $error) {
            $error = "This email is already registered.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Patient Registration | CareSlot</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <main class="login-card">
        <div class="logo">+</div>
        <h1>Create account</h1>
        <p class="muted">Register as a patient</p>
        <?php if ($error !== ""): ?><div class="message error"><?= clean($error) ?></div><?php endif; ?>

        <form method="post">
            <label>Full name</label>
            <input name="name" maxlength="100" required>
            <label>Email address</label>
            <input type="email" name="email" required>
            <label>Password (at least 6 characters)</label>
            <input type="password" name="password" minlength="6" required>
            <button class="button primary full-width">Create account</button>
        </form>
        <p class="center"><a href="login.php">Back to login</a></p>
    </main>
</body>
</html>
