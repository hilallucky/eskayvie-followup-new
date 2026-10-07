CREATE TABLE customers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL,
  name VARCHAR(120) NOT NULL,
  phone_number VARCHAR(40) NULL,
  birth_date DATE NULL,
  order_date DATE NULL,
  notes TEXT NULL,
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_customers_user_name (username, name),
  FOREIGN KEY (username) REFERENCES users(username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE customer_products (
  customer_id INT UNSIGNED NOT NULL,
  product_code VARCHAR(20) NOT NULL,
  PRIMARY KEY (customer_id, product_code),
  FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
  FOREIGN KEY (product_code) REFERENCES products(product_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;