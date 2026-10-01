<?php
declare(strict_types=1);

/**
 * Data access and business rules for patients.
 * There is deliberately no delete: patient records are kept for good.
 */

const PATIENT_GENDERS = ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'];

/** All patients, with age calculated by MySQL from the date of birth. */
function patients_list(): array
{
    return db()->query(
        'SELECT id, full_name, gender, date_of_birth, phone, email,
                TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) AS age
           FROM patients
          ORDER BY full_name'
    )->fetchAll();
}

function patient_find(int $id): ?array
{
    $stmt = db()->prepare(
        'SELECT id, full_name, gender, date_of_birth, phone, email, address
           FROM patients WHERE id = :id'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row === false ? null : $row;
}

/**
 * Clean and validate submitted form data.
 * Returns [cleanedData, errors]. errors is empty when the data is valid.
 * Optional fields (email, address) come back as '' when left blank.
 */
function patient_validate(array $input): array
{
    $data = [
        'full_name'     => input_string($input, 'full_name'),
        'gender'        => input_string($input, 'gender'),
        'date_of_birth' => input_string($input, 'date_of_birth'),
        'phone'         => normalize_phone(input_string($input, 'phone')),
        'email'         => strtolower(input_string($input, 'email')),
        'address'       => input_string($input, 'address'),
    ];
    $errors = [];

    $nameLength = mb_strlen($data['full_name']);
    if ($nameLength < 2 || $nameLength > 100) {
        $errors['full_name'] = 'Enter a name between 2 and 100 characters.';
    }

    if (!array_key_exists($data['gender'], PATIENT_GENDERS)) {
        $errors['gender'] = 'Choose a gender from the list.';
    }

    $dob = parse_ymd($data['date_of_birth']);
    if ($dob === null) {
        $errors['date_of_birth'] = 'Enter a valid date of birth.';
    } else {
        $today = new DateTimeImmutable('today');
        if ($dob > $today) {
            $errors['date_of_birth'] = 'Date of birth cannot be in the future.';
        } elseif ($dob < $today->modify('-120 years')) {
            $errors['date_of_birth'] = 'Date of birth is more than 120 years ago. Check the year.';
        }
    }

    if (!is_valid_phone($data['phone'])) {
        $errors['phone'] = 'Enter 7 to 15 digits, with an optional leading +.';
    }

    if ($data['email'] !== ''
        && (strlen($data['email']) > 120 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL))) {
        $errors['email'] = 'Enter a valid email address, or leave it blank.';
    }

    if (mb_strlen($data['address']) > 255) {
        $errors['address'] = 'Address must be 255 characters or fewer.';
    }

    return [$data, $errors];
}

/** Blank optional fields are stored as NULL, not as empty strings. */
function patient_db_params(array $data): array
{
    $data['email']   = $data['email'] === '' ? null : $data['email'];
    $data['address'] = $data['address'] === '' ? null : $data['address'];
    return $data;
}

/** $data must be the cleaned array from patient_validate(). Returns the new id. */
function patient_create(array $data): int
{
    $stmt = db()->prepare(
        'INSERT INTO patients (full_name, gender, date_of_birth, phone, email, address)
         VALUES (:full_name, :gender, :date_of_birth, :phone, :email, :address)'
    );
    $stmt->execute(patient_db_params($data));

    return (int) db()->lastInsertId();
}

function patient_update(int $id, array $data): void
{
    $stmt = db()->prepare(
        'UPDATE patients
            SET full_name = :full_name, gender = :gender, date_of_birth = :date_of_birth,
                phone = :phone, email = :email, address = :address
          WHERE id = :id'
    );
    $stmt->execute(patient_db_params($data) + ['id' => $id]);
}
