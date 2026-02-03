CREATE DATABASE IF NOT EXISTS billing_pro_9d;
USE billing_pro_9d;

-- 1. Staff aur Admin ke liye
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100),
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'staff') DEFAULT 'staff'
);

-- 2. Customers (CNIC + Phone identification ke sath)
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    cnic VARCHAR(20) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    address TEXT,
    profile_pic VARCHAR(255) DEFAULT 'default.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. Products (Stock aur Costing ke liye)
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barcode VARCHAR(100) UNIQUE,
    item_name VARCHAR(255) NOT NULL,
    cost_price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NOT NULL,
    stock_qty INT DEFAULT 0
);

-- 4. Invoices (Main Bill Head)
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    payment_mode ENUM('cash', 'borrow') DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id)
);

-- 5. Invoice_Items (Har bill ki detail)
CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT,
    product_id INT,
    product_name VARCHAR(255),
    qty INT,
    price_at_sale DECIMAL(10,2),
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 6. Credit_Ledger (Item-wise Udhaar tracking)
CREATE TABLE IF NOT EXISTS credit_ledger (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    invoice_item_id INT,
    item_name VARCHAR(255),
    total_amount DECIMAL(10,2),
    amount_paid DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending', 'cleared') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (invoice_item_id) REFERENCES invoice_items(id) ON DELETE CASCADE
);

-- 7. Credit_Payments (Jab banda thore thore paise dega uska record)
CREATE TABLE IF NOT EXISTS credit_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ledger_id INT,
    paid_amount DECIMAL(10,2),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ledger_id) REFERENCES credit_ledger(id) ON DELETE CASCADE
);

-- Default Admin Data
INSERT IGNORE INTO users (full_name, username, password, role) 
VALUES ('Main Admin', 'admin', 'admin123', 'admin');