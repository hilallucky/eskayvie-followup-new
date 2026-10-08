CREATE TABLE side_volume_months (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL,
  target_year SMALLINT UNSIGNED NOT NULL,
  target_month TINYINT UNSIGNED NOT NULL,
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_side_volume_month (username, target_year, target_month),
  FOREIGN KEY (username) REFERENCES users(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE side_volume_entries (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  side_volume_month_id INT UNSIGNED NOT NULL,
  product_code VARCHAR(20) NOT NULL,
  target_value DECIMAL(15,2) NULL,
  actual_value DECIMAL(15,2) NULL,
  UNIQUE KEY uq_side_volume_product (side_volume_month_id, product_code),
  FOREIGN KEY (side_volume_month_id) REFERENCES side_volume_months(id) ON DELETE CASCADE,
  FOREIGN KEY (product_code) REFERENCES products(product_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;