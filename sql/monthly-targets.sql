CREATE TABLE statuses (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  status_name VARCHAR(50) NOT NULL UNIQUE,
  sort_order SMALLINT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO statuses (status_name, sort_order) VALUES
  ('Not Started', 1), ('In Progress', 2), ('Achieved', 3), ('Not Achieved', 4);

CREATE TABLE monthly_targets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL,
  target_month TINYINT UNSIGNED NOT NULL,
  target_year SMALLINT UNSIGNED NOT NULL,
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_monthly_target (username, target_year, target_month),
  FOREIGN KEY (username) REFERENCES users(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE monthly_target_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  monthly_target_id INT UNSIGNED NOT NULL,
  day_of_month TINYINT UNSIGNED NOT NULL,
  target_income BIGINT UNSIGNED NULL,
  target_recruit INT UNSIGNED NULL,
  target_ro INT UNSIGNED NULL,
  rank_code VARCHAR(10) NULL,
  effort TEXT NULL,
  status_id SMALLINT UNSIGNED NULL,
  UNIQUE KEY uq_monthly_target_day (monthly_target_id, day_of_month),
  FOREIGN KEY (monthly_target_id) REFERENCES monthly_targets(id) ON DELETE CASCADE,
  FOREIGN KEY (rank_code) REFERENCES ranks(rank_code),
  FOREIGN KEY (status_id) REFERENCES statuses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;