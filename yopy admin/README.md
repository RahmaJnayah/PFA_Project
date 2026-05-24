# YOPY Admin Panel

> MVC PHP admin panel for the YOPY parenting app.  
> Matches the existing dark violet/lilac design language.

---

## Project Structure

```
yopy-admin/
│
├── index.php                   ← Front controller / router
│
├── config/
│   └── Database.php            ← PDO singleton
│
├── controllers/
│   └── AdminController.php     ← All actions (auth, dashboard, users, children, characters)
│
├── models/
│   ├── UserModel.php           ← Parent account CRUD
│   ├── ChildModel.php          ← Child profile CRUD
│   └── CharacterModel.php      ← Companion character CRUD
│
├── views/
│   ├── layout/
│   │   ├── header.php          ← HTML head, top bar, particle canvas
│   │   ├── sidebar.php         ← Navigation sidebar
│   │   └── footer.php          ← Closing tags
│   │
│   └── admin/
│       ├── login.php           ← Standalone login page
│       ├── dashboard.php       ← Stats + recent activity
│       ├── users.php           ← Parent accounts list
│       ├── user_form.php       ← Create / edit user form
│       ├── children.php        ← Child profiles list
│       ├── child_form.php      ← Create / edit child form
│       ├── characters.php      ← Character card grid
│       ├── character_form.php  ← Create / edit character (with live preview)
│       └── 404.php             ← Not found page
│
└── schema.sql                  ← MySQL schema + seed data
```

---

## Setup

### 1. Database

```bash
mysql -u root -p yopy_db < schema.sql
```

Then generate a real bcrypt hash for the admin password:

```php
<?php echo password_hash('your_secure_password', PASSWORD_BCRYPT);
```

Paste it into the `users` table seed in `schema.sql`, or update directly:

```sql
UPDATE users SET password_hash = '$2y$12$...' WHERE email = 'admin@yopy.app';
```

### 2. Environment variables (recommended)

Set these in your server config / `.env` / Docker:

| Variable      | Default          | Description              |
|---------------|------------------|--------------------------|
| `DB_HOST`     | `localhost`      | MySQL host               |
| `DB_PORT`     | `3306`           | MySQL port               |
| `DB_NAME`     | `yopy_db`        | Database name            |
| `DB_USER`     | `root`           | Database user            |
| `DB_PASS`     | *(empty)*        | Database password        |
| `ADMIN_EMAIL` | `admin@yopy.app` | Fallback admin email     |
| `ADMIN_HASH`  | *(bcrypt hash)*  | Fallback admin password  |

### 3. Web server

**Apache** — place in `public/` and use:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx**:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### 4. PHP requirements

- PHP **8.1+**
- Extensions: `pdo`, `pdo_mysql`, `mbstring`

---

## Routes

| URL param (`?action=`)    | Method | Description                    |
|---------------------------|--------|--------------------------------|
| `login`                   | GET    | Show login page                |
| `doLogin`                 | POST   | Process login credentials      |
| `logout`                  | GET    | Destroy session, redirect      |
| `dashboard`               | GET    | Stats + recent activity        |
| `users`                   | GET    | List parent accounts           |
| `users.create`            | GET    | New user form                  |
| `users.store`             | POST   | Save new user                  |
| `users.edit&id=N`         | GET    | Edit user form                 |
| `users.update`            | POST   | Save user changes              |
| `users.toggleStatus`      | POST   | Toggle active ↔ suspended     |
| `users.delete`            | POST   | Delete user                    |
| `children`                | GET    | List child profiles            |
| `children.create`         | GET    | New child form                 |
| `children.store`          | POST   | Save new child                 |
| `children.edit&id=N`      | GET    | Edit child form                |
| `children.update`         | POST   | Save child changes             |
| `children.delete`         | POST   | Delete child                   |
| `characters`              | GET    | Character card grid            |
| `characters.create`       | GET    | New character form             |
| `characters.store`        | POST   | Save new character             |
| `characters.edit&id=N`    | GET    | Edit character form            |
| `characters.update`       | POST   | Save character changes         |
| `characters.toggle`       | POST   | Toggle visible ↔ hidden       |
| `characters.delete`       | POST   | Delete character               |

---

## Security features

- **CSRF tokens** on every POST form
- **Password hashing** via `password_hash()` / `password_verify()` (bcrypt)
- **PDO prepared statements** — no raw SQL interpolation
- **Session auth guard** — all non-login routes redirect if unauthenticated
- **`htmlspecialchars()`** on all output
- **Input whitelisting** for enum fields (plan, status)
- **Session regeneration** on login (`session_regenerate_id(true)`)
