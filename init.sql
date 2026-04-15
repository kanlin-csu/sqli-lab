SET NAMES utf8mb4;

GRANT FILE ON *.* TO 'sqli'@'%';
FLUSH PRIVILEGES;

CREATE DATABASE IF NOT EXISTS sqli_lab;
USE sqli_lab;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50),
  password VARCHAR(50),
  email VARCHAR(100),
  role VARCHAR(50)
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO users (username, password, email, role) VALUES
('admin', '123456', 'admin@example.com', 'admin'),
('secret', 'password', 'sec@example.com', 'staff'),
('backup', 'user1234', 'backup@example.com', 'guest');

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  description TEXT
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO products (name, description) VALUES
('蘋果', '健康水果'),
('筆電', '效能優良'),
('可樂', '含糖飲料');

CREATE TABLE items (
  id INT PRIMARY KEY,
  title VARCHAR(255),
  description TEXT,
  body TEXT
) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

INSERT INTO items (id, title, description, body) VALUES
(1, '新聞一', '簡介一', '內文一...'),
(2, '新聞二', '簡介二', '內文二...'),
(3, '新聞三', '簡介三', '內文三...');
