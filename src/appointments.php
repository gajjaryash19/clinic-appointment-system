<?php
declare(strict_types=1);

/**
 * Data access and business rules for appointments.
 * No HTML in this file: pages call these functions.
 *
 * Rules (the database enforces the double-booking ones, see schema.sql):
 *  - One live appointment per doctor per slot, and per patient per slot.
 *  - Cancelling frees the slot; completed / no_show keep holding it.
 *  - Only a 'scheduled' appointment can change status or be rescheduled.
 */

require_once __DIR__ . '/doctors.php';
require_once __DIR__ . '/patients.php';

const APPOINTMENT_STATUSES = [
    'scheduled' => 'Scheduled',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
    'no_show'   => 'No-show',
];
/** The statuses a scheduled appointment may move to. All of them are final. */
const APPOINTMENT_NEW_STATUSES = ['completed', 'cancelled', 'no_show'];
const APPOINTMENT_LIST_LIMIT = 200;

// ---- Slots ------------------------------------------------------------------

/** Every bookable start time, e.g. ['09:00', '09:30', ... '16:30']. */
function slot_times(): array
{
    $times = [];
    $start = DateTimeImmutable::createFromFormat('!H:i', CLINIC_OPEN);
    $end   = DateTimeImmutable::createFromFormat('!H:i', CLINIC_CLOSE);
    $step  = '+' . SLOT_MINUTES . ' minutes';

    while ($start->modify($step) <= $end) {
        $times[] = $start->format('H:i');
        $start = $start->modify($step);
    }

    return $times;
}

function appointment_is_past(string $date, string $time): bool
{
    return new DateTimeImmutable($date . ' ' . $time) <= new DateTimeImmutable('now');
}

/** Free start times for a doctor on a date. $exceptId ignores one appointment (reschedule). */
function appointment_free_slots(int $doctorId, string $date, ?int $exceptId = null): array
{
    $stmt = db()->prepare(
        "SELECT TIME_FORMAT(appointment_time, '%H:%i')
           FROM appointments
          WHERE doctor_id = :doctor_id AND appointment_date = :date
            AND status <> 'cancelled' AND id <> :except_id"
    );
    $stmt->execute(['doctor_id' => $doctorId, 'date' => $date, 'except_id' => $exceptId ?? 0]);
    $booked = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $free = [];
    foreach (slot_times() as $time) {
        if (!in_array($time, $booked, true) && !appointment_is_past($date, $time)) {
            $free[] = $time;
        }
    }

    return $free;
}

/**
 * Free slots to show as a hint under the time field, or null when the doctor/date
 * in $old are not usable yet (so there is nothing meaningful to show).
 */
function appointment_free_slots_for(array $old, ?int $exceptId = null): ?array
{
    $doctorId = (int) ($old['doctor_id'] ?? 0);
    $date = parse_ymd((string) ($old['appointment_date'] ?? ''));
    if ($doctorId < 1 || $date === null || $date < new DateTimeImmutable('today')) {
        return null;
    }
    $doctor = doctor_find($doctorId);
    if ($doctor === null || (int) $doctor['is_active'] !== 1) {
        return null;
    }

    return appointment_free_slots($doctorId, $date->format('Y-m-d'), $exceptId);
}

function appointment_doctor_busy(int $doctorId, string $date, string $time, ?int $exceptId): bool
{
    $stmt = db()->prepare(
        "SELECT 1 FROM appointments
          WHERE doctor_id = :doctor_id AND appointment_date = :date AND appointment_time = :time
            AND status <> 'cancelled' AND id <> :except_id LIMIT 1"
    );
    $stmt->execute(['doctor_id' => $doctorId, 'date' => $date, 'time' => $time . ':00', 'except_id' => $exceptId ?? 0]);
    return (bool) $stmt->fetchColumn();
}

function appointment_patient_busy(int $patientId, string $date, string $time, ?int $exceptId): bool
{
    $stmt = db()->prepare(
        "SELECT 1 FROM appointments
          WHERE patient_id = :patient_id AND appointment_date = :date AND appointment_time = :time
            AND status <> 'cancelled' AND id <> :except_id LIMIT 1"
    );
    $stmt->execute(['patient_id' => $patientId, 'date' => $date, 'time' => $time . ':00', 'except_id' => $exceptId ?? 0]);
    return (bool) $stmt->fetchColumn();
}

// ---- Reading ----------------------------------------------------------------

