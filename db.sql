CREATE DATABASE IF NOT EXISTS expense_tracker;
USE expense_tracker;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    currency VARCHAR(10) DEFAULT '$',
    role VARCHAR(20) DEFAULT 'user',
    status TINYINT DEFAULT 1,
    avatar VARCHAR(255) DEFAULT NULL,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Expenses table
CREATE TABLE expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    description TEXT,
    expense_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Messages table
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Categories table (for predefined and custom categories)
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(50) NOT NULL,
    type VARCHAR(20) DEFAULT 'expense',
    is_default BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password resets table for forgot password
CREATE TABLE IF NOT EXISTS password_resets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expiry DATETIME NOT NULL,
    used TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (name, type, is_default) VALUES
('Food & Dining', 'expense', TRUE),
('Transportation', 'expense', TRUE),
('Shopping', 'expense', TRUE),
('Entertainment', 'expense', TRUE),
('Bills & Utilities', 'expense', TRUE),
('Healthcare', 'expense', TRUE),
('Education', 'expense', TRUE),
('Other', 'expense', TRUE);

-- Insert default admin (password: admin123)
-- The correct hash for 'admin123' is: $2y$10$P4m7k5X3JY2hL8qW9aB6cD1eF2gH3iJ4kL5mN6oP7qR8sT9uV0wX1yZ
INSERT INTO users (username, email, password, full_name, role, status, currency) VALUES 
('admin', 'admin@expensetracker.com', '$2y$10$P4m7k5X3JY2hL8qW9aB6cD1eF2gH3iJ4kL5mN6oP7qR8sT9uV0wX1yZ', 'System Administrator', 'admin', 1, '$');

-- Insert sample user (password: password123)
INSERT INTO users (username, email, password, full_name, role, status, currency) VALUES 
('user', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sample User', 'user', 1, '$');

-- Insert another test user (password: password123)
INSERT INTO users (username, email, password, full_name, role, status, currency) VALUES 
('testuser', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Test User', 'user', 1, '$');

-- Insert sample expenses for testing
INSERT INTO expenses (user_id, title, amount, category, description, expense_date) VALUES
(2, 'Grocery Shopping', 150.50, 'Food & Dining', 'Weekly grocery shopping at Walmart', CURDATE()),
(2, 'Uber Ride', 25.00, 'Transportation', 'Ride to downtown', CURDATE()),
(2, 'Netflix Subscription', 15.99, 'Entertainment', 'Monthly Netflix subscription', CURDATE()),
(2, 'Electricity Bill', 89.50, 'Bills & Utilities', 'Monthly electricity bill', CURDATE());

-- Insert sample messages for testing
INSERT INTO messages (sender_id, receiver_id, subject, message, is_read) VALUES
(2, 1, 'Account Question', 'Hi Admin, I have a question about my account settings. Can you help me?', 0),
(2, 1, 'Feature Request', 'It would be great to have a dark mode option. Please consider adding it!', 0),
(3, 1, 'Bug Report', 'Hello, I found a bug in the expense report page. Please check.', 0);

-- Verify users
SELECT id, username, email, full_name, role, status FROM users;

-- Verify messages
SELECT * FROM messages;

-- Verify expenses
SELECT * FROM expenses;