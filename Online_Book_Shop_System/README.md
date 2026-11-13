# 📚 Online Book Shop System (Render + Aiven Cloud MySQL)

An online bookstore web application featuring a modern catalog, author collections, category filters, interactive cart, customer query support, and an administrative dashboard. Fully modernized and containerized with **PHP 8.2 Apache**, **Aiven Cloud MySQL SSL integration**, and **Render Reverse Proxy optimizations**.

---

## 🚀 Live Deployment Architecture

- **Hosting Platform:** [Render](https://render.com/) (Web Service via Docker)
- **Database Service:** [Aiven Cloud MySQL](https://aiven.io/) (High Availability & TLS/SSL Enforced)
- **Runtime:** PHP 8.2 + Apache (mod_rewrite, headers, mysqli, pdo_mysql, gd, zip)
- **Container Port:** Dynamically bound to `$PORT` (Default: `10000`)
- **Web Root:** `/var/www/html`

---

## 🔐 Credentials & Default Accounts

| Role | Username | Password | Access Portal |
| :--- | :--- | :--- | :--- |
| **Admin / Default User** | `suyash` | `gulati` | `/admin/login.php` or `/login.php` |
| **Test Customer** | `a` | `a` | `/login.php` or modal login |

---

## 🛠️ Aiven Cloud MySQL Configuration

Aiven Cloud enforces secure transport (`require_secure_transport=ON`). The application natively enables `MYSQLI_CLIENT_SSL` using `mysqli_init()` and supports 5-digit custom ports.

### Environment Variables for Render

Configure these under **Render Dashboard &rarr; Your Service &rarr; Environment**:

| Variable Key | Description | Example Value |
| :--- | :--- | :--- |
| `PORT` | Container listen port (auto-set by Render) | `10000` |
| `DB_SERVER` | Aiven MySQL Hostname | `mysql-xxxxx.aivencloud.com` |
| `DB_PORT` | Aiven MySQL Port (5-digit) | `26284` |
| `DB_USERNAME` | Aiven Database Username | `avnadmin` |
| `DB_PASSWORD` | Aiven Database Password | `AVNS_xxxxxxxxxxxx` |
| `DB_NAME` | Aiven Database Name | `defaultdb` |
| `DB_SSL` | Force SSL transport | `true` |
| `BASE_URL` | Live production URL | `https://your-app-name.onrender.com` |

> **Alternative (Single URI):** You can also pass the entire Aiven connection string as `DATABASE_URL` or `MYSQL_URL`:
> `mysql://avnadmin:password@mysql-xxxxx.aivencloud.com:26284/defaultdb?ssl-mode=REQUIRED`

---

## ⚡ 1-Click Database Setup (`db_setup.php`)

Once deployed on Render, visit:
```text
https://<your-app-name>.onrender.com/db_setup.php
```

Features:
1. **Live Aiven Health Check:** Confirms SSL handshake, cipher suite, MySQL engine version, and active connection latency.
2. **1-Click SQL Importer:** Automatically parses `bookstore.sql`, cleans `CREATE DATABASE` / `USE` directives, bypasses foreign key checks, and imports tables (`cart`, `products`, `users`).
3. **Table & Admin Verification:** Checks that all 40+ books and default admin (`suyash`) are populated.
4. **Instant Launch:** Direct single-click button to jump straight to the store or admin portal.

---

## 🛡️ Critical Reverse Proxy & Port 10000 Fixes

When deploying Dockerized PHP applications on Render, Apache redirects can append internal container ports (`:10000`) if directory trailing slashes are requested. The following fixes have been built-in:

1. **Apache VirtualHost Configuration (`start.sh`):**
   - `UseCanonicalName Off`
   - `UseCanonicalPhysicalPort Off`
   - `ServerName localhost`
   - `SetEnvIf X-Forwarded-Proto "^https$" HTTPS=on`
2. **Rewrite Rules (`.htaccess`):**
   - Automatically strips `:10000` from host redirects.
   - Internal URL rewrites preserve canonical URLs without internal ports.
3. **Direct File Routing:**
   - Admin links explicitly use direct endpoints (e.g. `/admin/login.php` and `/admin/index.php`) instead of directory redirects.

---

## 📁 Project Directory Structure

```text
├── admin/
│   ├── index.php         # Admin dashboard (Product catalog, user counts, DB status)
│   └── login.php         # Direct-file admin login endpoint
├── css/                  # Bootstrap 3 & custom styles (my.css)
├── fonts/                # Glyphicon Halflings
├── img/                  # Books covers, banners, offers, author portraits
├── js/                   # Bootstrap and jQuery scripts
├── .htaccess             # Reverse-proxy port-stripping & security rules
├── .dockerignore         # Fast Docker build context exclusions
├── Author.php            # Books by author listing with price/discount sorting
├── bookstore.sql         # Original complete MySQL schema and seed data
├── cart.php              # Cart viewer, quantity updater, order checkout
├── dbconnect.php         # PHP 8.x + Aiven SSL MySQL singleton connection
├── db_setup.php          # 1-Click Aiven Cloud visual database installer & health checker
├── description.php       # Book detail view & purchase modal
├── destroy.php           # Session logout handler
├── Dockerfile            # Production PHP 8.2 Apache container configuration
├── index.php             # Main homepage, categories, slider, login/register modal
├── login.php             # Standalone & JSON API login endpoint
├── Product.php           # Category catalog with sort options
├── query.php             # User feedback / query mailer
├── README.md             # Project documentation and deployment guide
├── readme.txt            # Original source documentation
├── register.php          # Customer registration handler
├── render.yaml           # Render Infrastructure-as-Code Blueprint
├── Result.php            # Full-text book / author / publisher search
└── start.sh              # Dynamic PORT Apache startup script
```

---

## 🚀 How to Deploy on Render

### Method 1: Connect via GitHub (Recommended)

1. Push this repository to your GitHub account:
   ```bash
   git remote add origin https://github.com/<your-username>/Online_Book_Shop_System.git
   git branch -M main
   git push -u origin main
   ```
2. Log into [Render Dashboard](https://dashboard.render.com/).
3. Click **New + &rarr; Web Service**.
4. Select your GitHub repository.
5. In settings:
   - **Environment:** Docker
   - **Branch:** `main`
   - **Plan:** Free
6. Add the environment variables from the table above (`DB_SERVER`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `DB_NAME`, `DB_SSL`).
7. Click **Deploy Web Service**.
8. Once live, open `https://<your-app>.onrender.com/db_setup.php` and click **1-Click Import**!

### Method 2: Render Blueprint (`render.yaml`)

1. In Render Dashboard, click **New + &rarr; Blueprint**.
2. Select this repository.
3. Fill in the sensitive database credentials prompted by Render.
4. Render will automatically build the Docker image and deploy.

---

## 💻 Local Testing with Docker (Optional)

```bash
# Build the Docker image
docker build -t online-book-shop .

# Run the container locally with custom port and environment variables
docker run -d -p 8080:10000 \
  -e PORT=10000 \
  -e DB_SERVER=your-mysql-host.aivencloud.com \
  -e DB_PORT=26284 \
  -e DB_USERNAME=avnadmin \
  -e DB_PASSWORD=your-password \
  -e DB_NAME=defaultdb \
  -e DB_SSL=true \
  --name bookshop-app online-book-shop

# Open in browser
http://localhost:8080
```
