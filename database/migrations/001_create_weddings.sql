CREATE TABLE IF NOT EXISTS weddings (
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
