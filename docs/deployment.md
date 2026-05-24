# YOPY — Deployment Guide

## 1. Overview

This document explains how to **install, configure, and run the YOPY platform** on a server environment.

The system is designed as a **web application** using:

* Backend: PHP
* Frontend: HTML, CSS, JavaScript, Bootstrap
* Database: MySQL

The deployment process includes:

1. preparing the server environment
2. installing dependencies
3. configuring the database
4. configuring the application
5. starting the web server

---

# 2. System Requirements

Minimum environment requirements:

| Component  | Requirement                            |
| ---------- | -------------------------------------- |
| Web Server | Apache or Nginx                        |
| PHP        | PHP 8.0 or higher                      |
| Database   | MySQL 5.7+                             |
| Browser    | Modern browser (Chrome, Firefox, Edge) |

Recommended stack:

* Apache
* PHP 8+
* MySQL
* Linux server

Typical development environments include **XAMPP**, **LAMP**, or **WAMP** stacks.

---

# 3. Project Directory Structure

Example project structure after deployment:

```text
yopy/
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── images/
│   └── sounds/
│
├── controllers/
│   ├── AuthController.php
│   ├── ChildController.php
│   ├── GameController.php
│   ├── ScoreController.php
│   └── DashboardController.php
│
├── models/
│   ├── User.php
│   ├── Child.php
│   ├── Game.php
│   └── Score.php
│
├── views/
│   ├── auth/
│   ├── child/
│   ├── parent/
│   └── admin/
│
├── games/
│   ├── memory_game/
│   ├── puzzle_game/
│   └── math_game/
│
├── config/
│   └── database.php
│
├── public/
│   └── index.php
│
└── docs/
```

---

# 4. Installing the Database

### Step 1 — Create Database

```sql
CREATE DATABASE yopy;
```

---

### Step 2 — Create Database User

```sql
CREATE USER 'yopy_user'@'localhost' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON yopy.* TO 'yopy_user'@'localhost';
FLUSH PRIVILEGES;
```

---

### Step 3 — Import Schema

Execute the schema file created in **database-schema.md**.

Example:

```bash
mysql -u yopy_user -p yopy < schema.sql
```

---

# 5. Database Configuration

Edit the database configuration file:

```
config/database.php
```

Example configuration:

```php
$host = "localhost";
$dbname = "yopy";
$user = "yopy_user";
$password = "strong_password";
```

Use **PDO** for secure database connections.

Example:

```php
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $password);
```

---

# 6. Running the Application

Place the project directory inside the web server root.

Example paths:

Linux (Apache):

```
/var/www/html/yopy
```

Windows (XAMPP):

```
xampp/htdocs/yopy
```

Start the web server and open the application in a browser:

```
http://localhost/yopy
```

---

# 7. Development Environment Setup

For development, a local environment is recommended.

Steps:

1. install XAMPP or a similar stack
2. copy project folder to web root
3. configure database
4. run Apache and MySQL services

This environment allows developers to test the platform before deployment.

---

# 8. Production Deployment

When deploying to a production server, several additional steps are required.

### Enable HTTPS

Configure SSL certificates to ensure encrypted communication.

Example:

```
https://yourdomain.com
```

---

### Secure File Permissions

Example Linux permissions:

```bash
chmod -R 755 yopy
```

Restrict access to sensitive configuration files.

---

### Disable Debug Mode

Ensure error messages are not displayed to users.

In PHP configuration:

```
display_errors = Off
```

---

# 9. Backup Strategy

Regular database backups are recommended.

Example backup command:

```bash
mysqldump -u yopy_user -p yopy > backup.sql
```

Recommended schedule:

* daily incremental backup
* weekly full backup

---

# 10. Updating the System

To update the platform:

1. backup database
2. backup project files
3. deploy new code version
4. run database migrations if needed

Testing should always be performed before updating the production system.

---

# 11. Troubleshooting

Common issues and solutions:

| Issue                     | Cause                   | Solution                    |
| ------------------------- | ----------------------- | --------------------------- |
| database connection error | wrong credentials       | check config/database.php   |
| page not loading          | server not running      | start Apache service        |
| login failure             | incorrect password hash | verify authentication logic |

---

# 12. Deployment Summary

The deployment process consists of:

1. preparing the server environment
2. installing and configuring the database
3. deploying application files
4. starting the web server
5. verifying system functionality

Following these steps ensures the **YOPY platform operates reliably and securely in a production environment**.
