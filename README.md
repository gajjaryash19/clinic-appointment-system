# Clinic Appointment System

PHP 8.1+ and MySQL 8 (or MariaDB 10.5+). No framework, no Composer.

## Setup
1. Import the schema and seed data:
   ```
   mysql -u root -p < database/schema.sql
   mysql -u root -p clinic_db < database/seed.sql
   ```
2. Edit `config/config.php` (database user/password and `BASE_URL`).
3. Run it, either:
   - **XAMPP:** copy this folder into `htdocs/`, open `http://localhost/clinic-appointment-system/public/`
   - **PHP built-in server:** set `BASE_URL` to `''`, then `php -S localhost:8000 -t public`

## Features built so far
- [x] Foundation (config, DB connection, layout, CSRF, flash messages)
- [x] Doctors: add, list, edit, deactivate/reactivate
- [x] Patients: register, list, edit (no delete, by design)
- [x] Appointments: book, list + filters (doctor, patient, date range), status changes, reschedule

## Upgrading an existing database
If you imported `schema.sql` before the Appointments feature existed, run once:
```
mysql -u root -p < database/migrations/001_patient_slot_unique.sql
```
