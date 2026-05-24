# YOPY — Security Architecture

## 1. Overview

This document describes the **security architecture of the YOPY platform**.
The goal is to protect:

* user accounts
* child data
* application integrity
* database confidentiality

Because the platform is designed for **children and parents**, security and privacy are **critical design requirements**.

The system implements **multiple security layers**, including:

* authentication protection
* role-based access control
* input validation
* secure session management
* database protection

---

# 2. Security Principles

The security design of the platform follows several fundamental principles.

### Least Privilege

Each user has access only to the **minimum permissions required**.

Examples:

* children access only games
* parents access only their children
* administrators manage system resources

---

### Defense in Depth

Multiple protection mechanisms are implemented across layers:

```text
User Interface
      │
Input Validation
      │
Application Logic
      │
Authentication & Authorization
      │
Database Protection
```

If one layer fails, another layer still protects the system.

---

### Secure Data Handling

Sensitive data is handled using secure techniques such as:

* password hashing
* prepared SQL statements
* restricted database access

---

# 3. Authentication Security

The authentication module ensures that only authorized users can access the platform.

## Login Process

```text
User enters credentials
        │
        ▼
Server verifies username
        │
        ▼
Password hash comparison
        │
        ▼
Session creation
```

---

## Password Protection

Passwords are never stored as plain text.

They are stored as **secure hashed values** using PHP password hashing functions.

Example:

```
password_hash($password, PASSWORD_BCRYPT)
```

Benefits:

* protects passwords if database is compromised
* prevents password recovery attacks

---

# 4. Session Management

Sessions maintain user authentication during interaction with the platform.

Security measures include:

* secure session cookies
* session regeneration after login
* session expiration after inactivity

Example configuration:

```
session_regenerate_id(true);
```

This prevents **session fixation attacks**.

---

# 5. Role-Based Access Control

The platform implements **RBAC (Role-Based Access Control)**.

Roles supported:

| Role   | Permissions      |
| ------ | ---------------- |
| Child  | play games       |
| Parent | monitor children |
| Admin  | manage platform  |

Access checks are implemented at the **controller level**.

Example logic:

```
if($user_role !== "admin"){
    deny_access();
}
```

---

# 6. Input Validation

All user inputs must be validated before processing.

Sources of input include:

* login forms
* registration forms
* game results
* dashboard filters

Validation mechanisms:

* server-side validation
* type checking
* length restrictions
* allowed character sets

Example validation:

```
$email = filter_var($email, FILTER_VALIDATE_EMAIL);
```

---

# 7. SQL Injection Prevention

Database queries use **prepared statements**.

Unsafe example:

```
SELECT * FROM users WHERE email='$email'
```

Secure version:

```
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
```

Prepared statements prevent attackers from injecting SQL commands.

---

# 8. Cross-Site Scripting (XSS) Protection

To prevent **XSS attacks**, all dynamic content displayed in the browser is sanitized.

Example protection:

```
htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
```

This prevents malicious scripts from executing inside the browser.

---

# 9. Cross-Site Request Forgery Protection

Forms that modify data should use **CSRF tokens**.

Example workflow:

```text
User loads form
      │
Server generates CSRF token
      │
User submits form
      │
Server verifies token
```

If the token is invalid, the request is rejected.

---

# 10. Database Security

The database is protected through multiple measures.

### Restricted Access

The database account used by the application has limited privileges.

Example:

* SELECT
* INSERT
* UPDATE

Administrative privileges are avoided.

---

### Backup Strategy

Regular backups ensure recovery in case of failure.

Recommended backups:

* daily incremental backups
* weekly full backups

---

# 11. File Security

Uploaded files or game resources must be protected.

Rules include:

* restrict allowed file types
* limit file size
* store files outside public directories

Example allowed types:

* images (png, jpg)

---

# 12. Logging and Monitoring

Security events are logged for monitoring purposes.

Logged events include:

* login attempts
* failed authentication
* administrative actions

Example log record:

```text
[2026-03-01 10:21:14]
FAILED LOGIN
User: example@email.com
IP: 192.168.1.5
```

Logs help detect suspicious activity.

---

# 13. Secure Deployment Recommendations

When deploying the platform, additional protections should be implemented.

### HTTPS

All communication should use **HTTPS encryption**.

This prevents:

* credential interception
* session hijacking

---

### Server Hardening

Recommended measures:

* disable directory listing
* restrict file permissions
* keep PHP updated

---

### Database Isolation

The database server should not be publicly accessible.

Access should be restricted to the application server only.

---

# 14. Security Threat Model

Possible threats include:

| Threat              | Mitigation          |
| ------------------- | ------------------- |
| SQL Injection       | prepared statements |
| XSS                 | output sanitization |
| CSRF                | CSRF tokens         |
| Session hijacking   | secure cookies      |
| unauthorized access | RBAC                |

---

# 15. Security Summary

The YOPY platform implements a **layered security architecture** combining:

* secure authentication
* controlled authorization
* protected data handling
* safe database access
* monitoring and logging

These measures ensure that the platform provides a **safe and controlled digital environment for children and parents**.
