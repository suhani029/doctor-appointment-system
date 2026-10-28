<?php
require "db.php";
checkLogin();

if ($_SESSION["user"]["role"] !== "admin") {
    http_response_code(403);
    exit("Only the administrator can manage doctors.");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "add") {
        $name = trim($_POST["name"] ?? "");
        $specialty = trim($_POST["specialty"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $fee = max(0, (float)($_POST["fee"] ?? 0));
        $days = trim($_POST["days"] ?? "Monday-Friday");

        if ($name !== "" && $specialty !== "") {
            $query = $conn->prepare(
                "INSERT INTO doctors (name, specialty, phone, fee, available_days) VALUES (?, ?, ?, ?, ?)"
            );
            $query->execute([$name, $specialty, $phone, $fee, $days]);
            showMessage("Doctor added successfully.");
        } else {
            showMessage("Doctor name and specialty are required.", "error");
        }
    }

    if ($action === "delete") {
        $doctorId = (int)($_POST["doctor_id"] ?? 0);
        $check = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'Booked'");
        $check->execute([$doctorId]);

        if ((int)$check->fetchColumn() > 0) {
            showMessage("This doctor has active appointments. Cancel or complete them first.", "error");
        } else {
            try {
                $delete = $conn->prepare("DELETE FROM doctors WHERE id = ?");
                $delete->execute([$doctorId]);
                showMessage("Doctor deleted.");
            } catch (PDOException $error) {
                showMessage("This doctor has appointment or waitlist history and cannot be deleted.", "error");
            }
        }
    }

    header("Location: doctors.php");
    exit;
}

$doctors = $conn->query("SELECT * FROM doctors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Doctors | CareSlot</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<?php include "header.php"; ?>
<main class="container">
    <div class="page-title"><div><p class="eyebrow">DIRECTORY</p><h1>Manage doctors</h1><p class="muted">Add and view the clinic's doctors.</p></div></div>
    <?= getMessage() ?>
    <section class="panel">
        <h2>Add a doctor</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add">
            <div><label>Doctor name</label><input name="name" required maxlength="100" placeholder="Dr. First Last"></div>
            <div><label>Specialty</label><input name="specialty" required maxlength="100" placeholder="General Physician"></div>
            <div><label>Phone</label><input name="phone" maxlength="20"></div>
            <div><label>Consultation fee (₹)</label><input type="number" name="fee" min="0" step="0.01" value="300" required></div>
            <div><label>Available days</label><input name="days" value="Monday-Friday" required></div>
            <div class="form-end"><button class="button primary">Add doctor</button></div>
        </form>
    </section>
    <section class="panel">
        <h2>Doctor list</h2>
        <div class="table-wrap"><table><thead><tr><th>Name</th><th>Specialty</th><th>Phone</th><th>Fee</th><th>Available days</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($doctors as $doctor): ?>
            <tr>
                <td><?= clean($doctor["name"]) ?></td><td><?= clean($doctor["specialty"]) ?></td>
                <td><?= clean($doctor["phone"]) ?></td><td>₹<?= number_format((float)$doctor["fee"], 2) ?></td>
                <td><?= clean($doctor["available_days"]) ?></td>
                <td><form method="post" onsubmit="return confirm('Delete this doctor?')">
                    <input type="hidden" name="action" value="delete"><input type="hidden" name="doctor_id" value="<?= (int)$doctor["id"] ?>">
                    <button class="button danger small">Delete</button>
                </form></td>
            </tr>
        <?php endforeach; ?>
        </tbody></table></div>
    </section>
</main>
</body></html>
