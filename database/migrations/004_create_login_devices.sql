CREATE TABLE IF NOT EXISTS login_devices (
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
  CONSTRAINT fk_login_devices_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_login_devices_reviewer FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
);
