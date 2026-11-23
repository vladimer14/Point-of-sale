-- Point of Sale Version 1 - Database Schema
-- Import this file via phpMyAdmin or run install.php

CREATE DATABASE IF NOT EXISTS pos_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'cashier') DEFAULT 'cashier',
    full_name VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    category_id INT,
    sku VARCHAR(50) UNIQUE,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    image VARCHAR(255) DEFAULT 'placeholder.png',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Sales table
CREATE TABLE IF NOT EXISTS sales (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    payment_method ENUM('cash', 'card') DEFAULT 'cash',
    payment_amount DECIMAL(10,2) DEFAULT 0.00,
    change_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

-- Sale items table
CREATE TABLE IF NOT EXISTS sale_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB;

-- Default categories
INSERT IGNORE INTO categories (name) VALUES 
('Electronics'),
('Clothing'),
('Food & Beverages'),
('Groceries'),
('Accessories');

-- Default admin user: username=admin, password=admin123
INSERT IGNORE INTO users (username, password, role, full_name) VALUES 
('admin', '$2y$10$8K1p7Q3w5E6r8T0yU2iO1p3A5sD7fG9hJkL1zXcVbNmQwErTyUiOp', 'admin', 'Administrator');

-- Sample products
INSERT IGNORE INTO products (name, category_id, sku, price, stock, image) VALUES 
('Wireless Mouse', 1, 'MS-001', 25.00, 50, 'placeholder.png'),
('Mechanical Keyboard', 1, 'KB-002', 85.00, 30, 'placeholder.png'),
('USB-C Cable', 1, 'UC-003', 12.00, 100, 'placeholder.png'),
('T-Shirt Cotton', 2, 'TS-004', 18.00, 40, 'placeholder.png'),
('Jeans Denim', 2, 'JE-005', 45.00, 25, 'placeholder.png'),
('Coffee Beans 500g', 3, 'CB-006', 15.00, 60, 'placeholder.png'),
('Bottled Water', 3, 'BW-007', 2.00, 200, 'placeholder.png'),
('Phone Case', 4, 'PC-008', 9.00, 80, 'placeholder.png'),
('Headphones', 1, 'HP-009', 60.00, 20, 'placeholder.png'),
('Sneakers', 2, 'SN-010', 70.00, 15, 'placeholder.png');