CREATE DATABASE IF NOT EXISTS stationary_shop CHARACTER SET utf8mb4;
USE stationary_shop;

-- ---------------------------------------------------
-- Users
-- ---------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ---------------------------------------------------
-- Categories
-- ---------------------------------------------------
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- ---------------------------------------------------
-- Products
-- ---------------------------------------------------
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    stock INT DEFAULT 0,
    image VARCHAR(255) DEFAULT 'no-image.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- ---------------------------------------------------
-- Reviews & Ratings  (1 review per user per product)
-- ---------------------------------------------------
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (product_id, user_id)
);

-- ---------------------------------------------------
-- Cart (persisted per logged-in user)
-- ---------------------------------------------------
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- ---------------------------------------------------
-- Orders
-- ---------------------------------------------------
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    transaction_uuid VARCHAR(100) NOT NULL UNIQUE,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(30) DEFAULT 'esewa',
    status ENUM('pending','paid','failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- ---------------------------------------------------
-- Sample data
-- ---------------------------------------------------
INSERT INTO categories (name) VALUES
('Pens & Pencils'), ('Notebooks'), ('Art Supplies'), ('Office Supplies'), ('School Bags');

INSERT INTO products (category_id, name, description, price, stock, image) VALUES
(1, 'Gel Pen Set (10 pcs)', 'Smooth-writing gel pens in assorted colors, quick-dry ink.', 250.00, 50, 'gel-pen.jpg'),
(1, 'Mechanical Pencil 0.5mm', 'Lightweight mechanical pencil with soft grip.', 120.00, 80, 'pencil.jpg'),
(2, 'Spiral Notebook A5', '200-page ruled spiral notebook, hard cover.', 180.00, 60, 'notebook.jpg'),
(2, 'Sticky Notes Combo', 'Pack of 6 sticky note pads in different sizes/colors.', 150.00, 40, 'sticky-notes.jpg'),
(3, 'Watercolor Paint Set', '24-color watercolor set with brush included.', 650.00, 25, 'watercolor.jpg'),
(3, 'Sketchbook A4', '100gsm acid-free sketchbook, 40 sheets.', 320.00, 35, 'sketchbook.jpg'),
(4, 'Stapler Heavy Duty', 'Metal body stapler, 30-sheet capacity.', 400.00, 20, 'stapler.jpg'),
(4, 'A4 Printing Paper (500 sheets)', '75gsm multipurpose printing paper ream.', 550.00, 45, 'a4-paper.jpg'),
(5, 'School Backpack', 'Water-resistant backpack with laptop compartment.', 1800.00, 15, 'backpack.jpg'),
(5, 'Pencil Case', 'Durable zip pencil case with multiple compartments.', 220.00, 55, 'pencil-case.jpg');

