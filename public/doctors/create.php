<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/doctors.php';

$errors = [];
$old = ['full_name' => '', 'specialization_id' => 0, 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    [$data, $errors] = doctor_validate($_POST);
    $old = $data; // keep what the user typed

    if (!$errors) {
        try {
            doctor_create($data);
            flash_set('success', 'Doctor added.');
            redirect('/doctors/index.php'); // Post/Redirect/Get: a refresh cannot re-submit
        } catch (PDOException $e) {
            // Two people saving the same email at once: the UNIQUE key is the final judge.
            if (!is_duplicate_key($e)) {
                throw $e;
            }
            $errors['email'] = 'Another doctor already uses this email.';
        }
    }
}

$specializations = specializations_all();
$formAction  = BASE_URL . '/doctors/create.php';
$submitLabel = 'Save doctor';
$pageTitle   = 'Add doctor';
$activeNav   = 'doctors';
require __DIR__ . '/../../includes/header.php';
?>
<h1>Add doctor</h1>
<?php require __DIR__ . '/../../includes/doctor_form.php'; ?>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
