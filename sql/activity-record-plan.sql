CREATE TABLE activity_plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL,
  plan_month TINYINT UNSIGNED NOT NULL,
  plan_year SMALLINT UNSIGNED NOT NULL,
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_activity_plan (username, plan_year, plan_month),
  FOREIGN KEY (username) REFERENCES users(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE activity_plan_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activity_plan_id INT UNSIGNED NOT NULL,
  day_of_month TINYINT UNSIGNED NOT NULL,
  activity TEXT NULL,
  status_id SMALLINT UNSIGNED NULL,
  UNIQUE KEY uq_activity_plan_day (activity_plan_id, day_of_month),
  FOREIGN KEY (activity_plan_id) REFERENCES activity_plans(id) ON DELETE CASCADE,
  FOREIGN KEY (status_id) REFERENCES statuses(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;