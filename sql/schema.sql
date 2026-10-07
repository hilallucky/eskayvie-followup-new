CREATE TABLE ranks (
  rank_code VARCHAR(10) PRIMARY KEY,
  rank_name VARCHAR(50) NOT NULL,
  `no` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO ranks VALUES ('MGR','Manager',1),('SMGR','Sapphire Manager',2),('DMGR','Diamond Manager',3),('EMGR','Emerald Manager',4),('RMGR','Ruby Manager',5),('SMGR2','Sapphire Manager 2',6),('DMGR2','Diamond Manager 2',7),('EMGR2','Emerald Manager 2',8),('RMGR2','Ruby Manager 2',9),('SMGR3','Sapphire Manager 3',10),('DMGR3','Diamond Manager 3',11),('EMGR3','Emerald Manager 3',12),('RMGR3','Ruby Manager 3',13);

CREATE TABLE products (
  `no` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  product_code VARCHAR(20) NOT NULL UNIQUE,
  product_name VARCHAR(100) NOT NULL,
  product_price DECIMAL(10,2) NOT NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_date DATETIME NULL,
  created_by VARCHAR(30) NULL, updated_by VARCHAR(30) NULL, deleted_by VARCHAR(30) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
INSERT INTO products (product_code, product_name, product_price, status) VALUES
  ('P001','Phytax',100.00,'Active'),('P002','Nutrelle',200.00,'Active'),('P003','Savva',300.00,'Active'),('P004','Afeeya',400.00,'Active'),('P005','Xeero',500.00,'Active');

CREATE TABLE members (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  member_code VARCHAR(20) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  join_date DATE NOT NULL,
  rank_code VARCHAR(10) NOT NULL DEFAULT 'MGR',
  upline_code VARCHAR(20) NULL,
  sponsor_code VARCHAR(20) NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_date DATETIME NULL,
  created_by VARCHAR(30) NULL, updated_by VARCHAR(30) NULL, deleted_by VARCHAR(30) NULL,
  INDEX (sponsor_code), INDEX (upline_code),
  FOREIGN KEY (rank_code) REFERENCES ranks(rank_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL UNIQUE,
  member_code VARCHAR(20) NOT NULL UNIQUE,
  email VARCHAR(190) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  confirm_token CHAR(64) NULL,
  email_confirmed_at DATETIME NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_date DATETIME NULL,
  created_by VARCHAR(30) NULL, updated_by VARCHAR(30) NULL, deleted_by VARCHAR(30) NULL,
  FOREIGN KEY (member_code) REFERENCES members(member_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE followups (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(30) NOT NULL,
  member_code VARCHAR(20) NOT NULL,
  consume_product TINYINT(1) NOT NULL DEFAULT 0,
  product_name VARCHAR(100) NULL,
  consume_date DATE NULL,
  prospect_list TINYINT(1) NOT NULL DEFAULT 0,
  welcome_to_eskayvie TINYINT(1) NOT NULL DEFAULT 0,
  product TINYINT(1) NOT NULL DEFAULT 0,
  marketing_plan TINYINT(1) NOT NULL DEFAULT 0,
  hbo TINYINT(1) NOT NULL DEFAULT 0,
  remarks VARCHAR(255) NULL,
  created_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_date DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  deleted_date DATETIME NULL,
  created_by VARCHAR(30) NULL, updated_by VARCHAR(30) NULL, deleted_by VARCHAR(30) NULL,
  UNIQUE KEY uq_follow (username, member_code),
  FOREIGN KEY (username) REFERENCES users(username),
  FOREIGN KEY (member_code) REFERENCES members(member_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
