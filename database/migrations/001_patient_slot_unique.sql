-- Only needed if you imported an EARLIER schema.sql (before the Appointments feature).
-- New installs already have this. Run once:
--   mysql -u root -p < database/migrations/001_patient_slot_unique.sql
USE clinic_db;

ALTER TABLE appointments
  ADD UNIQUE KEY uq_patient_slot (patient_id, appointment_date, appointment_time, active_slot),
  DROP INDEX idx_appt_patient_date;  -- now redundant: uq_patient_slot starts with the same columns