function appointment_find(int $id): ?array
{
    $stmt = db()->prepare(
        "SELECT a.id, a.doctor_id, a.patient_id, a.appointment_date,
                TIME_FORMAT(a.appointment_time, '%H:%i') AS appointment_time,
                a.status, a.reason,
                d.full_name AS doctor_name, p.full_name AS patient_name
           FROM appointments a
           JOIN doctors  d ON d.id = a.doctor_id
           JOIN patients p ON p.id = a.patient_id
          WHERE a.id = :id"
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

/**
 * Read list filters from a query string. Returns [filters, errors].
 * A filter that fails validation is ignored (and reported), never passed to SQL.
 */
function appointment_filters(array $query): array
{
    $filters = [
        'doctor_id'  => int_param($query, 'doctor_id'),
        'patient_id' => int_param($query, 'patient_id'),
        'date_from'  => '',
        'date_to'    => '',
    ];
    $errors = [];

    foreach (['date_from', 'date_to'] as $key) {
        $value = input_string($query, $key);
        if ($value === '') {
            continue;
        }
        if (parse_ymd($value) === null) {
            $errors[$key] = 'Enter a valid date.';
            continue;
        }
        $filters[$key] = $value;
    }

    // Y-m-d strings compare correctly as plain strings
    if ($filters['date_from'] !== '' && $filters['date_to'] !== '' && $filters['date_from'] > $filters['date_to']) {
        $errors['date_to'] = '"To" cannot be before "From".';
        $filters['date_to'] = '';
    }

    return [$filters, $errors];
}

/** Builds the WHERE clause for the filters. Values go in as bound parameters only. */
function appointments_where(array $filters): array
{
    $conditions = [];
    $params = [];

    if ($filters['doctor_id'] !== null) {
        $conditions[] = 'a.doctor_id = :doctor_id';
        $params['doctor_id'] = $filters['doctor_id'];
    }
    if ($filters['patient_id'] !== null) {
        $conditions[] = 'a.patient_id = :patient_id';
        $params['patient_id'] = $filters['patient_id'];
    }
    if ($filters['date_from'] !== '') {
        $conditions[] = 'a.appointment_date >= :date_from';
        $params['date_from'] = $filters['date_from'];
    }
    if ($filters['date_to'] !== '') {
        $conditions[] = 'a.appointment_date <= :date_to';
        $params['date_to'] = $filters['date_to'];
    }

    return [$conditions ? 'WHERE ' . implode(' AND ', $conditions) : '', $params];
}

function appointments_count(array $filters): int
{
    [$where, $params] = appointments_where($filters);
    $stmt = db()->prepare("SELECT COUNT(*) FROM appointments a $where");
    $stmt->execute($params);

    return (int) $stmt->fetchColumn();
}

/** Newest first, with doctor and patient names. Capped at APPOINTMENT_LIST_LIMIT rows. */
function appointments_list(array $filters): array
{
    [$where, $params] = appointments_where($filters);
    $stmt = db()->prepare(
        "SELECT a.id, a.appointment_date, TIME_FORMAT(a.appointment_time, '%H:%i') AS appt_time,
                a.status, a.reason,
                d.full_name AS doctor_name, s.name AS specialization, p.full_name AS patient_name
           FROM appointments a
           JOIN doctors d         ON d.id = a.doctor_id
           JOIN specializations s ON s.id = d.specialization_id
           JOIN patients p        ON p.id = a.patient_id
           $where
          ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.id DESC
          LIMIT " . APPOINTMENT_LIST_LIMIT
    );
    $stmt->execute($params);

    return $stmt->fetchAll();
}

// ---- Validation and writing -------------------------------------------------

/**
 * Validate a booking or a reschedule. For a reschedule pass the appointment's own
 * id as $exceptId, and set patient_id / doctor_id in $input from the database row
 * (never from the form).  Returns [cleanedData, errors].
 */
function appointment_validate(array $input, ?int $exceptId = null): array
{
    $data = [
        'patient_id'       => (int) input_string($input, 'patient_id'),
        'doctor_id'        => (int) input_string($input, 'doctor_id'),
        'appointment_date' => input_string($input, 'appointment_date'),
        'appointment_time' => input_string($input, 'appointment_time'),
        'reason'           => input_string($input, 'reason'),
    ];
    $errors = [];

    $doctor = $data['doctor_id'] > 0 ? doctor_find($data['doctor_id']) : null;
    if ($doctor === null || (int) $doctor['is_active'] !== 1) {
        $errors['doctor_id'] = 'Choose an active doctor from the list.';
    }

    if ($data['patient_id'] < 1 || patient_find($data['patient_id']) === null) {
        $errors['patient_id'] = 'Choose a patient from the list.';
    }

    $date = parse_ymd($data['appointment_date']);
    $today = new DateTimeImmutable('today');
    if ($date === null) {
        $errors['appointment_date'] = 'Enter a valid date.';
    } elseif ($date < $today) {
        $errors['appointment_date'] = 'Choose today or a later date.';
    } elseif ($date > $today->modify('+' . MAX_BOOKING_DAYS . ' days')) {
        $errors['appointment_date'] = 'Bookings can be made up to ' . MAX_BOOKING_DAYS . ' days ahead.';
    }

    if (!in_array($data['appointment_time'], slot_times(), true)) {
        $errors['appointment_time'] = 'Choose a time from the list.';
    } elseif ($date !== null && !isset($errors['appointment_date'])
              && appointment_is_past($data['appointment_date'], $data['appointment_time'])) {
        $errors['appointment_time'] = 'That time has already passed today.';
    }

    if (mb_strlen($data['reason']) > 255) {
        $errors['reason'] = 'Reason must be 255 characters or fewer.';
    }

    // Friendly pre-checks. They give nice messages, but a race can still slip past them:
    // the UNIQUE keys in the database are the real guarantee (see appointment_conflict_error).
    $slotUsable = !isset($errors['doctor_id']) && !isset($errors['appointment_date']) && !isset($errors['appointment_time']);
    if ($slotUsable && appointment_doctor_busy($data['doctor_id'], $data['appointment_date'], $data['appointment_time'], $exceptId)) {
        $errors['appointment_time'] = sprintf('%s is already booked at %s on that date.', $doctor['full_name'], $data['appointment_time']);
    }
    if ($slotUsable && !isset($errors['patient_id'])
        && appointment_patient_busy($data['patient_id'], $data['appointment_date'], $data['appointment_time'], $exceptId)) {
        $errors['patient_id'] = 'This patient already has an appointment at that time.';
    }

    return [$data, $errors];
}

/** Cleaned data -> the bound parameters for the appointments table. */
function appointment_db_params(array $data): array
{
    return [
        'doctor_id'        => $data['doctor_id'],
        'patient_id'       => $data['patient_id'],
        'appointment_date' => $data['appointment_date'],
        'appointment_time' => $data['appointment_time'] . ':00',
        'reason'           => $data['reason'] === '' ? null : $data['reason'],
    ];
}

/** $data must be the cleaned array from appointment_validate(). Returns the new id. */
function appointment_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, reason)
         VALUES (:doctor_id, :patient_id, :appointment_date, :appointment_time, :reason)'
    );
    $stmt->execute(appointment_db_params($data));

    return (int) db()->lastInsertId();
}

