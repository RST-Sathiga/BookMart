# BookMart

A campus textbook marketplace inspired by CampusTrade. Students buy and sell textbooks with **on-campus pickup only**, secure **PayFast** payments, seller **wallets**, and built-in **messaging** to schedule meetups.

Built with **PHP**, **MySQL**, **HTML**, **CSS**, **JavaScript**, and **Bootstrap 5**.

---

## Features

| Feature | Description |
|---------|-------------|
| Textbook-only listings | Sellers list textbooks with title, author, course code, ISBN, condition, and cover image |
| University & campus filters | Browse and list by university and campus with designated pickup points |
| PayFast payments | Sandbox-ready checkout via PayFast |
| Pickup confirmation | Buyer and seller coordinate in the order chat; seller confirms pickup after verifying the buyer |
| Seller wallet | 15% platform commission deducted; balance available after pickup confirmation |
| Withdrawals | Sellers enter full bank details, optionally save for future payouts; admin approves and processes transfer |
| Chat | Message buyers/sellers to agree on pickup times |
| Admin dashboard | Manage users, approve listings, view orders, process withdrawals, view locations |

---

## Tech Stack

- PHP 8+ (XAMPP recommended)
- MySQL / MariaDB
- Bootstrap 5
- PayFast Sandbox

---

## Project Structure

```
Bookmart/
├── admin/                 # Separate admin dashboard
├── api/                   # AJAX endpoints (campus lookup)
├── assets/css/            # Theme (blue, white, grey, gold)
├── config/                # App configuration
├── database/schema.sql    # Database schema + seed data
├── includes/              # Shared auth, header, footer, helpers
├── uploads/               # Textbook cover images
├── index.php              # Landing page
├── marketplace.php        # Browse textbooks
├── sell.php               # Create listing
├── cart.php / checkout.php
├── payment_handler.php    # PayFast ITN + return URLs
├── orders.php / pickup.php
├── wallet.php             # Balance, withdrawals, transactions
├── messages.php / chat.php
└── login.php / register.php
```

---

## Installation (XAMPP + VS Code)

### 1. Clone or copy the project

Place the folder in your XAMPP web root:

```
C:\xampp\htdocs\Bookmart
```

### 2. Start services

Open **XAMPP Control Panel** and start **Apache** and **MySQL**.

### 3. Create / update the database

**Recommended:** open in your browser:

[http://localhost/Bookmart/database/install.php](http://localhost/Bookmart/database/install.php)

This creates all required tables (`products`, `orders`, `messages`, etc.).

**Alternative (phpMyAdmin):** import `database/schema.sql` for a full fresh install, or `database/fix_missing_tables.sql` to add missing tables only.

### 4. Configure the app

Edit `config/config.php` if needed:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bookmart');
define('SITE_URL', 'http://localhost/Bookmart');
```

Add your PayFast sandbox credentials:

```php
define('PAYFAST_MERCHANT_ID', 'your_merchant_id');
define('PAYFAST_MERCHANT_KEY', 'your_merchant_key');
```

### 5. Open in VS Code

```bash
code C:\xampp\htdocs\Bookmart
```

### 6. Visit the site

**Single platform link (auto-detected from your XAMPP folder):**

Open your project folder in the browser. Examples:

- `http://localhost/Bookmart`
- `http://localhost/Bookmart/marketplace.php`
- `http://localhost/Bookmart/admin/` (login as admin first)

If you get **404 Not Found**, check that Apache is running and the folder name in the URL matches your `htdocs` folder exactly (`Bookmart`).

**Database repair (if pages show table errors):**  
[http://localhost/Bookmart/database/install.php](http://localhost/Bookmart/database/install.php)

If upgrading an existing database, run once:

[http://localhost/Bookmart/database/migrate_profile_fields.php](http://localhost/Bookmart/database/migrate_profile_fields.php)

Registration requires a **facial profile photo** and **ID/passport number**.

---

## Default Admin Login

| Field | Value |
|-------|-------|
| **URL** | [http://localhost/Bookmart/admin](http://localhost/Bookmart/admin) |
| **Email** | `admin@bookmart.com` |
| **Password** | `password` |

Only this email with the `admin` role can access the admin dashboard. Change the password after first login.

### Institutions update (private colleges + manual entry)

Run once after setup:

[http://localhost/Bookmart/database/migrate_institutions.php](http://localhost/Bookmart/database/migrate_institutions.php)

This adds private colleges and enables manual institution/campus entry on register and sell pages.

---

## User Flow

1. **Register** with university and campus
2. **Sell:** submit a textbook listing (admin approval required)
3. **Buy:** add to cart → checkout → PayFast payment
4. **Chat** with the seller to arrange pickup
5. **Buyer** meets seller at the agreed campus pickup point
6. **Seller** confirms pickup in Bookmart
7. **Seller** requests withdrawal → admin processes payout

---

## PayFast Setup

1. Create a [PayFast Sandbox](https://sandbox.payfast.co.za/) account
2. Copy merchant ID and key into `config/config.php`
3. Ensure notify/return URLs point to your local or deployed site:
   - Return: `{SITE_URL}/payment_handler.php?status=success`
   - Cancel: `{SITE_URL}/payment_handler.php?status=cancel`
   - Notify: `{SITE_URL}/payment_handler.php`

For local ITN testing you may need a tunnel (e.g. ngrok) so PayFast can reach your notify URL.

---

## Security Notes

- Passwords are hashed with `password_hash()` (bcrypt)
- Prepared statements used for database queries
- Admin routes require `role = admin` and verified admin email
- Only approved textbooks appear in the marketplace
- Users cannot buy their own listings

---

## Color Theme

- Navy / blue primary: `#1e3a8a`, `#2563eb`
- White backgrounds
- Grey accents: `#6b7280`, `#f3f4f6`
- Gold highlights: `#d4af37`

---

## License

This project is provided for educational use. Customize and deploy according to your institution's requirements.

---

## Contributing

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Open a pull request

---

## Support

For issues, open a GitHub issue with steps to reproduce and your PHP/MySQL versions.
