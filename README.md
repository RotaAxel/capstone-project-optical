# Acebedo Optical Clinic — Management Information System

A full-stack clinic management system covering patient records, prescriptions, appointments, inventory, sales, reporting, and AI-powered demand forecasting.

---

## Table of Contents

1. [Tech Stack](#tech-stack)
2. [System Requirements](#system-requirements)
3. [Step-by-Step Setup](#step-by-step-setup)
4. [Database & Seed Data](#database--seed-data)
5. [Running the Project](#running-the-project)
6. [Default Login Accounts](#default-login-accounts)
7. [System Modules](#system-modules)
8. [Role & Access Matrix](#role--access-matrix)
9. [Analytics Engine](#analytics-engine)
10. [Re-seeding / Resetting Data](#reseeding--resetting-data)
11. [Troubleshooting](#troubleshooting)

---

## Tech Stack

| Layer | Technology |
|---|---|
| Frontend | Vue 3, Vite, Pinia, Vue Router, Chart.js, Axios |
| Backend | Laravel 12, PHP 8.2+, Laravel Sanctum (API tokens) |
| Database | MySQL 8.0+ |
| Local Server | XAMPP (Apache + MySQL) |
| Package Managers | Composer (PHP), npm (Node.js) |

---

## System Requirements

Before starting, make sure the following are installed on your machine:

| Tool | Minimum Version | Download |
|---|---|---|
| XAMPP | 8.2+ | https://www.apachefriends.org |
| PHP | 8.2+ | Included with XAMPP |
| Composer | 2.x | https://getcomposer.org |
| Node.js | 18.x+ | https://nodejs.org |
| npm | 9.x+ | Included with Node.js |
| Git | any | https://git-scm.com |

> **Check your versions** by running in a terminal:
> ```
> php -v
> composer -V
> node -v
> npm -v
> ```

---

## Step-by-Step Setup

### Step 1 — Copy the project files

Place the entire `capstone-project` folder inside XAMPP's web root:

```
C:\xampp\htdocs\capstone-project\
```

The folder structure should look like this:

```
capstone-project/
├── backend/          ← Laravel API
├── frontend/         ← Vue 3 SPA
└── README.md
```

---

### Step 2 — Start XAMPP

1. Open the **XAMPP Control Panel**
2. Click **Start** next to **Apache**
3. Click **Start** next to **MySQL**
4. Both should show green status indicators

---

### Step 3 — Create the database

**Option A — phpMyAdmin (recommended for beginners):**

1. Open your browser and go to: `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar
3. Enter database name: `acebedo_optical_clinic`
4. Set collation: `utf8mb4_unicode_ci`
5. Click **Create**

**Option B — MySQL CLI:**

```sql
CREATE DATABASE acebedo_optical_clinic
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

---

### Step 4 — Configure the backend environment

Open a terminal and navigate to the backend folder:

```bash
cd C:\xampp\htdocs\capstone-project\backend
```

Copy the example environment file:

```bash
cp .env.example .env
```

Open `.env` in a text editor and set these values:

```env
APP_NAME="Acebedo Optical Clinic"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=acebedo_optical_clinic
DB_USERNAME=root
DB_PASSWORD=              # Leave blank if using XAMPP default (no password)

FRONTEND_URL=http://localhost:5173

SANCTUM_STATEFUL_DOMAINS=localhost:5173
SESSION_DOMAIN=localhost
```

> **Note:** If your MySQL has a password set, enter it in `DB_PASSWORD=`.

---

### Step 5 — Install backend dependencies

Still inside the `backend` folder, run:

```bash
composer install
```

This downloads all PHP packages into the `vendor/` folder. It may take 1–3 minutes.

---

### Step 6 — Generate the application key

```bash
php artisan key:generate
```

This sets the `APP_KEY` value in your `.env` file. Required for encryption and sessions.

---

### Step 7 — Run database migrations

```bash
php artisan migrate
```

This creates all 19 database tables:

| Table | Purpose |
|---|---|
| `users` | Staff accounts with roles |
| `patients` | Patient demographics and medical history |
| `prescriptions` | OD/OS lens prescriptions per patient |
| `appointments` | Scheduled clinic visits |
| `product_categories` | Frame, lens, contact, sunglass, accessory |
| `suppliers` | Vendor contact information |
| `products` | Inventory items with stock levels |
| `stock_movements` | Full audit trail of every stock change |
| `sales` | Transaction headers |
| `sale_items` | Line items per transaction |
| `analytics_logs` | Stored forecasting results per product |
| `personal_access_tokens` | Sanctum API tokens |
| `sessions`, `cache`, `jobs` | Laravel framework tables |

---

### Step 8 — Seed the database

```bash
php artisan db:seed
```

This runs four seeders in order:

#### DatabaseSeeder
Creates the foundation data required by all other seeders:
- **3 user accounts** (Admin, Receptionist, Optometrist — all password: `password`)
- **6 product categories**: Eyeglass Frames, Single Vision Lens, Progressive Lens, Contact Lens, Sunglasses, Accessories
- **2 suppliers**: OpticalPro Supply Co. (Manila) and LensWorld Philippines (Cebu)
- **16 base products** with real SKUs (FR-001–005, LN-001–004, CL-001–002, SG-001–002, AC-001–003)

#### ProductInventorySeeder
Generates **516 additional products** across all categories:
- 80 Single Vision lens variants (brands: Essilor, Hoya, Zeiss, Nikon; indices 1.50–1.74; coatings: Anti-Radiation, Blue Cut, Photochromic)
- 70 Progressive lens variants (brands: Varilux, Hoya ID; types: Freeform, Standard, Digital)
- 50 Contact lens variants (brands: Acuvue, FreshLook, Bausch & Lomb; types: Daily to Extended Wear)
- 200 Eyeglass frame variants (20 brands × 10 materials × 10 shapes × 20 colors)
- 100 Sunglass variants (10 brands × 10 styles × 10 lens types)

**Total: 532 products** after both seeders.

#### PatientHistorySeeder
Creates **30 named Filipino patients** from Cebu with realistic clinic history:
- Each patient has 1–2 prescriptions with real clinical values (sphere, cylinder, axis, add, PD)
- Each patient has 1–3 past appointments (mostly completed)
- Older patients have up to 5 linked historical sales

#### HistoricalTransactionSeeder
Generates **~4.5 years of realistic sales history** (2021-01-01 to today):
- Uses **Pareto-weighted demand** — top 9 core products get ~55% of all transactions
- **COVID impact**: 2021 volume at 45%, recovering to 125% by 2026
- **Seasonal peaks**: December/January ×1.5, June/July ×1.3, March/April ×1.15
- **Saturday boost**: ×1.25 additional sales on Saturdays
- **35% bundle rate**: frame + lens sold together in same transaction
- Slow-moving items (contacts, sunglasses) sell every 3–5 days
- Accessories sell weekly

Expected output: **~17,000–25,000 sales records**, **~22,000–32,000 line items**

> **Warning:** This seeder truncates `sales` and `sale_items` before running. Any existing transaction data will be replaced.

> **Time:** The historical seeder processes ~2,000 days of data. It may take **2–5 minutes** to complete. Do not cancel it.

---

### Step 9 — Set up the storage link (for product images)

```bash
php artisan storage:link
```

This creates a `public/storage` symlink so uploaded product images are accessible from the browser.

---

### Step 10 — Install frontend dependencies

Open a **new terminal** and navigate to the frontend folder:

```bash
cd C:\xampp\htdocs\capstone-project\frontend
```

Install npm packages:

```bash
npm install
```

---

## Running the Project

You need **two terminals open at the same time**.

**Terminal 1 — Backend API:**

```bash
cd C:\xampp\htdocs\capstone-project\backend
php artisan serve
```

Runs at: `http://localhost:8000`

**Terminal 2 — Frontend:**

```bash
cd C:\xampp\htdocs\capstone-project\frontend
npm run dev
```

Runs at: `http://localhost:5173`

Open your browser and go to: **`http://localhost:5173`**

> Both terminals must stay open while using the system. Closing either one stops that part of the application.

---

## Default Login Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@acebedo.com | password |
| Receptionist | reception@acebedo.com | password |
| Optometrist | optometrist@acebedo.com | password |
| Inventory Staff | inventory@acebedo.com | password |

---

## System Modules

### Dashboard
Role-specific overview page. Each role sees a different layout:
- **Admin**: Today's sales, monthly revenue chart, low-stock alerts, upcoming appointments, top-selling products
- **Receptionist**: Today's appointments, patient count, upcoming schedule, no-show alerts
- **Optometrist**: Their own appointment queue, prescriptions written this week, upcoming patients
- **Inventory Staff**: Stock counts, out-of-stock items, FSN classification summary, recent stock movements

---

### Patients
Full patient record management.
- Search by name, patient code, or phone number
- Fields: name, date of birth, gender, phone, email, address, emergency contact, medical history
- Patient code auto-generated: `PAT-000001` format
- View linked prescriptions, appointments, and purchase history per patient
- **Roles**: Admin and Receptionist can create/edit; Optometrist can view only; Admin can delete

---

### Prescriptions
Optical prescription records.
- Fields: OD and OS sphere, cylinder, axis, add, PD; visual acuity; exam date; valid until date
- Linked to both a patient and the optometrist who wrote it
- Status tracked: Active (valid_until >= today) or Expired
- **Roles**: Admin and Optometrist can create/edit/delete; Receptionist can view only

---

### Appointments
Clinic scheduling system.
- Types: Eye Exam, Follow-up, Fitting, Other
- Statuses: Scheduled → Completed / Cancelled / No-show
- Optometrist is assigned per appointment; only active optometrists are selectable
- Deep-link from alerts navigates directly to the relevant appointment
- **Roles**: Admin and Receptionist create/delete; Optometrist can update status; all clinic staff can view

---

### Inventory
Product and stock management.
- Product fields: SKU (auto-generated `SKU-XXXXXXXX`), name, category, supplier, brand, description, image, cost price, selling price, stock quantity, reorder point, reorder quantity
- **Stock In**: Add stock from supplier with quantity and notes
- **Adjust**: Record damage, loss, or manual adjustments
- Every stock change creates a **Stock Movement** record for full auditability
- Filter by category, low-stock status, or search by name/SKU/brand
- **Roles**: Admin and Inventory Staff can manage; Receptionist can view (for sales form)

---

### Stock Movements
Read-only audit trail of every inventory change.
- Types: Stock In, Stock Out, Sale, Adjustment, Return, Damage, Loss
- Shows quantity before and after each change
- Filterable by movement type and product search
- **Roles**: Admin and Inventory Staff

---

### Sales (Transactions)
Point-of-sale transaction system.
- Multi-item cart per transaction
- Links to a patient and optionally to their prescription
- Per-item discount support
- Payment methods: Cash, Card, GCash, Maya, Other
- Auto-calculates change for cash payments
- Receipt number auto-generated: `REC-XXXXXXXX` format
- Stock is automatically decremented on sale completion
- **Roles**: Admin and Receptionist

---

### Reports
Summarized business reports with charts.

| Report | Description | Access |
|---|---|---|
| Daily Sales | All transactions for a selected date with totals | Admin |
| Monthly Sales | Day-by-day breakdown for a selected month | Admin |
| Inventory | Full product list with stock status and value | Admin, Inventory Staff |
| Top Products | Best-selling products for a date range | Admin, Inventory Staff |

---

### Alerts
Real-time role-based notifications:
- **Critical** (red): Out-of-stock products, overdue no-show appointments
- **Warning** (amber): Low-stock items, appointments starting within the hour, no-show appointments
- **Info** (blue): Today's appointment count, predictive restock alerts (stock will run out in < 14 days based on analytics)
- Each alert links directly to the relevant record

---

### Accounts
User management (Admin only).
- Create, edit, deactivate, or delete staff accounts
- Roles: Admin, Receptionist, Optometrist, Inventory Staff
- Guards: Cannot delete your own account; cannot remove the last remaining Admin account

---

## Role & Access Matrix

| Module | Admin | Receptionist | Optometrist | Inventory Staff |
|---|---|---|---|---|
| Dashboard | Full | Own view | Own view | Own view |
| Patients | Full CRUD | Create/Edit | View only | — |
| Prescriptions | Full CRUD | View only | Full CRUD | — |
| Appointments | Full CRUD | Create/Edit/Delete | View + Update status | — |
| Inventory | Full CRUD | View only | — | Full CRUD |
| Stock Movements | View | — | — | View |
| Sales | Full CRUD | Full CRUD | — | — |
| Reports (Sales) | Full | — | — | — |
| Reports (Inventory) | Full | — | — | View |
| Analytics | Full | — | — | View + Run |
| Alerts | All types | Appointment alerts | Prescription/Appt alerts | Stock alerts |
| Accounts | Full CRUD | — | — | — |

---

## Analytics Engine

The analytics module uses multiple forecasting algorithms to predict demand, calculate optimal order quantities, and classify product activity.

### How to Run Analytics

1. Log in as **Admin** or **Inventory Staff**
2. Navigate to **Analytics** in the sidebar
3. Analytics run **automatically** on page load if results are from a previous day
4. To force a manual recalculation, click **Run Analytics**
5. The computation processes all ~532 products and takes approximately **30–90 seconds**

### What It Computes

| Output | Description |
|---|---|
| **Forecast** (30-day) | Predicted units to sell in the next 30 days |
| **6-Month Forecast** | Monthly demand forecast for the next 6 months with 95% confidence interval |
| **EOQ** | Economic Order Quantity — most cost-efficient reorder amount |
| **ROP** | Reorder Point — stock level at which to place a new order |
| **FSN Classification** | Fast / Slow / Non-moving based on 4-year sales activity |
| **WMAPE** | Forecast error rate (fast-moving items only — lower is better) |

### Algorithm Selection (Automatic)

| Product Type | Algorithm | Condition |
|---|---|---|
| Regular (fast-moving) | **Holt-Winters** | ≥40% active weeks + ≥2 years of data |
| Regular (less history) | **Auto-ARIMA** | ≥40% active weeks but shorter history |
| Intermittent demand | **Croston's Method** | 10–40% active weeks |
| Non-moving / rare | **Optimised SES** | <10% active weeks |

### WMAPE Accuracy Rating

| Score | Label | Meaning |
|---|---|---|
| ≤ 30% | Good (green) | Forecast is reliable |
| 31–60% | Fair (amber) | Acceptable for planning |
| > 60% | Poor (red) | High uncertainty — use with caution |
| — | N/A | Slow/non-moving items (not applicable) |

### Charts Available

- **Predicted Future Demand** — Current demand + 6-month monthly forecast with 95% CI
- **Best Order Quantity (EOQ)** — Cost curve showing optimal order size
- **When to Reorder (ROP)** — 4-month stock simulation
- **Days Until Stock Runs Out** — Top 15 most urgent products by remaining days

---

## Re-seeding / Resetting Data

### Full reset (wipe everything and start fresh)

```bash
cd C:\xampp\htdocs\capstone-project\backend
php artisan migrate:fresh --seed
```

> **Warning:** This drops all tables and recreates them. All data is permanently deleted.

### Re-run only the historical sales data

```bash
php artisan db:seed --class=HistoricalTransactionSeeder
```

> This truncates `sales` and `sale_items` then regenerates ~4.5 years of transactions.

### Re-run only the product inventory

```bash
php artisan db:seed --class=ProductInventorySeeder
```

### Re-run only patient history

```bash
php artisan db:seed --class=PatientHistorySeeder
```

### Apply new migrations only (no data reset)

```bash
php artisan migrate
```

---

## Troubleshooting

### "No connection could be made" / Database error
- Open XAMPP Control Panel and make sure **MySQL is running** (green light)
- Verify `DB_DATABASE`, `DB_USERNAME`, and `DB_PASSWORD` in `backend/.env`
- Try connecting manually: `mysql -u root -p` in a terminal

### "php artisan" command not found
- Make sure PHP is added to your system PATH
- In XAMPP, PHP is located at `C:\xampp\php\php.exe`
- Add `C:\xampp\php` to your Windows Environment Variables → Path

### "composer" command not found
- Download and install Composer from https://getcomposer.org
- Restart your terminal after installation

### "npm" command not found
- Download and install Node.js from https://nodejs.org (includes npm)
- Restart your terminal after installation

### Frontend shows blank page or can't connect to API
- Make sure the backend is running: `php artisan serve` in `backend/`
- Check that the backend runs on port **8000** (default)
- Check `frontend/src/services/api.js` — base URL should be `http://localhost:8000/api`

### Images not showing for products
- Run `php artisan storage:link` in the `backend/` folder
- This creates the symlink from `public/storage` to `storage/app/public`

### Analytics run takes very long or times out
- The first run processes all 532 products — this is normal (30–90 seconds)
- Make sure PHP `max_execution_time` is set to at least 300 seconds in `php.ini`
- In XAMPP, find `php.ini` at `C:\xampp\php\php.ini` → search for `max_execution_time` → set to `300`

### Seeder runs but no sales data appears
- Run `php artisan db:seed --class=HistoricalTransactionSeeder` separately
- Check the terminal for error messages during seeding
- Make sure patients and products were seeded first (run `DatabaseSeeder` before `HistoricalTransactionSeeder`)

### Login returns "Too Many Requests" (429 error)
- The login endpoint is rate-limited to **10 attempts per minute** per IP
- Wait 1 minute and try again
- This is a security feature — normal in production

### After changing a user's role, the user still sees old navigation
- The user must **log out and log back in** for the new role to take effect
- This is expected behavior — the role is cached in the browser session

---

## Project File Structure

```
capstone-project/
├── backend/
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/Api/     ← All 12 API controllers
│   │   │   └── Middleware/
│   │   │       └── CheckRole.php    ← RBAC middleware
│   │   └── Models/                  ← 11 Eloquent models
│   ├── database/
│   │   ├── migrations/              ← 21 migration files
│   │   └── seeders/                 ← 4 seeders
│   ├── routes/
│   │   └── api.php                  ← 48 API routes
│   └── .env                         ← Environment config (create from .env.example)
│
└── frontend/
    └── src/
        ├── views/                   ← 12 Vue page components
        ├── router/index.js          ← Client-side routing + role guards
        ├── stores/auth.js           ← Pinia auth store
        └── services/api.js          ← Axios instance with auth header
```
