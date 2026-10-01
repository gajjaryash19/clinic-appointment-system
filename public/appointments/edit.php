<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/appointments.php';

$id = int_param($_GET, 'id');
$appointment = $id === null ? null : appointment_find($id);
if ($appointment === null) {
    abort_404();
}
if ($appointment['status'] !== 'scheduled') {
    flash_set('error', 'Only scheduled appointments can be rescheduled. This one is '
        . strtolower(APPOINTMENT_STATUSES[$appointment['status']]) . '.');
    redirect('/appointments/index.php');
}

$errors = [];
$old = [
    'patient_id'       => (int) $appointment['patient_id'],
    'doctor_id'        => (int) $appointment['doctor_id'],
    'appointment_date' => $appointment['appointment_date'],
    'appointment_time' => $appointment['appointment_time'],
    'reason'           => (string) $appointment['reason'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    // Doctor and patient come from the database row, never from the submitted form.
    $input = $_POST;
    $input['patient_id'] = (string) $appointment['patient_id'];
    $input['doctor_id']  = (string) $appointment['doctor_id'];

    [$data, $errors] = appointment_validate($input, $id);
    $old = $data;

    if (!$errors) {
        try {
            appointment_reschedule($id, $data);
            flash_set('success', 'Appointment rescheduled.');
            redirect('/appointments/index.php');
        } catch (PDOException $e) {
            $conflict = appointment_conflict_error($e);
            if ($conflict === null) {
                throw $e;
            }
            [$field, $message] = $conflict;
            $errors[$field] = $message;
        }
    }
}

$mode        = 'reschedule';
$freeSlots   = appointment_free_slots_for($old, $id); // our own slot counts as free for us
$formAction  = BASE_URL . '/appointments/edit.php?id=' . $id;
$submitLabel = 'Save changes';
$pageTitle   = 'Reschedule appointment';
$activeNav   = 'appointments';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Reschedule appointment</h1>
<?php require __DIR__ . '/../../includes/appointment_form.php'; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
