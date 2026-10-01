<?php
declare(strict_types=1);
require_once __DIR__ . '/../../includes/bootstrap.php';
require_once __DIR__ . '/../../src/appointments.php';

// Action-only endpoint: always redirects back to the list (keeping its filters).
require_post();
csrf_verify();

// Only plain query-string characters are allowed back in the redirect target.
$returnQuery = input_string($_POST, 'return_query');
if (preg_match('/^[A-Za-z0-9_=&%.+\-]*$/', $returnQuery) !== 1) {
    $returnQuery = '';
}
$back = '/appointments/index.php' . ($returnQuery !== '' ? '?' . $returnQuery : '');

$id = int_param($_POST, 'id');
$status = input_string($_POST, 'status');
$appointment = $id === null ? null : appointment_find($id);

if ($appointment === null) {
    flash_set('error', 'That appointment does not exist.');
    redirect($back);
}
if (!in_array($status, APPOINTMENT_NEW_STATUSES, true)) {
    flash_set('error', 'Invalid status.');
    redirect($back);
}

if (appointment_change_status($id, $status)) {
    flash_set('success', 'Appointment marked as ' . strtolower(APPOINTMENT_STATUSES[$status]) . '.');
} else {
    flash_set('error', 'Only scheduled appointments can be changed. This one is already '
        . strtolower(APPOINTMENT_STATUSES[$appointment['status']]) . '.');
}
redirect($back);
