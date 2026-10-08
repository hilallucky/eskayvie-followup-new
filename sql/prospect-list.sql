CREATE TABLE prospects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL,
  name VARCHAR(120) NOT NULL,
  phone_number VARCHAR(40) NULL,
  domicile VARCHAR(120) NULL,
  occupation VARCHAR(120) NULL,
  prospect_date DATE NULL,
  response ENUM('Baik','Kurang','Tidak Baik') NULL,
  reason TEXT NULL,
  follow_up TINYINT(1) NOT NULL DEFAULT 0,
  follow_up_date DATE NULL,
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_prospects_user (username, id),
  FOREIGN KEY (username) REFERENCES users(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;