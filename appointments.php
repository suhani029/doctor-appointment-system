<?php
require "db.php";
checkLogin();

$user = $_SESSION["user"];
$isAdmin = $user["role"] === "admin";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "book") {
        $doctorId = (int)($_POST["doctor_id"] ?? 0);
        $date = $_POST["appointment_date"] ?? "";
        $time = $_POST["appointment_time"] ?? "";
        $reason = trim($_POST["reason"] ?? "");

        if (!$date || !$time || $date < date("Y-m-d")) {
            showMessage("Choose a valid date and time today or in the future.", "error");
            header("Location: appointments.php"); exit;
        }

        try {
            $conn->beginTransaction();

            // Lock the doctor row while checking the requested slot.
            $doctorQuery = $conn->prepare("SELECT id FROM doctors WHERE id = ? FOR UPDATE");
            $doctorQuery->execute([$doctorId]);

            if (!$doctorQuery->fetch()) {
                throw new Exception("Please choose a valid doctor.");
            }

            $check = $conn->prepare(
                "SELECT id FROM appointments
                 WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status = 'Booked'
                 LIMIT 1"
            );
            $check->execute([$doctorId, $date, $time]);

            if ($check->fetch()) {
                $wait = $conn->prepare(
                    "INSERT INTO waitlist (patient_id, doctor_id, appointment_date, appointment_time, reason)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $wait->execute([$user["id"], $doctorId, $date, $time, $reason]);
                $conn->commit();
                showMessage("That slot is booked. You have been added to the waitlist.");
            } else {
                $patientId = $user["id"];
                if ($isAdmin && !empty($_POST["patient_id"])) {
                    $patientId = (int)$_POST["patient_id"];
                }
                $insert = $conn->prepare(
                    "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $insert->execute([$patientId, $doctorId, $date, $time, $reason]);
                $conn->commit();
                showMessage("Appointment booked successfully.");
            }
        } catch (Throwable $error) {
            if ($conn->inTransaction()) $conn->rollBack();
            showMessage("Booking could not be completed. Please check your selections and try again.", "error");
        }
        header("Location: appointments.php"); exit;
    }

    if ($action === "cancel") {
        $appointmentId = (int)($_POST["appointment_id"] ?? 0);

        if ($isAdmin) {
            $find = $conn->prepare("SELECT * FROM appointments WHERE id = ? AND status = 'Booked'");
            $find->execute([$appointmentId]);
        } else {
            $find = $conn->prepare("SELECT * FROM appointments WHERE id = ? AND patient_id = ? AND status = 'Booked'");
            $find->execute([$appointmentId, $user["id"]]);
        }
        $appointment = $find->fetch(PDO::FETCH_ASSOC);

        if (!$appointment) {
            showMessage("Appointment not found or already cancelled.", "error");
        } else {
            try {
                $conn->beginTransaction();
                $cancel = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ?");
                $cancel->execute([$appointmentId]);

                $nextQuery = $conn->prepare(
                    "SELECT * FROM waitlist
                     WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status = 'Waiting'
                     ORDER BY created_at, id LIMIT 1 FOR UPDATE"
                );
                $nextQuery->execute([
                    $appointment["doctor_id"],
                    $appointment["appointment_date"],
                    $appointment["appointment_time"]
                ]);
                $nextPatient = $nextQuery->fetch(PDO::FETCH_ASSOC);

                if ($nextPatient) {
                    $addAppointment = $conn->prepare(
                        "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, reason)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    $addAppointment->execute([
                        $nextPatient["patient_id"], $nextPatient["doctor_id"],
                        $nextPatient["appointment_date"], $nextPatient["appointment_time"],
                        $nextPatient["reason"]
                    ]);
                    $updateWaitlist = $conn->prepare("UPDATE waitlist SET status = 'Booked' WHERE id = ?");
                    $updateWaitlist->execute([$nextPatient["id"]]);
                }

                $conn->commit();
                showMessage($nextPatient
                    ? "Appointment cancelled. The next patient on the waitlist was booked into the slot."
                    : "Appointment cancelled successfully.");
            } catch (Throwable $error) {
                if ($conn->inTransaction()) $conn->rollBack();
                showMessage("Could not cancel the appointment. Please try again.", "error");
            }
        }
        header("Location: appointments.php"); exit;
    }

    if ($action === "complete" && $isAdmin) {
        $done = $conn->prepare("UPDATE appointments SET status = 'Completed' WHERE id = ? AND status = 'Booked'");
        $done->execute([(int)($_POST["appointment_id"] ?? 0)]);
        showMessage("Appointment status updated.");
        header("Location: appointments.php"); exit;
    }
}

