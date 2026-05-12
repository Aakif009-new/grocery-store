-- Create Database
CREATE DATABASE IF NOT EXISTS grocery_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE grocery_store;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- Categories Table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    parent_id INT DEFAULT NULL,
    image VARCHAR(255),
    description TEXT,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_slug (slug)
) ENGINE=InnoDB;

-- Products Table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    original_price DECIMAL(10, 2),
    category_id INT NOT NULL,
    brand VARCHAR(100),
    image VARCHAR(255),
    images TEXT,
    stock INT DEFAULT 0,
    unit VARCHAR(50) DEFAULT 'piece',
    weight VARCHAR(50),
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    views INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_category (category_id),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB;

-- Cart Table
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, session_id, product_id),
    INDEX idx_session (session_id)
) ENGINE=InnoDB;

-- Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    discount_amount DECIMAL(10, 2) DEFAULT 0,
    shipping_fee DECIMAL(10, 2) DEFAULT 0,
    tax_amount DECIMAL(10, 2) DEFAULT 0,
    grand_total DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    payment_method VARCHAR(50) NOT NULL,
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    shipping_name VARCHAR(100) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    shipping_address TEXT NOT NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_state VARCHAR(100) NOT NULL,
    shipping_zip VARCHAR(20) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_order_number (order_number),
    INDEX idx_user (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- Order Items Table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_order (order_id)
) ENGINE=InnoDB;

-- Insert Sample Categories
INSERT INTO categories (name, slug, description) VALUES
('Fruits & Vegetables', 'fruits-vegetables', 'Fresh fruits and vegetables'),
('Dairy & Eggs', 'dairy-eggs', 'Milk, cheese, yogurt and eggs'),
('Bakery', 'bakery', 'Fresh bread, cakes and pastries'),
('Meat & Seafood', 'meat-seafood', 'Fresh meat and seafood'),
('Beverages', 'beverages', 'Drinks and beverages'),
('Snacks', 'snacks', 'Chips, cookies and snacks');

-- Insert Sample Products
INSERT INTO products (name, slug, description, price, original_price, category_id, brand, image, stock, unit, is_featured) VALUES
('Fresh Organic Bananas', 'fresh-organic-bananas', 'Sweet and ripe organic bananas', 2.99, 3.99, 1, 'Organic Valley', 'banana.jpg', 150, '1 dozen', 1),
('Whole Milk', 'whole-milk', 'Fresh whole milk 1 gallon', 4.49, 4.99, 2, 'Dairy Fresh', 'milk.jpg', 200, '1 gallon', 1),
('Artisan Sourdough Bread', 'artisan-sourdough-bread', 'Freshly baked sourdough bread', 5.99, 6.99, 3, 'Bakers Choice', 'bread.jpg', 80, '1 loaf', 0),
('Premium Chicken Breast', 'premium-chicken-breast', 'Boneless skinless chicken breast', 8.99, 9.99, 4, 'Farm Fresh', 'chicken.jpg', 100, '1 lb', 1),
('Orange Juice', 'orange-juice', '100% pure orange juice', 3.99, 4.49, 5, 'Tropicana', 'orange-juice.jpg', 120, '64 oz', 0),
('Potato Chips', 'potato-chips', 'Crispy salted potato chips', 2.49, 2.99, 6, 'Lays', 'chips.jpg', 250, '8 oz', 0);

-- Insert Admin User (password: admin123)
INSERT INTO users (email, password_hash, full_name, phone, is_admin) VALUES
('admin@grocery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', '555-0000', 1);