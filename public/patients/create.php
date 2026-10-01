<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/patients.php';

$errors = [];
$old = ['full_name' => '', 'gender' => '', 'date_of_birth' => '', 'phone' => '', 'email' => '', 'address' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = patient_validate($_POST);
    $old = $data; // keep what the user typed

    if (!$errors) {
        patient_create($data);
        flash_set('success', 'Patient registered.');
        redirect('/patients/index.php'); // Post/Redirect/Get
    }
}

$formAction  = BASE_URL . '/patients/create.php';
$submitLabel = 'Register patient';
$pageTitle   = 'Register patient';
$activeNav   = 'patients';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Register patient</h1>
<?php require __DIR__ . '/../../includes/patient_form.php'; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
