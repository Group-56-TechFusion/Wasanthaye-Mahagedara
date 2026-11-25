CREATE TABLE IF NOT EXISTS customers (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
