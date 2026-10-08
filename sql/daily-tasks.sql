CREATE TABLE daily_task_months (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL,
  task_month TINYINT UNSIGNED NOT NULL,
  task_year SMALLINT UNSIGNED NOT NULL,
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_daily_task_month (username, task_year, task_month),
  FOREIGN KEY (username) REFERENCES users(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE daily_task_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  month_record_id INT UNSIGNED NOT NULL,
  day_of_month TINYINT UNSIGNED NOT NULL,
  learn TINYINT(1) NOT NULL DEFAULT 0,
  video_onboarding TINYINT(1) NOT NULL DEFAULT 0,
  consume_product TINYINT(1) NOT NULL DEFAULT 0,
  repeat_order TINYINT(1) NOT NULL DEFAULT 0,
  attend_zoom TINYINT(1) NOT NULL DEFAULT 0,
  contact_upline TINYINT(1) NOT NULL DEFAULT 0,
  sponsoring TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_daily_task_day (month_record_id, day_of_month),
  FOREIGN KEY (month_record_id) REFERENCES daily_task_months(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;