-- Clinic Appointment System - full schema (MySQL 8 / MariaDB 10.5+)
-- Run once:  mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS clinic_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE clinic_db;

CREATE TABLE IF NOT EXISTS admins (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS specializations (
  id   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS doctors (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name         VARCHAR(100) NOT NULL,
  specialization_id INT UNSIGNED NOT NULL,
  email             VARCHAR(120) NOT NULL UNIQUE,
  phone             VARCHAR(20)  NOT NULL,
  is_active         TINYINT(1)   NOT NULL DEFAULT 1,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_doctor_spec FOREIGN KEY (specialization_id)
    REFERENCES specializations(id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS patients (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name     VARCHAR(100) NOT NULL,
  gender        ENUM('male','female','other') NOT NULL,
  date_of_birth DATE NOT NULL,
  phone         VARCHAR(20)  NOT NULL,
  email         VARCHAR(120) NULL,
  address       VARCHAR(255) NULL,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS appointments (
  id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  doctor_id        INT UNSIGNED NOT NULL,
  patient_id       INT UNSIGNED NOT NULL,
  appointment_date DATE NOT NULL,
  appointment_time TIME NOT NULL,
  status           ENUM('scheduled','completed','cancelled','no_show')
                   NOT NULL DEFAULT 'scheduled',
  reason           VARCHAR(255) NULL,
  -- NULL when cancelled, 1 otherwise: lets the unique key ignore cancelled rows
  active_slot      TINYINT GENERATED ALWAYS AS (IF(status = 'cancelled', NULL, 1)) STORED,
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_appt_doctor  FOREIGN KEY (doctor_id)  REFERENCES doctors(id)  ON DELETE RESTRICT,
  CONSTRAINT fk_appt_patient FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE RESTRICT,

  -- One live appointment per doctor per slot, and one per patient per slot.
  -- Cancelled rows have active_slot = NULL, so they never block anything.
  UNIQUE KEY uq_doctor_slot  (doctor_id,  appointment_date, appointment_time, active_slot),
  UNIQUE KEY uq_patient_slot (patient_id, appointment_date, appointment_time, active_slot),
  KEY idx_appt_date (appointment_date)
) ENGINE=InnoDB;
