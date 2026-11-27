CREATE DATABASE IF NOT EXISTS joey_tahian;
USE joey_tahian;

CREATE TABLE IF NOT EXISTS users (                 -- admin only
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS inventory (             -- D2
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  category VARCHAR(60),
  price DECIMAL(10,2) NOT NULL,
  quantity INT NOT NULL DEFAULT 0,
  stage ENUM('made','packed','on_hand') NOT NULL DEFAULT 'on_hand',
  item_condition ENUM('new','returned_good','returned_damaged') NOT NULL DEFAULT 'new',
    image_url VARCHAR(500) DEFAULT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS quotes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(100) NOT NULL,
  contact VARCHAR(100) NOT NULL,
  requested_items TEXT NOT NULL,
  status ENUM('pending','reviewed','contacted','closed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (                -- D1
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_no VARCHAR(20) UNIQUE NOT NULL,
  customer_name VARCHAR(100) NOT NULL,
  contact VARCHAR(100) DEFAULT NULL, -- retained for installations using the original schema
  email VARCHAR(120) NOT NULL,
  mobile VARCHAR(30) NOT NULL,
  address VARCHAR(255) NOT NULL,
  total DECIMAL(12,2) NOT NULL,
  payment_method VARCHAR(20) NOT NULL DEFAULT 'COD',
  status ENUM('pending','confirmed','packed','delivered','cancelled') NOT NULL DEFAULT 'pending',
  confirmation_token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  confirmation_expires_at DATETIME DEFAULT NULL,
  email_confirmed_at DATETIME DEFAULT NULL,
  confirmation_email_sent_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_orders_confirmation_token (confirmation_token_hash)
);

CREATE TABLE IF NOT EXISTS order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  inventory_id INT NOT NULL,
  qty INT NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (inventory_id) REFERENCES inventory(id)
);

CREATE TABLE IF NOT EXISTS settings (             -- app settings
  setting_key VARCHAR(100) PRIMARY KEY,
  setting_value TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS sales_summary (         -- D4
  id INT AUTO_INCREMENT PRIMARY KEY,
  period_type ENUM('day','week','month','year') NOT NULL,
  period_start DATE NOT NULL,
  total_orders INT NOT NULL,
  total_sales DECIMAL(12,2) NOT NULL,
  refreshed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_period (period_type, period_start)
);

