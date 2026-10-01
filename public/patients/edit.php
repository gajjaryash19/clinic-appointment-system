<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/patients.php';

$id = int_param($_GET, 'id');
$patient = $id === null ? null : patient_find($id);
if ($patient === null) {
    abort_404();
}

$errors = [];
$old = $patient;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = patient_validate($_POST);
    $old = $data;

    if (!$errors) {
        patient_update($id, $data);
        flash_set('success', 'Patient details updated.');
        redirect('/patients/index.php');
    }
}

$formAction  = BASE_URL . '/patients/edit.php?id=' . $id;
$submitLabel = 'Save changes';
$pageTitle   = 'Edit patient';
$activeNav   = 'patients';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Edit patient</h1>
<?php require __DIR__ . '/../../includes/patient_form.php'; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
