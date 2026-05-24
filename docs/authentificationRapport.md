# YOPY Authentication Report

## Overview
The authentication flow is implemented in a classic MVC-style pattern:
- Routes define entry points and map to controller actions.
- Controllers handle HTTP requests, sessions, CSRF validation, and navigation.
- Models encapsulate database access (users and password reset tokens).
- Configuration provides database and email settings.

Key entry points live in [routes/web.php](routes/web.php). The main controller is [controllers/AuthController.php](controllers/AuthController.php). Data access is in [models/UserModel.php](models/UserModel.php) and [models/PasswordResetModel.php](models/PasswordResetModel.php). Configuration is in [config/database.php](config/database.php) and [config/app.php](config/app.php).

## Architecture and Flow
- Client submits forms to the routes in [routes/web.php](routes/web.php).
- The router invokes the corresponding method in [controllers/AuthController.php](controllers/AuthController.php).
- The controller validates CSRF tokens, reads input, updates session state, and redirects to view pages.
- Data reads and writes are performed via model classes that use MySQLi prepared statements.
- For password reset, a selector + token pattern is used and validated with a hashed token in the database.
- Email delivery uses PHPMailer if SMTP is configured, otherwise the reset link is logged to a local file.

## Sign-up (Register)
**Route:** GET/POST /auth/register (see [routes/web.php](routes/web.php))

**Flow in [controllers/AuthController.php](controllers/AuthController.php):**
1. Starts session if needed.
2. On POST, validates CSRF token against session.
3. Reads username, email, password, confirm_password.
4. Ensures passwords match.
5. Hashes password with `password_hash(..., PASSWORD_BCRYPT)`.
6. Creates user via `UserModel::create()`.
7. On success, sets a session message and redirects to login.

**Database interaction:**
- `UserModel::create()` uses a prepared INSERT into the `users` table (fields: username, email, password_hash, role). See [models/UserModel.php](models/UserModel.php).

**Role policy:**
- New accounts are created with role `parent` by default.

## Log-in
**Route:** GET/POST /auth/login (see [routes/web.php](routes/web.php))

**Flow in [controllers/AuthController.php](controllers/AuthController.php):**
1. Starts session if needed.
2. On POST, validates CSRF token.
3. Reads email and password.
4. Fetches the user by email.
5. Verifies the password with `password_verify`.
6. On success, stores `user_id`, `role`, and `username` in the session.
7. Redirects to the mode selection view.

**Database interaction:**
- `UserModel::findByEmail()` performs a prepared SELECT on `users` with the email. See [models/UserModel.php](models/UserModel.php).

## Log-out
**Route:** GET /auth/logout (see [routes/web.php](routes/web.php))

**Flow in [controllers/AuthController.php](controllers/AuthController.php):**
1. Starts session if needed.
2. Calls `session_destroy()`.
3. Redirects to login.

## Forgot Password (Request Reset)
**Route:** GET/POST /auth/forgot (see [routes/web.php](routes/web.php))

**Flow in [controllers/AuthController.php](controllers/AuthController.php):**
1. Starts session if needed.
2. On POST, validates CSRF token.
3. Reads the email.
4. Looks up the user (silent if not found).
5. If user exists:
   - Deletes existing reset entries for the email.
   - Generates a `selector` and `token`.
   - Stores `token_hash = sha256(token)` and `expires_at` in the database.
   - Builds a reset link with selector, token, and email as query parameters.
   - Sends the link via SMTP or logs it to [storage/logs/reset_emails.log](storage/logs/reset_emails.log).
6. Returns a generic success message to avoid account enumeration.

**Security policy notes:**
- The response does not reveal whether an email exists.
- Token validity is 30 minutes.

**Database interaction:**
- `PasswordResetModel::deleteByEmail()` deletes existing rows for the email.
- `PasswordResetModel::create()` inserts a new row into `password_resets`.
See [models/PasswordResetModel.php](models/PasswordResetModel.php).

## Reset Password (Use Reset Link)
**Route:** GET/POST /auth/reset (see [routes/web.php](routes/web.php))

**Flow in [controllers/AuthController.php](controllers/AuthController.php):**
1. Starts session if needed.
2. On GET:
   - Reads email, selector, token.
   - Looks up a valid reset row (expires_at must be in the future).
   - Hashes the token and compares with stored `token_hash` using `hash_equals`.
   - Sets an error if invalid; otherwise allows the reset form.
3. On POST:
   - Validates CSRF token.
   - Validates password and confirm_password.
   - Looks up the reset row and validates token hash.
   - Hashes new password with `password_hash(..., PASSWORD_BCRYPT)`.
   - Updates user password and deletes reset row.
   - Redirects to login with a success message.

**Database interaction:**
- `PasswordResetModel::findValidByEmailAndSelector()` retrieves valid reset row.
- `UserModel::updatePasswordByEmail()` updates the user password.
- `PasswordResetModel::deleteById()` removes the used reset row.
See [models/PasswordResetModel.php](models/PasswordResetModel.php) and [models/UserModel.php](models/UserModel.php).

## CSRF Protection
CSRF tokens are validated on all POST actions in [controllers/AuthController.php](controllers/AuthController.php):
- login
- register
- forgotPassword
- resetPassword

The token is checked against `$_SESSION['csrf_token']` and a request token, and failures lead to error messages and redirects.

## Session Usage
On successful authentication, the following session keys are set:
- `user_id`
- `role`
- `username`

Logout destroys the session.

## Database Connections
The models use MySQLi and load configuration from [config/database.php](config/database.php). Each method:
- Opens a new MySQLi connection.
- Uses prepared statements for queries.
- Closes the statement and connection.

The schema file [database/schema.sql](database/schema.sql) is empty in this repository, but the models imply these tables:
- `users` (columns: `id`, `username`, `email`, `password_hash`, `role`)
- `password_resets` (columns: `reset_id`, `email`, `selector`, `token_hash`, `expires_at`)

## Email Delivery
Reset emails are sent via PHPMailer if SMTP is configured in [config/app.php](config/app.php). If SMTP is not configured, the reset link is logged to [storage/logs/reset_emails.log](storage/logs/reset_emails.log) for manual testing.

## Notes and Observations
- [services/AuthService.php](services/AuthService.php) exists but is empty; all logic is in the controller.
- The system uses server-side sessions for authentication state.
- Passwords are hashed with bcrypt and verified using PHP's built-in password helpers.
