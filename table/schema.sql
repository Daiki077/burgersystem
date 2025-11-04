CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(150) NOT NULL,
  username VARCHAR(60) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('superadmin','admin') NOT NULL,
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);                        

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_name VARCHAR(150) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL,
  image VARCHAR(255) DEFAULT NULL,
  added_by INT DEFAULT NULL,
  date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  total DECIMAL(12,2) NOT NULL,
  payment_amount DECIMAL(12,2) DEFAULT NULL,
  change_amount DECIMAL(12,2) DEFAULT NULL,
  status ENUM('pending','received') NOT NULL DEFAULT 'pending',
  received_by INT DEFAULT NULL,
  received_at DATETIME DEFAULT NULL,
  date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  product_name VARCHAR(150) NOT NULL,
  product_id INT NOT NULL,
  qty INT NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  date_added DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (fullname, username, password, role, status)
VALUES ('Ichel abadinas','superadmin','$2a$12$2tFyy1yRW/ET4vFV4KdWDOoqtF80Z6xI6C.z9M7VwcAq2fbZNNAxG','superadmin','active'
);