<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/doctors.php';

$id = int_param($_GET, 'id');
$doctor = $id === null ? null : doctor_find($id);
if ($doctor === null) {
    abort_404();
}

$errors = [];
$old = $doctor;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = doctor_validate($_POST, $id);
    $old = $data;

    if (!$errors) {
        try {
            doctor_update($id, $data);
            flash_set('success', 'Doctor details updated.');
            redirect('/doctors/index.php');
        } catch (PDOException $e) {
            if (!is_duplicate_key($e)) {
                throw $e;
            }
            $errors['email'] = 'Another doctor already uses this email.';
        }
    }
}

$specializations = specializations_all();
$formAction  = BASE_URL . '/doctors/edit.php?id=' . $id;
$submitLabel = 'Save changes';
$pageTitle   = 'Edit doctor';
$activeNav   = 'doctors';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Edit doctor</h1>
<?php require __DIR__ . '/../../includes/doctor_form.php'; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
