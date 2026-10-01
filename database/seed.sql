-- Run after schema.sql:  mysql -u root -p clinic_db < database/seed.sql
INSERT INTO specializations (name) VALUES
  ('General Physician'), ('Cardiology'), ('Dermatology'), ('Dentistry'),
  ('ENT'), ('Gynecology'), ('Neurology'), ('Ophthalmology'),
  ('Orthopedics'), ('Pediatrics'), ('Psychiatry');
