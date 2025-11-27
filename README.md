# Stock Management System

A comprehensive stock management system built with native PHP, designed to help businesses track inventory, manage suppliers, and generate detailed reports for better decision-making.

## Features

- **User Management**:
  - Role-based access control (Admin and User roles)
  - Secure authentication system
  - User profile management

- **Supplier Management**:
  - Add, edit, and manage supplier information
  - Track supplier contact details
  - View supplier transaction history

- **Stock Management**:
  - Track item quantities and details
  - Set minimum stock thresholds for alerts
  - Full history of stock movements

- **Transaction Logging**:
  - Record all stock deposits and withdrawals
  - Associate transactions with suppliers
  - Track who performed each transaction

- **Reporting System**:
  - Generate reports for specific date ranges
  - Filter reports by supplier
  - Multiple report types: summary by item, summary by supplier, detailed transactions
  - Print and export options

- **Dashboard**:
  - Real-time overview of stock status
  - Low stock alerts
  - Recent transaction display

## Installation

1. Clone or download this repository to your web server directory
2. Ensure PHP and MySQL are installed and configured
3. Create a MySQL database for the application
4. Update the database connection details in `includes/db_connect.php`
5. Run the setup script by accessing `setup.php` in your browser
6. Log in with the default admin credentials (Username: admin, Password: admin123)

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache or Nginx)

## Database Structure

The system uses four main database tables:
- `users`: Stores user accounts and authentication details
- `suppliers`: Contains supplier information
- `stock`: Holds inventory items and their current quantities
- `transactions`: Records all stock movements

## Security Features

- Password hashing
- Prepared statements for database queries
- Input validation
- Session-based authentication
- Role-based access control

## License

This project is for demonstration purposes.