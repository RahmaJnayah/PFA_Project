# Admin account setup and access

## 1) Create the admin account (SQL)
Run this SQL query in your `yopy_platform` database:

```sql
INSERT INTO users (username, email, password_hash, role, plan, status)
VALUES ('YOPY Admin', 'admin@yopy.app', '$2b$12$xn2mKkJgpv.o1z8uRqdBiuTnLzZf2yWikdUxYLHZB1Zw8K.nLnYHO', 'admin', 'premium', 'active');
```

**Admin credentials (public/demo):**
- Email: admin@yopy.app
- Password: yopy2025!

## 2) Access the admin page and dashboard
You must log in first. The admin panel is protected and will redirect to the login page if you are not authenticated.

**Login URL:**
- http://localhost/yopy-platform/admin

**Direct admin front controller (also works):**
- http://localhost/yopy-platform/admin.php

After a successful login, you are redirected to the admin dashboard automatically.

## Notes
- If `/admin` shows "Not Found", make sure Apache has `AllowOverride All` for the `yopy-platform` folder and `.htaccess` exists.
- The admin panel routes are handled by `admin.php` (front controller).
