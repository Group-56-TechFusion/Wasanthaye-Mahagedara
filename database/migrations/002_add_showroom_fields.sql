ALTER TABLE branches
  ADD COLUMN type ENUM('head_office','showroom') NOT NULL DEFAULT 'showroom' AFTER name,
  ADD COLUMN address VARCHAR(255) NOT NULL DEFAULT '' AFTER type,
  ADD COLUMN contact_number VARCHAR(30) NULL AFTER address,
  ADD COLUMN status ENUM('active','inactive') NOT NULL DEFAULT 'active' AFTER contact_number;

UPDATE branches
SET type = 'head_office'
WHERE name = 'Nittambuwa';