/** Change date, time and reason of a scheduled appointment. Doctor and patient never change. */
function appointment_reschedule(int $id, array $data): void
{
    $params = appointment_db_params($data);
    unset($params['doctor_id'], $params['patient_id']);

    $stmt = db()->prepare(
        "UPDATE appointments
            SET appointment_date = :appointment_date, appointment_time = :appointment_time, reason = :reason
          WHERE id = :id AND status = 'scheduled'"
    );
    $stmt->execute($params + ['id' => $id]);
}

/**
 * Translate a UNIQUE-key failure into [fieldName, message], or null if the exception
 * is something else (the caller must rethrow it). Which key fired is in the error text.
 */
function appointment_conflict_error(PDOException $e): ?array
{
    if (!is_duplicate_key($e)) {
        return null;
    }
    if (str_contains($e->getMessage(), 'uq_patient_slot')) {
        return ['patient_id', 'This patient already has an appointment at that time.'];
    }

    return ['appointment_time', 'That time was just taken by another booking. Choose a different time.'];
}

/**
 * Move a SCHEDULED appointment to completed / cancelled / no_show.
 * One atomic UPDATE with "AND status = 'scheduled'" in the WHERE clause, so two people
 * clicking at once cannot both succeed. Returns false if nothing was changed.
 */
function appointment_change_status(int $id, string $newStatus): bool
{
    if (!in_array($newStatus, APPOINTMENT_NEW_STATUSES, true)) {
        return false;
    }
    $stmt = db()->prepare("UPDATE appointments SET status = :status WHERE id = :id AND status = 'scheduled'");
    $stmt->execute(['status' => $newStatus, 'id' => $id]);

    return $stmt->rowCount() === 1;
}
