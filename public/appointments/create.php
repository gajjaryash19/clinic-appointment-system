<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/appointments.php';

$errors = [];
// Links such as patients/index.php -> "Book appointment" pre-select the patient.
$old = [
    'patient_id'       => int_param($_GET, 'patient_id') ?? 0,
    'doctor_id'        => int_param($_GET, 'doctor_id') ?? 0,
    'appointment_date' => date('Y-m-d'),
    'appointment_time' => '',
    'reason'           => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = appointment_validate($_POST);
    $old = $data;

    if (!$errors) {
        try {
            appointment_create($data);
            flash_set('success', 'Appointment booked.');
            redirect('/appointments/index.php');
        } catch (PDOException $e) {
            // Someone else took the slot between our check and our INSERT.
            $conflict = appointment_conflict_error($e);
            if ($conflict === null) {
                throw $e;
            }
            [$field, $message] = $conflict;
            $errors[$field] = $message;
        }
    }
}

$mode        = 'create';
$doctors     = doctors_list(false);   // active doctors only
$patients    = patients_list();
$freeSlots   = appointment_free_slots_for($old);
$formAction  = BASE_URL . '/appointments/create.php';
$submitLabel = 'Book appointment';
$pageTitle   = 'Book appointment';
$activeNav   = 'appointments';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Book appointment</h1>
<?php require __DIR__ . '/../../includes/appointment_form.php'; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