$doctors = $conn->query("SELECT * FROM doctors ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$patients = $conn->query("SELECT id, name, email FROM users WHERE role = 'patient' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT a.*, d.name AS doctor_name, d.specialty, u.name AS patient_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        JOIN users u ON a.patient_id = u.id";
if (!$isAdmin) $sql .= " WHERE a.patient_id = " . (int)$user["id"];
$sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$appointments = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$waitSql = "SELECT w.*, d.name AS doctor_name, u.name AS patient_name
            FROM waitlist w
            JOIN doctors d ON w.doctor_id = d.id
            JOIN users u ON w.patient_id = u.id";
if (!$isAdmin) $waitSql .= " WHERE w.patient_id = " . (int)$user["id"];
$waitSql .= " ORDER BY w.created_at DESC";
$waitlist = $conn->query($waitSql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Appointments | CareSlot</title><link rel="stylesheet" href="assets/style.css"><script src="assets/script.js" defer></script></head>
<body>
<?php include "header.php"; ?>
<main class="container">
    <div class="page-title"><div><p class="eyebrow">SCHEDULING</p><h1>Appointments</h1><p class="muted">Book a slot or check your appointment status.</p></div></div>
    <?= getMessage() ?>
    <section class="panel">
        <h2>Book an appointment</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="book">
            <div><label>Doctor</label><select name="doctor_id" required><option value="">Select doctor</option>
                <?php foreach ($doctors as $doctor): ?><option value="<?= (int)$doctor["id"] ?>"><?= clean($doctor["name"]) ?> — <?= clean($doctor["specialty"]) ?></option><?php endforeach; ?>
            </select></div>
            <div><label>Date</label><input type="date" name="appointment_date" min="<?= date("Y-m-d") ?>" required></div>
            <div><label>Time</label><select name="appointment_time" required><option value="">Select time</option>
                <?php foreach (["09:00","09:30","10:00","10:30","11:00","11:30","12:00","12:30","14:00","14:30","15:00","15:30","16:00","16:30"] as $time): ?>
                <option value="<?= $time ?>"><?= date("g:i A", strtotime($time)) ?></option>
                <?php endforeach; ?>
            </select></div>
            <div><label>Reason for visit (optional)</label><input name="reason" maxlength="255" placeholder="General consultation"></div>
            <?php if ($isAdmin): ?><div><label>Patient</label><select name="patient_id"><option value="">Admin account</option><?php foreach ($patients as $patient): ?><option value="<?= (int)$patient["id"] ?>"><?= clean($patient["name"]) ?></option><?php endforeach; ?></select></div><?php endif; ?>
            <div class="form-end"><button class="button primary">Book appointment</button></div>
        </form>
    </section>

    <section class="panel">
        <div class="section-heading"><h2><?= $isAdmin ? "All appointments" : "My appointments" ?></h2><input id="tableSearch" class="search" placeholder="Search table..."></div>
        <div class="table-wrap"><table id="searchTable"><thead><tr><?php if ($isAdmin): ?><th>Patient</th><?php endif; ?><th>Doctor</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead><tbody>
        <?php foreach ($appointments as $appointment): ?>
            <tr><?php if ($isAdmin): ?><td><?= clean($appointment["patient_name"]) ?></td><?php endif; ?>
            <td><?= clean($appointment["doctor_name"]) ?><small class="sub-text"><?= clean($appointment["specialty"]) ?></small></td>
            <td><?= clean($appointment["appointment_date"]) ?></td><td><?= date("g:i A", strtotime($appointment["appointment_time"])) ?></td>
            <td><?= clean($appointment["reason"]) ?></td><td><span class="status <?= strtolower($appointment["status"]) ?>"><?= clean($appointment["status"]) ?></span></td><td>
            <?php if ($appointment["status"] === "Booked"): ?>
                <form method="post" class="inline" onsubmit="return confirm('Cancel this appointment?')"><input type="hidden" name="action" value="cancel"><input type="hidden" name="appointment_id" value="<?= (int)$appointment["id"] ?>"><button class="button danger small">Cancel</button></form>
                <?php if ($isAdmin): ?><form method="post" class="inline"><input type="hidden" name="action" value="complete"><input type="hidden" name="appointment_id" value="<?= (int)$appointment["id"] ?>"><button class="button secondary small">Complete</button></form><?php endif; ?>
            <?php else: ?>—<?php endif; ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$appointments): ?><tr><td colspan="7" class="empty">No appointments found yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>

    <section class="panel">
        <h2><?= $isAdmin ? "All waitlist entries" : "My waitlist entries" ?></h2>
        <p class="muted">When a booked appointment is cancelled, the oldest waiting patient for that exact slot is booked automatically.</p>
        <div class="table-wrap"><table><thead><tr><?php if ($isAdmin): ?><th>Patient</th><?php endif; ?><th>Doctor</th><th>Date</th><th>Time</th><th>Reason</th><th>Status</th></tr></thead><tbody>
        <?php foreach ($waitlist as $entry): ?><tr><?php if ($isAdmin): ?><td><?= clean($entry["patient_name"]) ?></td><?php endif; ?><td><?= clean($entry["doctor_name"]) ?></td><td><?= clean($entry["appointment_date"]) ?></td><td><?= date("g:i A", strtotime($entry["appointment_time"])) ?></td><td><?= clean($entry["reason"]) ?></td><td><span class="status <?= strtolower($entry["status"]) ?>"><?= clean($entry["status"]) ?></span></td></tr><?php endforeach; ?>
        <?php if (!$waitlist): ?><tr><td colspan="6" class="empty">Nobody is on the waitlist yet.</td></tr><?php endif; ?>
        </tbody></table></div>
    </section>
</main>
</body></html>
