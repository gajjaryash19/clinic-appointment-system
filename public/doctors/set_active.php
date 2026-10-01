<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/doctors.php';

// Action-only endpoint: no page is rendered, it always redirects back to the list.
require_post();
csrf_verify();

$id = int_param($_POST, 'id');
$active = input_string($_POST, 'active') === '1';
$doctor = $id === null ? null : doctor_find($id);

if ($doctor === null) {
    flash_set('error', 'That doctor does not exist.');
    redirect('/doctors/index.php');
}

if (!$active) {
    $upcoming = doctor_upcoming_count($id);
    if ($upcoming > 0) {
        flash_set('error', sprintf(
            '%s still has %d scheduled appointment%s. Cancel or complete %s first.',
            $doctor['full_name'], $upcoming, $upcoming === 1 ? '' : 's', $upcoming === 1 ? 'it' : 'them'
        ));
        redirect('/doctors/index.php');
    }
}

doctor_set_active($id, $active);
flash_set('success', $active
    ? $doctor['full_name'] . ' is active again.'
    : $doctor['full_name'] . ' was deactivated. Past appointments keep their record.');
redirect('/doctors/index.php');
