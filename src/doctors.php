<?php
declare(strict_types=1);

/**
 * Data access and business rules for doctors.
 * No HTML in this file: pages call these functions.
 */

function specializations_all(): array
{
    return db()->query('SELECT id, name FROM specializations ORDER BY name')->fetchAll();
}

function specialization_exists(int $id): bool
{
    $stmt = db()->prepare('SELECT 1 FROM specializations WHERE id = :id');
    $stmt->execute(['id' => $id]);
    return (bool) $stmt->fetchColumn();
}

/** Doctors with their specialization name. Active only, unless $includeInactive. */
function doctors_list(bool $includeInactive = false): array
{
    $sql = 'SELECT d.id, d.full_name, d.email, d.phone, d.is_active, s.name AS specialization
              FROM doctors d
              JOIN specializations s ON s.id = d.specialization_id';
    if (!$includeInactive) {
        $sql .= ' WHERE d.is_active = 1';
    }
    $sql .= ' ORDER BY d.full_name';

    return db()->query($sql)->fetchAll();
}

function doctor_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT id, full_name, specialization_id, email, phone, is_active
           FROM doctors WHERE id = :id'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

function doctor_email_taken(string $email, ?int $exceptId = null): bool
{
    // ids start at 1, so 0 means "no record to exclude"
    $stmt = db()->prepare('SELECT 1 FROM doctors WHERE email = :email AND id <> :id LIMIT 1');
    $stmt->execute(['email' => $email, 'id' => $exceptId ?? 0]);
    return (bool) $stmt->fetchColumn();
}

/**
 * Clean and validate submitted form data.
 * Returns [cleanedData, errors]. errors is empty when the data is valid.
 */
function doctor_validate(array $input, ?int $exceptId = null): array
{
    $data = [
        'full_name'         => input_string($input, 'full_name'),
        'specialization_id' => (int) input_string($input, 'specialization_id'),
        'email'             => strtolower(input_string($input, 'email')),
        'phone'             => normalize_phone(input_string($input, 'phone')),
    ];
    $errors = [];

    $nameLength = mb_strlen($data['full_name']);
    if ($nameLength < 2 || $nameLength > 100) {
        $errors['full_name'] = 'Enter a name between 2 and 100 characters.';
    }

    if ($data['specialization_id'] < 1 || !specialization_exists($data['specialization_id'])) {
        $errors['specialization_id'] = 'Choose a specialization from the list.';
    }

    if (strlen($data['email']) > 120 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } elseif (doctor_email_taken($data['email'], $exceptId)) {
        $errors['email'] = 'Another doctor already uses this email.';
    }

    if (!is_valid_phone($data['phone'])) {
        $errors['phone'] = 'Enter 7 to 15 digits, with an optional leading +.';
    }

    return [$data, $errors];
}

/** $data must be the cleaned array from doctor_validate(). Returns the new id. */
function doctor_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO doctors (full_name, specialization_id, email, phone)
         VALUES (:full_name, :specialization_id, :email, :phone)'
    );
    $stmt->execute($data);

    return (int) db()->lastInsertId();
}

function doctor_update(int $id, array $data): void
{
    $stmt = db()->prepare(
        'UPDATE doctors
            SET full_name = :full_name, specialization_id = :specialization_id,
                email = :email, phone = :phone
          WHERE id = :id'
    );
    $stmt->execute($data + ['id' => $id]);
}

/** Scheduled appointments from today onwards: these block deactivation. */
function doctor_upcoming_count(int $id): int
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM appointments
          WHERE doctor_id = :id AND status = 'scheduled' AND appointment_date >= CURDATE()"
    );
    $stmt->execute(['id' => $id]);
    return (int) $stmt->fetchColumn();
}

function doctor_set_active(int $id, bool $active): void
{
    $stmt = db()->prepare('UPDATE doctors SET is_active = :active WHERE id = :id');
    $stmt->execute(['active' => $active ? 1 : 0, 'id' => $id]);
}
