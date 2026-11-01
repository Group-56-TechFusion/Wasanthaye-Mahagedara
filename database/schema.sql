CREATE TABLE branches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE NOT NULL
);

INSERT INTO branches (name) VALUES
('Nittambuwa'), ('Kadawatha'), ('Malabe'), ('Kurunegala');

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','branch_manager') NOT NULL,
  branch_id INT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
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