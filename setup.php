<?php

/**
 * Database Setup Script
 * 
 * This script creates the necessary database structure for the Stock Management System.
 */

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = 'root';
$db_name = 'db_stockmanagement';

// Create connection to MySQL
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $db_name";
if ($conn->query($sql) === FALSE) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($db_name);

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating users table: " . $conn->error);
}

// Create suppliers table
$sql = "CREATE TABLE IF NOT EXISTS suppliers (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(50),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating suppliers table: " . $conn->error);
}

// Create stock table
$sql = "CREATE TABLE IF NOT EXISTS stock (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    unit VARCHAR(20) NOT NULL,
    quantity INT(11) NOT NULL DEFAULT 0,
    min_quantity INT(11) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating stock table: " . $conn->error);
}

// Create transactions table
$sql = "CREATE TABLE IF NOT EXISTS transactions (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    item_id INT(11) UNSIGNED NOT NULL,
    supplier_id INT(11) UNSIGNED NOT NULL,
    user_id INT(11) UNSIGNED NOT NULL,
    type ENUM('in', 'out') NOT NULL,
    quantity INT(11) NOT NULL,
    transaction_date DATETIME NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES stock(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) === FALSE) {
    die("Error creating transactions table: " . $conn->error);
}

// Check if admin user exists
$query = "SELECT COUNT(*) as count FROM users WHERE username = 'admin'";
$result = $conn->query($query);
$row = $result->fetch_assoc();

// Create default admin user if not exists
if ($row['count'] == 0) {
    // Create hashed password for 'admin123'
    $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);

    $sql = "INSERT INTO users (username, email, password, role) VALUES ('admin', 'admin@example.com', '$hashed_password', 'admin')";
    if ($conn->query($sql) === FALSE) {
        die("Error creating admin user: " . $conn->error);
    }

    echo "Admin user created. Username: admin, Password: admin123<br>";
}

// Check if default supplier exists
$query = "SELECT COUNT(*) as count FROM suppliers";
$result = $conn->query($query);
$row = $result->fetch_assoc();

// Create default supplier if none exists
if ($row['count'] == 0) {
    $sql = "INSERT INTO suppliers (name, contact_person, email, phone, address) 
            VALUES ('Default Supplier', 'Contact Person', 'contact@supplier.com', '123-456-7890', 'Supplier Address')";
    if ($conn->query($sql) === FALSE) {
        die("Error creating default supplier: " . $conn->error);
    }

    echo "Default supplier created.<br>";
}

echo "Database setup completed successfully!<br>";
echo "<a href='index.php'>Go to Login</a>";

// Close connection
$conn->close();
