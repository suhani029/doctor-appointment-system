<?php
require "db.php";
checkLogin();

$doctorCount = (int)$conn->query("SELECT COUNT(*) FROM doctors")->fetchColumn();
$patientCount = (int)$conn->query("SELECT COUNT(*) FROM users WHERE role = 'patient'")->fetchColumn();
$bookedCount = (int)$conn->query("SELECT COUNT(*) FROM appointments WHERE status = 'Booked'")->fetchColumn();
$waitingCount = (int)$conn->query("SELECT COUNT(*) FROM waitlist WHERE status = 'Waiting'")->fetchColumn();

$user = $_SESSION["user"];
$isAdmin = $user["role"] === "admin";

$sql = "SELECT a.id, a.appointment_date, a.appointment_time, a.status, d.name AS doctor_name, u.name AS patient_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON a.patient_id = u.id";
if (!$isAdmin) $sql .= " WHERE a.patient_id = " . (int)$user["id"];
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC LIMIT 8";
$recent = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Dashboard | CareSlot</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<?php include "header.php"; ?>
<main class="container">
    <div class="page-title">
        <div><p class="eyebrow">OVERVIEW</p><h1>Hello, <?= clean($user["name"]) ?>!</h1><p class="muted">Welcome to your clinic appointment dashboard.</p></div>
        <a class="button primary" href="appointments.php">+ Book appointment</a>
    </div>
    <?= getMessage() ?>
    <section class="stats">
        <div class="stat"><span class="stat-icon blue">+</span><div><p>Total doctors</p><strong><?= $doctorCount ?></strong></div></div>
        <div class="stat"><span class="stat-icon purple">♙</span><div><p>Patients</p><strong><?= $isAdmin ? $patientCount : "—" ?></strong></div></div>
        <div class="stat"><span class="stat-icon green">▣</span><div><p><?= $isAdmin ? "Booked appointments" : "My booked appointments" ?></p><strong><?= $isAdmin ? $bookedCount : (int)$conn->query("SELECT COUNT(*) FROM appointments WHERE patient_id = ".(int)$user["id"]." AND status='Booked'")->fetchColumn() ?></strong></div></div>
        <div class="stat"><span class="stat-icon orange">☷</span><div><p><?= $isAdmin ? "Waiting list entries" : "My waiting entries" ?></p><strong><?= $isAdmin ? $waitingCount : (int)$conn->query("SELECT COUNT(*) FROM waitlist WHERE patient_id = ".(int)$user["id"]." AND status='Waiting'")->fetchColumn() ?></strong></div></div>
    </section>
    <section class="quick-links">
        <a class="quick-card" href="appointments.php"><span class="quick-icon">▣</span><strong>Appointments</strong><span class="muted">Book, cancel, and track visits</span><span class="card-arrow">→</span></a>
        <?php if ($isAdmin): ?><a class="quick-card" href="doctors.php"><span class="quick-icon">✚</span><strong>Manage doctors</strong><span class="muted">Add or remove doctor records</span><span class="card-arrow">→</span></a><?php endif; ?>
    </section>
    <section class="panel">
        <div class="section-heading"><h2><?= $isAdmin ? "Recent appointments" : "My recent appointments" ?></h2><a href="appointments.php">View all →</a></div>
        <div class="table-wrap"><table><thead><tr><?php if ($isAdmin): ?><th>Patient</th><?php endif; ?><th>Doctor</th><th>Date</th><th>Time</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($recent as $appointment): ?><tr><?php if ($isAdmin): ?><td><?= clean($appointment["patient_name"]) ?></td><?php endif; ?><td><?= clean($appointment["doctor_name"]) ?></td><td><?= clean($appointment["appointment_date"]) ?></td><td><?= date("g:i A", strtotime($appointment["appointment_time"])) ?></td><td><span class="status <?= strtolower($appointment["status"]) ?>"><?= clean($appointment["status"]) ?></span></td></tr><?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="5" class="empty">No appointments yet. Start by booking a slot.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</main>
</body></html>
