-- Billing module tables. Run once in phpMyAdmin (database app_db).

CREATE TABLE IF NOT EXISTS bills (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bill_number VARCHAR(30) NOT NULL UNIQUE,
  booking_date DATE NOT NULL,
  branch_id INT NOT NULL,                       -- showroom that created the bill
  created_by INT NULL,                          -- users.id of the manager
  is_mobile_shop TINYINT(1) NOT NULL DEFAULT 0, -- created at the mobile shop

  customer_name VARCHAR(150) NOT NULL,
  customer_email VARCHAR(150) NOT NULL DEFAULT '',
  address VARCHAR(255) NOT NULL DEFAULT '',
  phone1 VARCHAR(25) NOT NULL,
  phone2 VARCHAR(25) NOT NULL DEFAULT '',
  note TEXT NULL,

  wedding_date DATE NULL,                       -- NULL = not decided / postponed
  dressing_location VARCHAR(150) NOT NULL DEFAULT '',
  wedding_hotel VARCHAR(150) NOT NULL DEFAULT '',

  groom_jacket_id INT UNSIGNED NULL,
  bestman_jacket_id INT UNSIGNED NULL,
  groom_kawani VARCHAR(100) NOT NULL DEFAULT '',
  bestman_kawani VARCHAR(100) NOT NULL DEFAULT '',
  bestmen_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  pageboys_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  fiber_sword TINYINT(1) NOT NULL DEFAULT 0,
  white_kawani TINYINT(1) NOT NULL DEFAULT 0,
  going_away_kit TINYINT(1) NOT NULL DEFAULT 0,
  homecoming_kit TINYINT(1) NOT NULL DEFAULT 0,

  package_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  transport DECIMAL(12,2) NOT NULL DEFAULT 0,
  discount DECIMAL(12,2) NOT NULL DEFAULT 0,
  total DECIMAL(12,2) NOT NULL DEFAULT 0,       -- package + transport - discount

  is_postponed TINYINT(1) NOT NULL DEFAULT 0,
  postponed_from DATE NULL,                     -- wedding date before postponing
  deleted_at DATETIME NULL,                     -- recycle bin = deleted_at IS NOT NULL
  deleted_by INT NULL,

  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_bills_branch (branch_id),
  INDEX idx_bills_wedding (wedding_date),
  INDEX idx_bills_booking (booking_date),
  INDEX idx_bills_deleted (deleted_at),
  FOREIGN KEY (branch_id) REFERENCES branches(id),
  FOREIGN KEY (groom_jacket_id) REFERENCES jackets(id) ON DELETE SET NULL,
  FOREIGN KEY (bestman_jacket_id) REFERENCES jackets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- seq 1 = advance, 2..4 = installments. branch_id = showroom the payment was taken at.
CREATE TABLE IF NOT EXISTS bill_payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bill_id INT UNSIGNED NOT NULL,
  seq TINYINT UNSIGNED NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  payment_date DATE NOT NULL,
  method ENUM('cash','online') NOT NULL DEFAULT 'cash',
  branch_id INT NOT NULL,
  recorded_by INT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_bill_seq (bill_id, seq),
  INDEX idx_pay_branch_date (branch_id, payment_date),
  FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
  FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bill_party_members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bill_id INT UNSIGNED NOT NULL,
  member_type ENUM('groom','bestman','pageboy') NOT NULL,
  position TINYINT UNSIGNED NOT NULL DEFAULT 1,
  name VARCHAR(100) NOT NULL DEFAULT '',
  cap_size VARCHAR(30) NOT NULL DEFAULT '',
  jacket_size VARCHAR(30) NOT NULL DEFAULT '',
  trouser_size VARCHAR(30) NOT NULL DEFAULT '',
  shoe_size VARCHAR(30) NOT NULL DEFAULT '',
  FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
