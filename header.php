<?php
$currentUser = $_SESSION["user"] ?? null;
?>
<header class="topbar">
    <a href="index.php" class="brand"><span class="logo small-logo">+</span> CareSlot</a>
    <nav class="top-links">
        <a href="index.php">Dashboard</a>
        <a href="appointments.php">Appointments</a>
        <?php if ($currentUser && $currentUser["role"] === "admin"): ?><a href="doctors.php">Doctors</a><?php endif; ?>
    </nav>
    <div class="user-area">
        <span><?= clean($currentUser["name"] ?? "") ?> <small>(<?= clean($currentUser["role"] ?? "") ?>)</small></span>
        <a class="button secondary small" href="logout.php">Logout</a>
    </div>
</header>
