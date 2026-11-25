CREATE TABLE branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE NOT NULL,
  type ENUM('head_office','showroom') NOT NULL DEFAULT 'showroom',
  address VARCHAR(255) NOT NULL DEFAULT '',
  contact_number VARCHAR(30) NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active'
);

INSERT INTO branches (name, type, address, status) VALUES
('Nittambuwa', 'head_office', '', 'active'),
('Kadawatha', 'showroom', '', 'active'),
('Malabe', 'showroom', '', 'active'),
('Kurunegala', 'showroom', '', 'active');

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','branch_manager') NOT NULL,
  branch_id INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE login_devices (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  user_agent VARCHAR(500) NOT NULL DEFAULT '',
  status ENUM('pending','approved','rejected','revoked') NOT NULL DEFAULT 'pending',
  first_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_by INT NULL,
  reviewed_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_login_devices_user_token (user_id, token_hash),
  KEY idx_login_devices_status_last_seen (status, last_seen_at),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL DEFAULT '',
  address VARCHAR(255) NOT NULL DEFAULT '',
  phone VARCHAR(25) NOT NULL UNIQUE,
  source ENUM('call_center','walk_in') NOT NULL DEFAULT 'walk_in',
  branch_id INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customers_branch (branch_id),
  FOREIGN KEY (branch_id) REFERENCES branches(id)
);

CREATE TABLE weddings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  groom_name VARCHAR(100) NOT NULL,
  bride_name VARCHAR(100) NOT NULL,
  wedding_date DATE NOT NULL,
  branch_id INT NOT NULL,
  location VARCHAR(150) NOT NULL DEFAULT '',
  hotel VARCHAR(150) NOT NULL DEFAULT '',
  estimated_amount DECIMAL(12, 2) NULL,
  maid_boys SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status VARCHAR(30) NOT NULL DEFAULT 'Pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_weddings_date_branch (wedding_date, branch_id),
  FOREIGN KEY (branch_id) REFERENCES branches(id)
);